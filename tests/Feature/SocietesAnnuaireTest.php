<?php

namespace Tests\Feature;

use App\Models\Societe;
use Database\Seeders\SocietesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocietesAnnuaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // pas d'appel réseau à brvm.org (bandeau du layout)
        $this->seed(SocietesSeeder::class);
    }

    public function test_index_lists_all_listed_societes_from_database(): void
    {
        $response = $this->get(route('societes.index'))->assertOk();

        $this->assertCount(48, $response->viewData('items'));
        $response->assertSee('BBGC');
        $response->assertSee('SGBC'); // absente de brvm_societes.php : vient de la table
    }

    public function test_existing_slugs_are_preserved(): void
    {
        $legacy = require app_path('Data/brvm_societes.php');

        foreach ($legacy as $slug => $data) {
            $this->get(route('societes.show', $slug))
                ->assertOk()
                ->assertSee($data['ticker']);
        }
    }

    public function test_bridge_bank_page_renders_without_dividend_or_description(): void
    {
        $this->get(route('societes.show', 'bridge-bank-ci'))
            ->assertOk()
            ->assertSee('BBGC');
    }

    public function test_societe_without_enrichment_gets_generated_slug(): void
    {
        $this->get(route('societes.show', 'societe-generale-cote-divoire'))
            ->assertOk()
            ->assertSee('SGBC');
    }

    public function test_delisted_societe_is_hidden(): void
    {
        Societe::where('code', 'SGBC')->update(['is_listed' => false]);

        $this->get(route('societes.show', 'societe-generale-cote-divoire'))->assertNotFound();
        $this->assertCount(47, $this->get(route('societes.index'))->viewData('items'));
    }
}
