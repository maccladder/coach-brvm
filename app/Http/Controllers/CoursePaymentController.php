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
     * Convertit n'importe quel retour (array|object|string) en array.
     */
    private function toArray(mixed $value): array
    {
        if (is_array($value)) return $value;

        if (is_string($value)) {
            // Si le service renvoie directement "ACCEPTED"
            return ['_raw' => $value];
        }

        // stdClass / objets => on convertit
        return json_decode(json_encode($value), true) ?? ['_raw' => $value];
    }

    /**
     * Extrait le status de manière robuste.
     * On accepte "ACCEPTED" / "SUCCESS" / "SUCCES" (selon implémentation).
     */
    private function extractStatus(mixed $check): string
    {
        $arr = $this->toArray($check);

        $candidates = [
            $arr['status'] ?? null,
            $arr['response'] ?? null,
            $arr['data']['status'] ?? null,
            $arr['data']['response'] ?? null,
            $arr['payment_status'] ?? null,
            $arr['transaction']['status'] ?? null,
            $arr['_raw'] ?? null,
        ];

        foreach ($candidates as $cand) {
            $cand = strtoupper(trim((string) $cand));
            if ($cand !== '') return $cand;
        }

        return '';
    }

    /**
     * Étape 1 : click acheter
     */
    public function buy(Course $course, Request $request, CinetpayService $cinetpay)
    {
        $user = $request->user();

        Log::info('[COURSE][BUY] start', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'price' => $course->price_fcfa,
        ]);

        $already = CoursePurchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists();

        if ($already) {
            Log::info('[COURSE][BUY] already purchased', [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);

            return redirect()->route('courses.show', $course->slug)
                ->with('success', 'Cours déjà acheté ✅');
        }

        $purchase = CoursePurchase::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['amount_fcfa' => (int) $course->price_fcfa]
        );

        Log::info('[COURSE][BUY] purchase ensured', [
            'purchase_id' => $purchase->id,
            'paid_at' => $purchase->paid_at,
            'amount_fcfa' => $purchase->amount_fcfa,
        ]);

        $transactionId = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::uuid();

        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount_paid'    => (int) $course->price_fcfa,
            'amount_virtual' => 0,
            'purpose'        => 'course',
            'status'         => 'PENDING',
            'meta'           => [
                'type'        => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
            ],
        ]);

        Log::info('[COURSE][BUY] payment created', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status,
            'amount_paid' => $payment->amount_paid,
        ]);

        $notifyUrl = route('cinetpay.ipn.course');
        $returnUrl = route('cinetpay.return.course', ['transaction_id' => $payment->transaction_id]);

        Log::info('[COURSE][BUY] cinetpay urls', [
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
        ]);

        try {
            $paymentUrl = $cinetpay->createPayment([
                'transaction_id' => $payment->transaction_id,
                'amount'         => $payment->amount_paid,
                'description'    => 'Achat formation : ' . $course->title,
                'notify_url'     => $notifyUrl,
                'return_url'     => $returnUrl,
            ]);

            Log::info('[COURSE][BUY] createPayment response', [
                'transaction_id' => $payment->transaction_id,
                'payment_url' => $paymentUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][BUY] createPayment exception', [
                'transaction_id' => $payment->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de générer le paiement CinetPay.');
        }

        if (!$paymentUrl) {
            Log::error('[COURSE][BUY] empty paymentUrl', [
                'transaction_id' => $payment->transaction_id,
            ]);

            return back()->with('error', 'Impossible de générer le paiement CinetPay.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Étape 2 : RETURN navigateur
     */
    public function return(Request $request, CinetpayService $cinetpay)
    {
        Log::info('[COURSE][RETURN] incoming', [
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'all' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

        Log::info('[COURSE][RETURN] transactionId resolved', [
            'transaction_id' => $transactionId,
        ]);

        if (!$transactionId) {
            return redirect()->route('courses.index')->with('error', 'Transaction introuvable (return).');
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            return redirect()->route('courses.index')->with('error', 'Paiement introuvable.');
        }

        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return redirect()->route('courses.my')->with('success', 'Paiement déjà validé ✅');
        }

        try {
            Log::info('[COURSE][RETURN] calling checkPayment', ['transaction_id' => $transactionId]);

            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][RETURN] checkPayment raw', [
                'raw_type' => gettype($check),
                'raw' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][RETURN] checkPayment exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('courses.index')->with('error', 'Paiement en cours de confirmation. Réessaie dans 1 minute.');
        }

        $status = $this->extractStatus($check);

        Log::info('[COURSE][RETURN] resolved status', [
            'transaction_id' => $transactionId,
            'status' => $status,
        ]);

        if (in_array($status, ['ACCEPTED', 'SUCCESS', 'SUCCES'], true)) {
            $this->markCoursePaid($payment);
            return redirect()->route('courses.my')->with('success', 'Paiement validé ✅ Votre cours est disponible.');
        }

        Log::warning('[COURSE][RETURN] payment not confirmed', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'status' => $status,
            'raw' => $check,
        ]);

        return redirect()->route('courses.index')->with('error', 'Paiement non confirmé ou annulé.');
    }

    /**
     * Étape 3 : IPN serveur-à-serveur
     */
    public function ipn(Request $request, CinetpayService $cinetpay)
    {
        Log::info('[COURSE][IPN] incoming', [
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'all' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

        Log::info('[COURSE][IPN] transactionId resolved', [
            'transaction_id' => $transactionId,
        ]);

        if (!$transactionId) {
            Log::warning('[COURSE][IPN] missing transaction_id');
            return response('missing transaction_id', 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            Log::warning('[COURSE][IPN] payment not found', ['transaction_id' => $transactionId]);
            return response('payment not found', 404);
        }

        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            Log::info('[COURSE][IPN] already accepted', ['payment_id' => $payment->id]);
            return response('already ok', 200);
        }

        try {
            Log::info('[COURSE][IPN] calling checkPayment', ['transaction_id' => $transactionId]);

            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][IPN] checkPayment raw', [
                'raw_type' => gettype($check),
                'raw' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][IPN] checkPayment exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return response('ok', 200);
        }

        $status = $this->extractStatus($check);

        Log::info('[COURSE][IPN] resolved status', [
            'transaction_id' => $transactionId,
            'status' => $status,
        ]);

        if (in_array($status, ['ACCEPTED', 'SUCCESS', 'SUCCES'], true)) {
            $this->markCoursePaid($payment);
            return response('ok', 200);
        }

        Log::warning('[COURSE][IPN] ignored - not accepted', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'status' => $status,
            'raw' => $check,
        ]);

        return response('ignored', 200);
    }

    /**
     * Marquer le paiement + l'achat comme payé
     */
    private function markCoursePaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            Log::info('[COURSE][MARK] start', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
            ]);

            if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
                Log::info('[COURSE][MARK] already accepted - stop', ['payment_id' => $payment->id]);
                return;
            }

            $payment->status = 'ACCEPTED';
            $payment->credited_at = now();
            $payment->save();

            $meta = $payment->meta ?? [];
            $purchaseId = $meta['purchase_id'] ?? null;
            $courseId = $meta['course_id'] ?? null;

            $purchase = null;

            if ($purchaseId) {
                $purchase = CoursePurchase::find($purchaseId);
            }

            if (!$purchase && $courseId) {
                $purchase = CoursePurchase::where('user_id', $payment->user_id)
                    ->where('course_id', $courseId)
                    ->first();
            }

            if ($purchase && !$purchase->paid_at) {
                $purchase->paid_at = now();
                $purchase->payment_ref = $payment->transaction_id;
                $purchase->save();

                Log::info('[COURSE][MARK] purchase marked paid', [
                    'purchase_id' => $purchase->id,
                    'paid_at' => $purchase->paid_at,
                ]);
            } else {
                Log::warning('[COURSE][MARK] purchase not updated', [
                    'purchase_found' => (bool) $purchase,
                    'already_paid' => (bool) ($purchase?->paid_at),
                ]);
            }
        });
    }
}
