<?php

namespace App\Services;

use App\Models\DailySummary;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AiVoiceService
{
    public function __construct(
        private ?Client $http = null,
    ) {
        $this->http = $this->http ?: new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 120,
        ]);
    }

    /**
     * Génère (ou réutilise) un MP3 pour un résumé DailySummary donné.
     * Retourne le chemin RELATIF dans storage/app/public.
     */
    public function makeAudioForSummary(DailySummary $summary): string
    {
        $markdown = $summary->summary_markdown ?? '';

        // slug unique pour ce résumé
        $slug = 'summary-' . $summary->id;

        return $this->makeAudioFromMarkdown($markdown, $slug);
    }

    /**
     * Génère (ou réutilise) un MP3 à partir d'un markdown arbitraire.
     *
     * @param string $markdown  Texte markdown à lire (sera nettoyé).
     * @param string $slug      Suffixe unique, ex: "clientboc-12"
     * @return string           Chemin relatif dans storage/app/public
     */
    public function makeAudioFromMarkdown(string $markdown, string $slug): string
    {
        // version pour invalider les anciens fichiers si on change le nettoyage
        $version      = 'v1';
        $relativePath = "tts/{$version}-{$slug}.mp3";
        $fullPath     = storage_path('app/public/' . $relativePath);

        // Si déjà généré avec cette version, on réutilise
        if (is_file($fullPath)) {
            return $relativePath;
        }

        // 1) Nettoyage "markdown" basique
        $raw = $markdown ?? '';

        // On ne garde que le texte après "Interprétation (IA)" s'il existe
        if (preg_match('/###\s*Interprétation\s*\(IA\)(.*)$/si', $raw, $m)) {
            $text = $m[1];
        } else {
            $text = $raw;
        }

        // **gras** ou __gras__
        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text);
        $text = preg_replace('/__(.*?)__/s', '$1', $text);

        // *italique* ou _italique_
        $text = preg_replace('/\*(.*?)\*/s', '$1', $text);
        $text = preg_replace('/_(.*?)_/s', '$1', $text);

        // Liens [texte](url) -> texte
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text);

        // Titres markdown "### Titre"
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);

        // Puces "- " au début de ligne
        $text = preg_replace('/^\-\s*/m', '', $text);

        // Séparateurs "---"
        $text = preg_replace('/^-{3,}\s*$/m', '', $text);

        // Nettoyer le reste des symboles markdown (# * _ - ` > etc.)
        $text = preg_replace('/[#\*\_\-\`\>]+/u', ' ', $text);

        // Nettoyage final
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            throw new \RuntimeException('Résumé vide, rien à lire.');
        }

        // Limiter la longueur
        $text = mb_substr($text, 0, 4000);

        // Choix de la voix
        $voice = env('OPENAI_TTS_VOICE', 'alloy'); // ou 'verse', 'nova', etc.

        try {
            $resp = $this->http->post('audio/speech', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'  => 'gpt-4o-mini-tts',
                    'voice'  => $voice,
                    'format' => 'mp3',
                    'input'  => $text,
                ],
            ]);

            $audioBinary = (string) $resp->getBody();

            if (!is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0775, true);
            }

            file_put_contents($fullPath, $audioBinary);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('TTS error: '.$e->getMessage());
            throw $e;
        }
    }
}
