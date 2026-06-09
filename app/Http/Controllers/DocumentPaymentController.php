<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPurchase;
use App\Services\CinetpayService;
use Illuminate\Http\Request;
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
