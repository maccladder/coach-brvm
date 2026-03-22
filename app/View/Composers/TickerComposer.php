<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use App\Services\BrvmMarketAiService;

class TickerComposer
{
    public function __construct(protected BrvmMarketAiService $svc) {}

    public function compose(View $view): void
    {
        // ✅ Cache 15 min pour ne pas appeler brvm.org à chaque page
        $tickerData = Cache::remember('brvm_ticker', 900, function () {
            try {
                $rows = $this->svc->fetchCloseAndChangeFromSite();
                return collect($rows)
                    ->filter(fn($s) => !empty($s['ticker']) && !empty($s['close']))
                    ->map(fn($s) => [
                        'ticker' => $s['ticker'],
                        'close'  => (float) $s['close'],
                        'change' => $s['change'] !== null ? (float) $s['change'] : null,
                    ])
                    ->values()
                    ->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });

        $view->with('tickerData', $tickerData);
    }
}
