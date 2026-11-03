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
        $this->assertSame(2000, $stats['revenue']);   // 1 000 + 2 × 500
        $this->assertSame(1, $stats['fins']['oubli']);
        $this->assertSame(5, $stats['hints_admin']);

        $this->assertSame(['unlocks' => 0, 'packs' => 1, 'revenue' => 500], $stats['last7']);
        $this->assertSame(['unlocks' => 1, 'packs' => 2, 'revenue' => 2000], $stats['last30']);
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

    public function test_un_stagiaire_est_identifie_comme_tel(): void
    {
        $this->withSession(['is_stagiaire' => true])
            ->post(route('admin.akwaba.hints', $this->player), ['count' => 1, 'reason' => 'test']);

        $this->assertSame('Code stagiaire (sans compte connecté)', GameCreditLedger::value('admin_label'));
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
