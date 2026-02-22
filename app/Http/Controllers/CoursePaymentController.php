<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\Payment;
use App\Services\CinetpayService;
use App\Services\PaystackService;
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
            return ['_raw' => $value];
        }

        return json_decode(json_encode($value), true) ?? ['_raw' => $value];
    }

    /**
     * Extrait le status de manière robuste.
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

    // ============================================================
    // CinetPay (tu peux laisser si tu veux, sinon tu peux retirer)
    // ============================================================

    public function buy(Course $course, Request $request, CinetpayService $cinetpay)
    {
        $user = $request->user();

        $already = CoursePurchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists();

        if ($already) {
            return redirect()->route('courses.show', $course->slug)
                ->with('success', 'Cours déjà acheté ✅');
        }

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
            'meta'           => [
                'type'        => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
                'provider'    => 'cinetpay',
            ],
        ]);

        $notifyUrl = route('cinetpay.ipn.course');
        $returnUrl = route('cinetpay.return.course', ['transaction_id' => $payment->transaction_id]);

        try {
            $paymentUrl = $cinetpay->createPayment([
                'transaction_id' => $payment->transaction_id,
                'amount'         => $payment->amount_paid,
                'description'    => 'Achat formation : ' . $course->title,
                'notify_url'     => $notifyUrl,
                'return_url'     => $returnUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('[COURSE][BUY][CINETPAY] exception', [
                'transaction_id' => $payment->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de générer le paiement CinetPay.');
        }

        if (!$paymentUrl) {
            return back()->with('error', 'Impossible de générer le paiement CinetPay.');
        }

        return redirect()->away($paymentUrl);
    }

    public function return(Request $request, CinetpayService $cinetpay)
    {
        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpmTransId');

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
            $check = $cinetpay->checkPayment($transactionId);
        } catch (\Throwable $e) {
            Log::error('[COURSE][RETURN][CINETPAY] check exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('courses.index')
                ->with('error', 'Paiement en cours de confirmation. Réessaie dans 1 minute.');
        }

        $status = $this->extractStatus($check);

        if (in_array($status, ['ACCEPTED', 'SUCCESS', 'SUCCES'], true)) {
            $this->markCoursePaid($payment);

            // ✅ Purchase pixel (flash) — pour éviter doublons
            return redirect()->route('courses.my')->with('success', 'Paiement validé ✅ Votre cours est disponible.')
                ->with('pixel_purchase', [
                    'content_type' => 'product',
                    'content_ids'  => [ (string) data_get($payment->meta, 'course_id') ],
                    'content_name' => (string) optional(Course::find(data_get($payment->meta, 'course_id')))->title,
                    'value'        => (int) $payment->amount_paid,
                    'currency'     => 'XOF',
                    'num_items'    => 1,
                ]);
        }

        return redirect()->route('courses.index')->with('error', 'Paiement non confirmé ou annulé.');
    }

    public function ipn(Request $request, CinetpayService $cinetpay)
    {
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
        } catch (\Throwable $e) {
            Log::error('[COURSE][IPN][CINETPAY] check exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return response('ok', 200);
        }

        $status = $this->extractStatus($check);

        if (in_array($status, ['ACCEPTED', 'SUCCESS', 'SUCCES'], true)) {
            $this->markCoursePaid($payment);
            return response('ok', 200);
        }

        return response('ignored', 200);
    }

    // ============================================================
    // ✅ PAYSTACK (celui qu'on utilise)
    // ============================================================

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

        $authUrl = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => (int) $payment->amount_paid, // ton PaystackService peut faire *100 dedans
            'reference'    => $payment->transaction_id,
            'callback_url' => $callbackUrl,
            'metadata'     => [
                'purpose'     => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
                'user_id'     => $user->id,
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

        $courseTitle = null;
        $courseId    = (int) data_get($payment->meta, 'course_id');
        if ($courseId) {
            $courseTitle = optional(Course::find($courseId))->title;
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

            // ✅ marquer purchase course paid
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

        // ✅ Purchase Pixel (flash session) -> à déclencher côté blade de courses.my
        return redirect()->route('courses.my')
            ->with('success', "✅ Paiement Paystack confirmé. Ton cours est disponible.")
            ->with('pixel_purchase', [
                'content_type' => 'product',
                'content_ids'  => [ (string) $courseId ],
                'content_name' => (string) ($courseTitle ?? ''),
                'value'        => (int) $payment->amount_paid,
                'currency'     => 'XOF',
                'num_items'    => 1,
            ]);
    }

    /**
     * Marquer le paiement + l'achat comme payé (utilisé par CinetPay)
     */
    private function markCoursePaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
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
            }
        });
    }
}
