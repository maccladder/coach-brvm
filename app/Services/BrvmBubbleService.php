<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class BrvmBubbleService
{
    public function extractFromBoc(?string $storagePath): array
    {
        Log::info('BrvmBubbleService: storagePath = ' . ($storagePath ?? 'NULL'));

        if (!$storagePath) {
            Log::warning('BrvmBubbleService: aucun chemin de fichier fourni');
            return [];
        }

        // 1️⃣ Récupérer le vrai chemin du PDF sur le disk "public"
        try {
            $fullPath = Storage::disk('public')->path($storagePath);
        } catch (\Throwable $e) {
            Log::error('BrvmBubbleService: erreur Storage::path : ' . $e->getMessage());
            return [];
        }

        if (!is_file($fullPath)) {
            Log::warning('BrvmBubbleService: fichier introuvable : ' . $fullPath);
            return [];
        }

        // 2️⃣ Extraire le texte du PDF
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($fullPath);

            // 👉 On essaie de ne garder que les pages 3 et 4 (index 2 et 3)
            $pages        = $pdf->getPages();
            $selectedText = '';

            foreach ([2, 3] as $i) {
                if (isset($pages[$i])) {
                    $selectedText .= $pages[$i]->getText() . "\n\n";
                }
            }

            // Si jamais on n'arrive pas à isoler, on retombe sur tout le texte
            $text = $selectedText !== '' ? $selectedText : $pdf->getText();

        } catch (\Throwable $e) {
            Log::error('BrvmBubbleService: erreur parsing PDF : ' . $e->getMessage());
            return [];
        }

        // 3️⃣ Appel OpenAI pour structurer le tableau
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            Log::error('BrvmBubbleService: pas de OPENAI_API_KEY dans .env');
            return [];
        }

        $systemPrompt = <<<SYS
Tu es un parseur de Bulletins Officiels de la Cote (BRVM).

On te fournit le texte (OCR) des pages 3 et 4 d'un BOC,
qui contiennent le "TABLEAU DES VARIATIONS DES SOCIÉTÉS"
(les actions du compartiment PRESTIGE + PRINCIPAL).

À partir de ce texte, tu dois reconstruire **une ligne par société** du tableau
des variations, sans en oublier. Pour chaque société, tu renvoies :

- "ticker"  : symbole de la société (ex: "SOGC", "BOAB").
- "name"    : nom de la société (ex: "SOGB CI", "BOA BÉNIN").
- "price"   : cours du jour / cours de clôture (nombre, sans séparateur de milliers).
              Si le tableau affiche "NC" ou qu'il manque, mets null.
- "change"  : variation du jour en pourcentage (nombre, ex: -1.45 pour -1,45 %).

IMPORTANT :
- Tu dois renvoyer **toutes** les sociétés présentes dans le tableau,
  même si certains champs sont "NC".
- Tu ignores les lignes de totaux ("TOTAL"), les titres de colonnes,
  et les sous-titres comme "COMPARTIMENT PRESTIGE", "COMPARTIMENT PRINCIPAL".
- Ne renvoie pas les obligations, indices, ou autres tableaux : uniquement les actions.

↪ TRÈS IMPORTANT :
Réponds **uniquement** avec un JSON valide exactement de la forme :

{
  "stocks": [
    {
      "ticker": "SOGC",
      "name": "SOGB CI",
      "price": 7975,
      "change": 0.82
    }
  ]
}

Sans texte avant ni après.
SYS;

        $userPrompt = "Voici le texte brut des pages actions du BOC :\n\n" . $text;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => config('services.openai.bubble_model', 'gpt-4.1-mini'),
                    'temperature' => 0.2,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);

            if (!$response->ok()) {
                Log::error('BrvmBubbleService: erreur HTTP OpenAI : '
                    . $response->status() . ' - ' . $response->body());
                return [];
            }

            $body    = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Log::error('BrvmBubbleService: réponse OpenAI sans contenu');
                return [];
            }

            $json = json_decode($content, true);
            if (!is_array($json) || !isset($json['stocks']) || !is_array($json['stocks'])) {
                Log::error('BrvmBubbleService: JSON retourné invalide : ' . $content);
                return [];
            }

            $results = [];
            foreach ($json['stocks'] as $row) {
                if (empty($row['ticker']) || !array_key_exists('change', $row)) {
                    continue;
                }

                $results[] = [
                    'ticker' => (string) $row['ticker'],
                    'name'   => (string) ($row['name'] ?? $row['ticker']),
                    'price'  => isset($row['price']) ? (float) $row['price'] : null,
                    'change' => (float) $row['change'],
                ];
            }

            Log::info('BrvmBubbleService: actions extraites (GPT-x) = ' . count($results));

            return $results;

        } catch (\Throwable $e) {
            Log::error('BrvmBubbleService: exception OpenAI : ' . $e->getMessage());
            return [];
        }
    }
}
