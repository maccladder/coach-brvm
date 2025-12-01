<?php

namespace App\Services;

use Log;
use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;

class AiInterpreter
{
    public function __construct(
        private ?Client $http = null,
        private ?Parser $pdfParser = null,
    ) {
        $this->http = $this->http ?: new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 60,
        ]);
        $this->pdfParser = $this->pdfParser ?: new Parser();
    }

    /**
     * @param  array{title:string,file_path:?string,notes:?string}[] $analyses
     * @param  array{issuer:string,period:string,statement_type:string,file_path:?string}[] $statements
     * @return string  Markdown d’interprétation
     */
    public function interpret(array $analyses, array $statements, string $targetDate): string
    {
        // 1) Extraire du texte des PDFs (on tronque pour rester léger)
        $texts = [];

        foreach ($analyses as $a) {
            $snippet = $this->extractTextFromStoragePdf($a['file_path']);
            $texts[] = "# Analyse : {$a['title']}\n" .
                       ($a['notes'] ? "Notes: {$a['notes']}\n" : "") .
                       ($snippet ? "Extrait:\n{$snippet}\n" : "");
        }

        foreach ($statements as $s) {
            $snippet = $this->extractTextFromStoragePdf($s['file_path']);
            $label = match ($s['statement_type']) {
                'income'  => 'Compte de résultat',
                'balance' => 'Bilan',
                'cashflow'=> 'Flux de trésorerie',
                default   => ucfirst($s['statement_type']),
            };
            $texts[] = "# État financier : {$s['issuer']} ({$s['period']}) – {$label}\n" .
                       ($snippet ? "Extrait:\n{$snippet}\n" : "");
        }

        if (empty($texts)) {
            return "_Aucune interprétation car aucun document n’a été importé à la date du {$targetDate}._";
        }

        // On limite la taille totale (sécurité)
        $joined = mb_substr(implode("\n\n----\n\n", $texts), 0, 6000);

        // 2) Appel au modèle IA (OpenAI)

        $promptSystem = <<<SYS
Tu es un coach d’investissement spécialisé sur la BRVM.
Tu t’adresses à un investisseur particulier ivoirien qui connaît les bases (action, dividende, rendement)
mais veut des conseils concrets et compréhensibles.

OBJECTIF :
- À partir du résumé brut de la séance (analyses importées, statistiques de marché, états financiers),
  tu dois :
  - résumer les infos clés,
  - expliquer clairement ce que cela peut signifier pour un investisseur à court, moyen et long terme,
  - proposer des pistes d’actions possibles (ex : « renforcer progressivement », « alléger », « attendre
    une meilleure fenêtre », « rester en observation »), en restant prudent.

CONTRAINTES :
- Ne donne jamais de certitudes ni de promesses de gains.
- Parle toujours en termes de scénarios possibles et de probabilité.
- N’invente pas de chiffres ; si une info n’apparaît pas dans les extraits, dis-le simplement.
- Ajoute toujours un rappel en fin de texte du type :
  « Ceci n’est pas un conseil d’investissement personnalisé ; faites vos propres vérifications. »

STRUCTURE ATTENDUE (en Markdown) :
1. **Résumé rapide du marché** (3 à 5 puces)
2. **Lecture pour l’investisseur**
   - Court terme (0–3 mois)
   - Moyen terme (3–12 mois)
   - Long terme (12+ mois)
3. **Idées d’actions possibles**
   Liste numérotée de 3 à 6 idées concrètes mais prudentes.
4. **Points de vigilance / risques**
5. **Rappel**
SYS;

        $promptUser = <<<USR
Date analysée : {$targetDate}

Voici des extraits des documents importés (analyses & états financiers).
Utilise uniquement ces infos et ton expérience générale des marchés émergents pour produire le texte
selon la structure demandée dans le message système.

Ne mentionne pas les fichiers ni les extraits dans ta réponse, parle directement à l’investisseur
comme si tu lui faisais un débrief oral de la séance BRVM du jour.

Docs :
{$joined}
USR;

        try {
            $resp = $this->http->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'gpt-4o-mini',
                    'temperature' => 0.4,
                    'messages'    => [
                        ['role' => 'system', 'content' => $promptSystem],
                        ['role' => 'user',   'content' => $promptUser],
                    ],
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            $text = $data['choices'][0]['message']['content'] ?? '';

            if (!trim($text)) {
                return "_Interprétation IA indisponible pour le moment._";
            }

            return $text;
        } catch (\Throwable $e) {
            Log::warning('AI error: '.$e->getMessage());
            return "_Interprétation IA indisponible (erreur technique)._";
        }
    }

    public function interpretFinancial(array $meta): string
{
    // $meta['file'] = chemin du PDF/Excel
    // $meta['company'], $meta['period']

    // Ici tu fais :
    // 1) lecture du fichier
    // 2) prompt spécial qui demande :
    //    - chiffre d’affaires
    //    - bénéfice net
    //    - capacité d’autofinancement
    //    - dettes
    //    - trésorerie
    // 3) retour en markdown structuré

    // Pour l’instant tu peux juste mettre un stub de test :
    return "### Analyse des états financiers de {$meta['company']} ({$meta['period']})\n\n".
           "- Chiffre d’affaires : …\n".
           "- Bénéfice net : …\n".
           "- Capacité d’autofinancement : …\n".
           "- Dettes : …\n".
           "- Trésorerie : …\n";
}

    private function extractTextFromStoragePdf(?string $storagePath): ?string
    {
        if (!$storagePath) return null;

        $full = storage_path('app/public/' . ltrim($storagePath, '/'));
        if (!is_file($full)) return null;

        try {
            $pdf  = $this->pdfParser->parseFile($full);
            $text = trim($pdf->getText());
            // On normalise et on tronque (1 500 chars)
            $text = preg_replace('/[ \t]+/', ' ', $text);

            return mb_substr($text, 0, 1500);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
