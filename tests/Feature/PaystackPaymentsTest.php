<?php

namespace Tests\Feature;

use App\Jobs\ForwardAdomCiWebhook;
use App\Mail\AbandonedPaymentMail;
use App\Mail\PaymentReceivedMail;
use App\Models\AdminNotification;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\CaurisAchat;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaystackPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret' => self::SECRET]);
        Mail::fake();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function postWebhook(array $body, ?string $signature = null)
    {
        $json = json_encode($body);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'               => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE'  => $signature ?? hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    private function chargeSuccess(string $reference, int $amountXof, string $currency = 'XOF', array $extra = []): array
    {
        return [
            'event' => 'charge.success',
            'data'  => array_merge([
                'reference' => $reference,
                'status'    => 'success',
                'amount'    => $amountXof * 100,
                'currency'  => $currency,
                'channel'   => 'mobile_money',
                'paid_at'   => now()->toIso8601String(),
                'customer'  => ['email' => 'client@example.com'],
            ], $extra),
        ];
    }

    private function makeGame(array $attrs = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'title'  => 'Le Prince de Yop',
            'slug'   => 'le-prince-de-yop',
            'type'   => 'game',
            'status' => 'published',
            'price'  => 1000,
        ], $attrs));
    }

    /** Reproduit MarketplacePaymentController::buyPaystack (purchase + payment "pending"). */
    private function pendingMarketplacePayment(User $user, MarketplaceProduct $product, string $ref, array $extra = []): Payment
    {
        DB::table('marketplace_purchases')->updateOrInsert(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'status'       => 'pending',
                'amount'       => (int) $product->price,
                'provider'     => 'paystack',
                'provider_ref' => $ref,
                'paid_at'      => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );

        return Payment::create(array_merge([
            'user_id'        => $user->id,
            'transaction_id' => $ref,
            'amount_paid'    => (int) $product->price,
            'amount_virtual' => 0,
            'purpose'        => 'marketplace',
            'status'         => 'pending',
            'meta'           => [
                'product_id'    => $product->id,
                'product_slug'  => $product->slug,
                'product_title' => $product->title,
                'provider'      => 'paystack',
            ],
        ], $extra));
    }

    private function purchaseStatus(User $user, MarketplaceProduct $product): ?string
    {
        return DB::table('marketplace_purchases')
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->value('status');
    }

    // ─── Webhook : marketplace ──────────────────────────────────────────────

    public function test_webhook_mp_debloque_le_jeu_et_reste_idempotent_si_appele_deux_fois(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();
        $this->pendingMarketplacePayment($user, $product, 'MP-20261026-AAAAAA');

        $body = $this->chargeSuccess('MP-20261026-AAAAAA', 1000);

        $this->postWebhook($body)->assertOk();
        $this->postWebhook($body)->assertOk();

        $this->assertSame('paid', $this->purchaseStatus($user, $product));

        $payment = Payment::where('transaction_id', 'MP-20261026-AAAAAA')->first();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->credited_at);

        // Effets de bord une seule fois
        Mail::assertSent(PaymentReceivedMail::class, 1);
        $this->assertSame(1, AdminNotification::where('type', 'payment_marketplace')->count());
    }

    public function test_webhook_mp_refuse_un_montant_ou_une_devise_incorrects(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();
        $this->pendingMarketplacePayment($user, $product, 'MP-20261026-BBBBBB');

        $this->postWebhook($this->chargeSuccess('MP-20261026-BBBBBB', 500))->assertOk();
        $this->postWebhook($this->chargeSuccess('MP-20261026-BBBBBB', 1000, 'NGN'))->assertOk();

        $this->assertSame('pending', $this->purchaseStatus($user, $product));
        $this->assertSame('pending', Payment::where('transaction_id', 'MP-20261026-BBBBBB')->value('status'));
        Mail::assertNotSent(PaymentReceivedMail::class);
    }

    public function test_webhook_mp_debloque_meme_si_une_tentative_plus_recente_a_remplace_la_reference(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();
        $this->pendingMarketplacePayment($user, $product, 'MP-OLD-111111');
        // 2e tentative : l'achat (unique par user+produit) pointe maintenant sur MP-NEW
        $this->pendingMarketplacePayment($user, $product, 'MP-NEW-222222');

        // L'utilisateur finit par payer l'ANCIEN lien
        $this->postWebhook($this->chargeSuccess('MP-OLD-111111', 1000))->assertOk();

        $this->assertSame('paid', $this->purchaseStatus($user, $product));
    }

    public function test_callback_navigateur_apres_webhook_redirige_sans_doublon(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();
        $this->pendingMarketplacePayment($user, $product, 'MP-CALLBACK-1');

        $body = $this->chargeSuccess('MP-CALLBACK-1', 1000);
        $this->postWebhook($body)->assertOk();

        $this->fakePaystack(['MP-CALLBACK-1' => $body['data']]);

        $this->actingAs($user)
            ->get('/paystack/marketplace/callback?reference=MP-CALLBACK-1')
            ->assertRedirect(route('marketplace.show', $product->slug))
            ->assertSessionHas('success');

        Mail::assertSent(PaymentReceivedMail::class, 1);
        $this->assertSame(1, AdminNotification::where('type', 'payment_marketplace')->count());
    }

    // ─── Webhook : formations ───────────────────────────────────────────────

    public function test_webhook_course_debloque_la_formation_une_seule_fois(): void
    {
        $user   = User::factory()->create();
        $course = Course::create(['title' => 'Bourse 101', 'slug' => 'bourse-101', 'price_fcfa' => 5000, 'cf_uid' => 'cf-test', 'is_active' => true]);
        $purchase = CoursePurchase::create(['user_id' => $user->id, 'course_id' => $course->id, 'amount_fcfa' => 5000]);

        Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => 'COURSE-1-1-ABCDEFGHIJ',
            'amount_paid'    => 5000,
            'amount_virtual' => 0,
            'purpose'        => 'course',
            'status'         => 'PENDING',
            'meta'           => ['course_id' => $course->id, 'purchase_id' => $purchase->id, 'provider' => 'paystack'],
        ]);

        $body = $this->chargeSuccess('COURSE-1-1-ABCDEFGHIJ', 5000);
        $this->postWebhook($body)->assertOk();

        $firstPaidAt = $purchase->fresh()->paid_at;
        $this->assertNotNull($firstPaidAt);

        $this->travel(5)->minutes();
        $this->postWebhook($body)->assertOk();

        $this->assertEquals($firstPaidAt, $purchase->fresh()->paid_at);
        $this->assertSame('ACCEPTED', Payment::where('transaction_id', 'COURSE-1-1-ABCDEFGHIJ')->value('status'));
    }

    // ─── Affiliation ────────────────────────────────────────────────────────

    public function test_commission_affilie_creee_une_fois_apres_paiement_reussi(): void
    {
        $affiliateUser = User::factory()->create();
        Affiliate::create(['user_id' => $affiliateUser->id, 'code' => 'KOFFI10', 'status' => 'active']);

        $buyer   = User::factory()->create();
        $product = $this->makeGame(['price' => 900]);
        DB::table('marketplace_products')->where('id', $product->id)->update(['affiliate_eligible' => true]);

        // Payment::create doit conserver affiliate_code (fillable)
        $payment = $this->pendingMarketplacePayment($buyer, $product, 'MP-20261026-AFFIL1', [
            'affiliate_code'        => 'KOFFI10',
            'affiliate_base_amount' => 1000,
        ]);
        $this->assertSame('KOFFI10', $payment->fresh()->affiliate_code);
        $this->assertSame(1000, (int) $payment->fresh()->affiliate_base_amount);

        $body = $this->chargeSuccess('MP-20261026-AFFIL1', 900);
        $this->postWebhook($body)->assertOk();
        $this->postWebhook($body)->assertOk();

        $this->assertSame(1, AffiliateCommission::where('payment_id', $payment->id)->count());
        $this->assertSame(1000, (int) AffiliateCommission::where('payment_id', $payment->id)->value('base_amount'));
    }

    // ─── Webhook : autres références ────────────────────────────────────────

    public function test_webhook_abrun_prem_debloque_le_premium_abidjan_run_une_seule_fois(): void
    {
        $user    = User::factory()->create(['email' => 'joueur@example.com']);
        $product = $this->makeGame(['title' => 'Abidjan Run', 'slug' => 'abidjan-run-le-jeu-run-a-livoirienne']);

        // Ancienne popup navigateur : metadata sans user_id → repli sur l'email client
        $body = $this->chargeSuccess('ABRUN_PREM_1712300000000', 1000, 'XOF', [
            'customer' => ['email' => 'joueur@example.com'],
            'metadata' => ['char' => 'zouglou', 'feature' => 'premium_chars'],
        ]);

        $this->postWebhook($body)->assertOk();
        $this->postWebhook($body)->assertOk();

        $this->assertSame(1, GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', 'premium_chars')
            ->count());
    }

    public function test_webhook_adom_ci_est_transfere_si_url_configuree(): void
    {
        Queue::fake();
        config(['services.adom_ci.webhook_url' => 'https://adom.example/paystack/webhook']);

        $body = $this->chargeSuccess('ADOM-CI-778899', 2500);
        $this->postWebhook($body)->assertOk();

        Queue::assertPushed(ForwardAdomCiWebhook::class, function ($job) use ($body) {
            return $job->url === 'https://adom.example/paystack/webhook'
                && $job->rawPayload === json_encode($body)
                && $job->signature === hash_hmac('sha512', json_encode($body), self::SECRET);
        });
    }

    public function test_webhook_adom_ci_sans_url_repond_200_sans_transfert(): void
    {
        Queue::fake();
        config(['services.adom_ci.webhook_url' => null]);

        $this->postWebhook($this->chargeSuccess('ADOM-CI-778899', 2500))->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_webhook_reference_inconnue_repond_200_et_signature_invalide_401(): void
    {
        $this->postWebhook($this->chargeSuccess('3f1c2a9e-7b1d-4c55-9a0e-2c6f8f1b9d10', 1500))->assertOk();
        $this->postWebhook($this->chargeSuccess('MP-X', 1000), 'bad-signature')->assertStatus(401);
    }

    // ─── Relance des paiements abandonnés ───────────────────────────────────

    public function test_notify_abandoned_relance_un_paiement_marketplace_en_minuscules(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();
        $payment = $this->pendingMarketplacePayment($user, $product, 'MP-ABANDON-1');
        $this->backdate($payment, minutes: 30);

        $this->artisan('payments:notify-abandoned')->assertSuccessful();

        Mail::assertQueued(AbandonedPaymentMail::class, function ($mail) use ($product) {
            return $mail->isMarketplace
                && $mail->productTitle === $product->title
                && str_contains($mail->whatsappUrl, 'wa.me/2250767123451');
        });
        $this->assertNotNull($payment->fresh()->abandoned_notified_at);
    }

    public function test_notify_abandoned_ne_relance_pas_un_paiement_regle_depuis(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();

        $abandoned = $this->pendingMarketplacePayment($user, $product, 'MP-ESSAI-1');
        $this->backdate($abandoned, minutes: 40);

        // 2e tentative réussie pour le même jeu
        $this->pendingMarketplacePayment($user, $product, 'MP-ESSAI-2');
        $this->postWebhook($this->chargeSuccess('MP-ESSAI-2', 1000))->assertOk();

        $this->artisan('payments:notify-abandoned')->assertSuccessful();

        Mail::assertNotQueued(AbandonedPaymentMail::class);
        $this->assertNull($abandoned->fresh()->abandoned_notified_at);
    }

    public function test_notify_abandoned_une_seule_relance_par_produit_sur_48h_et_rien_au_dela(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeGame();

        $first  = $this->pendingMarketplacePayment($user, $product, 'MP-T1');
        $second = $this->pendingMarketplacePayment($user, $product, 'MP-T2');
        $old    = $this->pendingMarketplacePayment($user, $this->makeGame(['title' => 'Vieux', 'slug' => 'vieux-jeu']), 'MP-OLD');
        $this->backdate($first, minutes: 60);
        $this->backdate($second, minutes: 30);
        $this->backdate($old, minutes: 49 * 60);

        $this->artisan('payments:notify-abandoned')->assertSuccessful();

        Mail::assertQueued(AbandonedPaymentMail::class, 1);
        $this->assertNotNull($first->fresh()->abandoned_notified_at);
        $this->assertNull($second->fresh()->abandoned_notified_at);
        $this->assertNull($old->fresh()->abandoned_notified_at);
    }

    // ─── Réconciliation ─────────────────────────────────────────────────────

    public function test_reconcile_dry_run_ne_modifie_rien_puis_applique(): void
    {
        $user    = User::factory()->create();
        $paid    = $this->makeGame();
        $dropped = $this->makeGame(['title' => 'Autre', 'slug' => 'autre-jeu']);

        $this->backdate($this->pendingMarketplacePayment($user, $paid, 'MP-REC-OK'), minutes: 120);
        $this->backdate($this->pendingMarketplacePayment($user, $dropped, 'MP-REC-KO'), minutes: 120);
        $cauris = CaurisAchat::create([
            'user_id' => $user->id, 'reference' => 'CAURIS-rec', 'pack' => 'petit',
            'prix' => 500, 'cauris' => 1000, 'status' => 'PENDING',
        ]);
        DB::table('cauris_achats')->where('id', $cauris->id)->update(['created_at' => now()->subHours(2)]);

        $this->fakePaystack([
            'MP-REC-OK'  => ['status' => 'success', 'amount' => 100000, 'currency' => 'XOF'],
            'MP-REC-KO'  => ['status' => 'abandoned', 'amount' => 100000, 'currency' => 'XOF'],
            'CAURIS-rec' => ['status' => 'failed', 'amount' => 50000, 'currency' => 'XOF'],
        ]);

        $this->artisan('payments:reconcile-paystack', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('pending', $this->purchaseStatus($user, $paid));
        $this->assertSame('pending', Payment::where('transaction_id', 'MP-REC-KO')->value('status'));
        $this->assertSame('PENDING', $cauris->fresh()->status);

        $this->artisan('payments:reconcile-paystack')->assertSuccessful();

        $this->assertSame('paid', $this->purchaseStatus($user, $paid));
        $this->assertSame('paid', Payment::where('transaction_id', 'MP-REC-OK')->value('status'));
        $this->assertSame('abandoned', Payment::where('transaction_id', 'MP-REC-KO')->value('status'));
        $this->assertSame('failed', $this->purchaseStatus($user, $dropped));
        $this->assertSame('FAILED', $cauris->fresh()->status);
    }

    private function fakePaystack(array $byReference): void
    {
        $fake = new class($byReference) extends PaystackService {
            public function __construct(private array $byReference) {}

            public function fetchTransaction(string $reference): array
            {
                if (!isset($this->byReference[$reference])) {
                    return ['found' => false, 'data' => null, 'error' => null];
                }

                return ['found' => true, 'data' => array_merge(
                    ['reference' => $reference, 'customer' => ['email' => 'client@example.com']],
                    $this->byReference[$reference]
                ), 'error' => null];
            }

            public function verify(string $reference): ?array
            {
                return $this->fetchTransaction($reference)['data'];
            }
        };

        $this->app->instance(PaystackService::class, $fake);
    }

    private function backdate(Payment $payment, int $minutes): void
    {
        DB::table('payments')->where('id', $payment->id)->update(['created_at' => now()->subMinutes($minutes)]);
    }
}
