<?php

namespace Tests\Feature;

use App\Jobs\SendAdminGrantNotificationJob;
use App\Models\AdminGrant;
use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\AkwabaService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AkwabaAdminTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceProduct $product;
    private User $player;
    private object $paystack;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->product = MarketplaceProduct::where('slug', 'akwaba')->firstOrFail();
        $this->player  = User::factory()->create(['name' => "Aya N'Guessan", 'email' => 'aya@example.com']);

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

    private function asAdmin(?User $account = null): static
    {
        if ($account) {
            $this->actingAs($account);
        }

        return $this->withSession(['is_admin' => true]);
    }

    private function balance(?User $user = null): int
    {
        return (int) GameCredit::where('user_id', ($user ?? $this->player)->id)->where('game_slug', 'akwaba')->value('balance');
    }

    private function isUnlocked(?User $user = null): bool
    {
        return app(AkwabaService::class)->isUnlocked(($user ?? $this->player)->fresh());
    }

    private function paidPurchase(User $user, int $amount = 1000, $paidAt = null): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id' => $user->id, 'product_id' => $this->product->id, 'amount' => $amount,
            'provider' => 'paystack', 'status' => 'paid', 'paid_at' => $paidAt ?? now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function progress(User $user, ?string $max, array $fins = []): void
    {
        GameProgress::create([
            'user_id' => $user->id, 'product_id' => $this->product->id,
            'data' => ['save' => $max, 'fins' => $fins, 'max' => $max],
        ]);
    }

    private function paystackPack(string $reference, array $metadata = [], int $amount = 500, string $status = 'success'): void
    {
        $this->paystack->transactions[$reference] = [
            'status' => $status, 'amount' => $amount * 100, 'currency' => 'XOF',
            'metadata' => $metadata + ['user_id' => $this->player->id, 'game' => 'akwaba', 'product' => 'hints5'],
        ];
    }

    // ─── Accès ──────────────────────────────────────────────────────────────

    public function test_tout_est_refuse_sans_code_admin(): void
    {
        $this->paystackPack('AKW-H-X');
        $loggedUser = User::factory()->create();

        foreach ([null, $loggedUser] as $who) {
            if ($who) {
                $this->actingAs($who);
            }

            $this->get(route('admin.akwaba.index'))->assertRedirect(route('admin.login.form'));
            $this->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'x'])->assertRedirect(route('admin.login.form'));
            $this->post(route('admin.akwaba.lock', $this->player), ['reason' => 'x'])->assertRedirect(route('admin.login.form'));
            $this->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'x'])->assertRedirect(route('admin.login.form'));
            $this->post(route('admin.akwaba.recover'), ['reference' => 'AKW-H-X'])->assertRedirect(route('admin.login.form'));
        }

        $this->assertFalse($this->isUnlocked());
        $this->assertSame(0, GameCreditLedger::count());
        $this->assertSame(0, AdminGrant::count());
    }

    public function test_le_menu_admin_contient_le_lien_akwaba(): void
    {
        $this->asAdmin()->get(route('admin.akwaba.index'))
            ->assertOk()
            ->assertSee(route('admin.akwaba.index'), false);
    }

    // ─── Page : joueurs, statistiques, historique ───────────────────────────

    public function test_la_liste_indique_si_l_indice_gratuit_est_utilise(): void
    {
        $this->progress($this->player, '2');
        $other = User::factory()->create(['name' => 'Koffi Autre']);
        $this->progress($other, '1');
        app(AkwabaService::class)->markFreeHintUsed($other, $this->product);

        $players = $this->asAdmin()->get(route('admin.akwaba.index'))
            ->assertOk()
            ->assertSee('Indice gratuit utilisé')
            ->viewData('players');

        $byName = collect($players->items())->keyBy('name');
        $this->assertNotNull($byName['Koffi Autre']->free_hint_at);
        $this->assertNull($byName["Aya N'Guessan"]->free_hint_at);
    }

    public function test_la_page_liste_les_joueurs_avec_leur_etat(): void
    {
        $payer = User::factory()->create(['name' => 'Kouamé Payeur']);
        $this->paidPurchase($payer);
        $this->progress($payer, '7', ['oubli', 'gardien']);
        app(AkwabaService::class)->addHints($payer, 3, 'admin', null, null, 'test');

        $this->progress($this->player, '2');
        User::factory()->create(['name' => 'Sans Akwaba']);

        $this->asAdmin()->get(route('admin.akwaba.index'))
            ->assertOk()
            ->assertSee('Kouamé Payeur')
            ->assertSee("Aya N'Guessan")
            ->assertDontSee('Sans Akwaba')
            ->assertSee('Oui · payé')
            ->assertSee("Fin de l'oubli, Fin du gardien")
            ->assertSee('Ch. 7');
    }

    public function test_la_recherche_filtre_les_joueurs(): void
    {
        $other = User::factory()->create(['name' => 'Koffi Autre', 'email' => 'koffi@example.com']);
        $this->progress($other, '1');
        $this->progress($this->player, '2');

        $this->asAdmin()->get(route('admin.akwaba.index', ['q' => 'aya@']))
            ->assertOk()
            ->assertSee("Aya N'Guessan")
            ->assertDontSee('Koffi Autre');
    }

    public function test_les_statistiques_entonnoir_conversion_et_chiffre_d_affaires(): void
    {
        // 4 joueurs connectés : prologue, ch. 2, ch. 2 + clic au cadenas, ch. 5 payé
        $a = User::factory()->create(); $this->progress($a, 'P');
        $b = User::factory()->create(); $this->progress($b, '2');
        $c = User::factory()->create(); $this->progress($c, '2');
        DB::table('marketplace_purchases')->insert([
            'user_id' => $c->id, 'product_id' => $this->product->id, 'amount' => 1000,
            'provider' => 'paystack', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $d = User::factory()->create(); $this->progress($d, '5', ['oubli']); $this->paidPurchase($d, 1000, now()->subDays(10));

        // Deux packs vendus (dont un il y a 20 jours), un ajout admin
        app(AkwabaService::class)->addHints($d, 5, 'purchase', 'AKW-H-1', 500);
        GameCreditLedger::create([
            'user_id' => $d->id, 'game_slug' => 'akwaba', 'kind' => 'hints', 'delta' => 5,
            'reason' => 'purchase', 'payment_reference' => 'AKW-H-OLD', 'amount' => 500,
        ]);
        GameCreditLedger::where('payment_reference', 'AKW-H-OLD')->update(['created_at' => now()->subDays(20)]);
        app(AkwabaService::class)->addHints($a, 5, 'admin', null, null, 'WhatsApp');

        $stats = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()->viewData('stats');

        $funnel = collect($stats['funnel'])->pluck('count', 'label');
        $this->assertSame(4, $funnel['Prologue']);
        $this->assertSame(3, $funnel['Chapitre 2']);
        $this->assertSame(2, $funnel['🔒 Cadenas']);     // c (tentative) + d (au-delà du ch. 2)
        $this->assertSame(1, $funnel['Chapitre 3']);
        $this->assertSame(1, $funnel['Chapitre 5']);
        $this->assertSame(0, $funnel['Chapitre 6']);

        $this->assertSame(4, $stats['players']);
        $this->assertSame(1, $stats['unlocks_paid']);
        $this->assertSame(50.0, $stats['conversion']);
        $this->assertSame(2, $stats['packs_sold']);
        $this->assertSame(['paystack' => 2000, 'manual' => 0, 'total' => 2000], $stats['revenue']);   // 1 000 + 2 × 500
        $this->assertSame(1, $stats['fins']['oubli']);
        $this->assertSame(5, $stats['hints_admin']);

        $this->assertSame(['unlocks' => 0, 'packs' => 1, 'revenue' => ['paystack' => 500, 'manual' => 0, 'total' => 500]], $stats['last7']);
        $this->assertSame(['unlocks' => 1, 'packs' => 2, 'revenue' => ['paystack' => 2000, 'manual' => 0, 'total' => 2000]], $stats['last30']);
    }

    public function test_l_historique_est_filtrable_par_joueur(): void
    {
        $other = User::factory()->create(['name' => 'Koffi Autre']);
        app(AkwabaService::class)->addHints($this->player, 2, 'admin', null, null, 'motif-aya');
        app(AkwabaService::class)->addHints($other, 3, 'admin', null, null, 'motif-koffi');

        $this->asAdmin()->get(route('admin.akwaba.index', ['user' => $this->player->id]))
            ->assertOk()
            ->assertSee('motif-aya')
            ->assertDontSee('motif-koffi');

        $this->asAdmin()->get(route('admin.akwaba.index', ['ledger_q' => 'Koffi']))
            ->assertOk()
            ->assertSee('motif-koffi')
            ->assertDontSee('motif-aya');
    }

    // ─── Déblocage manuel ───────────────────────────────────────────────────

    public function test_debloquer_exige_un_motif(): void
    {
        $this->asAdmin()->post(route('admin.akwaba.unlock', $this->player), ['reason' => ''])->assertSessionHasErrors('reason');

        $this->assertFalse($this->isUnlocked());
    }

    public function test_debloquer_cree_une_attribution_marketplace_avec_l_admin(): void
    {
        $admin = User::factory()->create(['name' => 'Ghislain Admin']);

        $this->asAdmin($admin)->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'paiement WhatsApp'])
            ->assertSessionHas('success');

        $grant = AdminGrant::firstOrFail();
        $this->assertSame(MarketplaceProduct::class, $grant->grantable_type);
        $this->assertSame($this->product->id, (int) $grant->grantable_id);
        $this->assertSame($admin->id, (int) $grant->granted_by);
        $this->assertStringContainsString('paiement WhatsApp', $grant->reason);
        $this->assertStringContainsString('Ghislain Admin', $grant->reason);
        $this->assertTrue($this->isUnlocked());
        Queue::assertPushed(SendAdminGrantNotificationJob::class);

        // Deuxième clic : rien de plus
        $this->asAdmin($admin)->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'doublon'])->assertSessionHas('error');
        $this->assertSame(1, AdminGrant::count());
    }

    public function test_retirer_revoque_l_attribution_avec_motif(): void
    {
        $this->asAdmin()->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'test']);

        $this->asAdmin()->post(route('admin.akwaba.lock', $this->player), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertTrue($this->isUnlocked());

        $this->asAdmin()->post(route('admin.akwaba.lock', $this->player), ['reason' => 'erreur de compte'])->assertSessionHas('success');

        $this->assertFalse($this->isUnlocked());
        $grant = AdminGrant::firstOrFail();
        $this->assertNotNull($grant->revoked_at);
        $this->assertStringContainsString('erreur de compte', $grant->revoke_reason);
        $this->assertStringContainsString('Code admin', $grant->revoke_reason); // admin sans compte connecté
    }

    public function test_retirer_ne_touche_pas_un_achat_paystack(): void
    {
        $this->paidPurchase($this->player);

        $this->asAdmin()->post(route('admin.akwaba.lock', $this->player), ['reason' => 'test'])->assertSessionHas('error');

        $this->assertTrue($this->isUnlocked());
    }

    // ─── Ajout d'indices ────────────────────────────────────────────────────

    public function test_ajouter_des_indices_exige_un_motif_et_un_nombre_valide(): void
    {
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => ''])->assertSessionHasErrors('reason');
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 0, 'reason' => 'x'])->assertSessionHasErrors('count');
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 101, 'reason' => 'x'])->assertSessionHasErrors('count');
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 'abc', 'reason' => 'x'])->assertSessionHasErrors('count');

        $this->assertSame(0, $this->balance());
        $this->assertSame(0, GameCreditLedger::count());
    }

    public function test_ajouter_des_indices_enregistre_le_motif_et_l_admin(): void
    {
        $admin = User::factory()->create(['name' => 'Ghislain Admin']);

        $this->asAdmin($admin)->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'paiement WhatsApp'])
            ->assertSessionHas('success');

        $this->assertSame(5, $this->balance());
        $this->assertDatabaseHas('game_credit_ledger', [
            'user_id' => $this->player->id, 'delta' => 5, 'reason' => 'admin', 'note' => 'paiement WhatsApp',
            'admin_user_id' => $admin->id, 'admin_label' => "Ghislain Admin (#{$admin->id}, code admin)",
        ]);
    }

    // ─── Code stagiaire : consultation seule ─────────────────────────────────

    public function test_le_stagiaire_consulte_mais_ne_peut_rien_modifier(): void
    {
        $this->paystackPack('AKW-H-STAGIAIRE');
        $stagiaire = fn () => $this->withSession(['is_stagiaire' => true]);

        $stagiaire()->get(route('admin.akwaba.index', ['user' => $this->player->id]))
            ->assertOk()
            ->assertSee('Consultation seule')
            ->assertSee('Réservé au code admin.')
            ->assertDontSee(route('admin.akwaba.unlock', $this->player), false)
            ->assertDontSee(route('admin.akwaba.hints', $this->player), false)
            ->assertDontSee(route('admin.akwaba.recover'), false);

        $message = 'Action réservée au code admin';
        $stagiaire()->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'x'])->assertForbidden()->assertSee($message);
        $stagiaire()->post(route('admin.akwaba.lock', $this->player), ['reason' => 'x'])->assertForbidden()->assertSee($message);
        $stagiaire()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'x'])->assertForbidden()->assertSee($message);
        $stagiaire()->post(route('admin.akwaba.recover'), ['reference' => 'AKW-H-STAGIAIRE'])->assertForbidden()->assertSee($message);

        $this->assertFalse($this->isUnlocked());
        $this->assertSame(0, AdminGrant::count());
        $this->assertSame(0, GameCreditLedger::count());
    }

    public function test_le_stagiaire_ne_peut_pas_retirer_un_deblocage(): void
    {
        $this->asAdmin()->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'WhatsApp']);

        $this->withSession(['is_admin' => false, 'is_stagiaire' => true])
            ->post(route('admin.akwaba.lock', $this->player), ['reason' => 'x'])->assertForbidden();

        $this->assertTrue($this->isUnlocked());
    }

    // ─── Montant encaissé hors Paystack ─────────────────────────────────────

    public function test_le_montant_encaisse_est_facultatif_et_valide(): void
    {
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'x', 'amount' => -1])->assertSessionHasErrors('amount');
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'x', 'amount' => 'abc'])->assertSessionHasErrors('amount');
        $this->asAdmin()->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'x', 'amount' => 2000000])->assertSessionHasErrors('amount');
        $this->assertSame(0, GameCreditLedger::count());
        $this->assertSame(0, AdminGrant::count());

        // Sans montant (ou 0) : cadeau, rien dans le CA
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 2, 'reason' => 'cadeau'])->assertSessionHas('success');
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 1, 'reason' => 'cadeau', 'amount' => 0])->assertSessionHas('success');
        $this->assertSame(0, (int) GameCreditLedger::sum('amount'));
    }

    public function test_le_chiffre_d_affaires_separe_paystack_et_manuel(): void
    {
        $payer = User::factory()->create();
        $this->paidPurchase($payer);                                                // Paystack 1 000
        app(AkwabaService::class)->addHints($payer, 5, 'purchase', 'AKW-H-P', 500); // Paystack 500

        $this->asAdmin()->post(route('admin.akwaba.unlock', $this->player), ['reason' => 'WhatsApp', 'amount' => 1000]);
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => 'WhatsApp', 'amount' => 500]);

        $this->assertSame(1000, (int) AdminGrant::value('amount_received'));
        $this->assertDatabaseHas('game_credit_ledger', ['reason' => 'admin', 'delta' => 5, 'amount' => 500]);

        $stats = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()->viewData('stats');
        $this->assertSame(['paystack' => 1500, 'manual' => 1500, 'total' => 3000], $stats['revenue']);
        $this->assertSame(['paystack' => 1500, 'manual' => 1500, 'total' => 3000], $stats['last7']['revenue']);

        // Un déblocage offert puis retiré (erreur) ne compte plus dans le CA manuel
        $this->asAdmin()->post(route('admin.akwaba.lock', $this->player), ['reason' => 'erreur']);
        $stats = $this->asAdmin()->get(route('admin.akwaba.index'))->viewData('stats');
        $this->assertSame(['paystack' => 1500, 'manual' => 500, 'total' => 2000], $stats['revenue']);
    }

    // ─── Entonnoir des visiteurs (sessions anonymes) ────────────────────────

    /** Une session (lettre répétée) passe par ces événements, à cette date, avec ce navigateur. */
    private function visit(string $letter, array $types, $at = null, ?string $browser = null): void
    {
        foreach ($types as $type) {
            DB::table('akwaba_events')->insert([
                'type' => $type, 'session_hash' => str_repeat($letter, 64), 'browser' => $browser,
                'created_at' => $at ?? now(),
            ]);
        }
    }

    // ─── Navigateurs et erreurs ─────────────────────────────────────────────

    public function test_l_entonnoir_filtre_par_navigateur(): void
    {
        // Facebook : 3 pages ouvertes, 1 seul jeu chargé ; Chrome : 2 ouvertes, 2 chargées, 1 au cadenas
        $this->visit('a', ['page_open', 'page_loaded'], null, 'facebook');
        $this->visit('b', ['page_open'], null, 'facebook');
        $this->visit('c', ['page_open'], null, 'facebook');
        $this->visit('d', ['page_open', 'page_loaded', 'paywall_open'], null, 'chrome');
        $this->visit('e', ['page_open', 'page_loaded'], null, 'chrome');
        $this->paidPurchase(User::factory()->create());

        $facebook = $this->asAdmin()->get(route('admin.akwaba.index', ['navigateur' => 'facebook']))->assertOk()
            ->assertSee('navigateur : Facebook (intégré)')
            ->viewData('stats');
        $this->assertSame([3, null], $this->steps($facebook, '7d')['page']);
        $this->assertSame([1, 33.3], $this->steps($facebook, '7d')['loaded']);
        $this->assertSame([null, null], $this->steps($facebook, 'all')['paid']);   // paiement non rattachable

        $all = $this->asAdmin()->get(route('admin.akwaba.index'))->viewData('stats');
        $this->assertSame(5, $this->steps($all, 'all')['page'][0]);
        $this->assertSame(1, $this->steps($all, 'all')['paid'][0]);

        // Valeur inconnue : pas de filtre
        $this->assertSame(5, $this->steps($this->asAdmin()->get(route('admin.akwaba.index', ['navigateur' => 'nokia']))->viewData('stats'), 'all')['page'][0]);
    }

    public function test_la_comparaison_par_navigateur_sur_7_jours(): void
    {
        $this->visit('a', ['page_open', 'page_loaded'], null, 'facebook');
        $this->visit('b', ['page_open'], null, 'facebook');
        $this->visit('c', ['page_open', 'page_loaded', 'new_game', 'wait_rest', 'paywall_open'], null, 'chrome');
        $this->visit('d', ['page_open'], now()->subDays(10), 'safari');   // hors période
        $this->visit('z', ['play_start']);                                 // avant le 04/11 : inconnu

        $rows = collect($this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()
            ->assertSee('Par navigateur')
            ->viewData('browsers'))->keyBy('browser');

        $this->assertSame([2, 1, 50.0], [$rows['facebook']['page'], $rows['facebook']['loaded'], $rows['facebook']['loaded_pct']]);
        $this->assertSame([1, 1, 1, 1, 100.0], [$rows['chrome']['page'], $rows['chrome']['start'], $rows['chrome']['wait'], $rows['chrome']['lock'], $rows['chrome']['lock_pct']]);
        $this->assertFalse($rows->has('safari'));
        $this->assertSame(1, $rows['inconnu']['loaded']);
    }

    public function test_la_comparaison_affiche_les_ouvertures_dans_chrome(): void
    {
        $this->visit('a', ['page_open', 'open_external'], null, 'facebook');
        $this->visit('b', ['page_open', 'open_external'], null, 'facebook');
        $this->visit('c', ['page_open'], null, 'facebook');
        $this->visit('d', ['page_open'], null, 'chrome');

        $response = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()->assertSee('Ouvert dans Chrome');
        $rows = collect($response->viewData('browsers'))->keyBy('browser');

        $this->assertSame(2, $rows['facebook']['external']);
        $this->assertSame(0, $rows['chrome']['external']);
    }

    public function test_les_erreurs_du_jeu_regroupees_par_message(): void
    {
        foreach ([['TypeError: a', 'facebook'], ['TypeError: a', 'chrome'], ['TypeError: a', 'facebook'], ['ReferenceError: b', 'safari']] as [$msg, $browser]) {
            DB::table('akwaba_errors')->insert(['msg' => $msg, 'line' => 10, 'col' => 4, 'browser' => $browser, 'created_at' => now()]);
        }

        $errors = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()
            ->assertSee('Erreurs du jeu')
            ->assertSee('TypeError: a')
            ->viewData('gameErrors');

        $this->assertSame('TypeError: a', $errors[0]['msg']);
        $this->assertSame(3, $errors[0]['count']);
        $this->assertSame(['facebook' => 2, 'chrome' => 1], $errors[0]['browsers']);
        $this->assertSame('10:4', $errors[0]['position']);
        $this->assertSame(1, $errors[1]['count']);
    }

    public function test_seules_les_50_dernieres_erreurs_sont_affichees(): void
    {
        for ($i = 0; $i < 60; $i++) {
            DB::table('akwaba_errors')->insert(['msg' => 'Erreur ' . $i, 'browser' => 'chrome', 'created_at' => now()]);
        }

        $errors = $this->asAdmin()->get(route('admin.akwaba.index'))->viewData('gameErrors');

        $this->assertSame(50, array_sum(array_column($errors, 'count')));
        $this->assertNotContains('Erreur 0', array_column($errors, 'msg'));
        $this->assertContains('Erreur 59', array_column($errors, 'msg'));
    }

    public function test_les_erreurs_de_validation_admin_restent_affichees(): void
    {
        // $errors (validation) n'est pas écrasé par les erreurs du jeu
        $this->asAdmin()->post(route('admin.akwaba.hints', $this->player), ['count' => 5, 'reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()->assertSee('alert-danger', false);
    }

    private function steps(array $stats, string $period): array
    {
        return collect($stats['sessions'][$period]['steps'])->mapWithKeys(fn ($s) => [$s['key'] => [$s['count'], $s['pct']]])->all();
    }

    public function test_l_entonnoir_complet_des_visiteurs_avec_les_pourcentages(): void
    {
        // 10 pages ouvertes, 8 jeux chargés, 6 parties, 5 au chapitre 1, 4 au chapitre 2,
        // 2 au cadenas (paywall_open ou lock_reached), 1 clic acheter, 1 paiement
        foreach (range('a', 'j') as $i => $l) {
            $types = ['page_open'];
            if ($i < 8) $types[] = 'page_loaded';
            if ($i < 5) $types[] = 'new_game';
            if ($i === 5) $types[] = 'continue';      // reprise : compte comme « partie »
            if ($i < 5) $types[] = 'ch1_reached';
            if ($i < 4) $types[] = 'ch2_reached';
            if ($i === 0) $types = array_merge($types, ['paywall_open', 'lock_reached', 'buy_click_full']);
            if ($i === 1) $types[] = 'paywall_open';
            $this->visit($l, $types);
        }
        $this->visit('z', ['play_start']);          // ancien nom de « jeu chargé »
        $this->paidPurchase(User::factory()->create());

        $response = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()
            ->assertSee('Entonnoir des visiteurs')
            ->assertSee('Page ouverte')
            ->assertSee('Jeu chargé')
            ->assertSee('Cadenas affiché')
            ->assertSee('Clic « acheter »')
            ->assertSee('24 h')
            ->assertSee('7 jours');
        $stats = $response->viewData('stats');

        $this->assertSame([
            'page'   => [10, null],
            'loaded' => [9, 90.0],     // 8 + 1 ancien play_start
            'start'  => [6, 66.7],     // new_game ou continue
            'ch1'    => [5, 83.3],
            'ch2'    => [4, 80.0],
            'lock'   => [2, 50.0],     // une session avec les deux événements ne compte qu'une fois
            'buy'    => [1, 50.0],
            'paid'   => [1, 100.0],
        ], $this->steps($stats, 'all'));

        $this->assertSame(1, $stats['sessions']['all']['details']['buy_click_full']);
        $this->assertSame(0, $stats['sessions']['all']['details']['buy_click_hints5']);
        $this->assertSame(1, $stats['sessions']['all']['details']['continue']);
        $this->assertSame(50.0, $stats['conversion_global']);   // 1 déblocage ÷ 2 sessions au cadenas
    }

    public function test_l_entonnoir_des_visiteurs_sur_24_heures_et_7_jours(): void
    {
        $this->visit('a', ['page_open', 'page_loaded'], now()->subHours(2));
        $this->visit('b', ['page_open', 'page_loaded', 'new_game'], now()->subDays(3));
        $this->visit('c', ['page_open'], now()->subDays(10));
        $this->paidPurchase(User::factory()->create(), 1000, now()->subDays(3));

        $stats = $this->asAdmin()->get(route('admin.akwaba.index'))->viewData('stats');

        $this->assertSame([1, null], $this->steps($stats, '24h')['page']);
        $this->assertSame([1, 100.0], $this->steps($stats, '24h')['loaded']);
        $this->assertSame([0, 0.0], $this->steps($stats, '24h')['start']);
        $this->assertSame([0, null], $this->steps($stats, '24h')['paid']);   // étape précédente à 0 : pas de %

        $this->assertSame([2, null], $this->steps($stats, '7d')['page']);
        $this->assertSame([1, 50.0], $this->steps($stats, '7d')['start']);
        $this->assertSame(1, $this->steps($stats, '7d')['paid'][0]);

        $this->assertSame(3, $this->steps($stats, 'all')['page'][0]);
    }

    public function test_l_entonnoir_des_visiteurs_vide_sans_erreur(): void
    {
        $stats = $this->asAdmin()->get(route('admin.akwaba.index'))->assertOk()->viewData('stats');

        foreach (['all', '24h', '7d'] as $period) {
            $this->assertSame(0, array_sum(array_column($this->steps($stats, $period), 0)));
        }
        $this->assertNull($stats['conversion_global']);
    }

    // ─── Rattrapage d'un pack ───────────────────────────────────────────────

    public function test_rattrapage_valide_credite_5_une_seule_fois(): void
    {
        $this->paystackPack('AKW-H-7-1700000000000-ABCD');

        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => 'AKW-H-7-1700000000000-ABCD'])
            ->assertSessionHas('success');
        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => 'AKW-H-7-1700000000000-ABCD'])
            ->assertSessionHas('info');

        $this->assertSame(5, $this->balance());
        $line = GameCreditLedger::where('payment_reference', 'AKW-H-7-1700000000000-ABCD')->sole();
        $this->assertSame('purchase', $line->reason);
        $this->assertSame(500, $line->amount);
        $this->assertSame('Code admin (sans compte connecté)', $line->admin_label);
    }

    public function test_rattrapage_d_un_pack_deja_credite_par_le_webhook(): void
    {
        app(AkwabaService::class)->addHints($this->player, 5, 'purchase', 'AKW-H-WEBHOOK', 500);
        $this->paystackPack('AKW-H-WEBHOOK');

        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => 'AKW-H-WEBHOOK'])->assertSessionHas('info');

        $this->assertSame(5, $this->balance());
    }

    public function test_rattrapage_refuse_une_reference_invalide_ou_non_confirmee(): void
    {
        $this->paystackPack('AKW-H-CHEAP', [], 100);
        $this->paystackPack('AKW-H-FAILED', [], 500, 'failed');
        $this->paystackPack('AKW-H-OTHERGAME', ['game' => 'autre']);

        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => 'MP-20261103-ABC'])->assertSessionHasErrors('reference');
        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => ''])->assertSessionHasErrors('reference');
        $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => "AKW-H-1'; DROP"])->assertSessionHasErrors('reference');

        foreach (['AKW-H-CHEAP', 'AKW-H-FAILED', 'AKW-H-OTHERGAME', 'AKW-H-UNKNOWN'] as $reference) {
            $this->asAdmin()->post(route('admin.akwaba.recover'), ['reference' => $reference])->assertSessionHas('error');
        }

        $this->assertSame(0, GameCreditLedger::count());
    }

    // ─── Fiche utilisateur ──────────────────────────────────────────────────

    public function test_la_fiche_utilisateur_affiche_l_encadre_akwaba(): void
    {
        $this->paidPurchase($this->player);
        $this->progress($this->player, '4', ['gardien']);
        app(AkwabaService::class)->addHints($this->player, 3, 'admin', null, null, 'test');

        $this->asAdmin()->get(route('admin.users.show', $this->player))
            ->assertOk()
            ->assertSee('🌙 Akwaba')
            ->assertSee('✅ Achetée')
            ->assertSee('Chapitre 4')
            ->assertSee('1/2')
            ->assertSee(route('admin.akwaba.index', ['user' => $this->player->id]), false);
    }
}
