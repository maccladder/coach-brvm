<?php

namespace Tests\Feature;

use App\Models\AdminGrant;
use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\AkwabaService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AkwabaTest extends TestCase
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

        // Créé par la migration 2026_11_03_000003
        $this->product = MarketplaceProduct::where('slug', 'akwaba')->firstOrFail();
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

    private function paystackHas(string $reference, int $amountXof, array $metadata, string $status = 'success', string $currency = 'XOF'): void
    {
        $this->paystack->transactions[$reference] = [
            'status'   => $status,
            'amount'   => $amountXof * 100,
            'currency' => $currency,
            'channel'  => 'mobile_money',
            'metadata' => $metadata,
        ];
    }

    private function hintsMetadata(?User $user = null): array
    {
        return ['user_id' => ($user ?? $this->player)->id, 'game' => 'akwaba', 'product' => 'hints5'];
    }

    private function postWebhook(string $reference, int $amountXof, ?string $signature = null)
    {
        $json = json_encode([
            'event' => 'charge.success',
            'data'  => ['reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100, 'currency' => 'XOF'],
        ]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    private function balance(?User $user = null): int
    {
        return (int) GameCredit::where('user_id', ($user ?? $this->player)->id)
            ->where('game_slug', 'akwaba')->where('kind', 'hints')->value('balance');
    }

    private function giveHints(int $count, ?User $user = null): void
    {
        app(AkwabaService::class)->addHints($user ?? $this->player, $count, 'admin', null, null, 'test');
    }

    private function purchase(string $status = 'paid', ?User $user = null): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id'    => ($user ?? $this->player)->id,
            'product_id' => $this->product->id,
            'amount'     => 1000,
            'provider'   => 'paystack',
            'status'     => $status,
            'paid_at'    => $status === 'paid' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function state(?User $user = null)
    {
        return $user ? $this->actingAs($user)->getJson(route('akwaba.state')) : $this->getJson(route('akwaba.state'));
    }

    // ─── Fichier du jeu ─────────────────────────────────────────────────────

    public function test_le_fichier_du_jeu_est_servi_tel_quel(): void
    {
        $response = $this->get(route('akwaba.file'));

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('text/html', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
        $this->assertSame(filesize(resource_path('games/akwaba.html')), $response->baseResponse->getFile()->getSize());
    }

    // ─── State ──────────────────────────────────────────────────────────────

    public function test_state_invite(): void
    {
        $this->state()->assertOk()->assertExactJson([
            'logged'   => false,
            'unlocked' => false,
            'hints'    => 0,
            'progress' => ['save' => null, 'fins' => []],
        ]);
    }

    public function test_state_connecte_sans_achat(): void
    {
        $this->state($this->player)->assertOk()->assertExactJson([
            'logged'   => true,
            'unlocked' => false,
            'hints'    => 0,
            'progress' => ['save' => null, 'fins' => []],
        ]);
    }

    public function test_state_connecte_avec_achat_indices_et_progression(): void
    {
        $this->purchase();
        $this->giveHints(4);
        GameProgress::create([
            'user_id'    => $this->player->id,
            'product_id' => $this->product->id,
            'data'       => ['save' => '5', 'fins' => ['oubli'], 'max' => '6'],
        ]);

        $this->state($this->player)->assertOk()->assertExactJson([
            'logged'   => true,
            'unlocked' => true,
            'hints'    => 4,
            'progress' => ['save' => '5', 'fins' => ['oubli']],
        ]);
    }

    public function test_state_achat_en_attente_ne_debloque_pas(): void
    {
        $this->purchase('pending');

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_state_attribution_admin_debloque_et_revocation_rebloque(): void
    {
        $grant = AdminGrant::create([
            'user_id'        => $this->player->id,
            'grantable_type' => MarketplaceProduct::class,
            'grantable_id'   => $this->product->id,
            'reason'         => 'Paiement WhatsApp',
            'granted_at'     => now(),
        ]);

        $this->state($this->player)->assertJsonPath('unlocked', true);

        $grant->update(['revoked_at' => now()]);

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_state_ne_montre_que_les_donnees_du_joueur_connecte(): void
    {
        $other = User::factory()->create();
        $this->purchase('paid', $other);
        $this->giveHints(9, $other);

        $this->state($this->player)->assertJsonPath('unlocked', false)->assertJsonPath('hints', 0);
    }

    // ─── Progression ────────────────────────────────────────────────────────

    public function test_progress_exige_une_connexion(): void
    {
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => []])->assertUnauthorized();
    }

    public function test_progress_valide_strictement_les_entrees(): void
    {
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '8', 'fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => 'X', 'fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => ['autre']])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => 'oubli'])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => ['1'], 'fins' => []])->assertStatus(422);

        $this->assertSame(0, GameProgress::count());
    }

    public function test_progress_sans_deblocage_limite_aux_chapitres_gratuits(): void
    {
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => []])->assertOk();
        $this->postJson(route('akwaba.progress'), ['save' => '3', 'fins' => []])->assertForbidden();
        $this->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => ['oubli']])->assertForbidden();

        $this->assertSame(['save' => '2', 'fins' => []], app(AkwabaService::class)->progress($this->player));
    }

    public function test_progress_fusionne_les_fins_sans_jamais_en_retirer(): void
    {
        $this->purchase();
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '7', 'fins' => ['oubli']])
            ->assertOk()->assertJsonPath('progress.fins', ['oubli']);

        // Nouvelle partie sur un autre téléphone : retour au prologue, fins locales vides
        $this->postJson(route('akwaba.progress'), ['save' => 'P', 'fins' => []])
            ->assertOk()->assertJsonPath('progress', ['save' => 'P', 'fins' => ['oubli']]);

        $this->postJson(route('akwaba.progress'), ['save' => null, 'fins' => ['gardien', 'oubli']])
            ->assertOk()->assertJsonPath('progress', ['save' => null, 'fins' => ['oubli', 'gardien']]);

        $data = GameProgress::where('user_id', $this->player->id)->where('product_id', $this->product->id)->value('data');
        $data = is_string($data) ? json_decode($data, true) : $data;
        $this->assertSame('7', $data['max']); // chapitre le plus loin, pour les stats
        $this->assertSame(1, GameProgress::count());
    }

    public function test_progress_d_un_joueur_n_affecte_pas_un_autre(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->player)->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => []])->assertOk();
        $this->actingAs($other)->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => []])->assertOk();

        $this->assertSame('2', app(AkwabaService::class)->progress($this->player)['save']);
        $this->assertSame('1', app(AkwabaService::class)->progress($other)['save']);
    }

    // ─── Indices ────────────────────────────────────────────────────────────

    public function test_hint_use_exige_une_connexion(): void
    {
        $this->postJson(route('akwaba.hint.use'), ['id' => 'n1'])->assertUnauthorized();
    }

    public function test_hint_use_avec_solde_zero_refuse(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'n1'])
            ->assertOk()->assertExactJson(['id' => 'n1', 'ok' => false, 'hints' => 0]);

        $this->assertSame(0, GameCreditLedger::where('reason', 'use')->count());
    }

    public function test_hint_use_consomme_un_indice_et_renvoie_le_nonce(): void
    {
        $this->giveHints(5);

        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'abc-123'])
            ->assertOk()->assertExactJson(['id' => 'abc-123', 'ok' => true, 'hints' => 4]);

        $this->assertSame(1, GameCreditLedger::where('user_id', $this->player->id)->where('reason', 'use')->where('delta', -1)->count());
    }

    public function test_hint_use_en_rafale_ne_passe_jamais_sous_zero(): void
    {
        $this->giveHints(3);
        $this->actingAs($this->player);

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->postJson(route('akwaba.hint.use'), ['id' => "n$i"])->json('ok');
        }

        $this->assertSame(3, count(array_filter($results)));
        $this->assertSame(0, $this->balance());
        $this->assertSame(3, GameCreditLedger::where('reason', 'use')->count());
    }

    public function test_le_decrement_est_conditionnel_au_niveau_sql(): void
    {
        // Simule deux requêtes qui ont lu « solde = 1 » en même temps : la seconde
        // mise à jour conditionnelle ne doit rien modifier.
        $this->giveHints(1);
        $service = app(AkwabaService::class);

        $this->assertTrue($service->useHint($this->player)['ok']);
        $this->assertFalse($service->useHint($this->player)['ok']);
        $this->assertSame(0, $this->balance());
    }

    public function test_hint_use_valide_le_nonce(): void
    {
        $this->giveHints(1);
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.hint.use'), [])->assertStatus(422);
        $this->postJson(route('akwaba.hint.use'), ['id' => str_repeat('x', 65)])->assertStatus(422);

        $this->assertSame(1, $this->balance());
    }

    // ─── Achat ──────────────────────────────────────────────────────────────

    public function test_buy_exige_une_connexion_et_un_produit_connu(): void
    {
        $this->postJson(route('akwaba.buy'), ['product' => 'full'])->assertUnauthorized();
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'vip'])->assertStatus(422);

        $this->assertSame([], $this->paystack->initialized);
    }

    public function test_buy_full_deja_possede_refuse(): void
    {
        $this->purchase();

        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])
            ->assertStatus(409)->assertJsonPath('already', true);

        $this->assertSame([], $this->paystack->initialized);
    }

    public function test_buy_full_attribue_par_l_admin_refuse(): void
    {
        AdminGrant::create([
            'user_id' => $this->player->id, 'grantable_type' => MarketplaceProduct::class,
            'grantable_id' => $this->product->id, 'granted_at' => now(),
        ]);

        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertStatus(409);
    }

    public function test_buy_full_initialise_un_achat_marketplace_au_prix_serveur(): void
    {
        $url = $this->actingAs($this->player)
            ->postJson(route('akwaba.buy'), ['product' => 'full', 'amount' => 1])
            ->assertOk()->json('url');

        $init = $this->paystack->initialized[0];
        $this->assertSame('https://checkout.paystack.com/' . $init['reference'], $url);
        $this->assertMatchesRegularExpression('/^MP-\d{14}-[A-Z0-9]{6}$/', $init['reference']);
        $this->assertSame(1000, $init['amount']);
        $this->assertSame('XOF', $init['currency']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame(route('akwaba.payment.return'), $init['callback_url']);
        $this->assertSame($this->player->id, $init['metadata']['user_id']);
        $this->assertSame('akwaba', $init['metadata']['game']);
        $this->assertSame('full', $init['metadata']['product']);

        $payment = Payment::where('transaction_id', $init['reference'])->firstOrFail();
        $this->assertSame(1000, (int) $payment->amount_paid);
        $this->assertSame('marketplace', $payment->purpose);
        $this->assertSame($this->product->id, (int) $payment->meta['product_id']);
        $this->assertSame('pending', DB::table('marketplace_purchases')->where('user_id', $this->player->id)->value('status'));
    }

    public function test_buy_hints5_initialise_500_xof(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'hints5', 'amount' => 5])->assertOk();

        $init = $this->paystack->initialized[0];
        $this->assertStringStartsWith('AKW-H-' . $this->player->id . '-', $init['reference']);
        $this->assertSame(500, $init['amount']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame($this->hintsMetadata(), $init['metadata']);
        $this->assertSame(0, Payment::count());
    }

    // ─── Webhook et retour : déblocage complet ──────────────────────────────

    public function test_webhook_full_debloque_l_histoire(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];

        $this->postWebhook($reference, 1000)->assertOk();

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    public function test_webhook_full_montant_falsifie_ne_debloque_pas(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];

        $this->postWebhook($reference, 100)->assertOk();

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_retour_full_debloque_puis_redirige_vers_le_jeu(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];
        $this->paystackHas($reference, 1000, ['user_id' => $this->player->id, 'product_id' => $this->product->id]);

        $this->get(route('akwaba.payment.return', ['reference' => $reference]))->assertRedirect('/jeux/akwaba?paid=1');

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    // ─── Webhook et retour : packs d'indices ────────────────────────────────

    public function test_webhook_hints_valide_credite_5(): void
    {
        $this->paystackHas('AKW-H-1-OK', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-OK', 500)->assertOk();

        $this->assertSame(5, $this->balance());
        $this->assertDatabaseHas('game_credit_ledger', [
            'user_id' => $this->player->id, 'delta' => 5, 'reason' => 'purchase',
            'payment_reference' => 'AKW-H-1-OK', 'amount' => 500,
        ]);
    }

    public function test_webhook_hints_rejoue_ne_credite_qu_une_fois(): void
    {
        $this->paystackHas('AKW-H-1-REPLAY', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-REPLAY', 500)->assertOk();
        $this->postWebhook('AKW-H-1-REPLAY', 500)->assertOk();
        $this->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-REPLAY']))->assertRedirect('/jeux/akwaba?paid=1');

        $this->assertSame(5, $this->balance());
        $this->assertSame(1, GameCreditLedger::where('payment_reference', 'AKW-H-1-REPLAY')->count());
    }

    public function test_webhook_hints_signature_invalide_refuse(): void
    {
        $this->paystackHas('AKW-H-1-SIG', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-SIG', 500, 'bad-signature')->assertStatus(401);

        $this->assertSame(0, $this->balance());
    }

    public function test_webhook_hints_se_fie_a_paystack_et_pas_au_corps_recu(): void
    {
        // Corps du webhook : 500 F ; transaction réelle chez Paystack : 100 F
        $this->paystackHas('AKW-H-1-CHEAP', 100, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-CHEAP', 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    public function test_webhook_hints_refuse_devise_statut_ou_metadonnees_invalides(): void
    {
        $this->paystackHas('AKW-H-1-USD', 500, $this->hintsMetadata(), 'success', 'USD');
        $this->paystackHas('AKW-H-1-FAIL', 500, $this->hintsMetadata(), 'failed');
        $this->paystackHas('AKW-H-1-GAME', 500, ['game' => 'autre'] + $this->hintsMetadata());
        $this->paystackHas('AKW-H-1-PROD', 500, ['product' => 'full'] + $this->hintsMetadata());
        $this->paystackHas('AKW-H-1-USER', 500, ['user_id' => 999999] + $this->hintsMetadata());

        foreach (['AKW-H-1-USD', 'AKW-H-1-FAIL', 'AKW-H-1-GAME', 'AKW-H-1-PROD', 'AKW-H-1-USER', 'AKW-H-1-UNKNOWN'] as $reference) {
            $this->postWebhook($reference, 500)->assertOk();
        }

        $this->assertSame(0, GameCreditLedger::count());
        $this->assertSame(0, GameCredit::sum('balance'));
    }

    public function test_retour_hints_credite_le_joueur_des_metadonnees(): void
    {
        $this->paystackHas('AKW-H-1-RET', 500, $this->hintsMetadata());

        // Même appelé par quelqu'un d'autre, le crédit va au joueur qui a payé
        $intruder = User::factory()->create();
        $this->actingAs($intruder)->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-RET']))
            ->assertRedirect('/jeux/akwaba?paid=1');

        $this->assertSame(5, $this->balance());
        $this->assertSame(0, $this->balance($intruder));
    }

    public function test_parcours_complet_achat_puis_utilisation(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'hints5'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];
        $this->paystackHas($reference, 500, $this->paystack->initialized[0]['metadata']);

        $this->postWebhook($reference, 500)->assertOk();
        $this->state($this->player)->assertJsonPath('hints', 5);

        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'x'])->assertJsonPath('hints', 4);
        $this->state($this->player)->assertJsonPath('hints', 4);
    }
}
