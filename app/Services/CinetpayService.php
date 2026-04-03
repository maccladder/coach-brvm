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
        $this->apiKey  = (string) env('CINETPAY_API_KEY', '');
        $this->siteId  = (string) env('CINETPAY_SITE_ID', '');
        $this->baseUrl = (string) env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com');

        $this->http = $http ?: new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /**
     * Crée un paiement et retourne l'URL de redirection CinetPay
     */
    public function createPayment(array $data): ?string
    {
        try {
            // ✅ metadata DOIT être une string pour CinetPay
            // On accepte:
            // - metadata string (on garde)
            // - metadata array (on JSON encode)
            $metadata = $data['metadata'] ?? ($data['transaction_id'] ?? '');
            if (is_array($metadata)) {
                $metadata = json_encode($metadata, JSON_UNESCAPED_SLASHES);
            }
            $metadata = (string) $metadata;

            $payload = [
                'apikey'         => $this->apiKey,
                'site_id'        => $this->siteId,
                'transaction_id' => (string) ($data['transaction_id'] ?? ''),
                'amount'         => (int) ($data['amount'] ?? 0),
                'currency'       => (string) ($data['currency'] ?? 'XOF'),
                'description'    => (string) ($data['description'] ?? 'Paiement'),
                'notify_url'     => (string) ($data['notify_url'] ?? ''),
                'return_url'     => (string) ($data['return_url'] ?? ''),
                'channels'       => (string) ($data['channels'] ?? 'ALL'),
                'metadata'       => $metadata,
            ];

            // ✅ Champs optionnels (ne cassent rien si absents)
            if (!empty($data['customer_name'])) {
                $payload['customer_name'] = (string) $data['customer_name'];
            }
            if (!empty($data['customer_email'])) {
                $payload['customer_email'] = (string) $data['customer_email'];
            }

            $response = $this->http->post('/v2/payment', ['json' => $payload]);
            $body = json_decode((string) $response->getBody(), true);

            // ✅ On garde ta règle actuelle (important pour ne pas casser le live)
            if (!empty($body['code']) && (string) $body['code'] === '201') {
                return $body['data']['payment_url'] ?? null;
            }

            Log::error('Cinetpay createPayment error', [
                'payload'  => $this->safePayloadForLogs($payload),
                'response' => $body,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Cinetpay createPayment exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Vérifie le statut d'un paiement.
     * Retourne par ex. "ACCEPTED", "REFUSED", "PENDING" ou null en cas d'erreur.
     */
    public function checkPayment(?string $transactionId): ?string
    {
        if (!$transactionId) {
            Log::warning('Cinetpay checkPayment appelé avec transactionId NULL');

            // ✅ Optionnel (uniquement si tu veux simuler en local volontairement)
            // .env : CINETPAY_FAKE_OK=true
            if (app()->environment('local') && env('CINETPAY_FAKE_OK', false)) {
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

            // ✅ On garde tes codes acceptés (00 / 201) pour ne pas casser
            if (!empty($body['code']) && in_array((string) $body['code'], ['00', '201'], true)) {
                return $body['data']['status'] ?? null;
            }

            Log::error('Cinetpay checkPayment error', [
                'transaction_id' => $transactionId,
                'response'       => $body,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Cinetpay checkPayment exception: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Évite de logger des infos sensibles en clair
     */
    private function safePayloadForLogs(array $payload): array
    {
        $p = $payload;

        if (!empty($p['apikey'])) {
            $p['apikey'] = '***';
        }
        if (!empty($p['site_id'])) {
            // site_id n'est pas ultra secret, mais on peut le masquer partiellement
            $p['site_id'] = substr((string) $p['site_id'], 0, 2) . '***';
        }

        return $p;
    }
}
