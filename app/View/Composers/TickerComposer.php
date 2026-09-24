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
                // close vide (pas encore d'échange, ex. 1er jour de cotation) :
                // repli sur buy_price (ouverture, sinon cours de référence veille)
                return collect($rows)
                    ->map(fn($s) => $s + ['display' => ($s['close'] ?? null) ?: ($s['buy_price'] ?? null)])
                    ->filter(fn($s) => !empty($s['ticker']) && !empty($s['display']))
                    ->map(fn($s) => [
                        'ticker' => $s['ticker'],
                        'close'  => (float) $s['display'],
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
