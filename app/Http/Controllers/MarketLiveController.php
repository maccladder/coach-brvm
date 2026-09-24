<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\CoursBrvm;

class MarketLiveController extends Controller
{
    public function index(CoursBrvm $cours)
    {
        $stocks   = [];
        $error    = null;
        $majSite  = null;
        $loadedAt = now()->setTimezone('Africa/Abidjan')->format('H:i:s');

        try {
            // cours / clôture préc. / variation selon la règle CoursBrvm
            $stocks = $cours->marcheEnDirect();

            if (empty($stocks)) {
                $error = 'Les cours BRVM ne sont pas disponibles pour le moment. Réessayez dans quelques instants.';
            } else {
                $maj     = $stocks[0]['maj'] ?? null;
                $majSite = $maj ? Carbon::parse($maj)->format('H\hi') : null;

                // tri par variation décroissante, nulls en dernier
                usort($stocks, function ($a, $b) {
                    $ca = $a['variation'] ?? null;
                    $cb = $b['variation'] ?? null;
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
            'majSite'  => $majSite,
        ]);
    }
}
