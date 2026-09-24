<?php

namespace App\View\Composers;

use App\Services\BrvmMarketSnapshot;
use App\Services\CoursBrvm;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TickerComposer
{
    public function __construct(
        protected BrvmMarketSnapshot $snapshot,
        protected CoursBrvm $cours,
    ) {}

    public function compose(View $view): void
    {
        // Lecture seule du dernier relevé (tâche planifiée brvm:refresh-market) :
        // aucune page n'attend brvm.org. Mis en forme une fois par relevé.
        $cle = 'brvm_bandeau:' . ($this->snapshot->fetchedAt()?->timestamp ?? 'aucun') . ':' . today()->toDateString();

        $tickerData = Cache::remember($cle, 900, fn () => collect($this->cours->enrichir($this->snapshot->rows()))
            // Même règle que /marche-en-direct : cours = clôture → ouverture → clôture préc. ;
            // variation officielle (calculée sur l'OPV le 1er jour d'une cotation)
            ->filter(fn ($s) => !empty($s['ticker']) && !empty($s['cours']))
            ->map(fn ($s) => [
                'ticker' => $s['ticker'],
                'close'  => (float) $s['cours'],
                'change' => $s['variation'],
            ])
            ->values()
            ->toArray());

        $view->with('tickerData', $tickerData);
    }
}
