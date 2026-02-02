<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Services\CinetpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // 3) Redirection CinetPay (⚠️ noms de routes corrigés)
        $paymentUrl = $this->cinetpay->createPayment([
            'transaction_id' => $transactionId,
            'amount'         => (int) $product->price,
            'description'    => "Achat Marketplace: {$product->title}",
            'notify_url'     => route('cinetpay.notify.marketplace'),
            'return_url'     => route('cinetpay.return.marketplace'),
            'customer_name'  => $user->name ?? 'Client',
            'customer_email' => $user->email ?? null,

            // Ton CinetpayService convertit metadata en string,
            // donc on peut mettre une string safe
            'metadata'       => (string) $transactionId,
        ]);

        if (!$paymentUrl) {
            // rollback logique
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
        // CinetPay renvoie souvent cpm_trans_id
        $transactionId = $request->input('transaction_id')
            ?? $request->input('cpm_trans_id')
            ?? $request->input('cpm_transaction_id');

        if (!$transactionId) {
            return response()->json(['ok' => false, 'message' => 'missing transaction_id'], 400);
        }

        // ✅ Idempotence: si déjà payé, on renvoie ok (évite double IPN)
        $purchase = DB::table('marketplace_purchases')->where('provider_ref', $transactionId)->first();
        if ($purchase && $purchase->status === 'paid') {
            return response()->json(['ok' => true, 'already' => 'paid']);
        }

        $status = $this->cinetpay->checkPayment($transactionId);

        // Stocker trace brute
        $oldMeta = Payment::where('transaction_id', $transactionId)->value('meta') ?? [];
        Payment::where('transaction_id', $transactionId)->update([
            'meta' => array_merge($oldMeta, [
                'ipn_payload'     => $request->all(),
                'checked_status'  => $status,
            ]),
        ]);

        if (in_array($status, ['ACCEPTED', 'PAID', 'SUCCESS'], true)) {

            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update([
                    'status'     => 'paid',
                    'paid_at'    => now(),
                    'updated_at' => now(),
                ]);

            Payment::where('transaction_id', $transactionId)->update([
                'status'      => 'paid',
                'credited_at' => now(),
            ]);

            return response()->json(['ok' => true]);
        }

        if (in_array($status, ['REFUSED', 'CANCELED', 'FAILED'], true)) {

            DB::table('marketplace_purchases')
                ->where('provider_ref', $transactionId)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            Payment::where('transaction_id', $transactionId)->update(['status' => 'failed']);

            return response()->json(['ok' => true]);
        }

        // Pending ou inconnu => on ne change rien
        return response()->json(['ok' => true, 'pending' => true]);
    }

    public function return(Request $request)
    {
        // Ici on ne “valide” pas à la confiance: c’est l’IPN qui valide.
        // Mais on peut afficher un message propre.
        return redirect()
            ->route('my.products')
            ->with('success', "✅ Retour paiement reçu. Si le paiement est validé, ton produit est débloqué.");
    }
}
