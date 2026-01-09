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

        // Déjà acheté ?
        $already = CoursePurchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('paid_at')
            ->exists();

        if ($already) {
            return redirect()->route('courses.show', $course->slug)
                ->with('success', 'Cours déjà acheté ✅');
        }

        // Créer/assurer une ligne d’achat (non payé)
        $purchase = CoursePurchase::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['amount_fcfa' => $course->price_fcfa]
        );

        // transaction_id unique
        $transactionId = 'COURSE-' . $course->id . '-' . $user->id . '-' . Str::uuid()->toString();

        // Créer un Payment (PENDING)
        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount_paid'    => (int) $course->price_fcfa,
            'amount_virtual' => 0,              // NOT NULL dans ta table
            'purpose'        => 'course',        // utile
            'status'         => 'PENDING',
            'meta'           => [
                'type'        => 'course',
                'course_id'   => $course->id,
                'purchase_id' => $purchase->id,
            ],
        ]);

        // Rediriger vers CinetPay
        $paymentUrl = $cinetpay->createPayment([
            'transaction_id' => $payment->transaction_id,
            'amount'         => $payment->amount_paid,
            'description'    => 'Achat formation : ' . $course->title,
            'notify_url'     => route('cinetpay.ipn.course'),
            'return_url'     => route('cinetpay.return.course', ['transaction_id' => $payment->transaction_id]),
        ]);

        if (!$paymentUrl) {
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
        // CinetPay peut renvoyer transaction_id OU cpm_trans_id
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

        // Si déjà validé => ok direct
        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return redirect()->route('courses.my')->with('success', 'Paiement déjà validé ✅');
        }

        // Vérification serveur via API
        try {
            $check = $cinetpay->checkPayment($transactionId);
        } catch (\Throwable $e) {
            Log::error('CinetPay checkPayment failed (return)', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('courses.index')
                ->with('error', 'Paiement en cours de confirmation. Réessaie dans 1 minute.');
        }

        $status = strtoupper((string)($check['status'] ?? $check['data']['status'] ?? ''));

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            $this->markCoursePaid($payment);
            return redirect()->route('courses.my')->with('success', 'Paiement validé ✅ Votre cours est disponible.');
        }

        return redirect()->route('courses.index')->with('error', 'Paiement non confirmé ou annulé.');
    }

    /**
     * Etape 3 : IPN serveur-à-serveur (le plus fiable).
     * Même logique : on check via API puis on marque payé.
     */
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

        // déjà traité => ok
        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return response('already ok', 200);
        }

        try {
            $check = $cinetpay->checkPayment($transactionId);
        } catch (\Throwable $e) {
            Log::error('CinetPay checkPayment failed (ipn)', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return response('check failed', 200); // on ne renvoie pas 500 à CinetPay
        }

        $status = strtoupper((string)($check['status'] ?? $check['data']['status'] ?? ''));

        if ($status === 'ACCEPTED' || $status === 'SUCCESS') {
            $this->markCoursePaid($payment);
            return response('ok', 200);
        }

        return response('ignored', 200);
    }

    /**
     * Marquer le paiement + l'achat comme payé (idempotent).
     */
    private function markCoursePaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            // déjà traité
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
                $purchase->amount_fcfa = $payment->amount_fcfa ?? $payment->amount_paid; // au cas où
                $purchase->save();
            }
        });
    }
}
