<?php

namespace Tests\Feature;

use App\Mail\PaymentReceivedMail;
use App\Models\CaurisAchat;
use App\Models\Course;
use App\Models\Donation;
use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\MobileMoneyCheckout;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaystackService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class MobileMoneyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_checkout';

    /** @var array<int, array{method: string, path: string, body: array}> */
    private array $calls = [];

    /** Réponses Paystack simulées : "METHOD /chemin" (préfixe) => [status, body] */
    private array $routes = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret' => self::SECRET,
            'checkout.types.marketplace.enabled' => true,
        ]);
        Mail::fake();

        $handler = function (RequestInterface $request) {
            $path = $request->getUri()->getPath();
            $this->calls[] = [
                'method' => $request->getMethod(),
                'path'   => $path,
                'body'   => json_decode((string) $request->getBody(), true) ?? [],
            ];

            foreach ($this->routes as $key => [$status, $body]) {
                [$method, $prefix] = explode(' ', $key, 2);
                if ($method === $request->getMethod() && str_starts_with($path, $prefix)) {
                    return Create::promiseFor(new Response($status, ['Content-Type' => 'application/json'], json_encode($body)));
                }
            }

            return Create::promiseFor(new Response(404, [], json_encode(['status' => false, 'message' => 'not mocked'])));
        };

        $client = new Client(['base_uri' => 'https://api.paystack.co', 'handler' => HandlerStack::create($handler)]);
        $this->app->instance(PaystackService::class, new PaystackService($client));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function paystackReturns(string $key, array $body, int $status = 200): void
    {
        $this->routes[$key] = [$status, $body];
    }

    private function calls(string $method, string $prefix): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c['method'] === $method && str_starts_with($c['path'], $prefix)));
    }

    private function game(array $attrs = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'title'  => 'Le Prince de Yop',
            'slug'   => 'le-prince-de-yop',
            'type'   => 'game',
            'status' => 'published',
            'price'  => 1000,
        ], $attrs));
    }

    private function chargeResponse(string $status, array $extra = []): array
    {
        return ['status' => true, 'message' => 'Charge attempted', 'data' => array_merge([
            'status'       => $status,
            'display_text' => 'Please complete authorization on your mobile phone',
        ], $extra)];
    }

    private function pay(string $type, array $params, string $operator = 'orange', string $phone = '0707123456')
    {
        return $this->postJson(route('checkout.mm.pay', $type), array_merge($params, compact('operator', 'phone')));
    }

    private function webhook(string $reference, int $amountXof, array $extra = [])
    {
        $json = json_encode(['event' => 'charge.success', 'data' => array_merge([
            'reference' => $reference,
            'status'    => 'success',
            'amount'    => $amountXof * 100,
            'currency'  => 'XOF',
            'channel'   => 'mobile_money',
            'customer'  => ['email' => 'client@example.com'],
        ], $extra)]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    // ─── Drapeaux ───────────────────────────────────────────────────────────

    public function test_drapeau_false_garde_l_ancien_flux_paystack(): void
    {
        config(['checkout.types.marketplace.enabled' => false]);
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]);

        $user    = User::factory()->create();
        $product = $this->game();

        $this->actingAs($user)
            ->post(route('paystack.marketplace.buy', $product))
            ->assertRedirect('https://checkout.paystack.com/abc');

        $this->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))->assertNotFound();
    }

    public function test_drapeau_true_redirige_vers_le_checkout_et_classic_force_l_ancien_flux(): void
    {
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz']]);

        $user    = User::factory()->create();
        $product = $this->game();

        $this->actingAs($user)
            ->post(route('paystack.marketplace.buy', $product))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]));

        // Lien "Autre moyen de paiement"
        $this->post(route('paystack.marketplace.buy', $product), ['classic' => 1])
            ->assertRedirect('https://checkout.paystack.com/xyz');
    }

    public function test_page_de_checkout_affiche_le_jeu_le_prix_et_les_badges(): void
    {
        $user    = User::factory()->create();
        $product = $this->game();

        $this->actingAs($user)
            ->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))
            ->assertOk()
            ->assertSee('Le Prince de Yop')
            ->assertSee('1 000 F CFA')
            ->assertSee('Accès à vie')
            ->assertSee('Téléphone et ordi')
            ->assertSee('Orange Money')->assertSee('MTN MoMo')->assertSee('data-classic="1"', false)
            ->assertDontSee('Autre moyen de paiement')
            ->assertSee('class="btn-wa-pill" href="https://wa.me/2250767123451?text=', false)
            ->assertSee("Besoin d'aide ? Écris-nous sur WhatsApp", false);
    }

    public function test_article_gratuit_garde_son_flux_claim(): void
    {
        $user = User::factory()->create();
        $free = $this->game(['title' => 'Garba Master', 'slug' => 'garba-master', 'price' => 0]);

        $this->actingAs($user)
            ->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $free->id]))
            ->assertRedirect(route('marketplace.show', 'garba-master'));
    }

    // ─── Charge, réutilisation, OTP ─────────────────────────────────────────

    public function test_paiement_marketplace_cree_le_charge_et_les_lignes_en_attente(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create(['email' => 'acheteur@example.com']);
        $product = $this->game();

        $res = $this->actingAs($user)->pay('marketplace', ['product' => $product->id], 'mtn', '05 05 12 34 56');

        $res->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('operator_label', 'MTN MoMo')
            ->assertJsonPath('display_text', 'Valide le paiement sur ton téléphone.')->assertJsonPath('message', null);

        $reference = $res->json('reference');
        $this->assertStringStartsWith('MP-', $reference);

        $charge = $this->calls('POST', '/charge')[0]['body'];
        $this->assertSame(100000, $charge['amount']);
        $this->assertSame('XOF', $charge['currency']);
        $this->assertSame('acheteur@example.com', $charge['email']);
        $this->assertSame(['phone' => '+2250505123456', 'provider' => 'mtn'], $charge['mobile_money']);
        $this->assertSame($product->id, $charge['metadata']['product_id']);
        $this->assertSame($user->id, $charge['metadata']['user_id']);

        $this->assertSame('pending', Payment::where('transaction_id', $reference)->value('status'));
        $this->assertSame('pending', DB::table('marketplace_purchases')->where('provider_ref', $reference)->value('status'));
    }

    public function test_une_seule_tentative_en_cours_par_article_et_changement_de_numero(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $first  = $this->pay('marketplace', ['product' => $product->id])->json('reference');
        $second = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->assertSame($first, $second);
        $this->assertCount(1, $this->calls('POST', '/charge'));

        // Autre numéro : nouvelle tentative, l'ancienne est remplacée
        $third = $this->pay('marketplace', ['product' => $product->id], 'mtn', '0505123456')->json('reference');
        $this->assertNotSame($first, $third);
        $this->assertSame('replaced', MobileMoneyCheckout::where('reference', $first)->value('status'));

        // Au-delà de 3 minutes : nouvelle référence même avec le même numéro
        $this->travel(4)->minutes();
        $fourth = $this->pay('marketplace', ['product' => $product->id], 'mtn', '0505123456')->json('reference');
        $this->assertNotSame($third, $fourth);
    }

    public function test_send_otp_puis_succes_via_webhook(): void
    {
        $this->paystackReturns('POST /charge/submit_otp', $this->chargeResponse('pay_offline'));
        $this->paystackReturns('POST /charge', $this->chargeResponse('send_otp', ['display_text' => 'Saisis le code reçu par SMS']));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $res = $this->pay('marketplace', ['product' => $product->id], 'mtn', '0505123456')
            ->assertOk()
            ->assertJsonPath('status', 'send_otp')
            ->assertJsonPath('needs_otp', true);

        $reference = $res->json('reference');

        $this->postJson(route('checkout.mm.otp', $reference), ['otp' => '123456'])
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('needs_otp', false);

        $this->assertSame(['otp' => '123456', 'reference' => $reference], $this->calls('POST', '/charge/submit_otp')[0]['body']);

        // Polling avant le webhook : toujours en attente, Paystack pas encore interrogé (< 10 s)
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'pending');
        $this->assertCount(0, $this->calls('GET', '/charge/'));

        $this->webhook($reference, 1000)->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Jouer maintenant')
            ->assertJsonPath('success_url', route('my.products.play', $product));

        $this->assertSame('paid', DB::table('marketplace_purchases')->where('provider_ref', $reference)->value('status'));
    }

    public function test_succes_idempotent_webhook_rejoue_et_polling(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $reference = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->webhook($reference, 1000)->assertOk();
        $this->webhook($reference, 1000)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');

        Mail::assertSent(PaymentReceivedMail::class, 1);
        $this->assertSame(1, Payment::where('purpose', 'marketplace')->whereNotNull('credited_at')->count());
    }

    public function test_montant_ou_devise_incorrects_ne_debloquent_rien(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $reference = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->webhook($reference, 500)->assertOk();
        $this->webhook($reference, 1000, ['currency' => 'GHS'])->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'pending');
        $this->assertNull(Payment::where('transaction_id', $reference)->value('credited_at'));
    }

    // ─── Secours polling + expiration ───────────────────────────────────────

    public function test_secours_get_charge_apres_10_secondes(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $reference = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => 100000, 'currency' => 'XOF',
        ]]);

        $this->travel(12)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');
        $this->assertCount(1, $this->calls('GET', '/charge/'));
        $this->assertSame('paid', Payment::where('transaction_id', $reference)->value('status'));
    }

    public function test_expiration_confirmee_par_paystack_affiche_non_debite(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => ['status' => 'pay_offline']]);

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $reference = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'abandoned']]);
        $this->travel(181)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('not_debited', true);
    }

    public function test_expiration_non_confirmee_ne_dit_pas_non_debite(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user    = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $reference = $this->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'ongoing']]);
        $this->travel(181)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'expired')
            ->assertJsonPath('not_debited', false);

        // Validé en retard sur le téléphone : le webhook fait passer la tentative en succès
        $this->webhook($reference, 1000)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');
    }

    public function test_echec_immediat_paystack_failed(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('failed', ['gateway_response' => 'Solde insuffisant']));

        $user    = User::factory()->create();
        $product = $this->game();

        $this->actingAs($user)->pay('marketplace', ['product' => $product->id])
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('not_debited', true)
            ->assertJsonPath('message', 'Solde insuffisant');
    }

    // ─── Sécurité ───────────────────────────────────────────────────────────

    public function test_limite_5_paiements_par_minute(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user = User::factory()->create();
        $this->actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $p = $this->game(['title' => "Jeu $i", 'slug' => "jeu-$i"]);
            $this->pay('marketplace', ['product' => $p->id])->assertOk();
        }

        $p = $this->game(['title' => 'Jeu 6', 'slug' => 'jeu-6']);
        $this->pay('marketplace', ['product' => $p->id])->assertStatus(429);
    }

    public function test_statut_d_une_tentative_d_un_autre_utilisateur_introuvable(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $product   = $this->game();
        $reference = $this->actingAs(User::factory()->create())->pay('marketplace', ['product' => $product->id])->json('reference');

        $this->actingAs(User::factory()->create())
            ->getJson(route('checkout.mm.status', $reference))
            ->assertNotFound();
    }

    public function test_numero_invalide_refuse(): void
    {
        $product = $this->game();

        $this->actingAs(User::factory()->create())
            ->pay('marketplace', ['product' => $product->id], 'orange', '0707')
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        $this->assertCount(0, $this->calls('POST', '/charge'));
    }

    // ─── Autres types (drapeaux activés pour le test) ──────────────────────

    public function test_formation(): void
    {
        config(['checkout.types.course.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user   = User::factory()->create();
        $course = Course::create(['title' => 'Bourse 101', 'slug' => 'bourse-101', 'price_fcfa' => 5000, 'cf_uid' => 'cf', 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('courses.buy.paystack', $course))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'course', 'course' => $course->id]));

        $reference = $this->pay('course', ['course' => $course->id])->assertOk()->json('reference');
        $this->assertStringStartsWith('COURSE-' . $course->id . '-', $reference);

        $this->webhook($reference, 5000)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Commencer la formation');
    }

    public function test_pack_brvm(): void
    {
        config(['checkout.types.pack.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pack.buy.paystack'))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'pack']));

        $reference = $this->pay('pack', [])->assertOk()->json('reference');
        $this->assertStringStartsWith('PACK-BRVM-' . $user->id . '-', $reference);
        $this->assertSame(3000000, $this->calls('POST', '/charge')[0]['body']['amount']);

        $this->webhook($reference, 30000)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Voir mon pack');
    }

    public function test_recharge_wallet(): void
    {
        config(['checkout.types.wallet.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('checkout.mm.show', ['type' => 'wallet', 'amount' => 2000]))
            ->assertOk()
            ->assertSee('Crédit immédiat')
            ->assertSee('Nouveau solde estimé : 200 000');

        $reference = $this->pay('wallet', ['amount' => 2000])->assertOk()->json('reference');
        $this->assertStringStartsWith('WALLET_', $reference);

        $this->webhook($reference, 2000)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');
        $this->assertSame(200000.0, (float) DB::table('virtual_wallets')->where('user_id', $user->id)->value('balance'));
    }

    public function test_cauris_depuis_le_jeu_en_json(): void
    {
        config(['checkout.types.cauris.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user = User::factory()->create();
        $this->actingAs($user);

        // Le jeu appelle /api/jeu/acheter en fetch : il reçoit l'URL du checkout
        $this->postJson(route('jeu.acheter'), ['pack' => 'petit'])
            ->assertOk()
            ->assertJsonPath('authorization_url', route('checkout.mm.show', ['type' => 'cauris', 'pack' => 'petit']));

        $reference = $this->pay('cauris', ['pack' => 'petit'])->assertOk()->json('reference');
        $this->assertStringStartsWith('CAURIS-', $reference);

        $this->webhook($reference, 500)->assertOk();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');
        $this->assertNotNull(CaurisAchat::where('reference', $reference)->value('credited_at'));
    }

    public function test_don_en_invite(): void
    {
        config(['checkout.types.don.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $this->post(route('donation.initiate'), [
            'donor_name' => 'Awa Koné', 'donor_email' => 'awa@example.com', 'amount' => 2500,
        ])->assertRedirect(route('checkout.mm.show', ['type' => 'don']));

        $this->get(route('checkout.mm.show', ['type' => 'don']))
            ->assertOk()
            ->assertSee('Merci pour ton soutien');

        $reference = $this->pay('don', [])->assertOk()->json('reference');
        $this->assertStringStartsWith('DON-', $reference);
        $this->assertSame('awa@example.com', $this->calls('POST', '/charge')[0]['body']['email']);

        $this->webhook($reference, 2500)->assertOk();
        $this->webhook($reference, 2500)->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_title', 'Merci !');
        $this->assertSame('ACCEPTED', Donation::where('reference', $reference)->value('status'));
    }

    public function test_premium_de_jeu(): void
    {
        config(['checkout.types.game_premium.enabled' => true]);
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $user = User::factory()->create();
        $game = $this->game(['title' => 'Gri-Gri', 'slug' => 'gri-gri-la-danse-des-perles']);
        $user->purchasedProducts()->attach($game->id, ['status' => 'paid', 'amount' => 1000, 'paid_at' => now()]);
        $this->actingAs($user);

        $this->postJson(route('games.init-premium-payment', $game), ['feature' => 'unlimited_continue'])
            ->assertOk()
            ->assertJsonPath('url', route('checkout.mm.show', ['type' => 'game_premium', 'product' => $game->id, 'feature' => 'unlimited_continue']));

        $reference = $this->pay('game_premium', ['product' => $game->id, 'feature' => 'unlimited_continue'])->assertOk()->json('reference');
        $this->assertMatchesRegularExpression('/^GAME' . $game->id . '_UNLIMITED_CONTINUE_\d+$/', $reference);

        $metadata = $this->calls('POST', '/charge')[0]['body']['metadata'];
        $this->webhook($reference, 1000, ['metadata' => $metadata])->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Jouer maintenant');
        $this->assertSame(1, GamePremiumUnlock::where('user_id', $user->id)->where('feature', 'unlimited_continue')->count());
    }
}
