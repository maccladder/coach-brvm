<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\VirtualWallet;
use App\Models\VirtualPosition;
use App\Models\WalletTransaction;
use App\Services\CinetpayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $positionsDb = VirtualPosition::where('user_id', $user->id)->get();


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

public function buy(Request $request)
{
    Log::info('BUY:start', [
        'user_id' => optional($request->user())->id,
        'payload' => $request->all(),
        'route'   => optional($request->route())->getName(),
        'method'  => $request->method(),
    ]);

    try {
        $validated = $request->validate([
            'ticker' => ['required', 'string'],
            'qty'    => ['required', 'integer', 'min:1'],
        ]);

        Log::info('BUY:validated', $validated);

        $user = $request->user();
        if (!$user) {
            Log::error('BUY:no_user');
            return back()->with('error', 'Utilisateur non connecté.');
        }

        // 1) Market
        $market = app(\App\Services\BrvmMarketAiService::class)->fetchCloseAndChangeFromSite();
        Log::info('BUY:market_count', ['count' => is_array($market) ? count($market) : null]);

        $ticker = strtoupper(trim((string)$request->ticker));
        $row = collect($market)->firstWhere('ticker', $ticker);

        Log::info('BUY:row', [
            'ticker'    => $ticker,
            'row_found' => !empty($row),
            'keys'      => is_array($row) ? array_keys($row) : null,
        ]);

        $price = $row['buy_price'] ?? null;
        if (!$price) {
            Log::warning('BUY:no_price', ['ticker' => $ticker, 'row' => $row]);
            return back()->with('error', 'Cours indisponible pour ce ticker.');
        }

        $price = (float) str_replace([' ', ','], ['', '.'], (string) $price);

        $qty = (int) $request->qty;
        $grossAmount = $price * $qty;

        // 2) Frais SGI
        $rate = (float) config('sgi.fee_rate', 0.006);
        $min  = (int)  config('sgi.fee_min', 500);

        $fee = max((int) round($grossAmount * $rate), $min);
        $totalDebit = $grossAmount + $fee;

        Log::info('BUY:amounts', [
            'price'      => $price,
            'qty'        => $qty,
            'gross'      => $grossAmount,
            'fee'        => $fee,
            'totalDebit' => $totalDebit,
            'rate'       => $rate,
            'min'        => $min,
        ]);

        DB::transaction(function () use ($user, $ticker, $row, $price, $qty, $grossAmount, $fee, $totalDebit, $rate, $min) {

            // Wallet (lock pour éviter concurrence)
            $wallet = \App\Models\VirtualWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = \App\Models\VirtualWallet::create(['user_id' => $user->id, 'balance' => 0]);
            }

            Log::info('BUY:wallet', [
                'wallet_id' => $wallet->id ?? null,
                'balance'   => $wallet->balance,
            ]);

            if ((float)$wallet->balance < (float)$totalDebit) {
                Log::warning('BUY:insufficient', [
                    'balance'   => $wallet->balance,
                    'need'      => $totalDebit,
                    'short'     => $totalDebit - (float)$wallet->balance,
                ]);
                throw new \Exception('Solde insuffisant (frais SGI inclus).');
            }

            $wallet->balance = (float)$wallet->balance - (float)$totalDebit;
            $wallet->save();

            Log::info('BUY:wallet_debited', [
                'new_balance' => $wallet->balance,
            ]);

            // Position (lock aussi)
            $position = \App\Models\VirtualPosition::where('user_id', $user->id)
                ->where('ticker', $ticker)
                ->lockForUpdate()
                ->first();

            if (!$position) {
                $position = \App\Models\VirtualPosition::create([
                    'user_id'    => $user->id,
                    'ticker'     => $ticker,
                    'name'       => ($row['name'] ?? $ticker),
                    'qty'        => 0,
                    'avg_price'  => 0,
                ]);
                Log::info('BUY:position_created', ['position_id' => $position->id ?? null]);
            } else {
                Log::info('BUY:position_found', [
                    'position_id' => $position->id ?? null,
                    'qty'         => $position->qty,
                    'avg_price'   => $position->avg_price,
                ]);
            }

            $oldQty = (int)$position->qty;
            $newQty = $oldQty + $qty;

            $newAvgPrice = (
                ($oldQty * (float)$position->avg_price) + ($qty * $price)
            ) / max(1, $newQty);

            $position->update([
                'qty'       => $newQty,
                'avg_price' => $newAvgPrice,
                'name'      => ($position->name ?: ($row['name'] ?? $ticker)),
            ]);

            Log::info('BUY:position_updated', [
                'old_qty'     => $oldQty,
                'new_qty'     => $newQty,
                'new_avg'     => $newAvgPrice,
            ]);

            $tx = \App\Models\VirtualWalletTransaction::create([
                'user_id' => $user->id,
                'type'    => 'buy',
                'ticker'  => $ticker,
                'qty'     => $qty,
                'price'   => $price,
                'amount'  => $totalDebit,
                'meta'    => [
                    'gross_amount' => $grossAmount,
                    'sgi_fee'      => $fee,
                    'sgi_rate'     => $rate,
                    'sgi_min'      => $min,
                ],
            ]);

            Log::info('BUY:tx_created', [
                'tx_id'  => $tx->id ?? null,
                'amount' => $tx->amount ?? null,
            ]);
        });

        Log::info('BUY:done', ['user_id' => $user->id, 'ticker' => $ticker, 'qty' => $qty]);

        return redirect()
            ->route('wallet.index')
            ->with('success', 'Achat effectué ✅ (frais SGI inclus)');
    }
    catch (\Throwable $e) {
        Log::error('BUY:FAILED', [
            'msg'   => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => collect($e->getTrace())->take(8)->all(), // petit extrait
        ]);

        return back()->with('error', $e->getMessage());
    }
}



