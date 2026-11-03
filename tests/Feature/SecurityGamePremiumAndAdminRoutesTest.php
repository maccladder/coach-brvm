<?php

namespace Tests\Feature;

use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Models\VendorPayoutRequest;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Correctifs S1 à S4 (AUDIT_AKWABA.md) :
 * - S1/S2 : verify-premium et premium-callback n'acceptent qu'une référence Paystack
 *   réussie, initialisée pour ce joueur, ce jeu et cette option, au bon montant, une seule fois.
 * - S3 : /admin/payouts/* exige le code admin.
 * - S4 : /admin/users et /admin/users/{user} exigent le code admin.
 */
class SecurityGamePremiumAndAdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private MarketplaceProduct $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->player = User::factory()->create();
        $this->game   = MarketplaceProduct::create([
            'title'  => 'Roi du Cacao',
            'slug'   => 'roi-du-cacao',
            'type'   => 'game',
            'status' => 'published',
            'price'  => 0,
        ]);

        DB::table('marketplace_purchases')->insert([
            'user_id'    => $this->player->id,
            'product_id' => $this->game->id,
            'amount'     => 0,
            'provider'   => 'free',
            'status'     => 'paid',
            'paid_at'    => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Transactions renvoyées par le faux Paystack, indexées par référence. */
    private function fakePaystack(array $byReference): void
    {
        $this->app->instance(PaystackService::class, new class($byReference) extends PaystackService {
            public function __construct(private array $byReference) {}

            public function verify(string $reference): ?array
            {
                return isset($this->byReference[$reference])
                    ? ['reference' => $reference] + $this->byReference[$reference]
                    : null;
            }
        });
    }

    private function transaction(int $amountXof, array $metadata, string $status = 'success', string $currency = 'XOF'): array
    {
        return [
            'status'   => $status,
            'amount'   => $amountXof * 100,
            'currency' => $currency,
            'metadata' => $metadata,
        ];
    }

    private function metadata(string $feature, ?User $user = null, ?MarketplaceProduct $product = null): array
    {
        return [
            'user_id'    => ($user ?? $this->player)->id,
            'product_id' => ($product ?? $this->game)->id,
            'feature'    => $feature,
        ];
    }

    private function verifyPremium(string $ref, string $feature)
    {
        return $this->actingAs($this->player)
            ->postJson(route('games.verify-premium', $this->game), ['ref' => $ref, 'feature' => $feature]);
    }

    private function unlockCount(?string $feature = null): int
    {
        return GamePremiumUnlock::where('user_id', $this->player->id)
            ->where('product_id', $this->game->id)
            ->when($feature, fn ($q) => $q->where('feature', $feature))
            ->count();
    }

    // ─── S1 : verify-premium ────────────────────────────────────────────────

    public function test_verify_premium_valide_debloque_une_seule_fois(): void
    {
        $this->fakePaystack(['GAME_OK' => $this->transaction(500, $this->metadata('turbo'))]);

        $this->verifyPremium('GAME_OK', 'turbo')->assertOk()->assertJsonPath('unlocked', true);
        $this->verifyPremium('GAME_OK', 'turbo')->assertOk()->assertJsonPath('already', true);

        $this->assertSame(1, $this->unlockCount('turbo'));
    }

    public function test_verify_premium_refuse_une_option_plus_chere_que_celle_payee(): void
    {
        // Payé « turbo » (500 F), réclame « magnat » (2 500 F)
        $this->fakePaystack(['GAME_TURBO' => $this->transaction(500, $this->metadata('turbo'))]);

        $this->verifyPremium('GAME_TURBO', 'magnat')->assertStatus(402);

        $this->assertSame(0, $this->unlockCount());
    }

    public function test_verify_premium_refuse_un_montant_falsifie(): void
    {
        $this->fakePaystack(['GAME_CHEAP' => $this->transaction(500, $this->metadata('magnat'))]);

        $this->verifyPremium('GAME_CHEAP', 'magnat')->assertStatus(402);

        $this->assertSame(0, $this->unlockCount());
    }

    public function test_verify_premium_refuse_une_devise_ou_un_statut_invalide(): void
    {
        $this->fakePaystack([
            'GAME_USD'    => $this->transaction(500, $this->metadata('turbo'), 'success', 'USD'),
            'GAME_FAILED' => $this->transaction(500, $this->metadata('turbo'), 'failed'),
        ]);

        $this->verifyPremium('GAME_USD', 'turbo')->assertStatus(402);
        $this->verifyPremium('GAME_FAILED', 'turbo')->assertStatus(402);

        $this->assertSame(0, $this->unlockCount());
    }

    public function test_verify_premium_refuse_la_reference_d_un_autre_joueur(): void
    {
        $other = User::factory()->create();
        $this->fakePaystack(['GAME_OTHER' => $this->transaction(500, $this->metadata('turbo', $other))]);

        $this->verifyPremium('GAME_OTHER', 'turbo')->assertStatus(402);

        $this->assertSame(0, GamePremiumUnlock::count());
    }

    public function test_verify_premium_refuse_un_autre_paiement_sans_metadonnees_de_jeu(): void
    {
        // Ex. un don ou un achat de cauris réussi
        $this->fakePaystack([
            'DON-123'    => $this->transaction(500, ['purpose' => 'don']),
            'CAURIS-456' => $this->transaction(500, ['user_id' => $this->player->id, 'pack' => 'petit']),
        ]);

        $this->verifyPremium('DON-123', 'turbo')->assertStatus(402);
        $this->verifyPremium('CAURIS-456', 'turbo')->assertStatus(402);

        $this->assertSame(0, $this->unlockCount());
    }

    public function test_verify_premium_refuse_la_reference_d_un_autre_jeu(): void
    {
        $otherGame = MarketplaceProduct::create([
            'title' => 'Autre', 'slug' => 'roi-du-cacao-le-jeu-ivoirien-addictif',
            'type' => 'game', 'status' => 'published', 'price' => 0,
        ]);
        $this->fakePaystack(['GAME_X' => $this->transaction(500, $this->metadata('turbo', null, $otherGame))]);

        $this->verifyPremium('GAME_X', 'turbo')->assertStatus(402);

        $this->assertSame(0, GamePremiumUnlock::count());
    }

    public function test_verify_premium_refuse_une_reference_deja_consommee(): void
    {
        // La référence a déjà servi à un déblocage enregistré (webhook, autre option…)
        GamePremiumUnlock::create([
            'user_id'      => $this->player->id,
            'product_id'   => $this->game->id,
            'feature'      => 'planteur',
            'paystack_ref' => 'GAME_USED',
            'amount'       => 1000,
            'unlocked_at'  => now(),
        ]);
        $this->fakePaystack(['GAME_USED' => $this->transaction(500, $this->metadata('turbo'))]);

        $this->verifyPremium('GAME_USED', 'turbo')->assertStatus(402);

        $this->assertSame(0, $this->unlockCount('turbo'));
    }

    // ─── S2 : premium-callback ──────────────────────────────────────────────

    public function test_premium_callback_valide_debloque_l_option_des_metadonnees(): void
    {
        $this->fakePaystack(['GAME_CB' => $this->transaction(1000, $this->metadata('planteur'))]);

        $this->actingAs($this->player)
            ->get(route('games.premium-callback', $this->game) . '?reference=GAME_CB')
            ->assertRedirect(route('my.products.play', $this->game));

        $this->assertSame(1, $this->unlockCount('planteur'));
    }

    public function test_premium_callback_refuse_reference_etrangere_ou_montant_faux(): void
    {
        $other = User::factory()->create();
        $this->fakePaystack([
            'GAME_OTHER' => $this->transaction(1000, $this->metadata('planteur', $other)),
            'GAME_CHEAP' => $this->transaction(500, $this->metadata('magnat')),
            'DON-1'      => $this->transaction(100, []),
        ]);

        foreach (['GAME_OTHER', 'GAME_CHEAP', 'DON-1'] as $ref) {
            $this->actingAs($this->player)
                ->get(route('games.premium-callback', $this->game) . '?reference=' . $ref)
                ->assertRedirect(route('my.products.play', $this->game));
        }

        $this->assertSame(0, GamePremiumUnlock::count());
    }

    // ─── S3 : /admin/payouts ────────────────────────────────────────────────

    private function payout(): VendorPayoutRequest
    {
        $vendor = User::factory()->create();

        return VendorPayoutRequest::create([
            'vendor_id'    => $vendor->id,
            'amount'       => 50000,
            'status'       => 'pending',
            'requested_at' => now(),
        ]);
    }

    public function test_payouts_refuses_a_un_utilisateur_connecte_non_admin(): void
    {
        $payout = $this->payout();

        $this->actingAs($this->player)->get(route('admin.payouts.index'))
            ->assertRedirect(route('admin.login.form'));
        $this->actingAs($this->player)->post(route('admin.payouts.approve', $payout))
            ->assertRedirect(route('admin.login.form'));
        $this->actingAs($this->player)->post(route('admin.payouts.paid', $payout))
            ->assertRedirect(route('admin.login.form'));
        $this->actingAs($this->player)->post(route('admin.payouts.reject', $payout), ['admin_note' => 'x'])
            ->assertRedirect(route('admin.login.form'));

        $this->assertSame('pending', $payout->fresh()->status);
    }

    public function test_payouts_accessibles_avec_le_code_admin(): void
    {
        Notification::fake();
        $payout = $this->payout();
        $admin  = User::factory()->create();

        $this->actingAs($admin)->withSession(['is_admin' => true])
            ->post(route('admin.payouts.approve', $payout))
            ->assertRedirect();

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame($admin->id, (int) $payout->fresh()->approved_by);
    }

    // ─── S4 : /admin/users ──────────────────────────────────────────────────

    public function test_admin_users_refuse_sans_code_admin(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login.form'));
        $this->get(route('admin.users.show', $this->player))->assertRedirect(route('admin.login.form'));

        $this->actingAs($this->player)->get(route('admin.users.index'))->assertRedirect(route('admin.login.form'));
        $this->actingAs($this->player)->get(route('admin.users.show', $this->player))->assertRedirect(route('admin.login.form'));
    }

    public function test_admin_users_accessibles_avec_le_code_admin(): void
    {
        $this->withSession(['is_admin' => true])->get(route('admin.users.index'))->assertOk();
        $this->withSession(['is_admin' => true])->get(route('admin.users.show', $this->player))->assertOk();
    }
}
