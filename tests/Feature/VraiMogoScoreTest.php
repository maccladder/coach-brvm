<?php

namespace Tests\Feature;

use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VraiMogoScoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'title' => 'Questions pour un vrai môgô',
            'slug' => 'questions-pour-un-vrai-mogo',
            'type' => 'game',
            'status' => 'published',
            'price' => 0,
            'game_html' => '<html></html>',
        ]);
    }

    private function grantAccess(User $user, MarketplaceProduct $product): void
    {
        $user->purchasedProducts()->attach($product->id, [
            'status' => 'paid',
            'amount' => 0,
            'paid_at' => now(),
        ]);
    }

    public function test_guest_without_access_is_rejected(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 1000, 'correct' => 5]);

        $res->assertStatus(403);
    }

    public function test_submits_score_and_returns_rank_total_best(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        $res = $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 3000, 'correct' => 15]);

        $res->assertOk()->assertJson(['rank' => 1, 'total' => 1, 'best' => 3000]);
        $this->assertDatabaseHas('game_scores', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'score' => 3000,
            'correct_answers' => 15,
        ]);
    }

    public function test_rejects_score_exceeding_max(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        $res = $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 7000, 'correct' => 20]);

        $res->assertStatus(422);
    }

    public function test_rejects_score_incoherent_with_correct_answers(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        // 2 bonnes reponses = 600 pts max, on tente 5000
        $res = $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 5000, 'correct' => 2]);

        $res->assertStatus(422);
    }

    public function test_throttles_submissions_within_30_seconds(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->grantAccess($user, $product);

        $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 1000, 'correct' => 5])->assertOk();
        $res = $this->actingAs($user)->postJson('/jeux/vrai-mogo/score', ['score' => 1200, 'correct' => 6]);

        $res->assertStatus(422);
        $this->assertEquals(1, GameScore::where('user_id', $user->id)->count());
    }

    public function test_leaderboard_returns_top_and_me(): void
    {
        $product = $this->makeProduct();
        $me = User::factory()->create(['name' => 'Kader']);
        $rival = User::factory()->create(['name' => 'Awa']);
        $this->grantAccess($me, $product);
        $this->grantAccess($rival, $product);

        GameScore::create(['user_id' => $me->id, 'product_id' => $product->id, 'score' => 2000, 'distance' => 0, 'coins' => 0, 'correct_answers' => 10, 'created_at' => now()->subMinutes(5)]);
        GameScore::create(['user_id' => $rival->id, 'product_id' => $product->id, 'score' => 4000, 'distance' => 0, 'coins' => 0, 'correct_answers' => 18, 'created_at' => now()->subMinutes(5)]);

        $res = $this->actingAs($me)->getJson('/jeux/vrai-mogo/classement');

        $res->assertOk();
        $res->assertJsonPath('total', 2);
        $res->assertJsonPath('me.rank', 2);
        $res->assertJsonPath('me.score', 2000);
        $res->assertJsonPath('top.0.name', 'Awa');
        $res->assertJsonPath('top.0.is_me', false);
        $res->assertJsonPath('top.1.name', 'Kader');
        $res->assertJsonPath('top.1.is_me', true);
    }

    public function test_leaderboard_404_when_product_missing(): void
    {
        $user = User::factory()->create();
        $res = $this->actingAs($user)->getJson('/jeux/vrai-mogo/classement');
        $res->assertStatus(404);
    }
}
