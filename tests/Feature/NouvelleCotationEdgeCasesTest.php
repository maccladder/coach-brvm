<?php

namespace Tests\Feature;

use App\Models\BocStock;
use App\Models\DailyBoc;
use App\Models\User;
use App\Models\VirtualPosition;
use App\Services\BrvmActionsAiService;
use App\Services\BrvmBubbleService;
use App\Services\BrvmMarketAiService;
use Database\Seeders\SocietesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cas limites d'une introduction en bourse (Bridge Bank / BBGC, 24/09/2026)
 * et exclusion d'une société radiée (MOVIS / SVOC).
 */
class NouvelleCotationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /** Ligne brvm.org de BBGC le jour de l'introduction, avant tout échange. */
    private const BBGC_JOUR_1 = [
        'ticker' => 'BBGC', 'name' => "BRIDGE BANK GROUP COTE D'IVOIRE",
        'volume' => null, 'prev' => 6750.0, 'open' => null, 'close' => null,
        'change' => 0.0, 'buy_price' => 6750.0,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(SocietesSeeder::class);

        $this->mock(BrvmActionsAiService::class, function ($mock) {
            $mock->shouldReceive('fetchMarketTableFromSite')->andReturn([self::BBGC_JOUR_1]);
        });
        // Bandeau du layout (TickerComposer) : passe par la sous-classe
        $this->partialMock(BrvmMarketAiService::class, function ($mock) {
            $mock->shouldReceive('fetchMarketTableFromSite')->andReturn([self::BBGC_JOUR_1]);
        });
    }

    private function bocDu(string $date, array $stocks): DailyBoc
    {
        $boc = DailyBoc::create(['date_boc' => $date, 'file_path' => "bocs/$date.pdf", 'original_name' => "$date.pdf"]);

        foreach ($stocks as $ticker => $change) {
            BocStock::create([
                'daily_boc_id' => $boc->id, 'date_boc' => $date, 'ticker' => $ticker,
                'name' => $ticker, 'price' => 1000, 'change' => $change,
            ]);
        }

        return $boc;
    }

    public function test_svoc_is_registered_as_delisted_by_migration(): void
    {
        $this->assertDatabaseHas('societes', ['code' => 'SVOC', 'is_listed' => false]);
    }

    public function test_delisted_svoc_is_excluded_from_radar_and_performances_but_history_is_kept(): void
    {
        $this->bocDu('2025-06-26', ['SVOC' => 7.5, 'SNTS' => 1.0]);
        $this->bocDu('2026-09-24', ['SVOC' => 9.0, 'SNTS' => 1.0, 'BBGC' => null, 'XNEW' => 2.0]);

        $bubbles = collect($this->getJson(route('radar.bubblesLatest'))->assertOk()->json('bubbles'))->pluck('ticker');
        $this->assertNotContains('SVOC', $bubbles);
        $this->assertContains('BBGC', $bubbles);
        $this->assertContains('XNEW', $bubbles); // ticker inconnu de societes : visible

        $companies = $this->get(route('radar.index'))->assertOk()->viewData('companies')->pluck('ticker');
        $this->assertNotContains('SVOC', $companies);
        $this->assertContains('BBGC', $companies);

        $top = collect($this->getJson(route('radar.data'))->assertOk()->json('datasets'))->pluck('label')->implode(' ');
        $this->assertStringNotContainsString('SVOC', $top);

        // Historique conservé et toujours consultable
        $this->assertSame(2, BocStock::where('ticker', 'SVOC')->count());
        $this->getJson('/api/stock/SVOC/history')->assertOk()->assertJsonPath('count', 2);
    }

    public function test_performance_data_keeps_null_change_as_gap(): void
    {
        $this->bocDu('2026-09-24', ['BBGC' => null]);

        $data = $this->getJson(route('radar.data', ['tickers' => ['BBGC']]))->assertOk()->json('datasets.0.data');
        $this->assertSame([null], $data);
    }

    public function test_history_with_single_point_is_served(): void
    {
        $this->bocDu('2026-09-24', ['BBGC' => 0.0]);

        $this->getJson('/api/stock/BBGC/history')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('first_date', '2026-09-24')
            ->assertJsonPath('last_date', '2026-09-24');
    }

    public function test_wallet_values_position_at_reference_price_before_first_trade(): void
    {
        $user = User::factory()->create();
        VirtualPosition::create([
            'user_id' => $user->id, 'ticker' => 'BBGC', 'name' => 'BRIDGE BANK',
            'qty' => 10, 'avg_price' => 6750,
        ]);

        $response = $this->actingAs($user)->get(route('wallet.index'))->assertOk();

        $this->assertEquals(67500, $response->viewData('totalValue'));
        $this->assertEquals(6750, $response->viewData('positions')[0]['price']);
    }

    public function test_ticker_banner_includes_stock_without_close(): void
    {
        Cache::forget('brvm_ticker');

        $this->get(route('societes.index'))->assertOk();

        $this->assertContains('BBGC', collect(Cache::get('brvm_ticker'))->pluck('ticker'));
        $this->assertEquals(6750, collect(Cache::get('brvm_ticker'))->firstWhere('ticker', 'BBGC')['close']);
    }

    public function test_boc_extraction_keeps_row_without_variation(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bocs/test.pdf', file_get_contents(storage_path('app/test.pdf')));
        config(['services.openai.key' => 'test']);

        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['stocks' => [
                ['ticker' => 'SNTS', 'name' => 'SONATEL', 'price' => 25000, 'change' => 1.2],
                ['ticker' => 'BBGC', 'name' => 'BRIDGE BANK', 'price' => 6750],
                ['ticker' => 'BOAC', 'name' => 'BOA CI', 'price' => 'NC', 'change' => 'NC'],
            ]])]]],
        ])]);

        $rows = collect(app(BrvmBubbleService::class)->extractFromBoc('bocs/test.pdf'))->keyBy('ticker');

        $this->assertCount(3, $rows);
        $this->assertNull($rows['BBGC']['change']);
        $this->assertSame(6750.0, $rows['BBGC']['price']);
        $this->assertNull($rows['BOAC']['price']);
        $this->assertNull($rows['BOAC']['change']);
        $this->assertSame(1.2, $rows['SNTS']['change']);
    }
}