public function buyRecap(Request $request)
{


    // ✅ Support GET + POST
    $ticker = strtoupper((string) $request->input('ticker', $request->query('ticker', '')));
    $qty    = (int) $request->input('qty', $request->query('qty', 0));

    // ✅ Si accès direct / refresh sans paramètres
    if (empty($ticker) || $qty < 1) {
        return redirect()
            ->route('wallet.index')
            ->with('error', 'Sélectionne une action et une quantité pour afficher le récapitulatif.');
    }

    $market = app(\App\Services\BrvmMarketAiService::class)
        ->fetchCloseAndChangeFromSite();

    $row = collect($market)->firstWhere('ticker', $ticker);
    $price = $row['buy_price'] ?? null;

    if (!$price) {
        return redirect()
            ->route('wallet.index')
            ->with('error', 'Cours indisponible pour ce ticker.');
    }

    $price = (float) str_replace([' ', ','], ['', '.'], (string) $price);

    $grossAmount = $price * $qty;

    $rate = config('sgi.fee_rate', 0.006);
    $min  = config('sgi.fee_min', 500);

    $fee   = max((int) round($grossAmount * $rate), (int) $min);
    $total = $grossAmount + $fee;

    return view('wallet.buy_recap', [
        'ticker'      => $ticker,
        'qty'         => $qty,
        'price'       => $price,
        'grossAmount' => $grossAmount,
        'fee'         => $fee,
        'total'       => $total,
        'rate'        => $rate,
        'min'         => $min,
    ]);
}


    public function sell(Request $r, BrvmMarketAiService $svc)
{
    $d = $r->validate([
        'ticker' => 'required',
        'qty'    => 'required|integer|min:1',
    ]);

    $ticker = strtoupper(trim($d['ticker']));
    $qty    = (int) $d['qty'];

    // ⚠️ adapte ici selon ton service (tu avais fetchMarket() ailleurs)
    $market = collect($svc->fetchMarket())->keyBy('ticker');
    $row    = $market->get($ticker);

    if (!$row) return back()->with('error', 'Ticker introuvable');

    $price = (float) ($row['close'] ?? 0);
    $name  = $row['name'] ?? $ticker;

    if ($price <= 0) return back()->with('error', 'Prix introuvable');

    $gain = $price * $qty;

    DB::transaction(function () use ($ticker, $qty, $price, $name, $gain) {
        $userId = auth()->id();

        $wallet = VirtualWallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);

        $pos = VirtualPosition::where('user_id', $userId)
            ->where('ticker', $ticker)
            ->lockForUpdate()
            ->first();

        if (!$pos || (int)$pos->qty < $qty) {
            throw new \RuntimeException('Quantité insuffisante');
        }

        $pos->qty = (int)$pos->qty - $qty;

        if ($pos->qty <= 0) $pos->delete();
        else $pos->save();

        $wallet->balance = (float)$wallet->balance + (float)$gain;
        $wallet->save();

        VirtualWalletTransaction::create([
            'user_id' => $userId,
            'type'    => 'sell',
            'ticker'  => $ticker,
            'qty'     => $qty,
            'price'   => $price,
            'amount'  => $gain,
            'meta'    => [
                'name' => $name,
            ],
        ]);
    });

    return back()->with('success', 'Vente effectuée ✅');
}

}
