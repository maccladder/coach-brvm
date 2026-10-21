<?php

namespace Tests\Feature;

use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinceDeYopProgressTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/jeux/prince-de-yop/progression';

    private function makeProduct(): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'title' => 'Le Prince de Yop',
            'slug' => 'le-prince-de-yop',
            'type' => 'game',
            'status' => 'published',
            'price' => 1000,
            'game_html' => '<!DOCTYPE html><title>Le Prince de Yop</title>',
        ]);
    }

    private function buyer(MarketplaceProduct $product): User
    {
        $user = User::factory()->create();
        $user->purchasedProducts()->attach($product->id, [
            'status' => 'paid',
            'amount' => 1000,
            'paid_at' => now(),
        ]);

        return $user;
    }

    private function progress(User $user, int $level, int $bonus = 0)
    {
        return $this->actingAs($user)->postJson(self::URL, ['type' => 'progress', 'level' => $level, 'bonus' => $bonus]);
    }

    private function advanceTo(User $user, int $level): void
    {
        for ($l = 1; $l <= $level; $l++) {
            $this->progress($user, $l)->assertOk()->assertJson(['saved' => true, 'level' => $l]);
        }
    }

    public function test_guest_is_rejected(): void
    {
        $this->makeProduct();

        $this->getJson(self::URL)->assertStatus(401);
        $this->postJson(self::URL, ['type' => 'progress', 'level' => 1, 'bonus' => 0])->assertStatus(401);
    }

    public function test_user_without_purchase_is_rejected(): void
    {
        $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(self::URL)->assertStatus(403);
        $this->progress($user, 1)->assertStatus(403);
        $this->assertEquals(0, GameProgress::count());
    }

    public function test_play_page_uses_dedicated_wrapper(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        $this->actingAs($user)->get(route('my.products.play', $product))
            ->assertOk()
            ->assertSee('PRINCE_YOP_PROGRESS_LOAD', false)
            ->assertDontSee('Classement');
    }

    public function test_fresh_player_gets_level_zero(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        $this->actingAs($user)->getJson(self::URL)
            ->assertOk()
            ->assertJson(['level' => 0, 'bonus' => 0, 'completed' => false]);
    }

    public function test_progress_is_saved_and_read_back(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        $this->progress($user, 1, 1)->assertOk()->assertJson(['saved' => true, 'level' => 1, 'bonus' => 1]);

        // Rechargement de la page → la progression revient du serveur.
        $this->actingAs($user)->getJson(self::URL)->assertOk()->assertJson(['level' => 1, 'bonus' => 1]);
    }

    public function test_skipping_a_level_is_rejected(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        $this->progress($user, 2)->assertStatus(422);
        $this->actingAs($user)->getJson(self::URL)->assertJson(['level' => 0]);
    }

    public function test_lower_progress_is_ignored(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);
        $this->advanceTo($user, 3);

        // Nouvelle partie depuis le début : fin du niveau 1 → on garde le niveau 3.
        $this->progress($user, 1)->assertOk()->assertJson(['saved' => false, 'level' => 3]);
        $this->progress($user, 3)->assertOk()->assertJson(['saved' => false, 'level' => 3]);
    }

    public function test_bonus_out_of_bounds_is_rejected(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        $this->progress($user, 1, 2)->assertStatus(422); // bonus > level
        $this->progress($user, 1, -1)->assertStatus(422);
        $this->progress($user, 6, 0)->assertStatus(422); // level hors 0–5
        $this->actingAs($user)->postJson(self::URL, ['type' => 'progress', 'level' => 1, 'bonus' => 'x'])->assertStatus(422);
        $this->actingAs($user)->postJson(self::URL, ['type' => 'hack', 'level' => 5])->assertStatus(422);
    }

    public function test_game_complete_requires_last_level(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);
        $this->advanceTo($user, 4);

        $this->actingAs($user)->postJson(self::URL, ['type' => 'game_complete', 'deaths' => 0])->assertStatus(422);
        $this->actingAs($user)->getJson(self::URL)->assertJson(['level' => 4, 'completed' => false]);
    }

    public function test_game_complete_resets_to_zero_and_keeps_completed(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);
        $this->advanceTo($user, 5);

        // Ordre envoyé par le jeu : GAME_COMPLETE puis PROGRESS 0.
        $this->actingAs($user)->postJson(self::URL, ['type' => 'game_complete', 'deaths' => 3])
            ->assertOk()
            ->assertJson(['saved' => true, 'level' => 0, 'bonus' => 0, 'completed' => true, 'completions' => 1]);
        $this->progress($user, 0)->assertOk()->assertJson(['saved' => false, 'level' => 0]);

        // Le joueur recommence : la progression repart, "completed" reste acquis.
        $this->progress($user, 1)->assertOk()->assertJson(['saved' => true, 'level' => 1, 'completed' => true]);

        $data = GameProgress::firstOrFail()->data;
        $this->assertSame(3, $data['total_deaths']);
        $this->assertNotNull($data['first_completed_at']);
    }

    public function test_level_complete_records_best_time_for_reached_levels_only(): void
    {
        $product = $this->makeProduct();
        $user = $this->buyer($product);

        // Niveau 0 pas encore franchi → refusé.
        $this->actingAs($user)->postJson(self::URL, ['type' => 'level_complete', 'level' => 0, 'time' => 90, 'deaths' => 1])->assertStatus(422);

        $this->progress($user, 1);
        $this->actingAs($user)->postJson(self::URL, ['type' => 'level_complete', 'level' => 0, 'time' => 90, 'deaths' => 1])->assertOk();
        $this->actingAs($user)->postJson(self::URL, ['type' => 'level_complete', 'level' => 0, 'time' => 120, 'deaths' => 2])->assertOk();
        $this->actingAs($user)->postJson(self::URL, ['type' => 'level_complete', 'level' => 0, 'time' => 999, 'deaths' => 0])->assertStatus(422);

        $data = GameProgress::firstOrFail()->data;
        $this->assertSame(90, $data['best_times']['0']);
        $this->assertSame(3, $data['total_deaths']);
    }
}
