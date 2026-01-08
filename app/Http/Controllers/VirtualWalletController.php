<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\VirtualWallet;
use App\Models\VirtualPosition;
use App\Services\CinetpayService;
use Illuminate\Support\Facades\DB;
use App\Services\BrvmActionsAiService;
use App\Models\VirtualWalletTransaction;
use App\Services\BrvmMarketAiService; // adapte au bon service

class VirtualWalletController extends Controller
{
    public function index(BrvmActionsAiService $svc)
    {
        $user = auth()->user();

        $wallet = VirtualWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // ✅ le bon appel (ton service EXISTE)
        $market = $svc->fetchMarketTableFromSite(); // retourne un array de stocks
        $prices = collect($market)->keyBy('ticker');

        // ⚠️ IMPORTANT: si ta table positions est liée au wallet, on filtre par wallet_id
        // (si tu as plutôt user_id dans positions, je te donne l’autre ligne juste après)
        $positionsDb = VirtualPosition::where('virtual_wallet_id', $wallet->id)->get();

        $positions = [];
        $totalValue = 0;

        foreach ($positionsDb as $pos) {
            $price = data_get($prices, $pos->ticker . '.close');
            $value = ($price && $pos->qty) ? ($price * $pos->qty) : 0;

            $totalValue += $value;

            $positions[] = [
                'ticker' => $pos->ticker,
                'name' => $pos->name,
                'qty' => (int) $pos->qty,
                'avg_price' => (float) $pos->avg_price,
                'price' => $price,
                'value' => $value,
            ];
        }

        return view('wallet.index', [
            'wallet' => $wallet,
            'positions' => $positions,
            'market' => $market,
            'totalValue' => $totalValue,
            'netWorth' => $wallet->balance + $totalValue,
        ]);
    }


