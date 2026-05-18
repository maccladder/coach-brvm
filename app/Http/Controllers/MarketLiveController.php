<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Services\BrvmActionsAiService;

class MarketLiveController extends Controller
{
    public function index(BrvmActionsAiService $svc)
    {
        $stocks   = [];
        $error    = null;
        $loadedAt = now()->setTimezone('Africa/Abidjan')->format('H:i:s');

        try {
            $stocks = $svc->fetchMarketTableFromSite();

            if (empty($stocks)) {
                $error = 'Les cours BRVM ne sont pas disponibles pour le moment. Réessayez dans quelques instants.';
            } else {
                // tri par variation décroissante, nulls en dernier
                usort($stocks, function ($a, $b) {
                    $ca = $a['change'] ?? null;
                    $cb = $b['change'] ?? null;
                    if ($ca === null && $cb === null) return 0;
                    if ($ca === null) return 1;
                    if ($cb === null) return -1;
                    return $cb <=> $ca;
                });
            }
        } catch (\Throwable $e) {
            Log::error('MarketLive scraping error', ['msg' => $e->getMessage()]);
            $error = 'Erreur lors de la récupération des cours BRVM. Réessayez dans quelques instants.';
        }

        return view('market.live', [
            'stocks'   => $stocks,
            'error'    => $error,
            'loadedAt' => $loadedAt,
        ]);
    }
}
