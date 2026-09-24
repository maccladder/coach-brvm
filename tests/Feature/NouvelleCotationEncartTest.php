<?php

namespace Tests\Feature;

use App\Models\BocStock;
use App\Models\DailyBoc;
use App\Services\BrvmMarketSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Encart « Nouvelle cotation » (config/cotations.php) sur l'accueil.
 */
class NouvelleCotationEncartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests(); // jamais d'appel direct à brvm.org
    }

    private function releve(?float $close, string $a = '2026-09-25 15:40:00'): void
    {
        Cache::forever(BrvmMarketSnapshot::CACHE_KEY, [
            'fetched_at' => $a,
            'rows'       => [[
                'ticker' => 'BBGC', 'name' => "BRIDGE BANK GROUP COTE D'IVOIRE", 'volume' => 5000,
                'prev' => 6750.0, 'open' => 7000.0, 'close' => $close, 'change' => 0.0, 'buy_price' => 7000.0,
            ]],
        ]);
    }

    private function clotureBoc(string $date, float $prix): void
    {
        $boc = DailyBoc::create(['date_boc' => $date, 'file_path' => "bocs/$date.pdf", 'original_name' => "$date.pdf"]);
        BocStock::create(['daily_boc_id' => $boc->id, 'date_boc' => $date, 'ticker' => 'BBGC', 'name' => 'BRIDGE BANK', 'price' => $prix, 'change' => null]);
    }

    private function encart(): ?string
    {
        $html  = $this->get('/welcome')->assertOk()->getContent();
        $debut = strpos($html, 'class="cb-cotation-sec"');

        return $debut === false ? null : substr($html, $debut, strpos($html, '</section>', $debut) - $debut);
    }

    public function test_shows_last_snapshot_price_and_variation_vs_ipo_price(): void
    {
        $this->travelTo('2026-09-25 16:00:00');
        $this->releve(7255.0);

        $encart = $this->encart();

        $this->assertNotNull($encart);
        $this->assertStringContainsString('Bridge Bank Group Côte d&#039;Ivoire', $encart);
        $this->assertStringContainsString('(BBGC)', $encart);
        $this->assertStringContainsString('7 255 F', $encart);
        $this->assertStringContainsString('+7,5 % vs OPV (6 750 F)', $encart);
        $this->assertStringContainsString('Dernier relevé BRVM : 25/09 à 15h40', $encart);
        $this->assertStringContainsString(route('societes.show', 'bridge-bank-ci'), $encart);
    }

    public function test_uses_boc_close_when_more_recent_than_snapshot(): void
    {
        $this->travelTo('2026-09-30 10:00:00');
        $this->releve(7255.0, '2026-09-25 15:40:00'); // relevé ancien (cron arrêté)
        $this->clotureBoc('2026-09-29', 6600.0);

        $encart = $this->encart();

        $this->assertStringContainsString('6 600 F', $encart);
        $this->assertStringContainsString('-2,2 % vs OPV', $encart);
        $this->assertStringContainsString('Clôture BOC du 29/09/2026', $encart);
    }

    public function test_without_any_price_shows_ipo_price(): void
    {
        $this->travelTo('2026-09-24 09:00:00');
        $this->releve(0.0); // pas encore d'échange : clôture à 0

        $encart = $this->encart();

        $this->assertStringContainsString('6 750 F', $encart);
        $this->assertStringContainsString("Prix de l'OPV · premier cours à venir", html_entity_decode($encart, ENT_QUOTES));
        $this->assertStringNotContainsString('vs OPV', $encart);
    }

    public function test_shown_until_end_date_included_then_hidden(): void
    {
        $this->releve(7255.0);

        $this->travelTo('2026-10-08 23:59:00');
        $this->assertNotNull($this->encart(), 'Encart attendu le dernier jour');

        $this->travelTo('2026-10-09 00:00:01');
        $this->assertNull($this->encart(), "L'encart doit disparaître après la date de fin");
    }

    public function test_hidden_before_first_listing_day(): void
    {
        $this->travelTo('2026-09-23 18:00:00');

        $this->assertNull($this->encart());
    }

    public function test_next_ipo_only_needs_a_config_entry(): void
    {
        config(['cotations.nouvelles' => [[
            'ticker' => 'XNEW', 'nom' => 'Nouvelle Société CI', 'prix_opv' => 1000,
            'premiere_cotation' => '2027-01-10', 'fin_affichage' => '2027-01-20', 'fiche' => 'orange-ci',
        ]]]);
        $this->travelTo('2027-01-12 10:00:00');

        $encart = $this->encart();

        $this->assertStringContainsString('Nouvelle Société CI', $encart);
        $this->assertStringContainsString('1 000 F', $encart);
        $this->assertStringNotContainsString('BBGC', $encart);
    }
}