    public function topupConfirm(Request $request)
{
    $request->validate([
        'amount_paid' => ['required','integer','min:1000','max:1000000'],
    ]);

    $amountPaid = (int) $request->amount_paid;
    $amountVirtual = $amountPaid * 100;

    // on génère un transaction_id unique
    $tx = 'WALLET_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6));

    // on stocke en base en PENDING
    $payment = Payment::create([
        'user_id' => $request->user()->id,
        'transaction_id' => $tx,
        'amount_paid' => $amountPaid,
        'amount_virtual' => $amountVirtual,
        'purpose' => 'wallet_topup',
        'status' => 'PENDING',
        'meta' => [
            'source' => 'wallet_topup_confirm',
        ],
    ]);

    return view('wallet.topup_confirm', compact('payment'));
}

public function topupPay(Request $request, CinetpayService $cinetpay)
{
    $request->validate([
        'payment_id' => ['required', 'integer', 'exists:payments,id'],
    ]);

    /** @var \App\Models\User $user */
    $user = $request->user();

    $payment = Payment::query()
        ->where('id', $request->payment_id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    // ✅ déjà crédité → on ne relance JAMAIS
    if ($payment->credited_at !== null) {
        return redirect()
            ->route('wallet.index')
            ->with('success', 'Paiement déjà validé ✅');
    }

    // ✅ transaction_id garanti
    if (empty($payment->transaction_id)) {
        $payment->transaction_id =
            'WALLET_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(5));
    }

    // ✅ marquer comme en cours
    $payment->status = 'PENDING';
    $payment->save();

    // ✅ URL retour utilisateur (GET)
    $returnUrl = route('cinetpay.return', [], true)
        . '?transaction_id=' . urlencode($payment->transaction_id);

    // ✅ création paiement CinetPay
    $paymentUrl = $cinetpay->createPayment([
        'transaction_id' => $payment->transaction_id,
        'amount'         => (int) $payment->amount_paid,
        'description'    => 'Top up portefeuille virtuel',
        'notify_url'     => route('cinetpay.ipn', [], true),
        'return_url'     => $returnUrl,
    ]);

    if (!$paymentUrl) {
        return back()->with('error', 'Impossible de créer le paiement CinetPay.');
    }

    // ✅ redirection vers CinetPay
    return redirect()->away($paymentUrl);
}


    public function topup(Request $request)
{
    $data = $request->validate([
        'amount' => ['required', 'integer', 'min:1000', 'max:100000000'],
    ], [
        'amount.min' => 'Le rechargement minimum est 1 000 FCFA.',
    ]);

    $user = auth()->user();
    $amount = (int) $data['amount'];

    DB::transaction(function () use ($user, $amount) {

        $wallet = VirtualWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // on incrémente le cash
        $wallet->increment('balance', $amount);

        // on log l’historique
        VirtualWalletTransaction::create([
            'user_id' => $user->id,
            'type'    => 'topup',
            'amount'  => $amount, // positif
            'meta'    => [
                'source' => 'manual',
            ],
        ]);
    });

    return back()->with('success', "✅ Rechargement effectué : ".number_format($amount, 0, ',', ' ')." FCFA");
}

    public function buy(Request $r, BrvmMarketAiService $svc)
    {
        $d = $r->validate([
            'ticker' => 'required',
            'qty' => 'required|integer|min:1',
        ]);

        $ticker = strtoupper(trim($d['ticker']));
        $qty = (int)$d['qty'];

        $market = collect($svc->fetchMarket())->keyBy('ticker'); // adapte
        $row = $market->get($ticker);

        if (!$row) return back()->with('error', 'Ticker introuvable');

        $price = (float)($row['close'] ?? 0);
        $name = $row['name'] ?? $ticker;

        if ($price <= 0) return back()->with('error', 'Prix introuvable');

        $cost = $price * $qty;

        DB::transaction(function () use ($ticker,$qty,$price,$name,$cost) {
            $userId = auth()->id();

            $wallet = VirtualWallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            if ($wallet->balance < $cost) throw new \RuntimeException('Solde insuffisant');

            $pos = VirtualPosition::firstOrCreate(
                ['user_id' => $userId, 'ticker' => $ticker],
                ['name' => $name, 'quantity' => 0, 'avg_price' => 0]
            );

            $oldQty = $pos->quantity;
            $oldAvg = (float)$pos->avg_price;

            $newQty = $oldQty + $qty;
            $newAvg = (($oldQty * $oldAvg) + ($qty * $price)) / $newQty;

            $pos->quantity = $newQty;
            $pos->avg_price = round($newAvg, 2);
            $pos->name = $name;
            $pos->save();

            $wallet->balance -= $cost;
            $wallet->save();

            VirtualWalletTransaction::create([
                'user_id' => $userId,
                'type' => 'buy',
                'ticker' => $ticker,
                'name' => $name,
                'price' => $price,
                'quantity' => $qty,
                'amount' => -$cost,
            ]);
        });

        return back()->with('success', 'Achat effectué');
    }

    public function sell(Request $r, BrvmMarketAiService $svc)
    {
        $d = $r->validate([
            'ticker' => 'required',
            'qty' => 'required|integer|min:1',
        ]);

        $ticker = strtoupper(trim($d['ticker']));
        $qty = (int)$d['qty'];

        $market = collect($svc->fetchMarket())->keyBy('ticker'); // adapte
        $row = $market->get($ticker);

        if (!$row) return back()->with('error', 'Ticker introuvable');

        $price = (float)($row['close'] ?? 0);
        $name = $row['name'] ?? $ticker;

        if ($price <= 0) return back()->with('error', 'Prix introuvable');

        $gain = $price * $qty;

        DB::transaction(function () use ($ticker,$qty,$price,$name,$gain) {
            $userId = auth()->id();

            $wallet = VirtualWallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);

            $pos = VirtualPosition::where('user_id', $userId)->where('ticker', $ticker)->lockForUpdate()->first();
            if (!$pos || $pos->quantity < $qty) throw new \RuntimeException('Quantité insuffisante');

            $pos->quantity -= $qty;
            if ($pos->quantity <= 0) $pos->delete();
            else $pos->save();

            $wallet->balance += $gain;
            $wallet->save();

            VirtualWalletTransaction::create([
                'user_id' => $userId,
                'type' => 'sell',
                'ticker' => $ticker,
                'name' => $name,
                'price' => $price,
                'quantity' => $qty,
                'amount' => $gain,
            ]);
        });

        return back()->with('success', 'Vente effectuée');
    }
}
