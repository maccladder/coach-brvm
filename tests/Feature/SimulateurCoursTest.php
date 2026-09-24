<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VirtualPosition;
use App\Models\VirtualWallet;
use App\Models\VirtualWalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Simulateur : achat et vente au dernier cours (clôture → ouverture → veille),
 * sur les vraies lignes brvm.org du 24/09/2026 à 12:06.
 * LNBB : ouverture 3 705, clôture 3 975.
 */
class SimulateurCoursTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-24 12:10:00');
        Http::fake(['www.brvm.org/*' => Http::response(file_get_contents(base_path('tests/Fixtures/brvm_cours_2026-09-24_1206.html')))]);

        $this->user = User::factory()->create();
        VirtualWallet::create(['user_id' => $this->user->id, 'balance' => 1_000_000]);
    }

    public function test_buy_recap_uses_last_price_not_opening_and_shows_its_time(): void
    {
        $this->actingAs($this->user)
            ->get(route('wallet.buy.recap', ['ticker' => 'LNBB', 'qty' => 2]))
            ->assertOk()
            ->assertViewHas('price', 3975.0)                // et non 3 705 (ouverture)
            ->assertSee('3 975 FCFA')
            ->assertSee('brvm.org, mis à jour le 24/09 à 12h06');
    }

    public function test_buy_executes_at_last_price_and_records_its_time(): void
    {
        $this->actingAs($this->user)
            ->post(route('wallet.buy'), ['ticker' => 'LNBB', 'qty' => 2])
            ->assertRedirect(route('wallet.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'au cours de 3 975 FCFA (brvm.org, 24/09 à 12h06)'));

        $tx = VirtualWalletTransaction::where('user_id', $this->user->id)->where('type', 'buy')->firstOrFail();
        $this->assertEquals(3975, $tx->price);
        $this->assertSame('2026-09-24T12:06:00+00:00', $tx->meta['cours_maj']);
        $this->assertEquals(3975, VirtualPosition::where('user_id', $this->user->id)->value('avg_price'));
    }

    public function test_buy_and_sell_use_the_same_price(): void
    {
        $this->actingAs($this->user)->post(route('wallet.buy'), ['ticker' => 'SOGC', 'qty' => 3]);
        $this->actingAs($this->user)->post(route('wallet.sell'), ['ticker' => 'SOGC', 'qty' => 3])
            ->assertSessionMissing('error');

        $prix = VirtualWalletTransaction::where('user_id', $this->user->id)->where('ticker', 'SOGC')
            ->orderBy('id')->pluck('price', 'type')->map(fn ($p) => (float) $p)->all();

        $this->assertSame(['buy' => 7650.0, 'sell' => 7650.0], $prix); // clôture (ouverture : 7 310)
    }

    public function test_bbgc_first_day_bought_at_last_price(): void
    {
        $this->actingAs($this->user)->post(route('wallet.buy'), ['ticker' => 'BBGC', 'qty' => 1]);

        $this->assertEquals(7255, VirtualWalletTransaction::where('ticker', 'BBGC')->value('price'));
    }

    public function test_wallet_list_and_valuation_use_last_price(): void
    {
        VirtualPosition::create(['user_id' => $this->user->id, 'ticker' => 'PALC', 'name' => 'PALM CI', 'qty' => 10, 'avg_price' => 8000]);

        $this->actingAs($this->user)->get(route('wallet.index'))
            ->assertOk()
            ->assertViewHas('totalValue', 83000.0)                 // 10 × 8 300 (clôture), pas 8 100 (ouverture)
            ->assertSee('PALC — PALM COTE D&#039;IVOIRE', false)
            ->assertSee('(8 300 F)', false);
    }
}
