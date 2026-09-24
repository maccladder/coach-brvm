<?php

namespace Tests\Feature;

use App\Models\BocStock;
use App\Models\DailyBoc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /marche-en-direct sur les vraies lignes brvm.org du 24/09/2026 à 12:06.
 */
class MarcheEnDirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-24 12:10:00');
        Http::fake(['www.brvm.org/*' => Http::response(file_get_contents(base_path('tests/Fixtures/brvm_cours_2026-09-24_1206.html')))]);

        $boc = DailyBoc::create(['date_boc' => '2026-09-23', 'file_path' => 'bocs/x.pdf', 'original_name' => 'x.pdf']);
        foreach (['LNBB' => 3700, 'PALC' => 8100, 'SOGC' => 7300, 'ORAC' => 20400, 'SHEC' => 2390, 'ABJC' => 3930] as $t => $p) {
            BocStock::create(['daily_boc_id' => $boc->id, 'date_boc' => '2026-09-23', 'ticker' => $t, 'name' => $t, 'price' => $p, 'change' => null]);
        }
    }

    /** Cellules texte d'une ligne du tableau : [ticker, société, cours, ouverture, clôture préc., variation, volume]. */
    private function ligne(string $html, string $ticker): array
    {
        $this->assertMatchesRegularExpression('#<tr data-search="' . strtolower($ticker) . '[^"]*">(.*?)</tr>#s', $html);
        preg_match('#<tr data-search="' . strtolower($ticker) . '[^"]*">(.*?)</tr>#s', $html, $m);
        preg_match_all('#<td[^>]*>(.*?)</td>#s', $m[1], $td);

        return array_map(fn ($c) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($c)))), $td[1]);
    }

    public function test_rows_are_consistent_arrow_matches_cours_vs_previous_close(): void
    {
        $html = $this->get(route('market.live'))->assertOk()->getContent();

        // [cours, ouverture, clôture préc., variation]
        $attendu = [
            'LNBB' => ['3 975 F', '3 705 F', '3 700 F', '▲ +7,43 %'],
            'PALC' => ['8 300 F', '8 100 F', '8 100 F', '▲ +2,47 %'],
            'SOGC' => ['7 650 F', '7 310 F', '7 300 F', '▲ +4,79 %'],
            'ORAC' => ['20 300 F', '20 500 F', '20 400 F', '▼ -0,49 %'],
            'SHEC' => ['2 295 F', '2 350 F', '2 390 F', '▼ -7,27 %'],   // variation officielle conservée
            'BBGC' => ['7 255 F', '7 255 F', '6 750 F', '▲ +7,48 %'],   // 1er jour : base = prix de l'OPV
        ];

        foreach ($attendu as $t => [$cours, $ouv, $prec, $var]) {
            $c = $this->ligne($html, $t);
            $this->assertSame([$cours, $ouv, $prec, $var], [$c[2], $c[3], $c[4], $c[5]], $t);
        }
    }

    public function test_shows_brvm_update_time(): void
    {
        $this->get(route('market.live'))->assertOk()->assertSee('cours brvm.org de <span>12h06</span>', false);
    }

    public function test_sorted_by_variation_desc(): void
    {
        $html = $this->get(route('market.live'))->getContent();

        $this->assertLessThan(strpos($html, 'data-search="lnbb'), strpos($html, 'data-search="bbgc'));  // +7,48 > +7,43
        $this->assertLessThan(strpos($html, 'data-search="shec'), strpos($html, 'data-search="orac'));  // -0,49 > -7,27
    }
}
