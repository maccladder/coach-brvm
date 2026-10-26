<?php

namespace App\Services\Payments;

use App\Mail\PaymentReceivedMail;
use App\Mail\SoftwareActivationMail;
use App\Models\AdminNotification;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\VendorPaymentReceivedNotification;
use App\Services\Affiliate\AffiliateCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Validation d'un achat marketplace payé via Paystack (référence MP-…).
 * Utilisé par le callback (retour navigateur), le webhook et la réconciliation :
 * strictement idempotent, le premier arrivé valide, les suivants ne font rien.
 */
class PaystackMarketplaceService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';
    public const IGNORED   = 'ignored';

    // Vendeur particulier dont les logiciels nécessitent une activation manuelle
    public const SOFTWARE_ACTIVATION_VENDOR_ID = 59;

    public function __construct(
        private AffiliateCommissionService $affiliateService
    ) {}

    /**
     * @param array $data  bloc "data" Paystack (webhook charge.success ou transaction/verify)
     */
    public function handleChargeSuccess(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));

        $payment = $reference !== '' ? Payment::where('transaction_id', $reference)->first() : null;

        if (!$payment) {
            Log::warning('PaystackMarketplaceService: payment not found', ['reference' => $reference]);
            return self::NOT_FOUND;
        }

        if ($payment->purpose !== 'marketplace') {
            Log::info('PaystackMarketplaceService: not a marketplace payment, ignored', [
                'reference' => $reference,
                'purpose'   => $payment->purpose,
            ]);
            return self::IGNORED;
        }

        if ($amountPaid <= 0 || $amountPaid !== (int) $payment->amount_paid || $currency !== 'XOF') {
            Log::error('PAYSTACK_MP_AMOUNT_MISMATCH', [
                'reference' => $reference,
                'db_amount' => (int) $payment->amount_paid,
                'paid'      => $amountPaid,
                'currency'  => $currency,
            ]);
            return self::MISMATCH;
        }

        $granted = false;

        DB::transaction(function () use ($payment, $reference, $data, &$granted) {

            $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if (strtolower((string) $paymentLocked->status) === 'paid') return;

            $productId = (int) data_get($paymentLocked->meta, 'product_id');

            // L'achat est unique par (user, produit) et son provider_ref suit la DERNIÈRE
            // tentative : on le retrouve par le couple, pas par la référence, pour qu'un
            // ancien lien de paiement réglé en retard débloque quand même le produit.
            if ($productId) {
                $purchase = DB::table('marketplace_purchases')
                    ->where('user_id', $paymentLocked->user_id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$purchase) {
                    DB::table('marketplace_purchases')->insert([
                        'user_id'      => $paymentLocked->user_id,
                        'product_id'   => $productId,
                        'amount'       => (int) $paymentLocked->amount_paid,
                        'provider'     => 'paystack',
                        'provider_ref' => $reference,
                        'status'       => 'paid',
                        'paid_at'      => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                } elseif ($purchase->status !== 'paid') {
                    DB::table('marketplace_purchases')
                        ->where('id', $purchase->id)
                        ->update([
                            'status'       => 'paid',
                            'provider'     => 'paystack',
                            'provider_ref' => $reference,
                            'paid_at'      => now(),
                            'updated_at'   => now(),
                        ]);
                }
            }

            $paymentLocked->status      = 'paid';
            $paymentLocked->credited_at = $paymentLocked->credited_at ?? now();
            $paymentLocked->meta        = array_merge((array) ($paymentLocked->meta ?? []), [
                'paystack' => [
                    'reference' => $reference,
                    'channel'   => $data['channel'] ?? null,
                    'paid_at'   => $data['paid_at'] ?? null,
                ],
            ]);
            $paymentLocked->save();

            $granted = true;
        });

        if (!$granted) {
            Log::info('PaystackMarketplaceService: already paid, skip', ['reference' => $reference]);
            return self::ALREADY;
        }

        $payment->refresh();

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
                Log::error('Marketplace paystack: mail error', [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // ✅ notif vendeur + notif admin
        $this->notifyVendorPayment($reference);
        $this->notifyAdminPayment($reference);

        // Commission affiliation — idempotente (UNIQUE payment_id)
        try {
            $this->affiliateService->tryCreateCommission($payment);
        } catch (\Throwable $e) {
            Log::error('Marketplace paystack: affiliateCommission error', [
                'reference'  => $reference,
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        $this->sendSoftwareActivationIfNeeded($payment);

        Log::info('PaystackMarketplaceService: produit débloqué', [
            'reference'  => $reference,
            'user_id'    => $payment->user_id,
            'product_id' => data_get($payment->meta, 'product_id'),
        ]);

        return self::GRANTED;
    }

    public static function isSoftwareActivationProduct(?MarketplaceProduct $product): bool
    {
        return $product
            && $product->type === 'software'
            && (int) $product->user_id === self::SOFTWARE_ACTIVATION_VENDOR_ID;
    }

    /**
     * SPECIAL FLOW (PAYSTACK ONLY) : software du vendeur particulier — mail d'activation
     * à l'acheteur, une seule fois (anti-doublon via meta).
     */
    private function sendSoftwareActivationIfNeeded(Payment $payment): void
    {
        $product = MarketplaceProduct::find((int) data_get($payment->meta, 'product_id'));
        if (!self::isSoftwareActivationProduct($product)) return;

        $meta = (array) ($payment->meta ?? []);
        if ((bool) data_get($meta, 'software_activation_mail_sent', false)) return;

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
                'reference'  => $payment->transaction_id,
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function notifyVendorPayment(string $transactionId): void
    {
        try {
            $payment = Payment::where('transaction_id', $transactionId)
                ->with('user')
                ->first();

            if (!$payment) return;

            $productId = (int) data_get($payment->meta, 'product_id');
            if (!$productId) return;

            $product = MarketplaceProduct::with('vendor')->find($productId);
            if (!$product) return;

            $vendor = $product->vendor ?: (!empty($product->user_id) ? User::find($product->user_id) : null);
            if (!$vendor) return;

            // ✅ anti-doublon: si déjà envoyé pour cette transaction, on stop
            $alreadySent = $vendor->notifications()
                ->where('type', VendorPaymentReceivedNotification::class)
                ->where('data->transaction_id', $transactionId)
                ->exists();

            if ($alreadySent) return;

            $buyerName = $payment->user?->name ?? 'Un client';
            $amount    = (int) $payment->amount_paid;

            $vendor->notify(new VendorPaymentReceivedNotification(
                transactionId: $transactionId,
                productId: $product->id,
                productTitle: $product->title,
                amount: $amount,
                buyerName: $buyerName,
                url: route('vendor.dashboard')
            ));

        } catch (\Throwable $e) {
            Log::error('notifyVendorPayment error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyAdminPayment(string $transactionId): void
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
}
