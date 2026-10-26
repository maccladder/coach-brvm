<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;

/**
 * Faux Paystack pour tester le checkout Mobile Money en local (CHECKOUT_MM_SIMULATE=true,
 * refusé en production). Aucun appel réseau. Le scénario dépend des 4 derniers chiffres
 * du numéro saisi :
 *
 *   …0001 → Wave : HTTP 400 "unprocessed_transaction" (réponse réelle observée en prod)
 *   …0002 → Orange : send_otp ; OTP 123456 → succès, tout autre code → échec
 *   …0003 → pay_offline, puis succès après 15 s (comme si le webhook était arrivé)
 *   …0004 → pay_offline, jamais confirmé → expiration à 180 s
 *   …0005 → failed immédiat (solde insuffisant)
 *   autre → comme 0003
 *
 * Mêmes formats de retour que PaystackService (charge / submitOtp / checkCharge / fetchTransaction).
 */
class MobileMoneySimulator
{
    public const VALID_OTP     = '123456';
    public const SUCCESS_AFTER = 15;

    public function charge(array $data): array
    {
        $reference = (string) ($data['reference'] ?? '');
        $digits    = preg_replace('/\D+/', '', (string) data_get($data, 'mobile_money.phone', ''));

        $scenario = match (substr($digits, -4)) {
            '0001'  => 'wave_error',
            '0002'  => 'otp',
            '0004'  => 'expire',
            '0005'  => 'failed',
            default => 'success',
        };

        Cache::put($this->key($reference), [
            'scenario' => $scenario,
            'amount'   => (int) ($data['amount'] ?? 0) * 100,
            'metadata' => $data['metadata'] ?? [],
            'started'  => now()->getTimestamp(),
            'paid'     => false,
        ], now()->addDay());

        return match ($scenario) {
            'wave_error' => $this->response(400, [
                'status'  => false,
                'message' => 'Charge attempted',
                'data'    => ['status' => 'failed', 'message' => 'Error occured while processing your request!'],
                'meta'    => ['nextStep' => 'The customer needs to escalate to Paystack...'],
                'type'    => 'api_error',
                'code'    => 'unprocessed_transaction',
            ]),
            'otp' => $this->response(200, [
                'status'  => true,
                'message' => 'Charge attempted',
                'data'    => [
                    'reference'    => $reference,
                    'status'       => 'send_otp',
                    'display_text' => 'Please complete authorization process on your mobile application',
                ],
            ]),
            'failed' => $this->response(200, [
                'status'  => true,
                'message' => 'Charge attempted',
                'data'    => ['reference' => $reference, 'status' => 'failed', 'gateway_response' => 'Insufficient funds'],
            ]),
            default => $this->response(200, [
                'status'  => true,
                'message' => 'Charge attempted',
                'data'    => [
                    'reference'    => $reference,
                    'status'       => 'pay_offline',
                    'display_text' => 'Please complete authorization on your mobile phone',
                ],
            ]),
        };
    }

    public function submitOtp(string $otp, string $reference): array
    {
        $state = Cache::get($this->key($reference));

        if (!$state) {
            return $this->response(400, ['status' => false, 'message' => 'Transaction not found']);
        }

        if ($otp !== self::VALID_OTP) {
            return $this->response(200, [
                'status'  => true,
                'message' => 'Charge attempted',
                'data'    => ['reference' => $reference, 'status' => 'failed', 'gateway_response' => 'Invalid OTP'],
            ]);
        }

        $state['paid'] = true;
        Cache::put($this->key($reference), $state, now()->addDay());

        return $this->response(200, ['status' => true, 'message' => 'Charge attempted', 'data' => $this->successData($reference, $state)]);
    }

    public function checkCharge(string $reference): array
    {
        $state = Cache::get($this->key($reference));

        if (!$state) {
            return $this->response(404, ['status' => false, 'message' => 'Transaction not found']);
        }

        if ($this->isPaid($state)) {
            return $this->response(200, ['status' => true, 'data' => $this->successData($reference, $state)]);
        }

        $status = match ($state['scenario']) {
            'failed', 'wave_error' => 'failed',
            'otp'                  => 'send_otp',
            default                => 'pay_offline',
        };

        return $this->response(200, ['status' => true, 'data' => ['reference' => $reference, 'status' => $status]]);
    }

    public function fetchTransaction(string $reference): array
    {
        $state = Cache::get($this->key($reference));

        if (!$state) {
            return ['found' => false, 'data' => null, 'error' => null];
        }

        if ($this->isPaid($state)) {
            return ['found' => true, 'data' => $this->successData($reference, $state), 'error' => null];
        }

        // Expiration : Paystack ne confirme ni succès ni échec
        $status = $state['scenario'] === 'failed' ? 'failed' : 'ongoing';

        return ['found' => true, 'data' => ['reference' => $reference, 'status' => $status], 'error' => null];
    }

    private function isPaid(array $state): bool
    {
        return $state['paid']
            || ($state['scenario'] === 'success' && now()->getTimestamp() - $state['started'] >= self::SUCCESS_AFTER);
    }

    private function successData(string $reference, array $state): array
    {
        return [
            'reference' => $reference,
            'status'    => 'success',
            'amount'    => $state['amount'],
            'currency'  => 'XOF',
            'channel'   => 'mobile_money',
            'metadata'  => $state['metadata'],
        ];
    }

    private function response(int $http, array $body): array
    {
        return [
            'ok'    => $http >= 200 && $http < 300 && !empty($body['status']),
            'http'  => $http,
            'body'  => $body,
            'error' => null,
        ];
    }

    private function key(string $reference): string
    {
        return 'mm_sim:' . $reference;
    }
}
