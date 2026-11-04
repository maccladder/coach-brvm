<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPurchase;
use App\Services\CinetpayService;
use App\Services\PaystackService;
use App\Services\Payments\PaystackDocumentService;
use App\Services\Payments\PaystackMobileMoneyCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentPaymentController extends Controller
{
    public function __construct(private CinetpayService $cinetpay)
    {
    }

    /**
     * POST /documents/{document}/buy
     */
    public function buy(Request $request, Document $document)
    {
        $user = $request->user();

        // Déjà acheté ?
        if ($document->isBoughtBy($user)) {
            return redirect()
                ->route('docs.public.show', $document->slug)
                ->with('success', '✅ Document déjà acheté. Tu peux le télécharger.');
        }

        // Créer purchase pending
        $purchase = DocumentPurchase::create([
            'user_id'      => $user->id,
            'document_id'  => $document->id,
            'amount'       => (int) $document->price,
            'currency'     => 'XOF',
            'provider'     => 'cinetpay',
            'provider_ref' => 'DOC-' . Str::upper(Str::random(10)) . '-' . time(),
            'status'       => 'pending',
        ]);

        // Init paiement CinetPay (ton service retourne l'URL)
        $paymentUrl = $this->cinetpay->createPayment([
            'transaction_id' => $purchase->provider_ref,
            'amount'         => $purchase->amount,
            'currency'       => $purchase->currency,
            'description'    => 'Achat document: ' . $document->title,
            'return_url'     => route('cinetpay.return.documents'),
            'notify_url'     => route('cinetpay.notify.documents'),
            'channels'       => 'ALL',
            // metadata string (ton service gère array->json aussi)
            'metadata'       => 'document_id=' . $document->id . ';purchase_id=' . $purchase->id,
            'customer_name'  => $user->name ?? 'Client',
            'customer_email' => $user->email ?? 'boursivoire@gmail.com',
        ]);

        if (!$paymentUrl) {
            $purchase->update(['status' => 'failed']);
            return back()->with('error', '❌ Paiement indisponible. Réessaie dans quelques instants.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * POST /documents/{document}/buy-paystack
     * Montant fixé par le serveur (prix du document en base), jamais par la requête.
     */
    public function buyPaystack(Request $request, Document $document, PaystackService $paystack, PaystackDocumentService $documents)
    {
        $user = $request->user();

        // Seuls les documents publiés et actifs s'achètent
        abort_unless($document->is_active && $document->status === 'published', 404);

        if ($document->isBoughtBy($user)) {
            return redirect()
                ->route('docs.public.show', $document->slug)
                ->with('success', '✅ Document déjà acheté. Tu peux le télécharger.');
        }

        if ((int) $document->price <= 0) {
            return back()->with('error', 'Ce document ne peut pas être acheté en ligne.');
        }

        // Checkout Mobile Money personnalisé si activé (CHECKOUT_MM_DOCUMENT) ; ?classic=1 = page Paystack
        $mm = app(PaystackMobileMoneyCheckout::class);
        if ($mm->shouldIntercept('document', $request)) {
            return redirect()->to($mm->entryUrl('document', ['document' => $document->id]));
        }

        $reference = $documents->createPending($document, $user);

        $authUrl = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => (int) $document->price,
            'currency'     => 'XOF',
            'reference'    => $reference,
            'callback_url' => route('paystack.documents.callback', [], true),
            'metadata'     => [
                'purpose'     => PaystackDocumentService::PURPOSE,
                'document_id' => $document->id,
                'user_id'     => $user->id,
            ],
        ]);

        if (!$authUrl) {
            $documents->markInitFailed($reference);
            return back()->with('error', "❌ Impossible d'initialiser le paiement. Réessaie ou achète via WhatsApp.");
        }

        return redirect()->away($authUrl);
    }

    /**
     * GET /documents/payment/paystack/callback (retour navigateur)
     * Revérifie la transaction chez Paystack ; la validation est la même que celle du webhook.
     */
    public function paystackCallback(Request $request, PaystackService $paystack, PaystackDocumentService $documents)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

        if ($reference === '' || !str_starts_with($reference, PaystackDocumentService::PREFIX)) {
            return redirect()->route('docs.public.index')->with('error', 'Référence de paiement manquante.');
        }

        $payment = \App\Models\Payment::where('transaction_id', $reference)->first();
        $slug    = data_get($payment?->meta, 'document_slug');
        $back    = $slug ? route('docs.public.show', $slug) : route('docs.public.index');

        $data = $paystack->verify($reference);

        if (!$data) {
            return redirect($back)->with('error', 'Impossible de vérifier la transaction pour le moment. Si tu as payé, le document sera débloqué automatiquement.');
        }

        if (($data['status'] ?? null) !== 'success') {
            return redirect($back)->with('error', 'Paiement non validé : ' . ($data['status'] ?? 'inconnu'));
        }

        $outcome = $documents->handleChargeSuccess($data);

        if (in_array($outcome, [PaystackDocumentService::NOT_FOUND, PaystackDocumentService::IGNORED], true)) {
            Log::error('[DOC][PAYSTACK] callback: paiement introuvable', ['reference' => $reference]);
            return redirect()->route('docs.public.index')->with('error', 'Paiement introuvable.');
        }

        if ($outcome === PaystackDocumentService::MISMATCH) {
            return redirect($back)->with('error', 'Montant du paiement invalide. Contacte-nous sur WhatsApp.');
        }

        return redirect($back)->with('success', '✅ Paiement confirmé. Document débloqué !');
    }

    /**
     * GET/POST /documents/payment/cinetpay/return
     * ⚠️ Le vrai statut vient du notify (IPN). Ici on informe seulement.
     */
    public function return(Request $request)
    {
        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpm_transid');

        if (!$transactionId) {
            return redirect()->route('dashboard')->with('error', 'Retour paiement incomplet.');
        }

        $purchase = DocumentPurchase::where('provider_ref', $transactionId)->first();

        if (!$purchase) {
            return redirect()->route('dashboard')->with('error', 'Transaction introuvable.');
        }

        if ($purchase->status === 'paid') {
            return redirect()
                ->route('docs.public.show', $purchase->document->slug)
                ->with('success', '✅ Paiement confirmé. Document débloqué !');
        }

        return redirect()
            ->route('docs.public.show', $purchase->document->slug)
            ->with('info', '⏳ Paiement en cours de validation. Attends 1-2 minutes puis rafraîchis.');
    }

    /**
     * POST /documents/payment/cinetpay/notify
     * IPN serveur : on check chez CinetPay et on marque paid/failed.
     */
    public function notify(Request $request)
    {
        $transactionId = $request->get('transaction_id')
            ?? $request->get('cpm_trans_id')
            ?? $request->get('cpm_transid');

        if (!$transactionId) {
            return response('missing transaction_id', 400);
        }

        $purchase = DocumentPurchase::where('provider_ref', $transactionId)->first();

        if (!$purchase) {
            return response('purchase not found', 404);
        }

        // idempotent
        if ($purchase->status === 'paid') {
            return response('ok', 200);
        }

        $status = $this->cinetpay->checkPayment($transactionId); // ACCEPTED / REFUSED / PENDING / null

        if ($status === 'ACCEPTED') {
            $purchase->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);
            return response('ok', 200);
        }

        if (in_array($status, ['REFUSED', 'CANCELED'], true)) {
            $purchase->update(['status' => 'failed']);
            return response('ok', 200);
        }

        // pending / unknown => on ne touche pas
        return response('ok', 200);
    }
}
