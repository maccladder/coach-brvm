<?php

namespace Tests\Feature;

use App\Services\BrvmActionsAiService;
use App\Services\BrvmMarketAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminVirtualWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // bandeau du layout : pas d'appel à brvm.org

        $marche = [
            ['ticker' => 'SNTS', 'name' => 'SONATEL', 'volume' => 10, 'prev' => 25000.0,
             'open' => 25000.0, 'close' => 25100.0, 'change' => 0.4, 'buy_price' => 25000.0],
        ];

        // Le contrôleur passe une instance de la sous-classe à AdminMarketController::api()
        $this->partialMock(BrvmMarketAiService::class, fn ($mock) => $mock
            ->shouldReceive('fetchMarketTableFromSite')->andReturn($marche));
        $this->mock(BrvmActionsAiService::class, fn ($mock) => $mock
            ->shouldReceive('fetchMarketTableFromSite')->andReturn($marche));
    }

    public function test_admin_wallet_page_renders_and_links_to_market_page(): void
    {
        $this->withSession([
            'is_admin'       => true,
            'virtual_wallet' => [
                'cash'      => 1_000_000,
                'history'   => [],
                'positions' => ['SNTS' => ['qty' => 2, 'avg' => 25000, 'name' => 'SONATEL']],
            ],
        ])
            ->get(route('admin.wallet.index'))
            ->assertOk()
            ->assertSee(route('admin.market.index'), false)
            ->assertViewHas('totalValue', 50200.0);
    }

    public function test_admin_wallet_requires_admin_session(): void
    {
        $this->get(route('admin.wallet.index'))->assertRedirect(route('admin.login.form'));
    }
}
