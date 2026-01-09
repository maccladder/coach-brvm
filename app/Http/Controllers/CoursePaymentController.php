<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\Payment;
use App\Services\CinetpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CoursePaymentController extends Controller
{
    /**
     * ÉTAPE 1 : clic sur "Acheter"
     */
    public function buy(Course $course, Request $request, CinetpayService $cinetpay)
    {
        $user = $request->user();

        Log::info('[COURSE][BUY] start', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'price' => $course->price_fcfa,
        ]);

        // Déjà acheté ?
        if (CoursePurchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists()
        ) {
            return redirect()->route('courses.show', $course->slug)
                ->with('success', 'Cours déjà acheté ✅');
        }

        // Achat
        $purchase = CoursePurchase::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['amount_fcfa' => (int) $course->price_fcfa]
        );

        $transactionId = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::uuid();

        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount_paid'    => (int) $course->price_fcfa,
            'amount_virtual' => 0,
            'purpose'        => 'course',
            'status'         => 'PENDING',
            'meta' => [
                'type'        => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
            ],
        ]);

        Log::info('[COURSE][BUY] payment created', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
        ]);

        $notifyUrl = route('cinetpay.ipn.course');
        $returnUrl = route('cinetpay.return.course', ['transaction_id' => $transactionId]);

        try {
            $paymentUrl = $cinetpay->createPayment([
                'transaction_id' => $transactionId,
                'amount'         => $payment->amount_paid,
                'description'    => 'Achat formation : ' . $course->title,
                'notify_url'     => $notifyUrl,
                'return_url'     => $returnUrl,
            ]);

            Log::info('[COURSE][BUY] redirecting to cinetpay', [
                'url' => $paymentUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][BUY] cinetpay error', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur paiement.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * ÉTAPE 2 : RETURN navigateur
     */
    public function return(Request $request, CinetpayService $cinetpay)
    {
        Log::info('[COURSE][RETURN] incoming', [
            'all' => $request->all(),
        ]);

        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

        if (!$transactionId) {
            return redirect()->route('courses.index')
                ->with('error', 'Transaction introuvable.');
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            return redirect()->route('courses.index')
                ->with('error', 'Paiement introuvable.');
        }

        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return redirect()->route('courses.my')
                ->with('success', 'Paiement déjà validé ✅');
        }

        try {
            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][RETURN] checkPayment raw', [
                'response' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][RETURN] check failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('courses.index')
                ->with('error', 'Confirmation en cours...');
        }

        // ✅ CORRECTION MAJEURE ICI
        $status = strtoupper(trim(
            $check['status']
            ?? $check['response']
            ?? $check['data']['status']
            ?? ''
        ));

        Log::info('[COURSE][RETURN] resolved status', [
            'status' => $status,
        ]);

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            $this->markCoursePaid($payment);

            return redirect()->route('courses.my')
                ->with('success', 'Paiement validé ✅');
        }

        Log::warning('[COURSE][RETURN] payment not confirmed', [
            'status' => $status,
            'raw' => $check,
        ]);

        return redirect()->route('courses.index')
            ->with('error', 'Paiement non confirmé.');
    }

    /**
     * ÉTAPE 3 : IPN serveur à serveur (LE PLUS FIABLE)
     */
    public function ipn(Request $request, CinetpayService $cinetpay)
    {
        Log::info('[COURSE][IPN] incoming', [
            'all' => $request->all(),
        ]);

        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

        if (!$transactionId) {
            return response('missing transaction_id', 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            return response('payment not found', 404);
        }

        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return response('already ok', 200);
        }

        try {
            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][IPN] checkPayment raw', [
                'response' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][IPN] check failed', [
                'error' => $e->getMessage(),
            ]);

            return response('ok', 200);
        }

        // ✅ CORRECTION MAJEURE ICI AUSSI
        $status = strtoupper(trim(
            $check['status']
            ?? $check['response']
            ?? $check['data']['status']
            ?? ''
        ));

        Log::info('[COURSE][IPN] resolved status', [
            'status' => $status,
        ]);

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            $this->markCoursePaid($payment);
            return response('ok', 200);
        }

        return response('ignored', 200);
    }

    /**
     * Marquer paiement + cours payé (idempotent)
     */
    private function markCoursePaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
                return;
            }

            $payment->update([
                'status' => 'ACCEPTED',
                'credited_at' => now(),
            ]);

            $meta = $payment->meta ?? [];

            $purchase = CoursePurchase::find($meta['purchase_id'] ?? null)
                ?? CoursePurchase::where('user_id', $payment->user_id)
                    ->where('course_id', $meta['course_id'] ?? null)
                    ->first();

            if ($purchase && !$purchase->paid_at) {
                $purchase->update([
                    'paid_at' => now(),
                    'payment_ref' => $payment->transaction_id,
                ]);
            }

            Log::info('[COURSE][MARK] course unlocked', [
                'payment_id' => $payment->id,
                'purchase_id' => $purchase?->id,
            ]);
        });
    }
}
