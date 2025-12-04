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
                'income'   => 'Compte de résultat',
                'balance'  => 'Bilan',
                'cashflow' => 'Flux de trésorerie',
                default    => ucfirst($s['statement_type']),
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
Tu t’adresses à un investisseur particulier ivoirien qui connaît les bases
(action, dividende, rendement) et veut un débrief TRÈS CONCIS de la séance.

OBJECTIF :
À partir du résumé brut de la séance (analyses importées, statistiques de marché,
éventuels tableaux de variations, états financiers), tu dois surtout faire ressortir :

1) Le **Top des fortes hausses**
2) Le **Top des fortes baisses**
3) Des **commentaires synthétiques** sur ces hausses et baisses
   (secteurs concernés, explications possibles, points de vigilance).

CONTRAINTES :
- Ne donne jamais de certitudes ni de promesses de gains.
- N’invente pas de chiffres précis (cours, %…) :
  - Si la variation exacte apparaît dans les documents, tu peux la reprendre.
  - Sinon, parle de manière qualitative (« légère hausse », « forte baisse », etc.).
- Si tu n’as pas assez d’informations pour identifier un top hausses/baisses fiable,
  dis-le clairement au lieu d’inventer.
- Réponse courte : vise environ **250 à 400 mots** maximum.
- Pas de bla-bla inutile ni de redites.

STRUCTURE ATTENDUE (en Markdown) :

1. **Top des fortes hausses**
   - Liste 3 à 5 valeurs maximum.
   - Format conseillé : `- TICKER – Nom : commentaire très court (+X % si l’info existe).`
   - Si tu n’as pas d’information fiable, écris :
     « Aucune liste fiable des plus fortes hausses n’est disponible dans les documents fournis. »

2. **Top des fortes baisses**
   - Même principe que pour les hausses.
   - Si tu n’as pas d’information fiable, écris :
     « Aucune liste fiable des plus fortes baisses n’est disponible dans les documents fournis. »

3. **Commentaires sur les mouvements du jour**
   - 3 à 6 puces maximum qui expliquent :
     - Ce que montrent ces hausses/baisses (secteurs, tendance du marché…)
     - Ce qu’un investisseur doit surveiller (risques, volatilité, niveaux élevés, etc.)
     - Éventuellement des idées générales d’attitude (rester patient, observer, éviter de paniquer…),
       sans donner d’ordre d’achat ou de vente personnalisé.

4. **Rappel**
   - Termine toujours par une ligne du style :
     « Ceci n’est pas un conseil d’investissement personnalisé ; faites vos propres vérifications. »
SYS;

        $promptUser = <<<USR
Date analysée : {$targetDate}

Voici des extraits des documents importés (analyses, états financiers, tableaux éventuels du BOC).
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
                    // Modèle configurable via .env
                    // (chez toi : OPENAI_MARKET_MODEL=gpt-4.1-mini)
                    'model'       => env('OPENAI_MARKET_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.4,
                    'max_tokens'  => 900, // plus court qu’avant
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
        return "### Analyse des états financiers de {$meta['company']} ({$meta['period']})\n\n" .
               "- Chiffre d’affaires : …\n" .
               "- Bénéfice net : …\n" .
               "- Capacité d’autofinancement : …\n" .
               "- Dettes : …\n" .
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
            $text = preg_replace('/[ \t]+/', ' ', $text);

            // On tronque à ~1500 caractères
            return mb_substr($text, 0, 1500);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
