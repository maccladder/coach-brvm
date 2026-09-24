<?php

namespace App\View\Composers;

use App\Services\BrvmMarketSnapshot;
use Illuminate\View\View;

class TickerComposer
{
    public function __construct(protected BrvmMarketSnapshot $snapshot) {}

    public function compose(View $view): void
    {
        // Lecture seule du dernier relevé (tâche planifiée brvm:refresh-market) :
        // aucune page n'attend brvm.org.
        $tickerData = collect($this->snapshot->rows())
            // close vide ou 0 (pas encore d'échange, ex. 1er jour de cotation) :
            // repli sur buy_price (ouverture, sinon cours de référence veille)
            ->map(fn ($s) => $s + ['display' => ($s['close'] ?? null) ?: ($s['buy_price'] ?? null)])
            ->filter(fn ($s) => !empty($s['ticker']) && !empty($s['display']))
            ->map(fn ($s) => [
                'ticker' => $s['ticker'],
                'close'  => (float) $s['display'],
                'change' => ($s['change'] ?? null) !== null ? (float) $s['change'] : null,
            ])
            ->values()
            ->toArray();

        $view->with('tickerData', $tickerData);
    }
}
