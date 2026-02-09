<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected Client $http;
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct(?Client $http = null)
    {
        $this->secretKey = (string) env('PAYSTACK_SECRET_KEY', '');
        $this->baseUrl   = (string) env('PAYSTACK_BASE_URL', 'https://api.paystack.co');

        $this->http = $http ?: new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /**
     * Initialise une transaction Paystack et retourne l'authorization_url
     */
    public function initialize(array $data): ?string
    {
        try {
            if (!$this->secretKey) {
                Log::error('Paystack initialize: missing secret key');
                return null;
            }

            // Paystack attend amount en "kobo-like" => XOF * 100
            $amountXof = (int) ($data['amount'] ?? 0);
            $payload = [
                'email'        => (string) ($data['email'] ?? ''),
                'amount'       => $amountXof * 100,
                'currency'     => (string) ($data['currency'] ?? 'XOF'),
                'reference'    => (string) ($data['reference'] ?? ''),
                'callback_url' => (string) ($data['callback_url'] ?? ''),
                'metadata'     => $data['metadata'] ?? [],
            ];

            $res = $this->http->post('/transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $res->getBody(), true);

            if (!empty($body['status']) && $body['status'] === true) {
                return $body['data']['authorization_url'] ?? null;
            }

            Log::error('Paystack initialize error', [
                'payload'  => $this->safePayloadForLogs($payload),
                'response' => $body,
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('Paystack initialize exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Vérifie une transaction Paystack (par reference)
     */
    public function verify(string $reference): ?array
    {
        try {
            if (!$this->secretKey) {
                Log::error('Paystack verify: missing secret key');
                return null;
            }

            $res = $this->http->get('/transaction/verify/' . urlencode($reference), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Accept'        => 'application/json',
                ],
            ]);

            $body = json_decode((string) $res->getBody(), true);

            if (!empty($body['status']) && $body['status'] === true) {
                return $body['data'] ?? null;
            }

            Log::error('Paystack verify error', [
                'reference' => $reference,
                'response'  => $body,
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('Paystack verify exception: ' . $e->getMessage(), [
                'reference' => $reference,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function safePayloadForLogs(array $payload): array
    {
        $p = $payload;
        if (!empty($p['email'])) {
            $p['email'] = '***';
        }
        return $p;
    }
}
