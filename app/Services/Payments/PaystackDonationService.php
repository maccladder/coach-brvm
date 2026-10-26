<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirmation d'un don payé via Paystack (référence DON-…).
 * Utilisé par le callback, le webhook et le checkout Mobile Money. Idempotent.
 */
class PaystackDonationService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';

    public function handleChargeSuccess(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));

        $outcome = self::NOT_FOUND;

        DB::transaction(function () use ($reference, $amountPaid, $currency, $data, &$outcome) {
            $donation = Donation::where('reference', $reference)->lockForUpdate()->first();

            if (!$donation) {
                Log::error('[DONATION][PAYSTACK] donation not found', ['reference' => $reference]);
                return;
            }

            if ($donation->status === 'ACCEPTED') {
                $outcome = self::ALREADY;
                return;
            }

            if ($amountPaid <= 0 || $amountPaid !== (int) $donation->amount || $currency !== 'XOF') {
                Log::error('[DONATION][PAYSTACK] amount mismatch', [
                    'reference' => $reference,
                    'expected'  => $donation->amount,
                    'received'  => $amountPaid,
                    'currency'  => $currency,
                ]);
                $donation->update(['status' => 'FAILED']);
                $outcome = self::MISMATCH;
                return;
            }

            $donation->update([
                'status'       => 'ACCEPTED',
                'confirmed_at' => now(),
                'meta'         => array_merge((array) ($donation->meta ?? []), [
                    'paystack' => [
                        'reference' => $reference,
                        'channel'   => $data['channel'] ?? null,
                        'paid_at'   => $data['paid_at'] ?? null,
                    ],
                ]),
            ]);

            $outcome = self::GRANTED;
        });

        return $outcome;
    }
}
