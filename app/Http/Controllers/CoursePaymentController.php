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
use App\Services\PaystackService;


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

    public function buyPaystack(Course $course, Request $request, PaystackService $paystack)
{
    $user = $request->user();

    // ✅ déjà acheté ?
    $already = CoursePurchase::where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->whereNotNull('paid_at')
        ->exists();

    if ($already) {
        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'Cours déjà acheté ✅');
    }

    // ✅ purchase ensured
    $purchase = CoursePurchase::firstOrCreate(
        ['user_id' => $user->id, 'course_id' => $course->id],
        ['amount_fcfa' => (int) $course->price_fcfa]
    );

    // ✅ reference unique
    $transactionId = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::upper(Str::random(10));

    // ✅ log payment
    $payment = Payment::create([
        'user_id'        => $user->id,
        'transaction_id' => $transactionId,
        'amount_paid'    => (int) $course->price_fcfa,
        'amount_virtual' => 0,
        'purpose'        => 'course',
        'status'         => 'PENDING',
        'credited_at'    => null,
        'meta'           => [
            'type'        => 'course',
            'course_id'   => $course->id,
            'purchase_id' => $purchase->id,
            'provider'    => 'paystack',
        ],
    ]);

    $callbackUrl = route('paystack.courses.callback', [], true);

    // ⚠️ IMPORTANT Paystack: amount doit être en plus petite unité.
    // Si ton Paystack est en XOF-like: souvent amount * 100.
    // (si ton Paystack attend déjà XOF direct, enlève *100, mais la majorité = *100)
    $authUrl = $paystack->initialize([
        'email'        => $user->email,
        'amount'       => (int) $payment->amount_paid, // ton PaystackService peut déjà faire *100 dedans
        // 'currency'  => 'XOF', // mets seulement si ton compte supporte, sinon enlève
        'reference'    => $payment->transaction_id,
        'callback_url' => $callbackUrl,
        'metadata'     => [
            'purpose'    => 'course',
            'course_id'  => $course->id,
            'purchase_id'=> $purchase->id,
            'user_id'    => $user->id,
        ],
    ]);

    if (!$authUrl) {
        $payment->status = 'FAILED';
        $payment->save();

        return back()->with('error', "❌ Impossible d'initialiser Paystack.");
    }

    return redirect()->away($authUrl);
}

public function paystackCallback(Request $request, PaystackService $paystack)
{
    $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

    Log::info('[COURSE][PAYSTACK_CALLBACK] hit', [
        'reference' => $reference,
        'all' => $request->all(),
        'url' => $request->fullUrl(),
    ]);

    if (!$reference) {
        return redirect()->route('courses.index')->with('error', 'Référence Paystack manquante.');
    }

    $data = $paystack->verify($reference);

    if (!$data) {
        return redirect()->route('courses.index')->with('error', 'Impossible de vérifier la transaction Paystack.');
    }

    if (($data['status'] ?? null) !== 'success') {
        return redirect()->route('courses.index')->with('error', 'Paiement non validé : ' . ($data['status'] ?? 'unknown'));
    }

    $payment = Payment::where('transaction_id', $reference)->first();

    if (!$payment) {
        Log::error('[COURSE][PAYSTACK_CALLBACK] payment not found', ['reference' => $reference, 'data' => $data]);
        return redirect()->route('courses.index')->with('error', 'Paiement introuvable en base.');
    }

    // ✅ Sécurité montant (Paystack renvoie souvent amount en *100)
    $paid = (int) (($data['amount'] ?? 0) / 100);
    if ($paid <= 0 || $paid !== (int) $payment->amount_paid) {
        Log::error('[COURSE][PAYSTACK_CALLBACK] amount mismatch', [
            'reference' => $reference,
            'db_amount' => (int) $payment->amount_paid,
            'paid' => $paid,
            'raw_amount' => $data['amount'] ?? null,
        ]);
        return redirect()->route('courses.index')->with('error', 'Montant Paystack invalide.');
    }

    DB::transaction(function () use ($payment, $reference, $data) {

        // ✅ lock payment
        $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->first();

        // idempotent
        if ($paymentLocked->status === 'ACCEPTED' && $paymentLocked->credited_at) {
            return;
        }

        $paymentLocked->status = 'ACCEPTED';
        $paymentLocked->credited_at = $paymentLocked->credited_at ?? now();
        $paymentLocked->meta = array_merge((array) ($paymentLocked->meta ?? []), [
            'paystack' => [
                'reference' => $reference,
                'channel'   => $data['channel'] ?? null,
                'paid_at'   => $data['paid_at'] ?? null,
            ],
        ]);
        $paymentLocked->save();

        // ✅ marquer purchase course paid (lock purchase si tu veux être extra strict)
        $meta = (array) ($paymentLocked->meta ?? []);
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

        if ($purchase && !$purchase->paid_at) {
            $purchase->paid_at = now();
            $purchase->payment_ref = $reference;
            $purchase->save();
        }
    });

    return redirect()->route('courses.my')
        ->with('success', "✅ Paiement Paystack confirmé. Ton cours est disponible.");
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
