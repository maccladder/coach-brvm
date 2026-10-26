<?php

namespace App\Services\Payments;

use App\Models\CoursePurchase;
use App\Models\Payment;
use App\Services\Affiliate\AffiliateCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Validation d'un achat de formation payé via Paystack (référence COURSE-…).
 * Utilisé par le callback, le webhook et la réconciliation. Idempotent (credited_at).
 */
class PaystackCourseService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';
    public const IGNORED   = 'ignored';

    public function __construct(
        private AffiliateCommissionService $affiliateService
    ) {}

    /**
     * @param array $data  bloc "data" Paystack (webhook charge.success ou transaction/verify)
     */
    public function handleChargeSuccess(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));

        $payment = $reference !== '' ? Payment::where('transaction_id', $reference)->first() : null;

        if (!$payment) {
            Log::error('[COURSE][PAYSTACK] payment not found', ['reference' => $reference]);
            return self::NOT_FOUND;
        }

        if ($payment->purpose !== 'course') {
            Log::info('[COURSE][PAYSTACK] not a course payment, ignored', [
                'reference' => $reference,
                'purpose'   => $payment->purpose,
            ]);
            return self::IGNORED;
        }

        if ($amountPaid <= 0 || $amountPaid !== (int) $payment->amount_paid || $currency !== 'XOF') {
            Log::error('[COURSE][PAYSTACK] amount mismatch', [
                'reference' => $reference,
                'db_amount' => (int) $payment->amount_paid,
                'paid'      => $amountPaid,
                'currency'  => $currency,
            ]);
            return self::MISMATCH;
        }

        $granted = false;

        DB::transaction(function () use ($payment, $reference, $data, &$granted) {

            $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($paymentLocked->credited_at) {
                return;
            }

            $paymentLocked->status      = 'ACCEPTED';
            $paymentLocked->credited_at = now();
            $paymentLocked->meta        = array_merge((array) ($paymentLocked->meta ?? []), [
                'paystack' => [
                    'reference' => $reference,
                    'channel'   => $data['channel'] ?? null,
                    'paid_at'   => $data['paid_at'] ?? null,
                ],
            ]);
            $paymentLocked->save();

            $meta       = (array) ($paymentLocked->meta ?? []);
            $purchaseId = $meta['purchase_id'] ?? null;
            $courseId   = $meta['course_id'] ?? null;

            $purchase = null;
            if ($purchaseId) {
                $purchase = CoursePurchase::where('id', $purchaseId)->lockForUpdate()->first();
            }
            if (!$purchase && $courseId) {
                $purchase = CoursePurchase::where('user_id', $paymentLocked->user_id)
                    ->where('course_id', $courseId)
                    ->lockForUpdate()
                    ->first();
            }
            if (!$purchase && $courseId) {
                $purchase = CoursePurchase::create([
                    'user_id'     => $paymentLocked->user_id,
                    'course_id'   => (int) $courseId,
                    'amount_fcfa' => (int) $paymentLocked->amount_paid,
                ]);
            }
            if ($purchase && !$purchase->paid_at) {
                $purchase->paid_at     = now();
                $purchase->payment_ref = $reference;
                $purchase->save();
            }

            $granted = true;
        });

        if (!$granted) {
            Log::info('[COURSE][PAYSTACK] already credited, skip', ['reference' => $reference]);
            return self::ALREADY;
        }

        // Commission affiliation — hors transaction, idempotente (UNIQUE payment_id)
        try {
            $this->affiliateService->tryCreateCommission($payment->refresh());
        } catch (\Throwable $e) {
            Log::error('[COURSE][PAYSTACK] affiliateCommission error', [
                'reference'  => $reference,
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        Log::info('[COURSE][PAYSTACK] cours débloqué', [
            'reference' => $reference,
            'user_id'   => $payment->user_id,
            'course_id' => data_get($payment->meta, 'course_id'),
        ]);

        return self::GRANTED;
    }
}
