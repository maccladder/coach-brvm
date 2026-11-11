<?php

namespace Tests\Feature;

use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\PacteService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Admin du Pacte (/admin/le-pacte) : même page qu'Akwaba (StoryGameAdminController), avec les
 * points de sauvegarde, les fins et l'entonnoir du Pacte (table game_events).
 */
class PacteAdminTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceProduct $product;
    private User $player;
    private object $paystack;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->product = MarketplaceProduct::where('slug', 'le-pacte')->firstOrFail();
        $this->player  = User::factory()->create(['name' => "Konan N'Dri", 'email' => 'konan@example.com']);

        $this->paystack = new class extends PaystackService {
            public array $transactions = [];

            public function __construct() {}

            public function verify(string $reference): ?array
            {
                return isset($this->transactions[$reference]) ? ['reference' => $reference] + $this->transactions[$reference] : null;
            }
        };
        $this->app->instance(PaystackService::class, $this->paystack);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function asAdmin(): static
    {
        return $this->withSession(['is_admin' => true]);
    }

    private function balance(): int
    {
        return (int) GameCredit::where('user_id', $this->player->id)->where('game_slug', 'le-pacte')->value('balance');
    }

    private function paidPurchase(User $user, $paidAt = null): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id' => $user->id, 'product_id' => $this->product->id, 'amount' => 1000,
            'provider' => 'paystack', 'status' => 'paid', 'paid_at' => $paidAt ?? now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Progression telle que PacteService l'enregistre : save = {point, etat}, max, fins. */
    private function progress(User $user, string $max, array $fins = []): void
    {
        GameProgress::create([
            'user_id' => $user->id, 'product_id' => $this->product->id,
            'data' => ['save' => ['point' => $max, 'etat' => '{}'], 'max' => $max, 'fins' => $fins],
        ]);
    }

    /** Une session (lettre répétée) du Pacte passe par ces événements. */
    private function visit(string $letter, array $types, $at = null, ?string $browser = 'chrome'): void
    {
        foreach ($types as $type) {
            DB::table('game_events')->insert([
                'game_slug' => 'le-pacte', 'type' => $type, 'session_hash' => str_repeat($letter, 64),
                'browser' => $browser, 'created_at' => $at ?? now(),
            ]);
        }
    }

    private function steps(array $stats, string $period): array
    {
        return collect($stats['sessions'][$period]['steps'])->mapWithKeys(fn ($s) => [$s['key'] => [$s['count'], $s['pct']]])->all();
    }

    // ─── Accès et menu ──────────────────────────────────────────────────────

    public function test_tout_est_refuse_sans_code_admin(): void
    {
        $this->get(route('admin.pacte.index'))->assertRedirect(route('admin.login.form'));
        $this->post(route('admin.pacte.unlock', $this->player), ['reason' => 'x'])->assertRedirect(route('admin.login.form'));
        $this->post(route('admin.pacte.hints', $this->player), ['count' => 5, 'reason' => 'x'])->assertRedirect(route('admin.login.form'));
        $this->post(route('admin.pacte.recover'), ['reference' => 'PCT-H-X'])->assertRedirect(route('admin.login.form'));
    }

    public function test_le_menu_admin_a_le_pacte_juste_sous_akwaba(): void
    {
        $html = $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()->getContent();

        $akwaba = strpos($html, '🌙 Akwaba</a>');
        $pacte  = strpos($html, '🐊 Le Pacte</a>');
        $this->assertNotFalse($akwaba);
        $this->assertNotFalse($pacte);
        $this->assertGreaterThan($akwaba, $pacte);
        $this->assertSame('', trim(strip_tags(substr($html, $akwaba + strlen('🌙 Akwaba</a>'), $pacte - $akwaba - strlen('🌙 Akwaba</a>')))));
    }

    public function test_l_en_tete_et_les_sections(): void
    {
        $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()
            ->assertSee('🐊 Le Pacte avec les caïmans de Yamoussoukro')
            ->assertSee('Produit #' . $this->product->id)
            ->assertSee('Publié')
            ->assertSee(route('pacte.show'), false)
            ->assertSee('Joueurs connectés')
            ->assertSee('Arrivés au cadenas')
            ->assertSee('Entonnoir des visiteurs')
            ->assertSee('Par navigateur')
            ->assertSee("Rattraper un pack d'indices", false)
            ->assertSee('PCT-H-…')
            ->assertDontSee('AKW-H-')
            ->assertDontSee('Erreurs du jeu');   // le Pacte ne remonte pas ses erreurs JavaScript
    }

    // ─── Joueurs, entonnoir, fins ───────────────────────────────────────────

    public function test_la_liste_des_joueurs_avec_chapitre_et_fins_nommees(): void
    {
        $this->progress($this->player, 'chap7', ['pacte', 'sacrifice']);
        $this->paidPurchase($this->player);
        $other = User::factory()->create(['name' => 'Ama Gratuite']);
        $this->progress($other, 'finGratuite');
        User::factory()->create(['name' => 'Sans Pacte']);

        $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()
            ->assertSee("Konan N'Dri")
            ->assertSee('Ama Gratuite')
            ->assertDontSee('Sans Pacte')
            ->assertSee('Oui · payé')
            ->assertSee('Rompre le pacte, Le sacrifice')
            ->assertSee('Ch. 7')
            ->assertSee('Cadenas');

        $this->asAdmin()->get(route('admin.pacte.index', ['q' => 'konan@']))->assertSee("Konan N'Dri")->assertDontSee('Ama Gratuite');
    }

    public function test_l_entonnoir_des_joueurs_suit_les_points_de_sauvegarde(): void
    {
        foreach (['gare', 'villa', 'funerailles', 'finGratuite', 'chap3', 'chap5'] as $max) {
            $this->progress(User::factory()->create(), $max, $max === 'chap5' ? ['justice'] : []);
        }

        $stats = $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()->viewData('stats');
        $funnel = collect($stats['funnel'])->pluck('count', 'label');

        $this->assertSame(['Prologue', 'Chapitre 1', 'Chapitre 2', '🔒 Cadenas', 'Chapitre 3', 'Chapitre 4', 'Chapitre 5', 'Chapitre 6', 'Chapitre 7'], $funnel->keys()->all());
        $this->assertSame(6, $funnel['Prologue']);
        $this->assertSame(5, $funnel['Chapitre 1']);
        $this->assertSame(4, $funnel['Chapitre 2']);
        $this->assertSame(3, $funnel['🔒 Cadenas']);    // finGratuite, chap3, chap5
        $this->assertSame(2, $funnel['Chapitre 3']);
        $this->assertSame(1, $funnel['Chapitre 5']);
        $this->assertSame(0, $funnel['Chapitre 6']);

        $this->assertSame(['pacte' => 0, 'justice' => 1, 'argent' => 0, 'gardien' => 0, 'sacrifice' => 0], $stats['fins']);
    }

    public function test_les_fins_decouvertes_sont_nommees(): void
    {
        $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()
            ->assertSee('Rompre le pacte')
            ->assertSee('La justice')
            ->assertSee("Garder l'argent")
            ->assertSee('Le nouveau gardien')
            ->assertSee('Le sacrifice');
    }

    public function test_l_entonnoir_des_visiteurs_et_le_detail(): void
    {
        $this->visit('a', ['page_open', 'page_loaded', 'new_game', 'ch1_reached', 'ch2_reached', 'lock_reached', 'buy_click_full', 'free_hint', 'lang_en']);
        $this->visit('b', ['page_open', 'page_loaded', 'continue', 'paywall_open', 'shop_open', 'lang_fr'], null, 'facebook');
        $this->visit('c', ['page_open']);
        DB::table('akwaba_events')->insert(['type' => 'page_open', 'session_hash' => str_repeat('z', 64), 'browser' => 'chrome', 'created_at' => now()]);
        $this->paidPurchase(User::factory()->create());

        $response = $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()
            ->assertSee('passage en anglais')
            ->assertSee('passage en français');
        $stats = $response->viewData('stats');
        $steps = $this->steps($stats, 'all');

        $this->assertSame(3, $steps['page'][0]);            // les événements d'Akwaba ne comptent pas
        $this->assertSame([2, 66.7], $steps['loaded']);
        $this->assertSame([2, 100.0], $steps['start']);      // nouvelle partie ou Continuer
        $this->assertSame(1, $steps['ch1'][0]);
        $this->assertSame([2, 200.0], $steps['lock']);       // lock_reached (a) ou paywall_open (b)
        $this->assertSame(1, $steps['buy'][0]);
        $this->assertSame(1, $steps['paid'][0]);
        $this->assertSame(['continue' => 1, 'free_hint' => 1, 'shop_open' => 1, 'buy_click_full' => 1, 'buy_click_hints5' => 0, 'lang_fr' => 1, 'lang_en' => 1],
            $stats['sessions']['all']['details']);
        $this->assertSame(50.0, $stats['conversion_global']);   // 1 déblocage ÷ 2 sessions au cadenas

        $facebook = $this->asAdmin()->get(route('admin.pacte.index', ['navigateur' => 'facebook']))->viewData('stats');
        $this->assertSame(1, $this->steps($facebook, 'all')['page'][0]);

        $rows = collect($response->viewData('browsers'))->keyBy('browser');
        $this->assertSame([2, 1, 1], [$rows['chrome']['page'], $rows['chrome']['loaded'], $rows['chrome']['lock']]);   // c : page seule
        $this->assertSame([1, 1, 100.0], [$rows['facebook']['page'], $rows['facebook']['lock'], $rows['facebook']['lock_pct']]);
        $this->assertArrayNotHasKey('wait', $rows['chrome']);
    }

    public function test_ventes_et_chiffre_d_affaires(): void
    {
        $this->paidPurchase($this->player, now()->subDays(10));
        app(PacteService::class)->addHints($this->player, 5, 'purchase', 'PCT-H-1', 500);
        app(PacteService::class)->addHints($this->player, 5, 'admin', null, 500, 'WhatsApp');

        $stats = $this->asAdmin()->get(route('admin.pacte.index'))->assertOk()->viewData('stats');

        $this->assertSame(1, $stats['unlocks_paid']);
        $this->assertSame(1, $stats['packs_sold']);
        $this->assertSame(['paystack' => 1500, 'manual' => 500, 'total' => 2000], $stats['revenue']);
        $this->assertSame(['unlocks' => 0, 'packs' => 1, 'revenue' => ['paystack' => 500, 'manual' => 500, 'total' => 1000]], $stats['last7']);
        $this->assertSame(1, $stats['last30']['unlocks']);
    }

    // ─── Gestes manuels ─────────────────────────────────────────────────────

    public function test_gerer_un_joueur_debloquer_et_ajouter_des_indices(): void
    {
        $this->progress($this->player, 'funerailles');

        $this->asAdmin()->get(route('admin.pacte.index', ['user' => $this->player->id]))->assertOk()
            ->assertSee('Chapitre en cours / max')
            ->assertSee('Ch. 2')
            ->assertSee(route('admin.pacte.unlock', $this->player), false);

        $this->asAdmin()->post(route('admin.pacte.unlock', $this->player), ['reason' => 'paiement WhatsApp', 'amount' => 1000])
            ->assertSessionHas('success');
        $this->assertTrue(app(PacteService::class)->isUnlocked($this->player->fresh()));
        $this->assertDatabaseHas('admin_grants', ['user_id' => $this->player->id, 'grantable_id' => $this->product->id, 'amount_received' => 1000]);
        $this->assertStringStartsWith('Le Pacte : paiement WhatsApp', \App\Models\AdminGrant::where('user_id', $this->player->id)->value('reason'));

        $this->asAdmin()->post(route('admin.pacte.hints', $this->player), ['count' => 5, 'reason' => 'geste'])->assertSessionHas('success');
        $this->assertSame(5, $this->balance());
        $this->assertDatabaseHas('game_credit_ledger', ['user_id' => $this->player->id, 'game_slug' => 'le-pacte', 'reason' => 'admin', 'delta' => 5]);

        $this->asAdmin()->post(route('admin.pacte.lock', $this->player), ['reason' => 'erreur'])->assertSessionHas('success');
        $this->assertFalse(app(PacteService::class)->isUnlocked($this->player->fresh()));
    }

    public function test_rattraper_un_pack_pct_h_une_seule_fois(): void
    {
        $this->paystack->transactions['PCT-H-9-OK'] = [
            'status' => 'success', 'amount' => 50000, 'currency' => 'XOF',
            'metadata' => ['user_id' => $this->player->id, 'game' => 'le-pacte', 'product' => 'hints5'],
        ];

        $this->asAdmin()->post(route('admin.pacte.recover'), ['reference' => 'PCT-H-9-OK'])->assertSessionHas('success');
        $this->asAdmin()->post(route('admin.pacte.recover'), ['reference' => 'PCT-H-9-OK'])->assertSessionHas('info');
        $this->assertSame(5, $this->balance());
        $this->assertSame(1, GameCreditLedger::where('payment_reference', 'PCT-H-9-OK')->count());

        // Préfixe d'Akwaba refusé, pack d'Akwaba non crédité au Pacte
        $this->asAdmin()->post(route('admin.pacte.recover'), ['reference' => 'AKW-H-9-OK'])->assertSessionHasErrors('reference');
        $this->paystack->transactions['PCT-H-9-AKW'] = [
            'status' => 'success', 'amount' => 50000, 'currency' => 'XOF',
            'metadata' => ['user_id' => $this->player->id, 'game' => 'akwaba', 'product' => 'hints5'],
        ];
        $this->asAdmin()->post(route('admin.pacte.recover'), ['reference' => 'PCT-H-9-AKW'])->assertSessionHas('error');
        $this->assertSame(5, $this->balance());
    }

    public function test_le_stagiaire_consulte_mais_ne_peut_rien_modifier(): void
    {
        $stagiaire = fn () => $this->withSession(['is_stagiaire' => true]);
        $message = 'le code stagiaire permet seulement de consulter la page Le Pacte';

        $stagiaire()->get(route('admin.pacte.index', ['user' => $this->player->id]))->assertOk()
            ->assertSee('Consultation seule')
            ->assertDontSee(route('admin.pacte.unlock', $this->player), false)
            ->assertDontSee(route('admin.pacte.recover'), false);

        $stagiaire()->post(route('admin.pacte.unlock', $this->player), ['reason' => 'x'])->assertForbidden()->assertSee($message);
        $stagiaire()->post(route('admin.pacte.hints', $this->player), ['count' => 5, 'reason' => 'x'])->assertForbidden()->assertSee($message);
        $stagiaire()->post(route('admin.pacte.recover'), ['reference' => 'PCT-H-STAGIAIRE'])->assertForbidden()->assertSee($message);
    }

    // ─── Événements anonymes envoyés par la page du jeu ─────────────────────

    public function test_la_page_enregistre_les_evenements_sans_donnee_personnelle(): void
    {
        $chrome = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0 Mobile Safari/537.36';

        $this->withHeader('User-Agent', $chrome)->postJson(route('pacte.event'), ['type' => 'new_game'])->assertNoContent();
        $this->postJson(route('pacte.event'), ['type' => 'wait_rest'])->assertStatus(422);   // hors liste blanche du Pacte
        $this->postJson(route('pacte.event'), ['type' => 'lang_de'])->assertStatus(422);

        $rows = DB::table('game_events')->get();
        $this->assertCount(1, $rows);
        $this->assertSame(['id', 'game_slug', 'type', 'session_hash', 'browser', 'created_at'], array_keys((array) $rows[0]));
        $this->assertSame(['le-pacte', 'new_game', 'chrome'], [$rows[0]->game_slug, $rows[0]->type, $rows[0]->browser]);
        $this->assertSame(64, strlen($rows[0]->session_hash));
        $this->assertNotSame(session()->getId(), $rows[0]->session_hash);
        $this->assertSame(0, DB::table('akwaba_events')->count());

        // Une seule ligne par session et par type
        $pacte = app(PacteService::class);
        $pacte->recordEvent('continue', 'session-x', $chrome);
        $pacte->recordEvent('continue', 'session-x', $chrome);
        $this->assertSame(1, DB::table('game_events')->where('type', 'continue')->count());
    }
}
