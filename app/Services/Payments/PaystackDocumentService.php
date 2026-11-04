<?php

namespace App\Services\Payments;

use App\Mail\PaymentReceivedMail;
use App\Models\Document;
use App\Models\DocumentPurchase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Achat d'un document « Études & Business plans » via Paystack (référence DOC-PS-…).
 * Même modèle que le marketplace : montant fixé par le serveur (prix en base), une ligne
 * document_purchases + une ligne payments en attente, puis validation strictement
 * idempotente partagée par le callback, le webhook, le checkout Mobile Money et la réconciliation.
 */
class PaystackDocumentService
{
    public const PREFIX  = 'DOC-PS-';
    public const PURPOSE = 'document';

    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';
    public const IGNORED   = 'ignored';
    public const DUPLICATE = 'duplicate';

    /**
     * Crée l'achat et le paiement en attente. Le montant vient TOUJOURS de la base.
     *
     * @return string la référence Paystack
     */
    public function createPending(Document $document, User $user, string $channel = 'paystack_page'): string
    {
        $reference = self::PREFIX . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        $amount    = (int) $document->price;

        DB::transaction(function () use ($document, $user, $reference, $amount, $channel) {
            $purchase = DocumentPurchase::create([
                'user_id'      => $user->id,
                'document_id'  => $document->id,
                'amount'       => $amount,
                'currency'     => 'XOF',
                'provider'     => 'paystack',
                'provider_ref' => $reference,
                'status'       => 'pending',
            ]);

            Payment::create([
                'user_id'        => $user->id,
                'transaction_id' => $reference,
                'amount_paid'    => $amount,
                'amount_virtual' => 0,
                'purpose'        => self::PURPOSE,
                'status'         => 'pending',
                'credited_at'    => null,
                'meta'           => [
                    'document_id'    => $document->id,
                    'document_slug'  => $document->slug,
                    'document_title' => $document->title,
                    'purchase_id'    => $purchase->id,
                    'provider'       => 'paystack',
                    'channel'        => $channel,
                ],
            ]);
        });

        return $reference;
    }

    /** Échec d'initialisation Paystack : on ferme l'achat et le paiement en attente. */
    public function markInitFailed(string $reference): void
    {
        DocumentPurchase::where('provider_ref', $reference)->where('status', 'pending')->update(['status' => 'failed']);
        Payment::where('transaction_id', $reference)->whereNull('credited_at')->update(['status' => 'failed']);
    }

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
            Log::warning('[DOC][PAYSTACK] payment not found', ['reference' => $reference]);
            return self::NOT_FOUND;
        }

        if ($payment->purpose !== self::PURPOSE) {
            Log::info('[DOC][PAYSTACK] not a document payment, ignored', ['reference' => $reference, 'purpose' => $payment->purpose]);
            return self::IGNORED;
        }

        if ($amountPaid <= 0 || $amountPaid !== (int) $payment->amount_paid || $currency !== 'XOF') {
            Log::error('[DOC][PAYSTACK] amount mismatch', [
                'reference' => $reference,
                'db_amount' => (int) $payment->amount_paid,
                'paid'      => $amountPaid,
                'currency'  => $currency,
            ]);
            return self::MISMATCH;
        }

        $outcome = self::ALREADY;

        DB::transaction(function () use ($payment, $reference, $data, &$outcome) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($locked->credited_at) {
                return;   // déjà validé : rien à refaire
            }

            $documentId = (int) data_get($locked->meta, 'document_id');
            $paystack   = [
                'reference' => $reference,
                'channel'   => $data['channel'] ?? null,
                'paid_at'   => $data['paid_at'] ?? null,
            ];

            // Document déjà payé par une AUTRE référence (deux onglets, changement de numéro…) :
            // l'argent est reçu mais aucun second achat n'est validé ; paiement « à rembourser »
            $alreadyPaid = DocumentPurchase::paid()
                ->where('user_id', $locked->user_id)
                ->where('document_id', $documentId)
                ->where('provider_ref', '!=', $reference)
                ->exists();

            if ($alreadyPaid) {
                DocumentPurchase::where('provider_ref', $reference)->where('status', '!=', 'paid')->update(['status' => 'duplicate']);

                $locked->status      = 'paid';
                $locked->credited_at = now();
                $locked->meta        = array_merge((array) $locked->meta, [
                    'duplicate'     => true,
                    'refund_status' => 'to_refund',
                    'paystack'      => $paystack,
                ]);
                $locked->save();

                $outcome = self::DUPLICATE;
                return;
            }

            // Une ligne par tentative (provider_ref unique) : on retrouve celle de CETTE référence
            $purchase = DocumentPurchase::where('provider_ref', $reference)->lockForUpdate()->first();

            if (!$purchase && $documentId) {
                $purchase = DocumentPurchase::create([
                    'user_id'      => $locked->user_id,
                    'document_id'  => $documentId,
                    'amount'       => (int) $locked->amount_paid,
                    'currency'     => 'XOF',
                    'provider'     => 'paystack',
                    'provider_ref' => $reference,
                    'status'       => 'pending',
                ]);
            }

            // Même un achat marqué failed/abandoned par la réconciliation est débloqué ici
            if ($purchase && $purchase->status !== 'paid') {
                $purchase->update(['status' => 'paid', 'paid_at' => now()]);
            }

            $locked->status      = 'paid';
            $locked->credited_at = now();
            $locked->meta        = array_merge((array) $locked->meta, ['paystack' => $paystack]);
            $locked->save();

            $outcome = self::GRANTED;
        });

        if ($outcome === self::ALREADY) {
            Log::info('[DOC][PAYSTACK] already credited, skip', ['reference' => $reference]);
            return self::ALREADY;
        }

        $payment->refresh();

        if ($outcome === self::DUPLICATE) {
            Log::warning('[DOC][PAYSTACK] paiement en double, à rembourser', [
                'reference'   => $reference,
                'user_id'     => $payment->user_id,
                'document_id' => data_get($payment->meta, 'document_id'),
            ]);
        }

        $this->notifyAdmins($payment);

        Log::info('[DOC][PAYSTACK] document débloqué', [
            'reference'   => $reference,
            'user_id'     => $payment->user_id,
            'document_id' => data_get($payment->meta, 'document_id'),
            'outcome'     => $outcome,
        ]);

        return $outcome;
    }

    /** Mail admin une seule fois (notified_at), comme le marketplace. */
    private function notifyAdmins(Payment $payment): void
    {
        if ($payment->notified_at) {
            return;
        }

        try {
            $payment->loadMissing('user');

            Mail::to([
                'maccladder@gmail.com',
                'ghislainkouadiodjaha@gmail.com',
            ])->send(new PaymentReceivedMail($payment));

            $payment->notified_at = now();
            $payment->save();
        } catch (\Throwable $e) {
            Log::error('[DOC][PAYSTACK] mail error', ['reference' => $payment->transaction_id, 'error' => $e->getMessage()]);
        }
    }
}
