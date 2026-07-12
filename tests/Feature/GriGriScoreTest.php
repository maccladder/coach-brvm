<?php

namespace Tests\Feature;

use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GriGriScoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'title' => 'GRI-GRI — La Danse des Perles',
            'slug' => 'gri-gri-la-danse-des-perles',
            'type' => 'game',
            'status' => 'published',
            'price' => 1000,
            'game_html' => '<html></html>',
        ]);
    }

    private function grantAccess(User $user, MarketplaceProduct $product): void
    {
        $user->purchasedProducts()->attach($product->id, [
            'status' => 'paid',
            'amount' => 1000,
            'paid_at' => now(),
        ]);
    }

    public function test_guest_without_access_is_rejected(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 500, 'level' => 1, 'coins' => 30]);

        $res->assertStatus(403);
    }

    public function test_submits_score_and_returns_best_and_rank(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        $res = $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 3000, 'level' => 2, 'coins' => 100, 'final' => false]);

        $res->assertOk()->assertJson(['saved' => true, 'best_score' => 3000, 'best_level' => 2, 'rank' => 1, 'is_new_best' => true]);
        $this->assertDatabaseHas('game_scores', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'score' => 3000,
            'level' => 2,
            'coins' => 100,
        ]);
    }

    public function test_rejects_score_incoherent_with_level(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        // Niveau 1 : plafond = 6000 + 1*7000 = 13000, on tente 999999
        $res = $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 999999, 'level' => 1, 'coins' => 50]);

        $res->assertStatus(422);
        $this->assertEquals(0, GameScore::where('user_id', $user->id)->count());
    }

    public function test_rejects_coins_incoherent_with_level(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        // Niveau 1 : plafond cauris = 500 + 1*400 = 900, on tente 999999
        $res = $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 1000, 'level' => 1, 'coins' => 999999]);

        $res->assertStatus(422);
    }

    public function test_throttles_rapid_submissions(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 1000, 'level' => 1, 'coins' => 50])->assertOk();
        $res = $this->actingAs($user)->postJson('/jeux/gri-gri/score', ['score' => 1500, 'level' => 2, 'coins' => 80]);

        $res->assertStatus(422);
        $this->assertEquals(1, GameScore::where('user_id', $user->id)->count());
    }

    public function test_leaderboard_shows_best_per_player_with_level_metric(): void
    {
        $product = $this->makeProduct();
        $me = User::factory()->create(['name' => 'Kader']);
        $rival = User::factory()->create(['name' => 'Awa']);
        $this->grantAccess($me, $product);
        $this->grantAccess($rival, $product);

        GameScore::create(['user_id' => $me->id, 'product_id' => $product->id, 'score' => 2000, 'distance' => 0, 'coins' => 50, 'level' => 3, 'created_at' => now()->subMinutes(5)]);
        GameScore::create(['user_id' => $rival->id, 'product_id' => $product->id, 'score' => 4000, 'distance' => 0, 'coins' => 80, 'level' => 6, 'created_at' => now()->subMinutes(5)]);

        $res = $this->actingAs($me)->get('/jeux/gri-gri/classement');

        $res->assertOk();
        $res->assertSee('Niveau');
        $res->assertSee('Awa');
        $res->assertSee('Kader');
    }

    public function test_leaderboard_404_when_product_missing(): void
    {
        $user = User::factory()->create();
        $res = $this->actingAs($user)->get('/jeux/gri-gri/classement');
        $res->assertStatus(404);
    }
}
