<?php

namespace Tests\Feature;

use App\Models\BocStock;
use App\Models\DailyBoc;
use App\Services\BrvmActionsAiService;
use App\Services\CoursBrvm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Règle des cours, sur les vraies lignes brvm.org du 24/09/2026 à 12:06
 * (tests/Fixtures/brvm_cours_2026-09-24_1206.html).
 *
 * Clôtures de la veille (23/09), relevées sur brvm.org avant séance :
 * LNBB 3 700, PALC 8 100, SOGC 7 300, ORAC 20 400, SHEC 2 390, ABJC 3 930.
 */
class CoursBrvmTest extends TestCase
{
    use RefreshDatabase;

    private const CLOTURES_23_09 = [
        'LNBB' => 3700, 'PALC' => 8100, 'SOGC' => 7300, 'ORAC' => 20400, 'SHEC' => 2390, 'ABJC' => 3930,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-24 12:10:00');
        Http::fake(['www.brvm.org/*' => Http::response(file_get_contents(base_path('tests/Fixtures/brvm_cours_2026-09-24_1206.html')))]);
    }

    private function bocDu(string $date, array $prix): void
    {
        $boc = DailyBoc::create(['date_boc' => $date, 'file_path' => "bocs/$date.pdf", 'original_name' => "$date.pdf"]);
        foreach ($prix as $ticker => $p) {
            BocStock::create(['daily_boc_id' => $boc->id, 'date_boc' => $date, 'ticker' => $ticker, 'name' => $ticker, 'price' => $p, 'change' => null]);
        }
    }

    private function lignes(): array
    {
        return collect(app(CoursBrvm::class)->marcheEnDirect())->keyBy('ticker')->all();
    }

    public function test_parser_reads_update_time_and_ignores_top_flop_rows(): void
    {
        $brutes = (new BrvmActionsAiService())->fetchMarketTableFromSite();

        $this->assertCount(7, $brutes); // ABJC BBGC LNBB ORAC PALC SHEC SOGC (lignes Top/Flop ignorées)
        $this->assertSame('2026-09-24T12:06:00+00:00', $brutes[0]['maj']);
    }

    public function test_with_previous_session_boc_cours_and_previous_close_are_consistent(): void
    {
        $this->bocDu('2026-09-23', self::CLOTURES_23_09);

        $l = $this->lignes();

        // [cours, clôture préc., variation officielle]
        $attendu = [
            'LNBB' => [3975.0, 3700.0, 7.43],   // avant : cours 3 705 (ouverture) / veille 3 975 → « hausse » incohérente
            'PALC' => [8300.0, 8100.0, 2.47],   // avant : 8 100 / 8 300
            'SOGC' => [7650.0, 7300.0, 4.79],   // avant : 7 310 / 7 650
            'ORAC' => [20300.0, 20400.0, -0.49],
        ];
        foreach ($attendu as $t => [$cours, $prec, $var]) {
            $this->assertSame($cours, $l[$t]['cours'], "$t cours");
            $this->assertSame($prec, $l[$t]['cloture_prec'], "$t clôture préc.");
            $this->assertSame('boc', $l[$t]['source_cloture_prec'], "$t source");
            $this->assertSame($var, $l[$t]['variation'], "$t variation");
            $this->assertSame($var <=> 0, $cours <=> $prec, "$t : sens du cours ≠ sens de la variation");
        }
    }

    public function test_shec_keeps_official_variation_even_if_it_differs_from_computation(): void
    {
        $this->bocDu('2026-09-23', self::CLOTURES_23_09);

        $shec = $this->lignes()['SHEC'];

        $this->assertSame(2295.0, $shec['cours']);
        $this->assertSame(2390.0, $shec['cloture_prec']);
        $this->assertSame(-7.27, $shec['variation']); // officielle (calcul : -3,97 %)
    }

    public function test_without_boc_previous_close_is_deduced_from_official_variation(): void
    {
        $l = $this->lignes();

        foreach (['LNBB', 'PALC', 'SOGC', 'ORAC', 'ABJC'] as $t) {
            $this->assertSame((float) self::CLOTURES_23_09[$t], $l[$t]['cloture_prec'], $t);
            $this->assertSame('deduite', $l[$t]['source_cloture_prec'], $t);
        }
    }

    public function test_stale_or_inconsistent_boc_is_not_used(): void
    {
        // BOC qui contredit la variation officielle (ex. BOC manquante / décalée)
        $this->bocDu('2026-09-23', ['LNBB' => 3975]);

        $lnbb = $this->lignes()['LNBB'];

        $this->assertSame(3700.0, $lnbb['cloture_prec']);
        $this->assertSame('deduite', $lnbb['source_cloture_prec']);
    }

    public function test_bbgc_first_day_uses_ipo_price_and_computed_variation(): void
    {
        $bbgc = $this->lignes()['BBGC'];

        $this->assertSame(7255.0, $bbgc['cours']);
        $this->assertSame(6750.0, $bbgc['cloture_prec']);
        $this->assertSame('opv', $bbgc['source_cloture_prec']);
        $this->assertSame(7.48, $bbgc['variation']); // le site affiche 0,00
        $this->assertTrue($bbgc['variation_calculee']);
    }

    public function test_bbgc_second_day_uses_previous_session_close(): void
    {
        $this->bocDu('2026-09-24', ['BBGC' => 7255]);
        $lignes = [[
            'ticker' => 'BBGC', 'open' => 7300.0, 'close' => 7400.0, 'change' => 2.0,
            'maj' => '2026-09-25T11:00:00+00:00',
        ]];

        $bbgc = app(CoursBrvm::class)->enrichir($lignes)[0];

        $this->assertSame(7255.0, $bbgc['cloture_prec']);
        $this->assertSame('boc', $bbgc['source_cloture_prec']);
        $this->assertSame(2.0, $bbgc['variation']); // officielle
    }

    public function test_cours_falls_back_close_then_open_then_previous_close(): void
    {
        $this->bocDu('2026-09-23', ['AAAA' => 1000, 'BBBB' => 1000, 'CCCC' => 1000]);
        $base = ['maj' => '2026-09-24T12:06:00+00:00', 'change' => 0.0];

        $l = collect(app(CoursBrvm::class)->enrichir([
            ['ticker' => 'AAAA', 'open' => 990.0, 'close' => 1010.0, 'change' => 1.0] + $base,
            ['ticker' => 'BBBB', 'open' => 1000.0, 'close' => 0.0] + $base,
            ['ticker' => 'CCCC', 'open' => null, 'close' => null] + $base,
        ]))->keyBy('ticker');

        $this->assertSame(1010.0, $l['AAAA']['cours']); // clôture
        $this->assertSame(1000.0, $l['BBBB']['cours']); // ouverture (clôture à 0)
        $this->assertSame(1000.0, $l['CCCC']['cours']); // clôture précédente
    }
}
