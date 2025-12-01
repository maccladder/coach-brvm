<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CinetpayService
{
    protected Client $http;
    protected string $apiKey;
    protected string $siteId;
    protected string $baseUrl;

    public function __construct(?Client $http = null)
    {
        $this->apiKey  = env('CINETPAY_API_KEY');
        $this->siteId  = env('CINETPAY_SITE_ID');
        $this->baseUrl = env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com');

        $this->http = $http ?: new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /**
     * Crée un paiement et retourne l’URL de redirection CinetPay
     */
    public function createPayment(array $data): ?string
    {
        try {
            $payload = [
                'apikey'         => $this->apiKey,
                'site_id'        => $this->siteId,
                'transaction_id' => $data['transaction_id'],
                'amount'         => (int) $data['amount'],
                'currency'       => 'XOF',
                'description'    => $data['description'] ?? 'Analyse BOC client',
                'notify_url'     => $data['notify_url'],
                'return_url'     => $data['return_url'],
                'channels'       => 'ALL',
                // metadata doit être une string selon l’erreur que tu as eue
                'metadata'       => (string) ($data['transaction_id'] ?? ''),
            ];

            $response = $this->http->post('/v2/payment', [
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            // Selon la doc CinetPay, vérifier le code de retour
            if (!empty($body['code']) && $body['code'] === '201') {
                // URL de redirection
                return $body['data']['payment_url'] ?? null;
            }

            Log::error('Cinetpay createPayment error', ['response' => $body]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Cinetpay createPayment exception: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie le statut d’un paiement.
     * Retourne par ex. "ACCEPTED", "REFUSED", "PENDING" ou null en cas d’erreur.
     */
   public function checkPayment(?string $transactionId): ?string
{
    if (!$transactionId) {
        Log::warning('Cinetpay checkPayment appelé avec transactionId NULL');

        // En local, on peut simuler ACCEPTED pour ne pas être bloqué
        if (app()->environment('local')) {
            return 'ACCEPTED';
        }

        return null;
    }

    try {
        $payload = [
            'apikey'         => $this->apiKey,
            'site_id'        => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        $response = $this->http->post('/v2/payment/check', ['json' => $payload]);
        $body     = json_decode((string) $response->getBody(), true);

        if (!empty($body['code']) && in_array($body['code'], ['00', '201'])) {
            return $body['data']['status'] ?? null;
        }

        Log::error('Cinetpay checkPayment error', ['response' => $body]);
        return null;
    } catch (\Throwable $e) {
        Log::error('Cinetpay checkPayment exception: '.$e->getMessage());
        return null;
    }
}
}
