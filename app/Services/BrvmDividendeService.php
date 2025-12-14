<?php

namespace App\Services;

use App\Models\BrvmDividende;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class BrvmDividendeService
{
    public function extractFromBoc(?string $storagePath): array
    {
        Log::info('BrvmDividendeService: storagePath = ' . ($storagePath ?? 'NULL'));

        if (!$storagePath) return [];

        try {
            $fullPath = Storage::disk('public')->path($storagePath);
        } catch (\Throwable $e) {
            Log::error('BrvmDividendeService: Storage::path error: ' . $e->getMessage());
            return [];
        }

        if (!is_file($fullPath)) {
            Log::warning('BrvmDividendeService: file not found: ' . $fullPath);
            return [];
        }

        // 1) Parse PDF, pages 3-4 (index 2-3)
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);

            $pages = $pdf->getPages();
            $selectedText = '';

            foreach ([2, 3] as $i) {
                if (isset($pages[$i])) {
                    $selectedText .= $pages[$i]->getText() . "\n\n";
                }
            }

            $text = $selectedText !== '' ? $selectedText : $pdf->getText();
        } catch (\Throwable $e) {
            Log::error('BrvmDividendeService: PDF parse error: ' . $e->getMessage());
            return [];
        }

        // 2) OpenAI
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            Log::error('BrvmDividendeService: missing OPENAI_API_KEY');
            return [];
        }

        $systemPrompt = <<<SYS
Tu es un parseur expert des Bulletins Officiels de la Cote (BRVM).

On te fournit le texte brut des pages 3 et 4 du BOC, contenant le tableau des actions.
Tu dois extraire POUR CHAQUE SOCIÉTÉ les colonnes suivantes (si absentes => null) :

- "ticker" : symbole (ex: SODE, SNTS, SGBC)
- "name" : nom de la société
- "dividende_net" : Montant net du dernier dividende payé (nombre, ex: 721.60). Si "NC" ou vide => null
- "date_paiement" : Date du dernier paiement (format YYYY-MM-DD). Si non lisible => null
- "rendement_net" : Rdt. Net en % (nombre, ex: 6.26). Si absent => null
- "per" : PER (nombre, ex: 14.02). Si absent => null

Règles IMPORTANTES :
- Ignore les lignes TOTAL, titres, sous-titres (PRESTIGE/PRINCIPAL), entêtes.
- Ignore obligations/indices, uniquement le tableau actions pages 3-4.
- Convertis les virgules en points pour les nombres.
- Les dates: si tu vois "18-août-25" => "2025-08-18". Si format "18/08/2025" => "2025-08-18".
- Réponds UNIQUEMENT avec un JSON valide au format EXACT :

{
  "dividendes": [
    {
      "ticker": "SDCC",
      "name": "SODECI CI",
      "dividende_net": 721.60,
      "date_paiement": "2025-08-18",
      "rendement_net": 6.26,
      "per": 14.02
    }
  ]
}

Sans texte avant ni après.
SYS;

        $userPrompt = "Voici le texte brut des pages 3 et 4 (actions) du BOC :\n\n" . $text;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_DIVIDENDE_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.1,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (!$response->ok()) {
                Log::error('BrvmDividendeService: OpenAI HTTP error: ' . $response->status() . ' - ' . $response->body());
                return [];
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Log::error('BrvmDividendeService: OpenAI empty content');
                return [];
            }

            $json = json_decode($content, true);
            if (!is_array($json) || !isset($json['dividendes']) || !is_array($json['dividendes'])) {
                Log::error('BrvmDividendeService: invalid JSON returned: ' . $content);
                return [];
            }

            // normalize result
            $out = [];
            foreach ($json['dividendes'] as $row) {
                $ticker = trim((string)($row['ticker'] ?? ''));
                if ($ticker === '') continue;

                $out[] = [
                    'ticker' => $ticker,
                    'name' => (string)($row['name'] ?? $ticker),
                    'dividende_net' => $this->numOrNull($row['dividende_net'] ?? null),
                    'date_paiement' => $this->dateOrNull($row['date_paiement'] ?? null),
                    'rendement_net' => $this->numOrNull($row['rendement_net'] ?? null),
                    'per' => $this->numOrNull($row['per'] ?? null),
                ];
            }

            Log::info('BrvmDividendeService: rows extracted = ' . count($out));
            return $out;

        } catch (\Throwable $e) {
            Log::error('BrvmDividendeService: exception OpenAI: ' . $e->getMessage());
            return [];
        }
    }

    public function upsertFromBoc(string $storagePath, ?string $bocDate = null): int
    {
        $rows = $this->extractFromBoc($storagePath);
        if (!$rows) return 0;

        $count = 0;

        foreach ($rows as $r) {
            BrvmDividende::updateOrCreate(
                ['ticker' => $r['ticker']],
                [
                    'societe' => $r['name'],
                    'dividende_net' => $r['dividende_net'],
                    'date_paiement' => $r['date_paiement'],
                    'rendement_net' => $r['rendement_net'],
                    'per' => $r['per'],
                    'boc_date_reference' => $bocDate,
                    'source_boc' => $storagePath,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function numOrNull($v): ?float
    {
        if ($v === null) return null;
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '' || strtoupper($v) === 'NC') return null;
            $v = str_replace([' ', "\u{00A0}"], '', $v);
            $v = str_replace(',', '.', $v);
        }
        return is_numeric($v) ? (float)$v : null;
    }

    private function dateOrNull($v): ?string
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '' || strtoupper($v) === 'NC') return null;

        // accept already good YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;

        // accept dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // accept "18-août-25" like => map months FR
        $months = [
            'janv' => '01','janvier'=>'01',
            'févr' => '02','fevr'=>'02','février'=>'02','fevrier'=>'02',
            'mars'=>'03',
            'avr'=>'04','avril'=>'04',
            'mai'=>'05',
            'juin'=>'06',
            'juil'=>'07','juillet'=>'07',
            'août'=>'08','aout'=>'08',
            'sept'=>'09','septembre'=>'09',
            'oct'=>'10','octobre'=>'10',
            'nov'=>'11','novembre'=>'11',
            'déc'=>'12','dec'=>'12','décembre'=>'12','decembre'=>'12',
        ];

        // normalize accents
        $low = mb_strtolower($v);
        $low = str_replace(['é','è','ê','ë'], 'e', $low);
        $low = str_replace(['à','â'], 'a', $low);
        $low = str_replace(['î','ï'], 'i', $low);
        $low = str_replace(['ô'], 'o', $low);
        $low = str_replace(['û','ü'], 'u', $low);
        $low = str_replace(['ç'], 'c', $low);

        // dd-mois-yy or dd-mois-yyyy
        if (preg_match('/^(\d{1,2})[-\s]([a-z]+)[-\s](\d{2}|\d{4})$/', $low, $m)) {
            $dd = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mois = $m[2];
            $yy = $m[3];

            $mm = null;
            foreach ($months as $k => $val) {
                if (str_starts_with($mois, $k)) { $mm = $val; break; }
            }
            if (!$mm) return null;

            if (strlen($yy) === 2) $yy = '20' . $yy;

            return "{$yy}-{$mm}-{$dd}";
        }

        return null;
    }
}
