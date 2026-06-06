<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\VendorPaymentReceivedNotification;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\CinetpayService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentReceivedMail;

// ✅ NEW: mail activation software
use App\Mail\SoftwareActivationMail;

class MarketplacePaymentController extends Controller
{
    public function __construct(private CinetpayService $cinetpay) {}

    private function notifyVendorPayment(string $transactionId): void
    {
        try {
            $payment = \App\Models\Payment::where('transaction_id', $transactionId)
                ->with('user')
                ->first();

            if (!$payment) return;

            $productId = (int) data_get($payment->meta, 'product_id');
            if (!$productId) return;

            $product = \App\Models\MarketplaceProduct::with('vendor')->find($productId);
            if (!$product) return;

            $vendor = $product->vendor ?: (!empty($product->user_id) ? \App\Models\User::find($product->user_id) : null);
            if (!$vendor) return;

            // ✅ anti-doublon: si déjà envoyé pour cette transaction, on stop
            $alreadySent = $vendor->notifications()
                ->where('type', \App\Notifications\VendorPaymentReceivedNotification::class)
                ->where('data->transaction_id', $transactionId)
                ->exists();

            if ($alreadySent) return;

            $buyerName = $payment->user?->name ?? 'Un client';
            $amount    = (int) $payment->amount_paid;

            $vendor->notify(new \App\Notifications\VendorPaymentReceivedNotification(
                transactionId: $transactionId,
                productId: $product->id,
                productTitle: $product->title,
                amount: $amount,
                buyerName: $buyerName,
                url: route('vendor.dashboard')
            ));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('notifyVendorPayment error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdminPayment(string $transactionId): void
    {
        if (!class_exists(AdminNotification::class)) return;

        $payment = Payment::where('transaction_id', $transactionId)->first();
        $title = data_get($payment?->meta, 'product_title', 'Marketplace');
        $amount = (int) ($payment?->amount_paid ?? 0);

        AdminNotification::create([
            'type'    => 'payment_marketplace',
            'title'   => 'Nouveau paiement marketplace',
            'message' => $title . ' — ' . number_format($amount, 0, ',', ' ') . ' FCFA',
            'url'     => route('admin.dashboard'),
            'read_at' => null,
        ]);
    }

    public function buy(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        $alreadyPaid = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if ($alreadyPaid) {
            return redirect()
                ->route('marketplace.show', $product->slug)
                ->with('success', '✅ Produit déjà acheté. Tu peux le télécharger.');
        }

        $transactionId = 'MP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

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

        $paymentUrl = $this->cinetpay->createPayment([
            'transaction_id' => $transactionId,
            'amount'         => (int) $product->price,
            'description'    => "Achat Marketplace: {$product->title}",
            'notify_url'     => route('cinetpay.notify.marketplace'),
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

        $status = $this->cinetpay->checkPayment($transactionId) ?? 'PENDING';

        $oldMeta = Payment::where('transaction_id', $transactionId)->value('meta') ?? [];
        Payment::where('transaction_id', $transactionId)->update([
            'meta' => array_merge($oldMeta, [
                'ipn_payload'    => $request->all(),
                'checked_status' => $status,
            ]),
        ]);

        if (in_array($status, ['ACCEPTED', 'PAID', 'SUCCESS'], true)) {

            DB::transaction(function () use ($transactionId, $request) {

                $purchase = DB::table('marketplace_purchases')
                    ->where('provider_ref', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if ($purchase && $purchase->status !== 'paid') {
                    DB::table('marketplace_purchases')
                        ->where('provider_ref', $transactionId)
                        ->update([
                            'status'     => 'paid',
                            'paid_at'    => now(),
                            'updated_at' => now(),
                        ]);
                }

                $payment = Payment::where('transaction_id', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) return;

                $payment->status = 'paid';
                $payment->credited_at = $payment->credited_at ?? now();
                $payment->save();

                // ✅ MAIL ADMIN une seule fois
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
                    }
                }
            });

            // ✅ notif vendeur + notif admin (hors transaction)
            $this->notifyVendorPayment($transactionId);
            $this->notifyAdminPayment($transactionId);

            return response()->json(['ok' => true]);
        }

        if (in_array($status, ['REFUSED', 'CANCELED', 'FAILED'], true)) {
            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            Payment::where('transaction_id', $transactionId)->update(['status' => 'failed']);

            return response()->json(['ok' => true]);
        }

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

                if ($payment) {
                    $payment->status = 'paid';
                    $payment->credited_at = $payment->credited_at ?? now();
                    $payment->save();

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

                // ✅ notif vendeur + notif admin
                $this->notifyVendorPayment($transactionId);
                $this->notifyAdminPayment($transactionId);
            }
        } else {
            if ($payment && $payment->status === 'paid') {
                $this->notifyVendorPayment($transactionId);
                $this->notifyAdminPayment($transactionId);
            }
        }

        if ($productSlug) {
            return redirect()
                ->route('marketplace.show', $productSlug)
                ->with('success', "✅ Paiement reçu. Si validé, le produit est débloqué.");
        }

        return redirect()
            ->route('my.products')
            ->with('success', "✅ Paiement reçu. Si validé, ton produit est débloqué.");
    }

    // =======================
    // PAYSTACK (ton code)
    // =======================

    public function buyPaystack(Request $request, MarketplaceProduct $product, PaystackService $paystack, AffiliateCommissionService $affiliateService)
    {
        $user = $request->user();

        $alreadyPaid = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if ($alreadyPaid) {
            return redirect()
                ->route('marketplace.show', $product->slug)
                ->with('success', '✅ Produit déjà acheté. Tu peux le télécharger / regarder.');
        }

        // ── Résolution code apporteur + remise éventuelle ─────────────────────
        $checkout = $affiliateService->resolveForCheckout($request, 'marketplace', $product->id, (int) $product->price);

        if ($checkout['warning']) {
            return back()->withInput()->with('warning', $checkout['warning']);
        }

        $amountToPay   = $checkout['amount'];
        $affiliateCode = $checkout['code'];
        // ─────────────────────────────────────────────────────────────────────

        $transactionId = 'MP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        DB::table('marketplace_purchases')->updateOrInsert(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'status'        => 'pending',
                'amount'        => $amountToPay,  // montant remisé stocké dans la purchase
                'provider'      => 'paystack',
                'provider_ref'  => $transactionId,
                'paid_at'       => null,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        Payment::updateOrCreate(
            ['transaction_id' => $transactionId],
            [
                'user_id'               => $user->id,
                'amount_paid'           => $amountToPay,  // R7 : montant remisé = référence de vérification
                'amount_virtual'        => 0,
                'purpose'               => 'marketplace',
                'status'                => 'pending',
                'credited_at'           => null,
                'affiliate_code'        => $affiliateCode,
                'affiliate_base_amount' => $affiliateCode ? (int) $product->price : null,
                'meta'                  => [
                    'product_id'    => $product->id,
                    'product_slug'  => $product->slug,
                    'product_title' => $product->title,
                    'provider'      => 'paystack',
                ],
            ]
        );

        $callbackUrl = route('paystack.marketplace.callback', [], true);

        $authUrl = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => $amountToPay,  // R7 : Paystack reçoit le montant remisé
            'currency'     => 'XOF',
            'reference'    => $transactionId,
            'callback_url' => $callbackUrl,
            'metadata'     => [
                'purpose'    => 'marketplace',
                'product_id' => $product->id,
                'user_id'    => $user->id,
            ],
        ]);

        if (!$authUrl) {
            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            Payment::where('transaction_id', $transactionId)->update(['status' => 'failed']);

            return back()->with('error', "❌ Impossible d'initialiser Paystack.");
        }

        return redirect()->away($authUrl);
    }

    public function paystackCallback(Request $request, PaystackService $paystack, AffiliateCommissionService $affiliateService)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

        if (!$reference) {
            return redirect()->route('marketplace.index')->with('error', 'Référence Paystack manquante.');
        }

        $data = $paystack->verify($reference);

        if (!$data) {
            return redirect()->route('marketplace.index')->with('error', 'Impossible de vérifier la transaction Paystack.');
        }

        if (($data['status'] ?? null) !== 'success') {
            return redirect()->route('marketplace.index')->with('error', 'Paiement non validé : ' . ($data['status'] ?? 'unknown'));
        }

        $payment = Payment::where('transaction_id', $reference)->first();
        if (!$payment) {
            Log::error('PAYSTACK_MP_PAYMENT_NOT_FOUND', ['reference' => $reference, 'data' => $data]);
            return redirect()->route('marketplace.index')->with('error', 'Paiement introuvable en base.');
        }

        $paid = (int) (($data['amount'] ?? 0) / 100);
        if ($paid <= 0 || $paid !== (int) $payment->amount_paid) {
            Log::error('PAYSTACK_MP_AMOUNT_MISMATCH', [
                'reference' => $reference,
                'db_amount' => (int) $payment->amount_paid,
                'paid' => $paid,
                'raw_amount' => $data['amount'] ?? null,
            ]);
            return redirect()->route('marketplace.index')->with('error', 'Montant Paystack invalide.');
        }

        $productSlug = data_get($payment->meta, 'product_slug');
        $productId   = (int) data_get($payment->meta, 'product_id');

        $product = $productId
            ? \App\Models\MarketplaceProduct::with('category')->find($productId)
            : null;

        $purchasePayload = [
            'content_type'     => 'product',
            'content_ids'      => [(string) ($product?->id ?? $productId)],
            'content_name'     => (string) ($product?->title ?? data_get($payment->meta,'product_title','Produit')),
            'content_category' => (string) ($product?->category?->name ?? ''),
            'value'            => (int) $payment->amount_paid,
            'currency'         => 'XOF',
            'order_id'         => (string) $reference,
        ];

        DB::transaction(function () use ($reference, $payment, $data) {

            $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($paymentLocked->status === 'paid') return;

            DB::table('marketplace_purchases')
                ->where('provider_ref', $reference)
                ->update([
                    'status'     => 'paid',
                    'paid_at'    => now(),
                    'updated_at' => now(),
                ]);

            $paymentLocked->status = 'paid';
            $paymentLocked->credited_at = $paymentLocked->credited_at ?? now();
            $paymentLocked->meta = array_merge((array) ($paymentLocked->meta ?? []), [
                'paystack' => [
                    'reference' => $reference,
                    'channel'   => $data['channel'] ?? null,
                    'paid_at'   => $data['paid_at'] ?? null,
                ],
            ]);
            $paymentLocked->save();

            if (!$paymentLocked->notified_at) {
                try {
                    $paymentLocked->loadMissing('user');

                    Mail::to([
                        'maccladder@gmail.com',
                        'ghislainkouadiodjaha@gmail.com',
                    ])->send(new PaymentReceivedMail($paymentLocked));

                    $paymentLocked->notified_at = now();
                    $paymentLocked->save();
                } catch (\Throwable $e) {
                    Log::error('Marketplace paystackCallback: mail error', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        // ✅ notif vendeur + notif admin
        $this->notifyVendorPayment($reference);
        $this->notifyAdminPayment($reference);

        // Commission affiliation — hors transaction, idempotente (UNIQUE payment_id)
        try {
            $payment->refresh(); // relit l'état post-transaction
            if ($payment->credited_at) {
                $affiliateService->tryCreateCommission($payment);
            }
        } catch (\Throwable $e) {
            Log::error('Marketplace paystackCallback: affiliateCommission error', [
                'reference'  => $reference,
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ✅ SPECIAL FLOW (PAYSTACK ONLY): software du vendeur particulier (ID=59)
        $specialVendorId = 59;
        if ($product && $product->type === 'software' && (int) $product->user_id === $specialVendorId) {

            // anti-doublon mail via meta
            $meta = (array) ($payment->meta ?? []);
            $alreadySent = (bool) data_get($meta, 'software_activation_mail_sent', false);

            if (!$alreadySent) {
                try {
                    $payment->loadMissing('user');

                    if (!empty($payment->user?->email)) {
                        Mail::to($payment->user->email)->send(new SoftwareActivationMail($product));
                    }

                    $meta['software_activation_mail_sent'] = true;
                    $payment->meta = $meta;
                    $payment->save();

                } catch (\Throwable $e) {
                    Log::error('SoftwareActivationMail error', [
                        'reference' => $reference,
                        'product_id' => $product->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()
                ->route('marketplace.activation', $product->id)
                ->with('success', "✅ Paiement confirmé. Finalisez l'activation en demandant votre licence.")
                ->with('fb_purchase', $purchasePayload);
        }

        // default redirects
        if ($productSlug) {
            return redirect()
                ->route('marketplace.show', $productSlug)
                ->with('success', "✅ Paiement Paystack confirmé. Produit débloqué.")
                ->with('fb_purchase', $purchasePayload);
        }

        return redirect()->route('my.products')
            ->with('success', "✅ Paiement Paystack confirmé. Produit débloqué.");
    }
}
