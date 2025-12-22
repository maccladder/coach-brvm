<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrvmActionsAiService
{
    /**
     * Extrait le tableau "Toutes" (actions) depuis la page BRVM
     * Retourne: ticker, name, prev, open, close, change
     */
    public function fetchMarketTableFromSite(): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            Log::error('BrvmActionsAiService: OPENAI_API_KEY manquant');
            return [];
        }

        $url = 'https://www.brvm.org/fr/cours-actions/0';

        // 1) Fetch HTML
        try {
            $http = Http::timeout(30)->withHeaders([
                'User-Agent' => 'CoachBRVM/1.0 (+https://coach-brvm.com)',
            ]);

            // ✅ local WAMP only
            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }

            $page = $http->get($url);

            if (!$page->ok()) {
                Log::error("BrvmActionsAiService: BRVM HTTP {$page->status()}");
                return [];
            }

            $html = (string) $page->body();
        } catch (\Throwable $e) {
            Log::error('BrvmActionsAiService: erreur fetch BRVM: ' . $e->getMessage());
            return [];
        }

        $htmlSlice = mb_substr($html, 0, 140000);

        // 2) Prompt IA
        $system = <<<SYS
Tu reçois le HTML de la page BRVM "Cours actions - Toutes".
Tu dois extraire une ligne par action (tableau "Toutes") avec EXACTEMENT ces champs :

- ticker : symbole (ex: ABJC)
- name   : nom complet
- prev   : cours veille (FCFA) nombre sans séparateur, ou null
- open   : cours ouverture (FCFA) nombre sans séparateur, ou null
- close  : cours clôture (FCFA) nombre sans séparateur, ou null
- change : variation (%) nombre (ex: -4.73), ou null

Règles:
- Ignore la colonne Volume (ne pas renvoyer volume).
- Normalise les nombres:
  - "3 020" => 3020
  - "-4,73" => -4.73
  - "NC" / vide => null
- Réponds UNIQUEMENT avec un JSON valide, rien d'autre:

{"stocks":[{"ticker":"ABJC","name":"SERVAIR ABIDJAN COTE D'IVOIRE","prev":3060,"open":3170,"close":3020,"change":-4.73}]}
SYS;

        $user = "HTML BRVM (extrait) :\n\n" . $htmlSlice;

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_BUBBLE_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.1,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if (!$resp->ok()) {
                Log::error('BrvmActionsAiService: OpenAI error ' . $resp->status() . ' ' . $resp->body());
                return [];
            }

            $content = $resp->json('choices.0.message.content');
            if (!$content) {
                Log::error('BrvmActionsAiService: contenu vide');
                return [];
            }

            $json = json_decode($content, true);
            if (!is_array($json) || !isset($json['stocks']) || !is_array($json['stocks'])) {
                Log::error('BrvmActionsAiService: JSON invalide: ' . $content);
                return [];
            }

            // 3) Nettoyage + prix_achat calculé
            $out = [];
            foreach ($json['stocks'] as $r) {
                if (empty($r['ticker'])) continue;

                $prev  = array_key_exists('prev', $r)  ? ($r['prev']  !== null ? (float)$r['prev']  : null) : null;
                $open  = array_key_exists('open', $r)  ? ($r['open']  !== null ? (float)$r['open']  : null) : null;
                $close = array_key_exists('close', $r) ? ($r['close'] !== null ? (float)$r['close'] : null) : null;
                $chg   = array_key_exists('change', $r)? ($r['change']!== null ? (float)$r['change']: null) : null;

                // Prix utilisé pour acheter (open si > 0 sinon close sinon prev)
                $buyPrice = null;
                if ($open !== null && $open > 0) $buyPrice = $open;
                elseif ($close !== null && $close > 0) $buyPrice = $close;
                elseif ($prev !== null && $prev > 0) $buyPrice = $prev;

                $out[] = [
                    'ticker' => strtoupper(trim((string)$r['ticker'])),
                    'name'   => trim((string)($r['name'] ?? $r['ticker'])),
                    'prev'   => $prev,
                    'open'   => $open,
                    'close'  => $close,
                    'change' => $chg,
                    'buy_price' => $buyPrice,
                ];
            }

            return $out;

        } catch (\Throwable $e) {
            Log::error('BrvmActionsAiService: exception ' . $e->getMessage());
            return [];
        }
    }
}
