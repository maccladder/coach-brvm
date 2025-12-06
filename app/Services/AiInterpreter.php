<?php

namespace App\Services;

use Imagick;
use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;
// Facultatif : si tu installes le package pour l'OCR
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

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
     * BOC / marché du jour
     *
     * @param  array{title:string,file_path:?string,notes:?string}[] $analyses
     * @param  array{issuer:string,period:string,statement_type:string,file_path:?string}[] $statements
     * @return string  Markdown d’interprétation
     */
    public function interpret(array $analyses, array $statements, string $targetDate): string
    {
        // 1) Extraire du texte des PDFs (on tronque pour rester léger)
        $texts = [];

        foreach ($analyses as $a) {
            // ⚠️ BOC : on garde l’ancien extracteur tel quel
            $snippet = $this->extractTextFromStoragePdf($a['file_path']);
            $texts[] = "# Analyse : {$a['title']}\n" .
                       ($a['notes'] ? "Notes: {$a['notes']}\n" : "") .
                       ($snippet ? "Extrait:\n{$snippet}\n" : "");
        }

        foreach ($statements as $s) {
            // ⚠️ BOC : idem, pas de changement
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

        // 2) Appel au modèle IA (OpenAI) pour le marché du jour

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
                    'model'       => env('OPENAI_MARKET_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.4,
                    'max_tokens'  => 900,
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
            Log::warning('AI error (BOC): '.$e->getMessage());
            return "_Interprétation IA indisponible (erreur technique)._";
        }
    }

    /**
     * Analyse d’un état financier unique (module bilan/états que tu viens de créer)
     *
     * $meta attendu :
     * - company    : nom de la société
     * - period     : période / exercice
     * - file_path  : chemin du fichier dans storage/app/public (optionnel mais recommandé)
     */
    public function interpretFinancial(array $meta): string
    {
        $company   = $meta['company']   ?? 'l’entreprise';
        $period    = $meta['period']    ?? 'période inconnue';
        $filePath  = $meta['file_path'] ?? null;

        // 🔎 Nouveau : extracteur spécial états financiers (avec fallback OCR)
        $snippet = $this->extractFinancialTextFromStoragePdf($filePath);

        if (!$snippet) {
            $snippet = "Aucun texte lisible n'a pu être extrait du document fourni.";
        }

        // On coupe à 6000 caractères pour laisser passer plus de tableaux
        $snippet = mb_substr($snippet, 0, 6000);

        $promptSystem = <<<SYS
Tu es un analyste financier expérimenté, spécialisé dans les entreprises cotées
et les marchés émergents (dont l'Afrique de l'Ouest).

On te fournit le TEXTE brut d'états financiers (compte de résultat, bilan,
flux de trésorerie, annexes…), généralement au format OHADA / BCEAO.

Avant de rédiger ton analyse, tu dois IMPÉRATIVEMENT essayer d'extraire les
montants numériques clés depuis les tableaux, même si la mise en forme est
abîmée (colonnes cassées, espaces, retours à la ligne, etc.).

OBJECTIF : à partir de ces informations, tu dois ressortir de manière claire
et structurée :

1. Le **chiffre d’affaires** de la période.
2. Le **bénéfice net** (ou perte nette) de la période.
3. La **capacité d’autofinancement** (CAF) ou, si le terme n’apparaît pas tel quel,
   une approximation via les flux de trésorerie d’exploitation ou un indicateur
   équivalent (par exemple CAFG dans le tableau des flux).
4. Les **dettes de l’entreprise** (idéalement dette totale, et si possible distinguer
   dettes financières / autres dettes, long terme / court terme).
5. La **trésorerie de l’entreprise** (disponibilités, soldes de trésorerie / banque).

Quand c’est possible, tu utilises en priorité les lignes typiques des états
financiers OHADA / BCEAO, par exemple (liste indicative) :
- "XB – CHIFFRE D’AFFAIRES"
- "XI – RÉSULTAT D’EXPLOITATION"
- "XJ – RÉSULTAT NET"
- "FA – CAFG" (ou une autre ligne CAF)
- "DD" pour les dettes financières et ressources assimilées
- "DP" pour les dettes circulantes
- "ZA" / "ZH" pour la trésorerie au début / fin d’exercice

CONTRAINTES IMPORTANTES :
- Si une ligne ou un tableau contient clairement le montant recherché, tu dois
  l’utiliser. Tu dois être tolérant avec les espaces, les points, les virgules,
  les retours à la ligne, etc.
- S’il y a plusieurs années (N, N-1…), tu prends en priorité l’année la plus
  récente (N).
- Tu n’utilises la formule « Information non trouvée dans les états fournis »,
  que si tu n’as VRAIMENT trouvé ni ligne ni montant correspondant dans tout
  le texte fourni.
- Ne te contente pas de donner un chiffre : ajoute toujours un court commentaire
  (niveau élevé / faible, en hausse / baisse par rapport à l’année précédente
  si c’est visible, etc.).
- N’invente pas de données ni de comparaisons si elles n’apparaissent pas dans
  le texte.
- Tu restes pédagogique, comme si tu expliquais cela à un investisseur
  particulier.

STRUCTURE ATTENDUE (en Markdown) :

1. **Contexte rapide**
   2–3 phrases maximum présentant l’entreprise et la période analysée.

2. **Chiffre d’affaires**
   - Montant (si disponible)
   - Commentaire court

3. **Bénéfice net**
   - Montant (si disponible, préciser s’il s’agit d’un bénéfice ou d’une perte)
   - Commentaire court

4. **Capacité d’autofinancement / flux d’exploitation**
   - Montant ou indication
   - Commentaire court sur la capacité de l’entreprise à générer du cash

5. **Dettes de l’entreprise**
   - Niveaux principaux (totales, financières, etc. si visibles)
   - Commentaire sur le niveau d’endettement (raisonnable / élevé, tendance si visible)

6. **Trésorerie**
   - Montant de trésorerie / disponibilités (si trouvé)
   - Commentaire sur la marge de manœuvre de trésorerie

7. **Conclusion pour l’investisseur**
   - 3 à 5 puces maximum résumant les forces / faiblesses majeures qui
     ressortent des états.

Termine toujours par une phrase de rappel du type :
« Ceci n’est pas un conseil d’investissement personnalisé ; faites vos propres vérifications. »
SYS;

        $promptUser = <<<USR
Entreprise : {$company}
Période / Exercice : {$period}

Ci-dessous, tu trouves le texte extrait des états financiers de l’entreprise
(compte de résultat, bilan, tableau de flux de trésorerie, annexes, etc.).

TA TÂCHE :
1. Parcours le texte et identifie les lignes et tableaux qui contiennent :
   - Chiffre d’affaires
   - Résultat net
   - Résultat d’exploitation
   - CAF / flux de trésorerie d’exploitation
   - Dettes (totales, financières, circulantes…)
   - Trésorerie (début / fin d’exercice)
2. Extrais les montants correspondant à l’exercice le plus récent.
3. Rédige ensuite la synthèse selon la structure demandée dans le message système.

Texte des états financiers :
{$snippet}
USR;

        try {
            $resp = $this->http->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => env('OPENAI_MARKET_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.3,   // sérieux / analytique
                    'max_tokens'  => 900,
                    'messages'    => [
                        ['role' => 'system', 'content' => $promptSystem],
                        ['role' => 'user',   'content' => $promptUser],
                    ],
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            $text = $data['choices'][0]['message']['content'] ?? '';

            if (!trim($text)) {
                return "_Analyse financière IA indisponible pour le moment._";
            }

            return $text;
        } catch (\Throwable $e) {
            \Log::warning('AI error (financial): '.$e->getMessage());
            return "_Analyse financière IA indisponible (erreur technique)._";
        }
    }

    /**
     * Ancien extracteur générique pour le BOC (ON NE LE TOUCHE PAS)
     * Tronque à ~1500 caractères.
     */
    private function extractTextFromStoragePdf(?string $storagePath): ?string
    {
        if (!$storagePath) return null;

        $full = storage_path('app/public/' . ltrim($storagePath, '/'));
        if (!is_file($full)) return null;

        try {
            $pdf  = $this->pdfParser->parseFile($full);
            $text = trim($pdf->getText());
            $text = preg_replace('/[ \t]+/', ' ', $text);

            // On tronque à ~1500 caractères (comportement historique)
            return mb_substr($text, 0, 1500);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Nouvel extracteur dédié aux états financiers (avec fallback OCR).
     * Tronque à ~6000 caractères.
     */
    private function extractFinancialTextFromStoragePdf(?string $storagePath): ?string
    {
        if (!$storagePath) return null;

        $full = storage_path('app/public/' . ltrim($storagePath, '/'));
        if (!is_file($full)) return null;

        // 1) On tente d'abord la méthode classique (texte "vrai")
        try {
            $pdf  = $this->pdfParser->parseFile($full);
            $text = trim($pdf->getText());
            $text = preg_replace('/[ \t]+/', ' ', $text);

            if (mb_strlen($text) > 100) {
                // On a du texte exploitable
                return mb_substr($text, 0, 6000);
            }
        } catch (\Throwable $e) {
            // on tentera l'OCR en dessous
        }

        // 2) Si quasi rien trouvé → on essaie l’OCR (scan)
        return $this->extractTextWithOcr($full);
    }

    /**
     * OCR optionnel (scan -> texte) pour les états financiers.
     * Nécessite Imagick + tesseract-ocr + thiagoalessio/tesseract_ocr.
     * Si non disponibles, renvoie simplement null.
     */
    private function extractTextWithOcr(string $fullPath): ?string
    {
        if (!is_file($fullPath)) {
            return null;
        }

        // Si Imagick ou Tesseract ne sont pas installés, on ne fait rien
        if (!class_exists(Imagick::class) || !class_exists(TesseractOCR::class)) {
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(200, 200);
            // On prend les 3 premières pages : [0-2]
            $imagick->readImage($fullPath . '[0-2]');
            $imagick->setImageFormat('png');

            $tmpFiles = [];
            foreach ($imagick as $index => $page) {
                $tmp = storage_path('app/tmp/ocr_' . uniqid() . "_{$index}.png");
                $page->writeImage($tmp);
                $tmpFiles[] = $tmp;
            }
            $imagick->clear();
            $imagick->destroy();

            $allText = '';
            foreach ($tmpFiles as $tmp) {
                $text = (new TesseractOCR($tmp))
                    ->lang('fra', 'eng')
                    ->psm(6) // mode "bloc / tableau"
                    ->run();

                $allText .= "\n" . $text;
                @unlink($tmp);
            }

            $allText = trim(preg_replace('/[ \t]+/', ' ', $allText));

            if (!mb_strlen($allText)) {
                return null;
            }

            return mb_substr($allText, 0, 6000);
        } catch (\Throwable $e) {
            \Log::warning('OCR error: ' . $e->getMessage());
            return null;
        }
    }
}
