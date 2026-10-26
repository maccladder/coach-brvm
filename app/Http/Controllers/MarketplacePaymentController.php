<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaystackMobileMoneyCheckout;
use App\Models\AdminNotification;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\VendorPaymentReceivedNotification;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\CinetpayService;
use App\Services\PaystackService;
use App\Services\Payments\PaystackMarketplaceService;
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

    // Logique partagée avec le webhook Paystack : voir PaystackMarketplaceService
    private function notifyVendorPayment(string $transactionId): void
    {
        app(PaystackMarketplaceService::class)->notifyVendorPayment($transactionId);
    }

    private function notifyAdminPayment(string $transactionId): void
    {
        app(PaystackMarketplaceService::class)->notifyAdminPayment($transactionId);
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

        if (!$productSlug) {
            $pid = (int) data_get($payment?->meta, 'product_id');
            if ($pid) $productSlug = MarketplaceProduct::where('id', $pid)->value('slug');
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

        // Checkout Mobile Money personnalisé si activé pour ce type (config/checkout.php) ; ?classic=1 = ancien flux
        $mm = app(PaystackMobileMoneyCheckout::class);
        if ($mm->shouldIntercept('marketplace', $request)) {
            return redirect()->to($mm->entryUrl('marketplace', [
                'product'        => $product->id,
                'affiliate_code' => $request->input('affiliate_code'),
            ]));
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

    public function paystackCallback(Request $request, PaystackService $paystack, PaystackMarketplaceService $marketplaceService)
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

        $outcome = $marketplaceService->handleChargeSuccess($data);

        if (in_array($outcome, [PaystackMarketplaceService::NOT_FOUND, PaystackMarketplaceService::IGNORED], true)) {
            Log::error('PAYSTACK_MP_PAYMENT_NOT_FOUND', ['reference' => $reference, 'data' => $data]);
            return redirect()->route('marketplace.index')->with('error', 'Paiement introuvable en base.');
        }

        if ($outcome === PaystackMarketplaceService::MISMATCH) {
            return redirect()->route('marketplace.index')->with('error', 'Montant Paystack invalide.');
        }

        $payment = Payment::where('transaction_id', $reference)->first();

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

        // ✅ SPECIAL FLOW (PAYSTACK ONLY): software du vendeur particulier (ID=59)
        // (le mail d'activation est envoyé par PaystackMarketplaceService)
        if (PaystackMarketplaceService::isSoftwareActivationProduct($product)) {
            return redirect()
                ->route('marketplace.activation', $product->id)
                ->with('success', "✅ Paiement confirmé. Finalisez l'activation en demandant votre licence.")
                ->with('fb_purchase', $purchasePayload);
        }

        // default redirects
        $productSlug = $productSlug ?: $product?->slug;

        if ($productSlug) {
            return redirect()
                ->route('marketplace.show', $productSlug)
                ->with('success', "✅ Paiement Paystack confirmé. Produit débloqué.")
                ->with('fb_purchase', $purchasePayload);
        }

        return redirect()->route('my.products')
            ->with('success', "✅ Paiement Paystack confirmé. Produit débloqué.");
    }

    /**
     * Claim gratuit : ajoute un produit price=0 à la collection de l'utilisateur.
     */
    public function claimFree(Request $request, MarketplaceProduct $product)
    {
        if ((int) $product->price > 0) {
            abort(403, 'Ce produit n\'est pas gratuit.');
        }

        $user = $request->user();

        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
            || $product->isGrantedTo($user);

        if (!$hasAccess) {
            DB::table('marketplace_purchases')->insertOrIgnore([
                'user_id'      => $user->id,
                'product_id'   => $product->id,
                'amount'       => 0,
                'currency'     => 'XOF',
                'provider'     => 'free',
                'provider_ref' => 'free-' . Str::uuid(),
                'status'       => 'paid',
                'paid_at'      => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            Log::info('Marketplace claim gratuit', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'slug'       => $product->slug,
            ]);
        }

        return redirect()->route('marketplace.show', $product->slug)
            ->with('success', '🎮 ' . $product->title . ' ajouté à votre collection ! Tu peux y accéder maintenant.');
    }
}
