<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\VirtualWallet;
use App\Models\VirtualWalletTransaction;
use App\Services\CinetpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReceivedMail;

class PaymentController extends Controller
{
    // ✅ IPN appelé par CinetPay (POST)
    public function cinetpayIpn(Request $request, CinetpayService $cinetpay)
    {
        $transactionId = $request->input('transaction_id') ?? $request->input('cpm_trans_id');

        if (!$transactionId) {
            Log::warning('IPN sans transaction_id', ['payload' => $request->all()]);
            return response('missing transaction_id', 400);
        }

        $status = $cinetpay->checkPayment($transactionId); // ACCEPTED / REFUSED / PENDING

        if (!$status) {
            return response('status null', 200);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            Log::warning('IPN payment introuvable', ['transaction_id' => $transactionId]);
            return response('payment not found', 200);
        }

        // On met à jour le statut + on crédite si ACCEPTED + on envoie mail (1 seule fois)
        $this->applyPaymentStatusAndCreditIfNeeded($payment, $status);

        return response('OK', 200);
    }

    // ✅ Return utilisateur (GET)
    public function cinetpayReturn(Request $request, CinetpayService $cinetpay)
    {
        $transactionId = $request->query('transaction_id')
            ?? $request->query('cpm_trans_id');

        if (!$transactionId) {
            return redirect()->route('wallet.index')->with('error', 'Transaction introuvable.');
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        if (!$payment) {
            return redirect()->route('wallet.index')->with('error', 'Paiement introuvable.');
        }

        // On re-check côté serveur (fiable)
        $status = $cinetpay->checkPayment($transactionId) ?? 'PENDING';

        $this->applyPaymentStatusAndCreditIfNeeded($payment, $status);

        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return redirect()->route('wallet.index')->with('success', 'Paiement validé ✅ Portefeuille crédité !');
        }

        if ($payment->status === 'REFUSED') {
            return redirect()->route('wallet.index')->with('error', 'Paiement refusé ❌');
        }

        return redirect()->route('wallet.index')->with('error', 'Paiement en attente ⏳ (réessaie dans 1 minute).');
    }

    private function applyPaymentStatusAndCreditIfNeeded(Payment $payment, string $status): void
    {
        DB::transaction(function () use ($payment, $status) {

            // refresh + lock pour éviter double crédit / double mail
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            // Mise à jour du statut
            $payment->status = $status;
            $payment->save();

            // Si pas accepté => stop
            if ($status !== 'ACCEPTED') {
                return;
            }

            /**
             * 1) Envoi email admin (UNE SEULE FOIS)
             * On le fait ici dès que c'est ACCEPTED, même si déjà crédité, car parfois le crédit est déjà fait
             * mais le mail non envoyé (ou inverse). Donc on se base sur notified_at.
             */
            if (!$payment->notified_at) {
                try {
                    // charger user pour le mail (si relation user() existe)
                    $payment->loadMissing('user');

                    Mail::to([
                        'maccladder@gmail.com',
                        'ghislainkouadiodjaha@gmail.com',
                    ])->send(new PaymentReceivedMail($payment));

                    $payment->notified_at = now();
                    $payment->save();
                } catch (\Throwable $e) {
                    Log::error('Erreur envoi mail paiement', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Important: on ne bloque pas le crédit même si le mail échoue
                }
            }

            /**
             * 2) Crédit du wallet (UNE SEULE FOIS)
             */
            if ($payment->credited_at) {
                return;
            }

            $wallet = VirtualWallet::firstOrCreate(
                ['user_id' => $payment->user_id],
                ['balance' => 0]
            );

            // crédit wallet
            $wallet->increment('balance', $payment->amount_virtual);

            // log transaction wallet (en minuscule si tu as une contrainte CHECK)
            VirtualWalletTransaction::create([
                'user_id' => $payment->user_id,
                'type' => 'topup', // IMPORTANT: 'topup' (pas TOPUP)
                'amount' => $payment->amount_virtual,
                'meta' => [
                    'source' => 'cinetpay',
                    'transaction_id' => $payment->transaction_id,
                    'paid' => $payment->amount_paid,
                ],
            ]);

            $payment->credited_at = now();
            $payment->save();
        });
    }
}
