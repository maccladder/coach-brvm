<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AvatarService
{
    private Client $http;

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?: new Client([
            'base_uri' => env('DID_BASE_URL', 'https://api.d-id.com'),
            'timeout'  => 120,
        ]);
    }

    /**
     * Génère une vidéo d’avatar parlant le texte donné et retourne l’URL finale.
     *
     * @param  string  $text
     * @return string|null
     */
    public function generateTalkingHead(string $text): ?string
    {
        $sourceUrl = env('AVATAR_SOURCE_URL');
        if (!$sourceUrl) {
            Log::warning('AvatarService: AVATAR_SOURCE_URL manquant');
            return null;
        }

        // 🔊 Voix masculine FR naturelle
        $voiceProvider = env('DID_VOICE_PROVIDER', 'microsoft');
        $voiceId       = env('DID_VOICE_ID', 'fr-FR-HenriNeural');

        // Payload envoyé à D-ID
        $payload = [
            'source_url' => $sourceUrl,
            'script' => [
                'type'     => 'text',
                'input'    => $text,
                'provider' => [
                    'type'     => $voiceProvider,
                    'voice_id' => $voiceId,
                ],
                // Optionnel : réglages de la voix
                'audio_config' => [
                    'speaking_rate' => 1.0,
                    'pitch'         => 0.0,
                ],
            ],
        ];

        try {
            // 1) Création du talk
            $resp = $this->http->post('/talks', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('DID_API_KEY') . ':'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data   = json_decode((string) $resp->getBody(), true);
            $talkId = $data['id'] ?? null;

            if (!$talkId) {
                Log::warning('AvatarService: pas d’ID retourné', $data ?? []);
                return null;
            }

            // 2) Polling pour attendre la vidéo
            $maxTries = 18;   // ~50 secondes
            $delayMs  = 3000; // 3 sec entre polls

            for ($i = 0; $i < $maxTries; $i++) {
                usleep($delayMs * 1000);

                $check = $this->http->get("/talks/{$talkId}", [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode(env('DID_API_KEY') . ':'),
                    ],
                ]);

                $info = json_decode((string) $check->getBody(), true);

                // Vidéo prête
                if (!empty($info['result_url'])) {
                    return $info['result_url'];
                }

                // Erreur serveur
                if (!empty($info['status']) && $info['status'] === 'error') {
                    Log::warning('AvatarService: erreur talk', $info);
                    return null;
                }
            }

            Log::warning("AvatarService: timeout pour talk {$talkId}");
            return null;
        } catch (\Throwable $e) {
            Log::warning('AvatarService error: ' . $e->getMessage());
            return null;
        }
    }
}
