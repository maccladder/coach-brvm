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
     * Etape 1 : click "Acheter"
     * -> crée purchase + payment PENDING
     * -> redirige vers CinetPay
     */
    public function buy(Course $course, Request $request, CinetpayService $cinetpay)
    {
        $user = $request->user();

        Log::info('[COURSE][BUY] start', [
            'user_id' => $user?->id,
            'course_id' => $course->id,
            'course_price_fcfa' => $course->price_fcfa,
        ]);

        // Déjà acheté ?
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

        // Créer/assurer une ligne d’achat (non payé)
        $purchase = CoursePurchase::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['amount_fcfa' => (int) $course->price_fcfa]
        );

        Log::info('[COURSE][BUY] purchase ensured', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'course_id' => $purchase->course_id,
            'paid_at' => $purchase->paid_at,
            'amount_fcfa' => $purchase->amount_fcfa,
        ]);

        // transaction_id unique
        $transactionId = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::uuid()->toString();

        // Créer un Payment (PENDING)
        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount_paid'    => (int) $course->price_fcfa,
            'amount_virtual' => 0,               // NOT NULL dans ta table
            'purpose'        => 'course',         // utile
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

        // URLs envoyées à CinetPay
        $notifyUrl = route('cinetpay.ipn.course');
        $returnUrl = route('cinetpay.return.course', ['transaction_id' => $payment->transaction_id]);

        Log::info('[COURSE][BUY] cinetpay urls', [
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount_paid,
        ]);

        // Rediriger vers CinetPay
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
            Log::error('[COURSE][BUY] createPayment returned empty url', [
                'transaction_id' => $payment->transaction_id,
            ]);

            return back()->with('error', 'Impossible de générer le paiement CinetPay.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Etape 2 : retour navigateur après paiement.
     * IMPORTANT : peut arriver en GET ou POST, et l’id peut être cpm_trans_id.
     * On vérifie via l’API CinetPay avant de marquer payé.
     */
    public function return(Request $request, CinetpayService $cinetpay)
    {
        Log::info('[COURSE][RETURN] incoming', [
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'all' => $request->all(),
            'ip' => $request->ip(),
        ]);

        // CinetPay peut renvoyer transaction_id OU cpm_trans_id
        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

        Log::info('[COURSE][RETURN] transactionId resolved', [
            'transaction_id' => $transactionId,
        ]);

        if (!$transactionId) {
            Log::warning('[COURSE][RETURN] missing transaction id');

            return redirect()->route('courses.index')
                ->with('error', 'Transaction introuvable (return).');
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::warning('[COURSE][RETURN] payment not found', [
                'transaction_id' => $transactionId,
            ]);

            return redirect()->route('courses.index')
                ->with('error', 'Paiement introuvable.');
        }

        Log::info('[COURSE][RETURN] payment found', [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'credited_at' => $payment->credited_at,
            'user_id' => $payment->user_id,
        ]);

        // Si déjà validé => ok direct
        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            Log::info('[COURSE][RETURN] already accepted', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return redirect()->route('courses.my')
                ->with('success', 'Paiement déjà validé ✅');
        }

        // Vérification serveur via API
        try {
            Log::info('[COURSE][RETURN] calling checkPayment', [
                'transaction_id' => $transactionId,
            ]);

            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][RETURN] checkPayment response', [
                'transaction_id' => $transactionId,
                'response' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][RETURN] checkPayment exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('courses.index')
                ->with('error', 'Paiement en cours de confirmation. Réessaie dans 1 minute.');
        }

        $status = strtoupper((string)($check['status'] ?? $check['data']['status'] ?? ''));

        Log::info('[COURSE][RETURN] computed status', [
            'transaction_id' => $transactionId,
            'computed_status' => $status,
        ]);

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            Log::info('[COURSE][RETURN] marking course paid', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            $this->markCoursePaid($payment);

            return redirect()->route('courses.my')
                ->with('success', 'Paiement validé ✅ Votre cours est disponible.');
        }

        Log::warning('[COURSE][RETURN] payment not confirmed', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'computed_status' => $status,
            'raw' => $check,
        ]);

        return redirect()->route('courses.index')
            ->with('error', 'Paiement non confirmé ou annulé.');
    }

    /**
     * Etape 3 : IPN serveur-à-serveur (le plus fiable).
     * Même logique : on check via API puis on marque payé.
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
            Log::warning('[COURSE][IPN] missing transaction id');
            return response('missing transaction_id', 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::warning('[COURSE][IPN] payment not found', [
                'transaction_id' => $transactionId,
            ]);
            return response('payment not found', 404);
        }

        Log::info('[COURSE][IPN] payment found', [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'credited_at' => $payment->credited_at,
            'user_id' => $payment->user_id,
        ]);

        // déjà traité => ok
        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            Log::info('[COURSE][IPN] already accepted', [
                'payment_id' => $payment->id,
            ]);
            return response('already ok', 200);
        }

        try {
            Log::info('[COURSE][IPN] calling checkPayment', [
                'transaction_id' => $transactionId,
            ]);

            $check = $cinetpay->checkPayment($transactionId);

            Log::info('[COURSE][IPN] checkPayment response', [
                'transaction_id' => $transactionId,
                'response' => $check,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][IPN] checkPayment exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return response('check failed', 200); // pas de 500 à CinetPay
        }

        $status = strtoupper((string)($check['status'] ?? $check['data']['status'] ?? ''));

        Log::info('[COURSE][IPN] computed status', [
            'transaction_id' => $transactionId,
            'computed_status' => $status,
        ]);

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            Log::info('[COURSE][IPN] marking course paid', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            $this->markCoursePaid($payment);

            return response('ok', 200);
        }

        Log::warning('[COURSE][IPN] ignored - not accepted', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'computed_status' => $status,
            'raw' => $check,
        ]);

        return response('ignored', 200);
    }

    /**
     * Marquer le paiement + l'achat comme payé (idempotent).
     */
    private function markCoursePaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            Log::info('[COURSE][MARK] start', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'credited_at' => $payment->credited_at,
            ]);

            // déjà traité
            if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
                Log::info('[COURSE][MARK] already accepted - stop', [
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            $payment->status = 'ACCEPTED';
            $payment->credited_at = now();
            $payment->save();

            Log::info('[COURSE][MARK] payment updated', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'credited_at' => $payment->credited_at,
            ]);

            $meta = $payment->meta ?? [];
            $purchaseId = $meta['purchase_id'] ?? null;
            $courseId = $meta['course_id'] ?? null;

            $purchase = null;

            if ($purchaseId) {
                $purchase = CoursePurchase::find($purchaseId);
                Log::info('[COURSE][MARK] purchase by id', [
                    'purchase_id' => $purchaseId,
                    'found' => (bool) $purchase,
                ]);
            }

            if (!$purchase && $courseId) {
                $purchase = CoursePurchase::where('user_id', $payment->user_id)
                    ->where('course_id', $courseId)
                    ->first();

                Log::info('[COURSE][MARK] purchase by user/course', [
                    'user_id' => $payment->user_id,
                    'course_id' => $courseId,
                    'found' => (bool) $purchase,
                    'purchase_id' => $purchase?->id,
                ]);
            }

            if ($purchase && !$purchase->paid_at) {
                $purchase->paid_at = now();
                $purchase->payment_ref = $payment->transaction_id;
                $purchase->amount_fcfa = $purchase->amount_fcfa ?? $payment->amount_paid;
                $purchase->save();

                Log::info('[COURSE][MARK] purchase marked paid', [
                    'purchase_id' => $purchase->id,
                    'paid_at' => $purchase->paid_at,
                    'payment_ref' => $purchase->payment_ref,
                    'amount_fcfa' => $purchase->amount_fcfa,
                ]);
            } else {
                Log::warning('[COURSE][MARK] purchase not updated', [
                    'purchase_found' => (bool) $purchase,
                    'already_paid' => $purchase?->paid_at ? true : false,
                    'purchase_id' => $purchase?->id,
                ]);
            }
        });
    }
}
