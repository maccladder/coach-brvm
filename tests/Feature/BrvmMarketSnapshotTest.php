<?php

namespace Tests\Feature;

use App\Services\BrvmMarketSnapshot;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrvmMarketSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_BRVM = '<table>'
        . '<tr><td>SNTS</td><td>SONATEL</td><td>1 200</td><td>25 000</td><td>25 100</td><td>25 300</td><td>1,20 %</td></tr>'
        . '<tr><td>BBGC</td><td>BRIDGE BANK GROUP COTE D\'IVOIRE</td><td>5 000</td><td>6 750</td><td>7 000</td><td>7 255</td><td>0,00 %</td></tr>'
        . '</table>';

    /** brvm.org ne répond pas : chaque appel échoue comme un timeout. */
    private function brvmEnPanne(): void
    {
        Http::fake(['www.brvm.org/*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);
    }

    public function test_welcome_never_calls_brvm_even_when_brvm_times_out(): void
    {
        $this->brvmEnPanne();
        Cache::forget(BrvmMarketSnapshot::CACHE_KEY);

        $debut = microtime(true);
        $this->get('/welcome')->assertOk();

        Http::assertNothingSent();
        $this->assertLessThan(3, microtime(true) - $debut);
    }

    public function test_welcome_ticker_shows_last_snapshot_without_calling_brvm(): void
    {
        Http::fake(['www.brvm.org/*' => Http::response(self::PAGE_BRVM)]);
        $this->artisan('brvm:refresh-market')->assertSuccessful();

        $this->brvmEnPanne();
        $this->get('/welcome')
            ->assertOk()
            ->assertSeeInOrder(['<span class="sym">BBGC</span>', '7 255 F'], false);

        Http::assertNothingSent();
    }

    public function test_refresh_keeps_previous_snapshot_when_brvm_fails(): void
    {
        Http::fake(['www.brvm.org/*' => Http::response(self::PAGE_BRVM)]);
        $this->artisan('brvm:refresh-market')->assertSuccessful();
        $avant = Cache::get(BrvmMarketSnapshot::CACHE_KEY);

        $this->brvmEnPanne();
        $this->artisan('brvm:refresh-market')->assertFailed();

        $this->assertSame($avant, Cache::get(BrvmMarketSnapshot::CACHE_KEY));
        $this->assertCount(2, app(BrvmMarketSnapshot::class)->rows());
    }

    public function test_refresh_is_scheduled_during_session_and_after_close_on_weekdays(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'brvm:refresh-market'));

        $this->assertEqualsCanonicalizing(
            ['*/10 * * * 1-5', '0 16 * * 1-5'],
            $events->pluck('expression')->all()
        );
        $events->each(fn ($e) => $this->assertSame('Africa/Abidjan', $e->timezone));
    }

    public function test_brvm_request_uses_8_second_timeout(): void
    {
        $options = null;
        Http::fake(function ($request, $opts) use (&$options) {
            $options = $opts;
            return Http::response(self::PAGE_BRVM);
        });

        app(BrvmMarketSnapshot::class)->refresh();

        $this->assertSame(8, $options['timeout']);
    }
}
