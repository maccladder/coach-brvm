<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiVoiceService
{
    private Client $http;

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?: new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 120,   // on laisse large pour la synthèse vocale
        ]);
    }

    /**
     * Génère un MP3 à partir d’un texte (markdown possible).
     *
     * @param  string $markdown   Texte ou markdown à lire à voix haute
     * @param  string $baseName   Préfixe du fichier (ex: 'clientfinancial-25')
     * @return string|null        Chemin relatif sur le disk "public" (ex: tts/xxx.mp3)
     */
    public function makeAudioFromMarkdown(string $markdown, string $baseName): ?string
    {
        // 1) Nettoyage rapide du markdown → texte “lisible”
        $text = $markdown;

        // On enlève les titres, puces, gras, etc.
        $text = preg_replace('/^\s*#+\s*/m', '', $text);        // # Titre
        $text = preg_replace('/^\s*[-*]\s+/m', '', $text);      // - liste
        $text = str_replace(['**', '*', '_', '`'], '', $text);  // markdown simple
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text); // liens [txt](url)
        $text = strip_tags($text);                              // au cas où
        $text = preg_replace("/\n{2,}/", "\n", $text);
        $text = trim($text);

        // Pour éviter d’envoyer un roman à l’API
        $text = mb_substr($text, 0, 4000);

        if ($text === '') {
            Log::warning('AiVoiceService: texte vide, audio non généré.');
            return null;
        }

        try {
            Log::info('AiVoiceService: appel TTS OpenAI en cours…');

            $resp = $this->http->post('audio/speech', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    // modèle & voix configurables via .env
                    'model' => env('OPENAI_TTS_MODEL', 'gpt-4o-mini-tts'),
                    'voice' => env('OPENAI_TTS_VOICE', 'alloy'),
                    'input' => $text,
                    'format' => 'mp3',
                ],
            ]);

            $audioBinary = (string) $resp->getBody();

            if ($audioBinary === '') {
                Log::warning('AiVoiceService: réponse audio vide.');
                return null;
            }

            // 2) On enregistre le MP3 sur le disk "public"
            $dir = 'tts';
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            $filename = $dir . '/' . $baseName . '-' . time() . '.mp3';

            Storage::disk('public')->put($filename, $audioBinary);

            Log::info('AiVoiceService: audio généré avec succès', [
                'path' => $filename,
            ]);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning('AiVoiceService: erreur TTS : ' . $e->getMessage());
            return null;
        }
    }
}
