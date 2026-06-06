<?php

namespace App\Services\Payments;

use App\Mail\PackBrvmConfirmationMail;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\PackWhatsappToken;
use App\Models\Payment;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaystackPackService
{
    public function __construct(
        private AffiliateCommissionService $affiliateService
    ) {}

    /**
     * Traitement métier du webhook Paystack pour les packs (charge.success).
     * Idempotent : vérifie credited_at avant tout traitement.
     */
    public function handleChargeSuccess(array $data): void
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $email      = (string) data_get($data, 'customer.email');

        if ($reference === '') {
            Log::warning('PaystackPackService: missing reference');
            return;
        }

        $token     = null;
        $user      = null;
        $courseIds = [];

        DB::transaction(function () use ($reference, $amountPaid, $email, $data, &$token, &$user, &$courseIds) {

            $payment = Payment::where('transaction_id', $reference)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning('PaystackPackService: payment not found', [
                    'reference' => $reference,
                    'email'     => $email,
                ]);
                return;
            }

            if ($payment->purpose !== 'pack_brvm') {
                Log::info('PaystackPackService: not a pack_brvm, ignored', [
                    'reference' => $reference,
                    'purpose'   => $payment->purpose,
                ]);
                return;
            }

            // Idempotence
            if ($payment->credited_at !== null) {
                Log::info('PaystackPackService: already processed, skip', ['reference' => $reference]);
                return;
            }

            // Vérification montant
            if ((int) $payment->amount_paid !== $amountPaid) {
                Log::error('PaystackPackService: amount mismatch', [
                    'reference' => $reference,
                    'db_amount' => $payment->amount_paid,
                    'paid'      => $amountPaid,
                ]);
                $payment->status = 'REFUSED';
                $payment->save();
                return;
            }

            $user      = User::find($payment->user_id);
            $courseIds = (array) data_get($payment->meta, 'course_ids', []);

            if (!$user) {
                Log::error('PaystackPackService: user not found', ['user_id' => $payment->user_id]);
                return;
            }

            // Assigner les cours (firstOrCreate évite les doublons)
            foreach ($courseIds as $courseId) {
                $purchase = CoursePurchase::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => (int) $courseId],
                    ['amount_fcfa' => 0, 'payment_ref' => $reference]
                );
                if (!$purchase->paid_at) {
                    $purchase->paid_at     = now();
                    $purchase->payment_ref = $reference;
                    $purchase->save();
                }
            }

            // Générer le token WhatsApp
            $token = Str::random(64);
            PackWhatsappToken::create(['user_id' => $user->id, 'token' => $token]);

            // Marquer le paiement comme traité
            $payment->status      = 'ACCEPTED';
            $payment->credited_at = now();
            $payment->notified_at = now();
            $payment->meta        = array_merge((array) ($payment->meta ?? []), [
                'paystack' => [
                    'event'          => 'charge.success',
                    'reference'      => $reference,
                    'amount_raw'     => $data['amount'] ?? null,
                    'currency'       => $data['currency'] ?? null,
                    'customer_email' => $email,
                    'paid_at'        => $data['paid_at'] ?? null,
                    'channel'        => $data['channel'] ?? null,
                ],
            ]);
            $payment->save();

            Log::info('PaystackPackService: pack activated via webhook', [
                'reference' => $reference,
                'user_id'   => $user->id,
            ]);
        });

        // Commission affiliation — hors transaction pour éviter tout rollback croisé
        // Idempotence garantie par UNIQUE(payment_id) dans affiliate_commissions
        $confirmedPayment = Payment::where('transaction_id', $reference)
            ->whereNotNull('credited_at')
            ->first();
        if ($confirmedPayment) {
            try {
                $this->affiliateService->tryCreateCommission($confirmedPayment);
            } catch (\Throwable $e) {
                Log::error('PaystackPackService: affiliateCommission error', [
                    'reference'  => $reference,
                    'payment_id' => $confirmedPayment->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Email envoyé hors transaction
        if ($token && $user) {
            $courses = Course::whereIn('id', $courseIds)->get();
            try {
                Mail::to($user->email)->send(new PackBrvmConfirmationMail($user, $token, $courses));
            } catch (\Throwable $e) {
                Log::error('PaystackPackService: email failed', [
                    'reference' => $reference,
                    'user_id'   => $user->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }
}
