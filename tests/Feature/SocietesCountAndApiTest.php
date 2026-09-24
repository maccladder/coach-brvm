<?php

namespace Tests\Feature;

use App\Models\Societe;
use Database\Seeders\SocietesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocietesCountAndApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['services.n8n.api_key' => 'test-key']);
        $this->seed(SocietesSeeder::class);
    }

    public function test_listed_count_is_dynamic_and_cache_is_invalidated(): void
    {
        $this->assertSame(48, Societe::listedCount());

        Societe::where('code', 'SGBC')->first()->update(['is_listed' => false]);

        $this->assertSame(47, Societe::listedCount());
    }

    public function test_landing_shows_dynamic_count_and_8_countries(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertViewHas('nbSocietes', 48)
            ->assertDontSee('45+')
            ->assertSeeInOrder(['48', 'Sociétés cotées', '8', 'Pays UEMOA'], false);
    }

    public function test_n8n_societes_requires_api_key(): void
    {
        $this->getJson('/api/n8n/societes')->assertUnauthorized();
        $this->getJson('/api/n8n/societes', ['X-API-KEY' => 'wrong'])->assertUnauthorized();
    }

    public function test_n8n_societes_lists_bridge_bank(): void
    {
        $response = $this->getJson('/api/n8n/societes', ['X-API-KEY' => 'test-key'])
            ->assertOk()
            ->assertJsonPath('count', 48)
            ->assertJsonFragment([
                'ticker'       => 'BBGC',
                'sector'       => 'Banque',
                'country'      => 'CI',
                'listing_date' => '2026-09-24',
            ]);

        $this->assertCount(48, $response->json('societes'));
    }

    public function test_n8n_news_accepts_bbgc(): void
    {
        $this->postJson('/api/n8n/news', [
            'titre'    => 'Bridge Bank fait son entrée à la BRVM',
            'url'      => 'https://example.com/bridge-bank-brvm',
            'societes' => ['BBGC'],
        ], ['X-API-KEY' => 'test-key'])->assertCreated();

        $this->assertDatabaseHas('news', ['source_url' => 'https://example.com/bridge-bank-brvm']);
    }
}
