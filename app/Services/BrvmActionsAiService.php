<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrvmActionsAiService
{
    /**
     * Récupère les actions BRVM (table "Toutes") sans IA
     */
    public function fetchMarketTableFromSite(): array
{
    $url = 'https://www.brvm.org/fr/cours-actions/0';

    try {
        $resp = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Boursiv/1.0 (+https://boursiv.com)',
            ])
            ->withOptions([
                'verify' => false, // ❌ désactive SSL PARTOUT (local + prod)
            ])
            ->get($url);

        if (!$resp->ok()) {
            Log::error('BRVM HTTP error', ['status' => $resp->status()]);
            return [];
        }

        $html = (string) $resp->body();
    } catch (\Throwable $e) {
        Log::error('BRVM fetch exception', ['msg' => $e->getMessage()]);
        return [];
    }

    // 1️⃣ extraire tous les <tr>
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $matches);
    $rows = $matches[0] ?? [];

    // 2️⃣ garder uniquement les lignes avec ticker (ABJC, BOABF, etc.)
    $rows = array_values(array_filter($rows, function ($tr) {
        return preg_match('/<td[^>]*>\s*[A-Z]{3,6}\s*<\/td>/si', $tr);
    }));

    if (empty($rows)) {
        Log::warning('BRVM: aucune ligne action détectée');
        return [];
    }

    $stocks = [];

    foreach ($rows as $tr) {
        // extraire les <td>
        preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tr, $tds);
        $cells = $tds[1] ?? [];

        // nettoyage
        $cells = array_map(function ($c) {
            $c = strip_tags($c);
            $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $c = trim(preg_replace('/\s+/u', ' ', $c));
            return $c;
        }, $cells);

        /**
         * Structure BRVM actuelle :
         * 0 => Ticker
         * 1 => Nom
         * 2 => Volume
         * 3 => Cours veille
         * 4 => Cours ouverture
         * 5 => Cours clôture
         * 6 => Variation
         */
        if (count($cells) < 6) {
            continue;
        }

        $ticker = $cells[0] ?? null;
        if (!$ticker) {
            continue;
        }

        $name = $cells[1] ?? $ticker;

        $toNumber = function ($v, bool $percent = false) {
            if (!$v || strtoupper($v) === 'NC' || $v === '-') {
                return null;
            }
            $v = str_replace(' ', '', $v);
            $v = str_replace(',', '.', $v);
            $v = str_replace('%', '', $v);
            return is_numeric($v) ? (float) $v : null;
        };

        $volume = $toNumber($cells[2] ?? null);
        $prev   = $toNumber($cells[3] ?? null);
        $open   = $toNumber($cells[4] ?? null);
        $close  = $toNumber($cells[5] ?? null);
        $change = $toNumber($cells[6] ?? null, true);

        // règle prix d'achat
        $buyPrice = ($open > 0) ? $open : (($close > 0) ? $close : $prev);

        $stocks[] = [
            'ticker'    => strtoupper($ticker),
            'name'      => $name,
            'volume'    => $volume,
            'prev'      => $prev,
            'open'      => $open,
            'close'     => $close,
            'change'    => $change,
            'buy_price' => $buyPrice,
        ];
    }

    // tri stable
    usort($stocks, fn ($a, $b) => strcmp($a['ticker'], $b['ticker']));

    return $stocks;
}

}
