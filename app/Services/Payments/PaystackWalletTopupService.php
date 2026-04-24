<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\VirtualWallet;
use App\Models\VirtualWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWalletTopupService
{
    /**
     * Traitement métier du webhook Paystack (charge.success)
     * - retrouve le Payment via reference
     * - vérifie montant
     * - crédite wallet une seule fois (idempotent)
     */
    public function handleChargeSuccess(array $data): void
    {
        $reference = (string) ($data['reference'] ?? '');
        if ($reference === '') {
            Log::warning('PaystackWalletTopupService: missing reference');
            return;
        }

        // Paystack envoie amount en "plus petite unité" (kobo-like).
        // Dans tes logs tu fais /100 => on garde pareil.
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100); // FCFA
        $email      = (string) data_get($data, 'customer.email');

        DB::transaction(function () use ($reference, $amountPaid, $email, $data) {

            /** @var Payment|null $payment */
            $payment = Payment::where('transaction_id', $reference)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning('PaystackWalletTopupService: payment not found', [
                    'reference' => $reference,
                    'email' => $email,
                ]);
                return;
            }

            // ✅ On ne traite que les topups wallet
            if ($payment->purpose !== 'wallet_topup') {
                Log::info('PaystackWalletTopupService: not a wallet_topup, ignored', [
                    'reference' => $reference,
                    'purpose' => $payment->purpose,
                ]);
                return;
            }

            // ✅ Idempotence : déjà crédité => stop
            if ($payment->credited_at !== null) {
                Log::info('PaystackWalletTopupService: already credited, skip', [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            // ✅ Sécurité montant (optionnel mais recommandé)
            if ((int) $payment->amount_paid !== $amountPaid) {
                Log::error('PaystackWalletTopupService: amount mismatch', [
                    'reference' => $reference,
                    'db_amount_paid' => $payment->amount_paid,
                    'paystack_amount_paid' => $amountPaid,
                ]);

                // On peut marquer REFUSED ou laisser PENDING pour enquête
                $payment->status = 'REFUSED';
                $payment->notified_at = now();
                $payment->meta = array_merge((array) $payment->meta, [
                    'paystack' => [
                        'event' => 'charge.success',
                        'amount_raw' => $data['amount'] ?? null,
                        'currency' => $data['currency'] ?? null,
                        'customer_email' => $email,
                        'mismatch' => true,
                    ],
                ]);
                $payment->save();

                return;
            }

            // ✅ Créditer le wallet
            $wallet = VirtualWallet::where('user_id', $payment->user_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = VirtualWallet::create([
                    'user_id' => $payment->user_id,
                    'balance' => 0,
                ]);
            }

            // Le montant virtuel est celui déjà prévu dans ta logique
            $wallet->balance = (float) $wallet->balance + (float) $payment->amount_virtual;
            $wallet->save();

            // Historique wallet
            VirtualWalletTransaction::create([
                'user_id' => $payment->user_id,
                'type'    => 'topup',
                'amount'  => (int) $payment->amount_virtual,
                'meta'    => [
                    'source' => 'paystack',
                    'reference' => $reference,
                    'amount_paid' => (int) $payment->amount_paid,
                ],
            ]);

            // ✅ Marquer payment comme OK
            $payment->status      = 'ACCEPTED';
            $payment->credited_at = now();
            $payment->notified_at = now();
            $payment->meta = array_merge((array) $payment->meta, [
                'paystack' => [
                    'event' => 'charge.success',
                    'reference' => $reference,
                    'amount_raw' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'customer_email' => $email,
                    'paid_at' => $data['paid_at'] ?? null,
                    'channel' => $data['channel'] ?? null,
                ],
            ]);
            $payment->save();

            Log::info('PaystackWalletTopupService: wallet credited', [
                'reference' => $reference,
                'user_id' => $payment->user_id,
                'amount_virtual' => $payment->amount_virtual,
            ]);
        });
    }
}
