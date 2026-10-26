<?php

namespace Tests\Feature;

use App\Models\MarketplaceProduct;
use App\Models\MobileMoneyCheckout;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaystackMobileMoneyCheckout;
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
 * Liste d'emails autorisés (test en prod), mode simulation, réponses Paystack réelles
 * (Wave unprocessed_transaction, Orange send_otp) et nettoyage de l'affichage.
 */
class MobileMoneyCheckoutSimulationTest extends TestCase
{
    use RefreshDatabase;

    /** Appels HTTP réels vers "Paystack" (doit rester vide en simulation) */
    private array $calls = [];

    /** Corps JSON envoyés, par "METHOD /chemin" (dernier appel) */
    private array $bodies = [];

    /** Réponse renvoyée par le faux Paystack à POST /charge : [http, body] */
    private array $chargeReply = [200, ['status' => true, 'data' => ['status' => 'pay_offline']]];

    /** Réponse de GET /transaction/verify (callback de l'ancien flux) */
    private ?array $verifyData = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret'           => 'sk_test_sim',
            'checkout.types.marketplace.enabled' => true,
            'checkout.allowed_emails'            => '',
            'checkout.simulate'                  => false,
            'checkout.wave_enabled'              => false,
        ]);
        Mail::fake();

        $handler = function (RequestInterface $request) {
            $this->calls[] = $request->getMethod() . ' ' . $request->getUri()->getPath();
            $this->bodies[$request->getMethod() . ' ' . $request->getUri()->getPath()] = json_decode((string) $request->getBody(), true);

            if ($this->verifyData && str_starts_with($request->getUri()->getPath(), '/transaction/verify/')) {
                return Create::promiseFor(new Response(200, [], json_encode(['status' => true, 'data' => $this->verifyData])));
            }

            if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/charge') {
                [$http, $body] = $this->chargeReply;
                return Create::promiseFor(new Response($http, [], json_encode($body)));
            }
            if (str_starts_with($request->getUri()->getPath(), '/transaction/initialize')) {
                return Create::promiseFor(new Response(200, [], json_encode(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/old']])));
            }

            return Create::promiseFor(new Response(200, [], json_encode(['status' => true, 'data' => ['status' => 'pay_offline']])));
        };

        $client = new Client(['base_uri' => 'https://api.paystack.co', 'handler' => HandlerStack::create($handler)]);
        $this->app->instance(PaystackService::class, new PaystackService($client));
    }

    private function game(): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'title' => 'Le Prince de Yop', 'slug' => 'le-prince-de-yop', 'type' => 'game',
            'status' => 'published', 'price' => 1000,
        ]);
    }

    private function pay(MarketplaceProduct $product, string $phone, string $operator = 'orange')
    {
        return $this->postJson(route('checkout.mm.pay', 'marketplace'), [
            'product' => $product->id, 'operator' => $operator, 'phone' => $phone,
        ]);
    }

    private function simulate(): void
    {
        config(['checkout.simulate' => true]);
    }

    // ─── Liste d'emails autorisés ───────────────────────────────────────────

    public function test_liste_autorisee_seuls_ces_comptes_utilisent_le_checkout(): void
    {
        config(['checkout.allowed_emails' => 'MacCladder@gmail.com, autre@example.com']);
        $product = $this->game();

        $admin  = User::factory()->create(['email' => 'maccladder@gmail.com']);
        $client = User::factory()->create(['email' => 'client@example.com']);

        $this->actingAs($admin)
            ->post(route('paystack.marketplace.buy', $product))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]));

        // Tous les autres : ancien flux Paystack, et le checkout leur est fermé
        $this->actingAs($client)
            ->post(route('paystack.marketplace.buy', $product))
            ->assertRedirect('https://checkout.paystack.com/old');

        $this->actingAs($client)
            ->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))
            ->assertNotFound();
        $this->actingAs($client)->pay($product, '0707000003')->assertNotFound();
    }

    public function test_liste_vide_drapeau_normal_ouvert_a_tous(): void
    {
        $product = $this->game();

        $this->actingAs(User::factory()->create())
            ->post(route('paystack.marketplace.buy', $product))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]));
    }

    // ─── Simulation ─────────────────────────────────────────────────────────

    public function test_simulation_interdite_en_production(): void
    {
        $this->simulate();
        $this->app['env'] = 'production';
        // En "production", Laravel ne saute plus le CSRF des tests : hors sujet ici
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->assertFalse(app(PaystackMobileMoneyCheckout::class)->simulating());

        $this->actingAs(User::factory()->create())->pay($this->game(), '0707000003')->assertOk();

        // Le vrai Paystack a été appelé : la simulation a été ignorée
        $this->assertContains('POST /charge', $this->calls);
    }

    public function test_simulation_0001_wave_indisponible(): void
    {
        $this->simulate();
        config(['checkout.wave_enabled' => true]);

        $this->actingAs(User::factory()->create())->pay($this->game(), '0707000001', 'wave')
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('start_failed', true)
            ->assertJsonPath('not_debited', false)
            ->assertJsonPath('message', 'Wave est momentanément indisponible, choisis Orange Money ou MTN MoMo.');

        $this->assertSame([], $this->calls);
    }

    public function test_simulation_0002_orange_code_de_paiement_valide_puis_succes(): void
    {
        $this->simulate();
        $user = User::factory()->create();
        $product = $this->game();
        $this->actingAs($user);

        $res = $this->pay($product, '0707000002')
            ->assertOk()
            ->assertJsonPath('status', 'send_otp')
            ->assertJsonPath('needs_otp', true)
            ->assertJsonPath('display_text', "Termine l'autorisation du paiement dans ton application Mobile Money.")
            ->assertJsonPath('message', null);
        $this->assertStringContainsString('Orange Money', $res->json('otp_help'));

        $reference = $res->json('reference');

        // Pas de délai qui court tant que le code n'est pas saisi
        $this->travel(5)->minutes();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'send_otp');

        $this->postJson(route('checkout.mm.otp', $reference), ['otp' => '123456'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Jouer maintenant');

        $this->assertSame('paid', DB::table('marketplace_purchases')->where('provider_ref', $reference)->value('status'));
        $this->assertSame([], $this->calls);
    }

    public function test_simulation_0002_mauvais_code_echec(): void
    {
        $this->simulate();
        $this->actingAs(User::factory()->create());

        $reference = $this->pay($this->game(), '0707000002')->json('reference');

        $this->postJson(route('checkout.mm.otp', $reference), ['otp' => '999999'])
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('start_failed', false)
            ->assertJsonPath('not_debited', true)
            ->assertJsonPath('message', 'Code de paiement invalide.');

        $this->assertNull(Payment::where('transaction_id', $reference)->value('credited_at'));
    }

    public function test_simulation_0003_succes_apres_15_secondes(): void
    {
        $this->simulate();
        $this->actingAs(User::factory()->create());

        $reference = $this->pay($this->game(), '0707000003')->assertJsonPath('status', 'pending')->json('reference');

        $this->travel(12)->seconds();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'pending');

        $this->travel(9)->seconds();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'success');

        $this->assertNotNull(Payment::where('transaction_id', $reference)->value('credited_at'));
        $this->assertSame([], $this->calls);
    }

    public function test_simulation_0004_expiration_a_180_secondes(): void
    {
        $this->simulate();
        $this->actingAs(User::factory()->create());

        $reference = $this->pay($this->game(), '0707000004')->json('reference');

        $this->travel(170)->seconds();
        $this->getJson(route('checkout.mm.status', $reference))->assertJsonPath('status', 'pending');

        $this->travel(11)->seconds();
        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'expired')
            ->assertJsonPath('not_debited', false)
            ->assertJsonPath('start_failed', false);
    }

    public function test_simulation_0005_echec_immediat(): void
    {
        $this->simulate();

        $this->actingAs(User::factory()->create())->pay($this->game(), '0707000005')
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('not_debited', true)
            ->assertJsonPath('start_failed', false)
            ->assertJsonPath('message', 'Solde insuffisant.');
    }

    public function test_page_signale_le_mode_simulation(): void
    {
        $this->simulate();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $this->game()->id]))
            ->assertOk()
            ->assertSee('Mode simulation');
    }

    // ─── Réponses réelles Paystack + affichage ─────────────────────────────

    public function test_vraie_reponse_wave_unprocessed_transaction(): void
    {
        config(['checkout.wave_enabled' => true]);
        $this->chargeReply = [400, [
            'status' => false, 'message' => 'Charge attempted',
            'data' => ['status' => 'failed', 'message' => 'Error occured while processing your request!'],
            'type' => 'api_error', 'code' => 'unprocessed_transaction',
        ]];

        $res = $this->actingAs(User::factory()->create())->pay($this->game(), '0707123456', 'wave')
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('start_failed', true)
            ->assertJsonPath('not_debited', false)
            ->assertJsonPath('message', 'Wave est momentanément indisponible, choisis Orange Money ou MTN MoMo.');

        $this->assertStringNotContainsString('Charge attempted', $res->getContent());
    }

    public function test_erreur_api_autre_operateur_paiement_non_demarre(): void
    {
        $this->chargeReply = [400, ['status' => false, 'message' => 'Charge attempted', 'type' => 'api_error']];

        $this->actingAs(User::factory()->create())->pay($this->game(), '0505123456', 'mtn')
            ->assertJsonPath('start_failed', true)
            ->assertJsonPath('not_debited', false)
            ->assertJsonPath('message', "Le paiement n'a pas pu démarrer. Réessaie ou choisis un autre opérateur.");
    }

    public function test_vraie_reponse_orange_send_otp_sans_message_de_premier_niveau(): void
    {
        $this->chargeReply = [200, ['status' => true, 'message' => 'Charge attempted', 'data' => [
            'reference' => 'x', 'status' => 'send_otp',
            'display_text' => 'Please complete authorization process on your mobile application',
        ]]];

        $res = $this->actingAs(User::factory()->create())->pay($this->game(), '0707123456')
            ->assertJsonPath('status', 'send_otp')
            ->assertJsonPath('message', null)
            ->assertJsonPath('action_url', null)
            ->assertJsonPath('qr_code', null);

        $this->assertStringNotContainsString('Charge attempted', $res->getContent());
    }

    // ─── Wave ───────────────────────────────────────────────────────────────

    public function test_wave_desactive_affiche_en_premier_avec_mention_page_paystack(): void
    {
        $product = $this->game();
        $this->actingAs(User::factory()->create());

        $html = $this->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))
            ->assertOk()
            ->assertSee('data-classic="1"', false)
            ->assertSee('Tu vas être redirigé vers la page sécurisée Paystack pour payer avec Wave')
            ->assertSee('action="' . route('checkout.mm.classic', 'marketplace') . '"', false)
            ->getContent();

        // Wave en premier
        $this->assertLessThan(strpos($html, 'data-operator="orange"'), strpos($html, 'data-operator="wave"'));

        // Pas de Charge API pour Wave tant qu'il n'est pas activé
        $this->pay($product, '0707123456', 'wave')->assertStatus(422)->assertJsonValidationErrors('operator');
        $this->assertNotContains('POST /charge', $this->calls);
    }

    public function test_wave_desactive_flux_classique_limite_au_mobile_money_puis_retour_produit_debloque(): void
    {
        $product = $this->game();
        $this->actingAs(User::factory()->create());

        $this->post(route('checkout.mm.classic', 'marketplace'), ['product' => $product->id, 'operator' => 'wave'])
            ->assertRedirect('https://checkout.paystack.com/old');

        $init = $this->bodies['POST /transaction/initialize'];
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame(100000, $init['amount']);
        $this->assertStringStartsWith('MP-', $init['reference']);
        $this->assertSame('pending', Payment::where('transaction_id', $init['reference'])->value('status'));

        // Retour Paystack (callback habituel) : page du produit débloqué
        $this->verifyData = ['reference' => $init['reference'], 'status' => 'success', 'amount' => 100000, 'currency' => 'XOF'];
        $this->get(route('paystack.marketplace.callback', ['reference' => $init['reference']]))
            ->assertRedirect(route('marketplace.show', $product->slug))
            ->assertSessionHas('success');

        $this->assertSame('paid', DB::table('marketplace_purchases')->where('provider_ref', $init['reference'])->value('status'));
    }

    public function test_autre_moyen_de_paiement_sans_restriction_de_canal(): void
    {
        $product = $this->game();
        $this->actingAs(User::factory()->create());

        $this->post(route('paystack.marketplace.buy', $product), ['classic' => 1])
            ->assertRedirect('https://checkout.paystack.com/old');

        $this->assertArrayNotHasKey('channels', $this->bodies['POST /transaction/initialize']);
    }

    public function test_wave_active_passe_par_le_charge_api(): void
    {
        config(['checkout.wave_enabled' => true]);
        $product = $this->game();
        $this->actingAs(User::factory()->create());

        $this->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))
            ->assertOk()
            ->assertSee('data-operator="wave"', false)
            ->assertDontSee('data-classic="1"', false);

        $this->pay($product, '0707123456', 'wave')->assertOk()->assertJsonPath('status', 'pending');

        $this->assertSame('wave', $this->bodies['POST /charge']['mobile_money']['provider']);
        $this->assertNotContains('POST /transaction/initialize', $this->calls);
    }

    public function test_aide_code_orange_mentionne_144_82(): void
    {
        $this->simulate();

        $help = $this->actingAs(User::factory()->create())->pay($this->game(), '0707000002')->json('otp_help');

        $this->assertStringContainsString('#144*82#', $help);
        $this->assertStringNotContainsString('#144#', $help);
    }

    public function test_pas_de_bouton_ni_image_vides(): void
    {
        $product = $this->game();
        $this->actingAs(User::factory()->create());

        // URL et QR invalides ou vides → null (le front masque bouton et image)
        $this->chargeReply = [200, ['status' => true, 'data' => [
            'status' => 'pay_offline', 'url' => 'pas-une-url', 'qr_code' => 'texte-brut',
        ]]];
        $this->pay($product, '0707123456')
            ->assertJsonPath('action_url', null)
            ->assertJsonPath('qr_code', null);

        // Valides → transmis
        MobileMoneyCheckout::query()->update(['status' => 'replaced']);
        $this->chargeReply = [200, ['status' => true, 'data' => [
            'status' => 'pay_offline', 'url' => 'https://pay.wave.com/c/abc', 'qr_code' => 'data:image/png;base64,iVBORw0KGgo=',
        ]]];
        $this->pay($product, '0505123456', 'mtn')
            ->assertJsonPath('action_url', 'https://pay.wave.com/c/abc')
            ->assertJsonPath('qr_code', 'data:image/png;base64,iVBORw0KGgo=');

        // La page force [hidden] (sinon bouton .btn et <img> restent visibles vides)
        $this->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $product->id]))
            ->assertSee('[hidden] { display: none !important; }', false);
    }

    public function test_texte_inconnu_garde_l_original_en_petit(): void
    {
        $this->chargeReply = [200, ['status' => true, 'data' => [
            'status' => 'pay_offline', 'display_text' => 'Dial *123# to approve',
        ]]];

        $this->actingAs(User::factory()->create())->pay($this->game(), '0707123456')
            ->assertJsonPath('display_text', null)
            ->assertJsonPath('display_text_raw', 'Dial *123# to approve');
    }
}
