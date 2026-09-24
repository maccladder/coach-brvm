<?php

namespace Tests\Feature;

use App\Models\FinancialReport;
use App\Models\Societe;
use Database\Seeders\SocietesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocietesBridgeBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_adds_bridge_bank(): void
    {
        $bbgc = Societe::where('code', 'BBGC')->first();

        $this->assertNotNull($bbgc);
        $this->assertSame('Banque', $bbgc->sector);
        $this->assertSame('CI', $bbgc->country);
        $this->assertTrue((bool) $bbgc->is_listed);
        $this->assertSame('2026-09-24', substr((string) $bbgc->listing_date, 0, 10));
    }

    public function test_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_09_24_000001_add_bridge_bank_to_societes_table.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, Societe::where('code', 'BBGC')->count());
    }

    public function test_seeder_is_idempotent_and_keeps_financial_reports(): void
    {
        $this->seed(SocietesSeeder::class);

        $sonatel = Societe::where('code', 'SNTS')->firstOrFail();
        FinancialReport::create([
            'societe_id' => $sonatel->id,
            'year'       => 2025,
            'period'     => 'FY',
            'status'     => 'published',
        ]);

        $this->seed(SocietesSeeder::class);

        $this->assertSame(48, Societe::where('is_listed', true)->count());
        $this->assertSame(1, Societe::where('code', 'BBGC')->count());
        $this->assertSame($sonatel->id, Societe::where('code', 'SNTS')->value('id'));
        $this->assertSame(1, FinancialReport::where('societe_id', $sonatel->id)->count());
    }
}
