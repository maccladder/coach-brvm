<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected Client $http;
    protected string $secretKey;
    protected string $baseUrl;
    protected string $currency;

    public function __construct(?Client $http = null)
    {
        $this->secretKey = (string) env('PAYSTACK_SECRET_KEY', '');
        $this->baseUrl   = (string) env('PAYSTACK_BASE_URL', 'https://api.paystack.co');
        $this->currency  = (string) env('PAYSTACK_CURRENCY', 'XOF');

        $this->http = $http ?: new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /**
     * Retourne l'URL de paiement (authorization_url) ou null.
     * amount attendu en FCFA -> Paystack veut en sous-unité => x100
     */
    public function createPayment(array $data): ?string
    {
        try {
            if (!$this->secretKey) {
                Log::error('PAYSTACK_SECRET_KEY manquante');
                return null;
            }

            $amountMajor   = (int) ($data['amount'] ?? 0); // ex: 5000 FCFA
            $amountSubunit = $amountMajor * 100;

            $metadata = $data['metadata'] ?? ($data['transaction_id'] ?? '');
            if (is_array($metadata)) {
                $metadata = json_encode($metadata, JSON_UNESCAPED_SLASHES);
            }
            $metadata = (string) $metadata;

            $payload = array_filter([
                'email'        => (string) ($data['customer_email'] ?? $data['email'] ?? ''),
                'amount'       => $amountSubunit,
                'currency'     => (string) ($data['currency'] ?? $this->currency),
                'reference'    => (string) ($data['transaction_id'] ?? ''), // on réutilise ton transaction_id
                'callback_url' => (string) ($data['return_url'] ?? $data['callback_url'] ?? ''),
                'metadata'     => $metadata,
            ], fn($v) => $v !== null && $v !== '');

            $res = $this->http->post('/transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $res->getBody(), true);

            if (($body['status'] ?? false) === true) {
                return $body['data']['authorization_url'] ?? null;
            }

            Log::error('Paystack init error', ['response' => $body, 'payload' => $this->safe($payload)]);
            return null;

        } catch (\Throwable $e) {
            Log::error('Paystack init exception: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Vérifie une transaction via reference.
     * Retourne: ACCEPTED | REFUSED | PENDING | null
     */
    public function checkPayment(?string $reference): ?string
    {
        if (!$reference) return null;

        try {
            $res = $this->http->get('/transaction/verify/' . urlencode($reference), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Accept'        => 'application/json',
                ],
            ]);

            $body = json_decode((string) $res->getBody(), true);
            $status = $body['data']['status'] ?? null;

            if (!$status) return null;

            return match ($status) {
                'success' => 'ACCEPTED',
                'failed', 'abandoned', 'reversed' => 'REFUSED',
                default => 'PENDING',
            };

        } catch (\Throwable $e) {
            Log::error('Paystack verify exception: '.$e->getMessage(), ['reference' => $reference]);
            return null;
        }
    }

    private function safe(array $payload): array
    {
        $p = $payload;
        if (!empty($p['email'])) {
            $p['email'] = preg_replace('/(^.).*(@.*$)/', '$1***$2', (string) $p['email']);
        }
        return $p;
    }
}
