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
        $this->secretKey = (string) config('services.paystack.secret', '');
        $this->baseUrl   = (string) config('services.paystack.base_url', 'https://api.paystack.co');

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

    /**
     * Comme verify(), sans exception ni log : distingue une transaction inconnue de
     * Paystack (found=false) d'une erreur réseau/API (error). Utilisé par la réconciliation.
     *
     * @return array{found: bool, data: ?array, error: ?string}
     */
    public function fetchTransaction(string $reference): array
    {
        if (!$this->secretKey) {
            return ['found' => false, 'data' => null, 'error' => 'missing secret key'];
        }

        try {
            $res = $this->http->get('/transaction/verify/' . urlencode($reference), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Accept'        => 'application/json',
                ],
                'http_errors' => false,
            ]);

            $code = $res->getStatusCode();
            $body = json_decode((string) $res->getBody(), true);

            if ($code === 200 && !empty($body['status']) && $body['status'] === true) {
                return ['found' => true, 'data' => $body['data'] ?? null, 'error' => null];
            }

            if (in_array($code, [400, 404], true)) {
                return ['found' => false, 'data' => null, 'error' => null];
            }

            return ['found' => false, 'data' => null, 'error' => 'HTTP ' . $code . ' ' . ($body['message'] ?? '')];

        } catch (\Throwable $e) {
            return ['found' => false, 'data' => null, 'error' => $e->getMessage()];
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
