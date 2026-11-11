<?php

namespace Tests\Feature;

use App\Http\Controllers\PacteController;
use App\Models\AdminGrant;
use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\PacteService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le Pacte avec les caïmans : pont PacteHost (état, sauvegarde, indices, fins), achats
 * Paystack (page hébergée, webhook, retour) et injection du pont dans le jeu servi.
 */
class PacteApiTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    private MarketplaceProduct $product;
    private User $player;
    private object $paystack;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret' => self::SECRET]);
        Mail::fake();

        $this->product = MarketplaceProduct::where('slug', 'le-pacte')->firstOrFail();
        $this->player  = User::factory()->create();

        // Faux Paystack : transactions renvoyées par verify(), appels à initialize() enregistrés
        $this->paystack = new class extends PaystackService {
            public array $transactions = [];
            public array $initialized = [];

            public function __construct() {}

            public function initialize(array $data): ?string
            {
                $this->initialized[] = $data;
                return 'https://checkout.paystack.com/' . $data['reference'];
            }

            public function verify(string $reference): ?array
            {
                return isset($this->transactions[$reference])
                    ? ['reference' => $reference] + $this->transactions[$reference]
                    : null;
            }
        };
        $this->app->instance(PaystackService::class, $this->paystack);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function purchase(?User $user = null): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id' => ($user ?? $this->player)->id, 'product_id' => $this->product->id, 'amount' => 1000,
            'provider' => 'paystack', 'status' => 'paid', 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function state(?User $user = null)
    {
        return $user ? $this->actingAs($user)->getJson(route('pacte.state')) : $this->getJson(route('pacte.state'));
    }

    /** Envoie le JSON tel quel (comme fetch), sans passer par la normalisation des tableaux de test. */
    private function postRaw(string $route, string $json)
    {
        return $this->actingAs($this->player)->call('POST', route($route), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json',
        ], $json);
    }

    private function etat(array $extra = []): array
    {
        return array_merge(['inventaire' => ['carnet', 'billet'], 'poche' => 2000, 'choix' => [], 'jauges' => ['mefiance' => 1, 'zie' => 0, 'soif' => 0]], $extra);
    }

    private function balance(?User $user = null): int
    {
        return (int) GameCredit::where('user_id', ($user ?? $this->player)->id)
            ->where('game_slug', 'le-pacte')->where('kind', 'hints')->value('balance');
    }

    private function paystackHas(string $reference, int $amountXof, array $metadata, string $status = 'success', string $currency = 'XOF'): void
    {
        $this->paystack->transactions[$reference] = [
            'status' => $status, 'amount' => $amountXof * 100, 'currency' => $currency,
            'channel' => 'mobile_money', 'metadata' => $metadata,
        ];
    }

    private function hintsMetadata(): array
    {
        return ['user_id' => $this->player->id, 'game' => 'le-pacte', 'product' => 'hints5'];
    }

    private function postWebhook(string $reference, int $amountXof)
    {
        $json = json_encode(['event' => 'charge.success', 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100, 'currency' => 'XOF',
        ]]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    // ─── État ───────────────────────────────────────────────────────────────

    public function test_state_invite(): void
    {
        $this->state()->assertOk()->assertExactJson([
            'logged' => false, 'unlocked' => false, 'hints' => 0, 'freeUsed' => false, 'save' => null,
        ]);
    }

    public function test_state_connecte_avec_l_indice_gratuit(): void
    {
        $this->state($this->player)->assertOk()->assertExactJson([
            'logged' => true, 'unlocked' => false, 'hints' => 1, 'freeUsed' => false, 'save' => null,
        ]);
    }

    public function test_state_debloque_par_achat_ou_attribution_admin(): void
    {
        $this->purchase();
        $this->state($this->player)->assertJsonPath('unlocked', true);

        $other = User::factory()->create();
        AdminGrant::create(['user_id' => $other->id, 'grantable_type' => MarketplaceProduct::class, 'grantable_id' => $this->product->id, 'granted_at' => now()]);
        $this->state($other)->assertJsonPath('unlocked', true);
    }

    public function test_les_routes_du_pont_demandent_la_connexion(): void
    {
        foreach (['pacte.progress', 'pacte.fin', 'pacte.hint.use', 'pacte.hint.free', 'pacte.buy'] as $route) {
            $this->postJson(route($route), [])->assertUnauthorized();
        }
    }

    // ─── Sauvegarde ─────────────────────────────────────────────────────────

    public function test_sauvegarde_gratuite_puis_reprise_depuis_un_autre_navigateur(): void
    {
        $etat = $this->etat();
        $this->postRaw('pacte.progress', json_encode(['save' => ['point' => 'villa', 'etat' => $etat]]))->assertOk();

        // Autre navigateur : même compte, nouvelle session
        $this->flushSession();
        $this->state($this->player)->assertJsonPath('save.point', 'villa')->assertJsonPath('save.etat', $etat);
    }

    public function test_l_etat_est_stocke_tel_quel(): void
    {
        // Objets vides, espaces et textes vides : rien ne doit être transformé
        $json = '{"save":{"point":"gare","etat":{"echecs":{},"inventaire":[],"objectif":"  Réunir 4 500 FCFA  ","note":"","poche":1.5,"jauges":{"mefiance":0}}}}';
        $this->postRaw('pacte.progress', $json)->assertOk();

        // Relu en objets : {} reste un objet, [] un tableau
        $etat = json_decode($this->state($this->player)->getContent())->save->etat;
        $this->assertSame(
            '{"echecs":{},"inventaire":[],"objectif":"  Réunir 4 500 FCFA  ","note":"","poche":1.5,"jauges":{"mefiance":0}}',
            json_encode($etat, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)
        );
    }

    public function test_sauvegarde_null_efface_la_partie(): void
    {
        $this->postRaw('pacte.progress', json_encode(['save' => ['point' => 'gare', 'etat' => $this->etat()]]))->assertOk();
        $this->postRaw('pacte.progress', '{"save":null}')->assertOk();

        $this->state($this->player)->assertJsonPath('save', null);
        $this->assertSame('gare', GameProgress::where('user_id', $this->player->id)->value('data')['max']);
    }

    public function test_le_point_fin_gratuite_est_accepte_sans_deblocage(): void
    {
        $this->postRaw('pacte.progress', json_encode(['save' => ['point' => 'finGratuite', 'etat' => $this->etat()]]))->assertOk();

        $this->state($this->player)->assertJsonPath('save.point', 'finGratuite');
    }

    public function test_la_suite_payante_est_refusee_sans_deblocage(): void
    {
        foreach (['chap3', 'chap4', 'chap7'] as $point) {
            $this->postRaw('pacte.progress', json_encode(['save' => ['point' => $point, 'etat' => $this->etat()]]))->assertForbidden();
        }
        $this->state($this->player)->assertJsonPath('save', null);

        $this->purchase();
        $this->postRaw('pacte.progress', json_encode(['save' => ['point' => 'chap5', 'etat' => $this->etat()]]))->assertOk();
        $this->postRaw('pacte.progress', json_encode(['save' => ['point' => 'chap3', 'etat' => $this->etat()]]))->assertOk();

        $data = GameProgress::where('user_id', $this->player->id)->value('data');
        $this->assertSame('chap3', $data['save']['point']);
        $this->assertSame('chap5', $data['max']); // point le plus loin atteint
    }

    public function test_sauvegarde_invalide_refusee(): void
    {
        $this->postRaw('pacte.progress', '{}')->assertStatus(422);
        $this->postRaw('pacte.progress', '{"save":{"point":"chap9","etat":{}}}')->assertStatus(422);
        $this->postRaw('pacte.progress', '{"save":{"point":"gare"}}')->assertStatus(422);
        $this->postRaw('pacte.progress', '{"save":{"point":"gare","etat":[1,2]}}')->assertStatus(422);
        $this->postRaw('pacte.progress', '{"save":"gare"}')->assertStatus(422);

        $trop = json_encode(['save' => ['point' => 'gare', 'etat' => ['x' => str_repeat('a', PacteService::MAX_ETAT_BYTES)]]]);
        $this->postRaw('pacte.progress', $trop)->assertStatus(422);
    }

    // ─── Fins ───────────────────────────────────────────────────────────────

    public function test_fin_enregistree_avec_les_choix(): void
    {
        $this->purchase();
        $choix = [['chap' => 'Chapitre 1', 'qui' => 'Tantie Adjoua', 'resume' => 'Tu as montré le testament à Tantie.', 'note' => "Tantie Adjoua s'en souviendra"]];

        $this->postRaw('pacte.fin', json_encode(['cle' => 'justice', 'choix' => $choix]))->assertOk();
        $this->postRaw('pacte.fin', json_encode(['cle' => 'gardien', 'choix' => []]))->assertOk();
        $this->postRaw('pacte.fin', json_encode(['cle' => 'justice', 'choix' => $choix]))->assertOk();

        $data = GameProgress::where('user_id', $this->player->id)->value('data');
        $this->assertSame(['justice', 'gardien'], $data['fins']);
        $this->assertSame($choix, $data['fins_detail']['justice']['choix']);
    }

    public function test_fin_refusee_sans_deblocage_ou_invalide(): void
    {
        $this->postRaw('pacte.fin', json_encode(['cle' => 'pacte', 'choix' => []]))->assertForbidden();

        $this->purchase();
        $this->postRaw('pacte.fin', json_encode(['cle' => 'inconnue', 'choix' => []]))->assertStatus(422);
        $this->postRaw('pacte.fin', json_encode(['cle' => 'pacte', 'choix' => 'texte']))->assertStatus(422);
    }

    // ─── Indices ────────────────────────────────────────────────────────────

    public function test_l_indice_gratuit_est_consomme_en_premier_puis_le_solde(): void
    {
        app(PacteService::class)->addHints($this->player, 2, 'admin', null, null, 'test');
        $this->state($this->player)->assertJsonPath('hints', 3);

        $this->actingAs($this->player)->postJson(route('pacte.hint.use'))->assertOk()->assertExactJson(['ok' => true, 'hints' => 2]);
        $this->assertSame(2, $this->balance()); // le gratuit, pas le solde payé
        $this->state($this->player)->assertJsonPath('freeUsed', true)->assertJsonPath('hints', 2);

        $this->actingAs($this->player)->postJson(route('pacte.hint.use'))->assertExactJson(['ok' => true, 'hints' => 1]);
        $this->actingAs($this->player)->postJson(route('pacte.hint.use'))->assertExactJson(['ok' => true, 'hints' => 0]);
        $this->actingAs($this->player)->postJson(route('pacte.hint.use'))->assertExactJson(['ok' => false, 'hints' => 0]);
        $this->assertSame(0, $this->balance());
        $this->assertSame(2, GameCreditLedger::where('user_id', $this->player->id)->where('reason', 'use')->count());
    }

    public function test_l_indice_gratuit_deja_utilise_en_invite_n_est_pas_rendu(): void
    {
        $this->actingAs($this->player)->postJson(route('pacte.hint.free'))->assertOk();
        $this->actingAs($this->player)->postJson(route('pacte.hint.free'))->assertOk(); // idempotent

        $this->state($this->player)->assertJsonPath('freeUsed', true)->assertJsonPath('hints', 0);
        $this->actingAs($this->player)->postJson(route('pacte.hint.use'))->assertExactJson(['ok' => false, 'hints' => 0]);
    }

    public function test_les_indices_du_pacte_et_d_akwaba_sont_separes(): void
    {
        app(\App\Services\AkwabaService::class)->addHints($this->player, 5, 'admin', null, null, 'test');

        $this->state($this->player)->assertJsonPath('hints', 1);
        $this->assertSame(0, $this->balance());
    }

    // ─── Achats (page Paystack hébergée) ────────────────────────────────────

    public function test_buy_full_initialise_un_achat_marketplace_au_prix_serveur(): void
    {
        $url = $this->actingAs($this->player)
            ->postJson(route('pacte.buy'), ['product' => 'full', 'amount' => 1])
            ->assertOk()->json('url');

        $init = $this->paystack->initialized[0];
        $this->assertSame('https://checkout.paystack.com/' . $init['reference'], $url);
        $this->assertMatchesRegularExpression('/^MP-\d{14}-[A-Z0-9]{6}$/', $init['reference']);
        $this->assertSame(1000, $init['amount']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame(route('pacte.payment.return'), $init['callback_url']);
        $this->assertSame(['le-pacte', 'full', $this->player->id], [$init['metadata']['game'], $init['metadata']['product'], $init['metadata']['user_id']]);

        $payment = Payment::where('transaction_id', $init['reference'])->firstOrFail();
        $this->assertSame('le-pacte', $payment->meta['product_slug']);
        $this->assertSame('pending', DB::table('marketplace_purchases')->where('user_id', $this->player->id)->value('status'));
    }

    public function test_buy_hints5_initialise_500_xof(): void
    {
        $this->actingAs($this->player)->postJson(route('pacte.buy'), ['product' => 'hints5'])->assertOk();

        $init = $this->paystack->initialized[0];
        $this->assertStringStartsWith('PCT-H-' . $this->player->id . '-', $init['reference']);
        $this->assertSame(500, $init['amount']);
        $this->assertSame($this->hintsMetadata(), $init['metadata']);
    }

    public function test_buy_full_deja_debloque_409_et_produit_inconnu_422(): void
    {
        $this->purchase();
        $this->actingAs($this->player)->postJson(route('pacte.buy'), ['product' => 'full'])->assertStatus(409)->assertJsonPath('already', true);
        $this->actingAs($this->player)->postJson(route('pacte.buy'), ['product' => 'vip'])->assertStatus(422);
        $this->assertSame([], $this->paystack->initialized);
    }

    public function test_webhook_full_debloque_la_suite(): void
    {
        $this->actingAs($this->player)->postJson(route('pacte.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];

        $this->postWebhook($reference, 1000)->assertOk();

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    public function test_retour_full_debloque_puis_renvoie_au_jeu(): void
    {
        $this->actingAs($this->player)->postJson(route('pacte.buy'), ['product' => 'full'])->assertOk();
        $init = $this->paystack->initialized[0];
        $this->paystackHas($init['reference'], 1000, $init['metadata']);

        $this->get(route('pacte.payment.return', ['reference' => $init['reference']]))->assertRedirect('/jeux/le-pacte?paid=1');

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    public function test_webhook_hints_credite_5_une_seule_fois(): void
    {
        $this->paystackHas('PCT-H-1-OK', 500, $this->hintsMetadata());

        $this->postWebhook('PCT-H-1-OK', 500)->assertOk();
        $this->postWebhook('PCT-H-1-OK', 500)->assertOk();
        $this->get(route('pacte.payment.return', ['reference' => 'PCT-H-1-OK']))->assertRedirect('/jeux/le-pacte?paid=1');

        $this->assertSame(5, $this->balance());
        $this->state($this->player)->assertJsonPath('hints', 6); // 5 + l'indice gratuit
        $this->assertSame(1, GameCreditLedger::where('payment_reference', 'PCT-H-1-OK')->count());
    }

    public function test_webhook_hints_refuse_montant_ou_jeu_falsifie(): void
    {
        $this->paystackHas('PCT-H-1-CHEAP', 499, $this->hintsMetadata());
        $this->paystackHas('PCT-H-1-AKW', 500, ['user_id' => $this->player->id, 'game' => 'akwaba', 'product' => 'hints5']);

        $this->postWebhook('PCT-H-1-CHEAP', 500)->assertOk();
        $this->postWebhook('PCT-H-1-AKW', 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    public function test_un_pack_d_akwaba_ne_credite_pas_le_pacte(): void
    {
        $this->paystackHas('AKW-H-1-X', 500, ['user_id' => $this->player->id, 'game' => 'akwaba', 'product' => 'hints5']);
        $this->postWebhook('AKW-H-1-X', 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    // ─── Page hôte et jeu servi ─────────────────────────────────────────────

    public function test_le_jeu_servi_commence_par_le_pont(): void
    {
        preg_match('/<iframe[^>]+src="([^"]+)"/', $this->get(route('pacte.show'))->getContent(), $m);
        $content = $this->get(html_entity_decode($m[1]))->assertOk()->getContent();

        $this->assertStringStartsWith(PacteController::HOST_BRIDGE, $content);
        $this->assertSame(PacteController::HOST_BRIDGE . file_get_contents(resource_path('games/le-pacte/index.html')), $content);
    }

    public function test_la_page_definit_le_pont_avant_l_iframe(): void
    {
        $guest = $this->get(route('pacte.show'))->getContent();
        $this->assertLessThan(strpos($guest, '<iframe'), strpos($guest, 'window.PacteHost = LOGGED'));
        $this->assertStringContainsString('const LOGGED     = false', $guest);

        $logged = $this->actingAs($this->player)->get(route('pacte.show'))->getContent();
        $this->assertStringContainsString('const LOGGED     = true', $logged);
        $this->assertStringContainsString(json_encode(route('pacte.progress', [], false)), $logged);
    }

    public function test_l_achat_marketplace_standard_renvoie_au_jeu(): void
    {
        config(['checkout.types.marketplace.enabled' => true]);

        $this->actingAs($this->player)
            ->get(route('checkout.mm.show', ['type' => 'marketplace', 'product' => $this->product->id]))
            ->assertRedirect(route('pacte.show'));
    }
}
