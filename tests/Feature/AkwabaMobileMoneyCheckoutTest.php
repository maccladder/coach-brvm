<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\MarketplaceProduct;
use App\Models\MobileMoneyCheckout;
use App\Models\Payment;
use App\Models\User;
use App\Services\AkwabaService;
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

/**
 * Akwaba dans le checkout Mobile Money personnalisé (CHECKOUT_MM_AKWABA) : histoire complète
 * (MP-) et pack de 5 indices (AKW-H-), mêmes règles que la page Paystack hébergée.
 */
class AkwabaMobileMoneyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_checkout';

    private array $calls = [];
    private array $routes = [];
    private MarketplaceProduct $product;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret'      => self::SECRET,
            'checkout.types.akwaba.enabled' => true,
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

        $this->product = MarketplaceProduct::where('slug', 'akwaba')->firstOrFail();
        $this->player  = User::factory()->create();
        $this->actingAs($this->player);
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

    private function chargeResponse(string $status, array $extra = []): array
    {
        return ['status' => true, 'message' => 'Charge attempted', 'data' => array_merge([
            'status'       => $status,
            'display_text' => 'Please complete authorization on your mobile phone',
        ], $extra)];
    }

    private function pay(string $which, string $operator = 'orange', string $phone = '0707123456')
    {
        return $this->postJson(route('checkout.mm.pay', 'akwaba'), ['product' => $which, 'operator' => $operator, 'phone' => $phone]);
    }

    private function webhook(string $reference, int $amountXof)
    {
        $json = json_encode(['event' => 'charge.success', 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100,
            'currency' => 'XOF', 'channel' => 'mobile_money',
        ]]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    /** transaction/verify renvoie cette transaction (relue par la validation des indices). */
    private function verifyReturns(string $reference, int $amountXof, array $metadata = [], string $status = 'success', string $currency = 'XOF'): void
    {
        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => $status, 'amount' => $amountXof * 100, 'currency' => $currency,
            'metadata'  => $metadata + ['user_id' => $this->player->id, 'game' => 'akwaba', 'product' => 'hints5'],
        ]]);
    }

    private function balance(): int
    {
        return (int) GameCredit::where('user_id', $this->player->id)->where('game_slug', 'akwaba')->value('balance');
    }

    private function unlocked(): bool
    {
        return app(AkwabaService::class)->isUnlocked($this->player->fresh());
    }

    // ─── Drapeau ────────────────────────────────────────────────────────────

    public function test_drapeau_false_garde_la_page_paystack_hebergee(): void
    {
        config(['checkout.types.akwaba.enabled' => false]);
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]);

        foreach (['full', 'hints5'] as $which) {
            $this->postJson(route('akwaba.buy'), ['product' => $which])
                ->assertOk()->assertJsonPath('url', 'https://checkout.paystack.com/abc');
        }

        $this->assertCount(2, $this->calls('POST', '/transaction/initialize'));
        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'full']))->assertNotFound();
    }

    public function test_drapeau_true_renvoie_le_checkout_et_classic_garde_la_page_paystack(): void
    {
        foreach (['full', 'hints5'] as $which) {
            $this->postJson(route('akwaba.buy'), ['product' => $which])
                ->assertOk()
                ->assertJsonPath('url', route('checkout.mm.show', ['type' => 'akwaba', 'product' => $which]));
        }
        $this->assertCount(0, $this->calls('POST', '/transaction/initialize'));

        // Secours « Payer par la page Paystack »
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz']]);
        $this->postJson(route('akwaba.buy'), ['product' => 'hints5', 'classic' => 1])
            ->assertOk()->assertJsonPath('url', 'https://checkout.paystack.com/xyz');

        $init = $this->calls('POST', '/transaction/initialize')[0]['body'];
        $this->assertSame(50000, $init['amount']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame(route('akwaba.payment.return'), $init['callback_url']);
    }

    // ─── Écran du checkout ──────────────────────────────────────────────────

    public function test_l_ecran_affiche_le_produit_le_prix_et_le_retour_au_jeu(): void
    {
        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'full']))
            ->assertOk()
            ->assertSee('Akwaba · Histoire complète')
            ->assertSee('1 000')
            // URLs échappées dans le HTML (& → &amp;)
            ->assertSee(route('akwaba.show', ['paiement' => 'annule', 'produit' => 'full']))
            ->assertSee(route('akwaba.show', ['paiement' => 'echec', 'produit' => 'full']))
            ->assertSee("On t'active l'accès, tu paies après");

        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'hints5']))
            ->assertOk()
            ->assertSee('Akwaba · 5 indices')
            ->assertSee('500');
    }

    public function test_produit_inconnu_ou_histoire_deja_debloquee_renvoie_au_jeu(): void
    {
        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'vip']))->assertRedirect(route('akwaba.show'));

        DB::table('marketplace_purchases')->insert([
            'user_id' => $this->player->id, 'product_id' => $this->product->id, 'amount' => 1000,
            'provider' => 'paystack', 'status' => 'paid', 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'full']))->assertRedirect(route('akwaba.show'));
        $this->pay('full')->assertStatus(409);
        $this->assertCount(0, $this->calls('POST', '/charge'));
    }

    public function test_l_invite_est_envoye_a_la_connexion(): void
    {
        auth()->logout();

        $this->get(route('checkout.mm.show', ['type' => 'akwaba', 'product' => 'full']))->assertRedirect(route('login'));
    }

    // ─── Histoire complète ──────────────────────────────────────────────────

    public function test_full_valide_par_le_webhook_avec_les_metadonnees_akwaba(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $res = $this->pay('full', 'mtn', '0505123456')->assertOk()->assertJsonPath('status', 'pending');
        $reference = $res->json('reference');
        $this->assertMatchesRegularExpression('/^MP-\d{14}-[A-Z0-9]{6}$/', $reference);

        $charge = $this->calls('POST', '/charge')[0]['body'];
        $this->assertSame(100000, $charge['amount']);
        $this->assertSame($this->player->id, $charge['metadata']['user_id']);
        $this->assertSame('akwaba', $charge['metadata']['game']);
        $this->assertSame('full', $charge['metadata']['product']);
        $this->assertSame($this->product->id, $charge['metadata']['product_id']);
        $this->assertSame('+2250505123456', $charge['mobile_money']['phone']);

        $payment = Payment::where('transaction_id', $reference)->firstOrFail();
        $this->assertSame(1000, (int) $payment->amount_paid);
        $this->assertSame('mobile_money_checkout', $payment->meta['channel']);
        $this->assertFalse($this->unlocked());

        $this->webhook($reference, 1000)->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_url', route('akwaba.show', ['paid' => 1]))
            ->assertJsonPath('success_label', 'Retour au jeu')
            ->assertJsonPath('auto_return', 2);
        $this->assertTrue($this->unlocked());
    }

    public function test_full_montant_falsifie_ne_debloque_pas(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $reference = $this->pay('full')->json('reference');

        $this->webhook($reference, 100)->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'pending');
        $this->assertFalse($this->unlocked());
    }

    public function test_full_webhook_rejoue_un_seul_achat(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $reference = $this->pay('full')->json('reference');

        $this->webhook($reference, 1000)->assertOk();
        $this->webhook($reference, 1000)->assertOk();
        $this->get(route('akwaba.payment.return', ['reference' => $reference]));

        $this->assertSame(1, DB::table('marketplace_purchases')->where('product_id', $this->product->id)->where('status', 'paid')->count());
        $this->assertSame(0, Payment::where('meta', 'like', '%"duplicate":true%')->count());
    }

    public function test_full_paye_deux_fois_trace_un_paiement_en_double_a_rembourser(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        // Changement de numéro : la 1re tentative est remplacée, puis validée quand même
        $first  = $this->pay('full', 'orange', '0707000001')->json('reference');
        $second = $this->pay('full', 'mtn', '0505000002')->json('reference');
        $this->assertNotSame($first, $second);
        $this->assertSame('replaced', MobileMoneyCheckout::where('reference', $first)->value('status'));

        $this->webhook($first, 1000)->assertOk();
        $this->assertTrue($this->unlocked());

        $this->webhook($second, 1000)->assertOk();

        // Un seul achat, le second paiement est marqué « à rembourser »
        $this->assertSame(1, DB::table('marketplace_purchases')->where('product_id', $this->product->id)->count());
        $this->assertSame($first, DB::table('marketplace_purchases')->where('product_id', $this->product->id)->value('provider_ref'));
        $dup = Payment::where('transaction_id', $second)->firstOrFail();
        $this->assertTrue($dup->meta['duplicate']);
        $this->assertSame('to_refund', $dup->meta['refund_status']);
        $this->assertSame(1, AdminNotification::where('type', 'payment_duplicate')->count());

        // Visible dans /admin/akwaba, et hors du chiffre d'affaires
        $response = $this->withSession(['is_admin' => true])->get(route('admin.akwaba.index'))->assertOk()
            ->assertSee('Paiements en double à rembourser')
            ->assertSee($second);
        $this->assertSame(1000, $response->viewData('stats')['revenue']['paystack']);

        // Rejouer le webhook du doublon ne recrée rien
        $this->webhook($second, 1000)->assertOk();
        $this->assertSame(1, AdminNotification::where('type', 'payment_duplicate')->count());
    }

    // ─── Pack de 5 indices ──────────────────────────────────────────────────

    public function test_hints5_valide_par_le_suivi_en_relisant_paystack(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));

        $reference = $this->pay('hints5')->assertOk()->json('reference');
        $this->assertStringStartsWith('AKW-H-' . $this->player->id . '-', $reference);

        $charge = $this->calls('POST', '/charge')[0]['body'];
        $this->assertSame(50000, $charge['amount']);
        $this->assertSame(['akwaba', 'hints5', $this->player->id], [$charge['metadata']['game'], $charge['metadata']['product'], $charge['metadata']['user_id']]);

        // /charge dit success ; la validation relit quand même transaction/verify
        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'success', 'amount' => 50000, 'currency' => 'XOF']]);
        $this->verifyReturns($reference, 500);
        $this->travel(12)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_url', route('akwaba.show', ['paid' => 1]));

        $this->assertSame(5, $this->balance());
        $this->assertCount(1, $this->calls('GET', '/transaction/verify/'));
    }

    public function test_hints5_rejoue_webhook_retour_et_suivi_un_seul_credit(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $reference = $this->pay('hints5')->json('reference');
        $this->verifyReturns($reference, 500);

        $this->webhook($reference, 500)->assertOk();
        $this->webhook($reference, 500)->assertOk();
        $this->get(route('akwaba.payment.return', ['reference' => $reference]));
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');

        $this->assertSame(5, $this->balance());
        $this->assertSame(1, GameCreditLedger::where('payment_reference', $reference)->count());
    }

    public function test_hints5_montant_ou_devise_falsifies_chez_paystack_aucun_credit(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $reference = $this->pay('hints5')->json('reference');

        // /charge annonce un succès, mais la transaction relue chez Paystack n'est pas de 500 XOF
        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'success', 'amount' => 50000, 'currency' => 'XOF']]);
        $this->verifyReturns($reference, 100);
        $this->travel(12)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'failed');
        $this->webhook($reference, 500)->assertOk();

        $this->verifyReturns($reference, 500, [], 'success', 'USD');
        $this->webhook($reference, 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    public function test_hints5_metadonnees_d_un_autre_jeu_aucun_credit(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $reference = $this->pay('hints5')->json('reference');
        $this->verifyReturns($reference, 500, ['game' => 'autre']);

        $this->webhook($reference, 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    // ─── Échec et abandon ───────────────────────────────────────────────────

    public function test_echec_operateur_rien_n_est_credite_et_retour_au_jeu_propose(): void
    {
        foreach (['full', 'hints5'] as $which) {
            $this->paystackReturns('POST /charge', $this->chargeResponse('failed', ['gateway_response' => 'Insufficient funds']));

            $this->pay($which, 'orange', $which === 'full' ? '0707111111' : '0707222222')
                ->assertOk()
                ->assertJsonPath('status', 'failed')
                ->assertJsonPath('not_debited', true)
                ->assertJsonPath('fail_url', route('akwaba.show', ['paiement' => 'echec', 'produit' => $which]))
                ->assertJsonPath('fail_label', 'Retour au jeu');
        }

        $this->assertFalse($this->unlocked());
        $this->assertSame(0, $this->balance());
    }

    public function test_abandon_expire_confirme_par_paystack(): void
    {
        $this->paystackReturns('POST /charge', $this->chargeResponse('pay_offline'));
        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => ['status' => 'pay_offline']]);

        $reference = $this->pay('hints5')->json('reference');

        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'abandoned']]);
        $this->travel(181)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('not_debited', true);

        $this->assertSame(0, $this->balance());
    }

    public function test_la_page_hote_propose_reessayer_et_la_page_paystack(): void
    {
        $html = $this->get(route('akwaba.show', ['paiement' => 'echec', 'produit' => 'full']))->assertOk()->getContent();

        $this->assertStringContainsString("'Réessayer'", $html);
        $this->assertStringContainsString("'Payer par la page Paystack'", $html);
        $this->assertStringContainsString("{ product: product, classic: 1 }", $html);
        $this->assertStringContainsString("failKind === 'echec' || failKind === 'annule'", $html);
    }

    // ─── Wave et chemins de côté ────────────────────────────────────────────

    public function test_wave_passe_par_la_page_paystack_hebergee(): void
    {
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/wave']]);

        $this->post(route('checkout.mm.classic', 'akwaba'), ['product' => 'full', 'operator' => 'wave'])
            ->assertRedirect('https://checkout.paystack.com/wave');

        $init = $this->calls('POST', '/transaction/initialize')[0]['body'];
        $this->assertSame(100000, $init['amount']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame('full', $init['metadata']['product']);
        $this->assertSame(route('akwaba.payment.return'), $init['callback_url']);
    }

    public function test_l_achat_marketplace_standard_d_akwaba_renvoie_au_jeu(): void
    {
        config(['checkout.types.marketplace.enabled' => true]);

        $this->post(route('paystack.marketplace.buy', $this->product))->assertRedirect(route('akwaba.show'));
        $this->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $this->product->id]))->assertRedirect(route('akwaba.show'));

        $this->assertSame(0, Payment::count());
        $this->assertCount(0, $this->calls('POST', '/transaction/initialize'));
    }

    // ─── Simulation locale ──────────────────────────────────────────────────

    public function test_simulation_locale_credite_le_pack_via_le_simulateur(): void
    {
        config(['checkout.simulate' => true]);

        $reference = $this->pay('hints5', 'mtn', '0505120003')->assertOk()->json('reference');

        $this->travel(16)->seconds();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');

        $this->assertSame(5, $this->balance());
        $this->assertSame([], $this->calls);  // aucun appel au vrai Paystack
    }
}
