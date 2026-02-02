<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Services\CinetpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ✅ Ajouts (mail + logs)
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentReceivedMail;

class MarketplacePaymentController extends Controller
{
    public function __construct(private CinetpayService $cinetpay) {}

    public function buy(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        // ✅ déjà acheté ?
        $alreadyPaid = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if ($alreadyPaid) {
            return redirect()
                ->route('marketplace.show', $product->slug)
                ->with('success', '✅ Produit déjà acheté. Tu peux le télécharger.');
        }

        // ✅ transaction id unique
        $transactionId = 'MP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        // 1) Créer/MAJ purchase pending
        DB::table('marketplace_purchases')->updateOrInsert(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'status'        => 'pending',
                'amount'        => (int) $product->price,
                'provider'      => 'cinetpay',
                'provider_ref'  => $transactionId,
                'paid_at'       => null,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        // 2) Log Payment (recommandé)
        Payment::updateOrCreate(
            ['transaction_id' => $transactionId],
            [
                'user_id'         => $user->id,
                'amount_paid'     => (int) $product->price,
                'amount_virtual'  => 0,
                'purpose'         => 'marketplace',
                'status'          => 'pending',
                'credited_at'     => null,
                'meta'            => [
                    'product_id'    => $product->id,
                    'product_slug'  => $product->slug,
                    'product_title' => $product->title,
                ],
            ]
        );

        // 3) Redirection CinetPay
        $paymentUrl = $this->cinetpay->createPayment([
            'transaction_id' => $transactionId,
            'amount'         => (int) $product->price,
            'description'    => "Achat Marketplace: {$product->title}",
            'notify_url'     => route('cinetpay.notify.marketplace'),

            // ✅ CRUCIAL : on passe transaction_id dans return_url
            'return_url'     => route('cinetpay.return.marketplace', ['transaction_id' => $transactionId]),

            'customer_name'  => $user->name ?? 'Client',
            'customer_email' => $user->email ?? null,
            'metadata'       => (string) $transactionId,
        ]);

        if (!$paymentUrl) {
            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            $oldMeta = Payment::where('transaction_id', $transactionId)->value('meta') ?? [];
            Payment::where('transaction_id', $transactionId)->update([
                'status' => 'failed',
                'meta'   => array_merge($oldMeta, ['error' => 'createPayment returned null']),
            ]);

            return back()->with('error', "❌ Impossible d'initier le paiement CinetPay.");
        }

        return redirect()->away($paymentUrl);
    }

    public function notify(Request $request)
    {
        $transactionId = $request->input('transaction_id')
            ?? $request->input('cpm_trans_id')
            ?? $request->input('cpm_transaction_id');

        if (!$transactionId) {
            return response()->json(['ok' => false, 'message' => 'missing transaction_id'], 400);
        }

        // ✅ check status côté CinetPay
        $status = $this->cinetpay->checkPayment($transactionId) ?? 'PENDING';

        // ✅ tracer payload IPN
        $oldMeta = Payment::where('transaction_id', $transactionId)->value('meta') ?? [];
        Payment::where('transaction_id', $transactionId)->update([
            'meta' => array_merge($oldMeta, [
                'ipn_payload'    => $request->all(),
                'checked_status' => $status,
            ]),
        ]);

        // ✅ Paiement validé
        if (in_array($status, ['ACCEPTED', 'PAID', 'SUCCESS'], true)) {

            DB::transaction(function () use ($transactionId, $request) {

                // lock purchase
                $purchase = DB::table('marketplace_purchases')
                    ->where('provider_ref', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if (!$purchase) {
                    Log::warning('Marketplace notify: purchase introuvable', [
                        'transaction_id' => $transactionId,
                        'payload' => $request->all(),
                    ]);
                    // On continue quand même pour mettre à jour Payment + mail si possible
                } elseif ($purchase->status !== 'paid') {
                    DB::table('marketplace_purchases')
                        ->where('provider_ref', $transactionId)
                        ->update([
                            'status'     => 'paid',
                            'paid_at'    => now(),
                            'updated_at' => now(),
                        ]);
                }

                // lock payment
                $payment = Payment::where('transaction_id', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning('Marketplace notify: payment introuvable', [
                        'transaction_id' => $transactionId,
                        'payload' => $request->all(),
                    ]);
                    return;
                }

                // update payment status
                $payment->status = 'paid';
                $payment->credited_at = $payment->credited_at ?? now(); // juste historiser
                $payment->save();

                // ✅ MAIL ADMIN UNE SEULE FOIS (notified_at)
                if (!$payment->notified_at) {
                    try {
                        $payment->loadMissing('user');

                        Mail::to([
                            'maccladder@gmail.com',
                            'ghislainkouadiodjaha@gmail.com',
                        ])->send(new PaymentReceivedMail($payment));

                        $payment->notified_at = now();
                        $payment->save();
                    } catch (\Throwable $e) {
                        Log::error('Marketplace notify: erreur envoi mail paiement', [
                            'transaction_id' => $transactionId,
                            'error' => $e->getMessage(),
                            'payload' => $request->all(),
                        ]);
                        // Important: ne pas casser la transaction juste pour le mail
                    }
                }
            });

            return response()->json(['ok' => true]);
        }

        // ✅ Refusé / annulé / échoué
        if (in_array($status, ['REFUSED', 'CANCELED', 'FAILED'], true)) {

            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            Payment::where('transaction_id', $transactionId)->update(['status' => 'failed']);

            return response()->json(['ok' => true]);
        }

        // Pending ou inconnu
        return response()->json(['ok' => true, 'pending' => true]);
    }

    public function return(Request $request)
    {
        $transactionId = $request->input('transaction_id')
            ?? $request->input('cpm_trans_id')
            ?? $request->input('cpm_transaction_id')
            ?? $request->query('transaction_id')
            ?? $request->query('cpm_trans_id');

        if (!$transactionId) {
            return redirect()
                ->route('marketplace.index')
                ->with('error', "❌ Retour paiement reçu, mais transaction introuvable.");
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        $productSlug = data_get($payment?->meta, 'product_slug');

        // Optionnel : check rapide si IPN pas encore passé
        $purchase = DB::table('marketplace_purchases')->where('provider_ref', $transactionId)->first();

        if ($purchase && $purchase->status !== 'paid') {
            $status = $this->cinetpay->checkPayment($transactionId) ?? 'PENDING';

            if (in_array($status, ['ACCEPTED', 'PAID', 'SUCCESS'], true)) {

                DB::table('marketplace_purchases')
                    ->where('provider_ref', $transactionId)
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);

                // update Payment + (backup mail si IPN n'est jamais appelé)
                if ($payment) {
                    $payment->status = 'paid';
                    $payment->credited_at = $payment->credited_at ?? now();
                    $payment->save();

                    // ✅ Backup notif mail (si IPN n'a pas envoyé)
                    if (!$payment->notified_at) {
                        try {
                            $payment->loadMissing('user');

                            Mail::to([
                                'maccladder@gmail.com',
                                'ghislainkouadiodjaha@gmail.com',
                            ])->send(new PaymentReceivedMail($payment));

                            $payment->notified_at = now();
                            $payment->save();
                        } catch (\Throwable $e) {
                            Log::error('Marketplace return: erreur envoi mail paiement', [
                                'transaction_id' => $transactionId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        } else {
            // Si purchase est déjà paid, on peut aussi faire backup mail au cas où
            if ($payment && $payment->status === 'paid' && !$payment->notified_at) {
                try {
                    $payment->loadMissing('user');

                    Mail::to([
                        'maccladder@gmail.com',
                        'ghislainkouadiodjaha@gmail.com',
                    ])->send(new PaymentReceivedMail($payment));

                    $payment->notified_at = now();
                    $payment->save();
                } catch (\Throwable $e) {
                    Log::error('Marketplace return: erreur backup mail', [
                        'transaction_id' => $transactionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Redirection finale
        if ($productSlug) {
            return redirect()
                ->route('marketplace.show', $productSlug)
                ->with('success', "✅ Paiement reçu. Si validé, le produit est débloqué.");
        }

        return redirect()
            ->route('my.products')
            ->with('success', "✅ Paiement reçu. Si validé, ton produit est débloqué.");
    }
}
