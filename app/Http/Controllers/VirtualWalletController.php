<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\VirtualWallet;
use App\Models\VirtualPosition;
use App\Models\WalletTransaction;
use App\Services\CinetpayService;
use App\Services\PaystackService;
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

            $history = VirtualWalletTransaction::where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->limit(100)
    ->get();

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
            'history'    => $history,
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

public function paystackCallback(Request $request, PaystackService $paystack)
{
    $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

    Log::error('PAYSTACK_WALLET_CALLBACK_HIT', [
        'reference' => $reference,
        'all' => $request->all(),
        'url' => $request->fullUrl(),
    ]);

    if (!$reference) {
        return redirect()->route('wallet.index')->with('error', 'Référence Paystack manquante.');
    }

    // 1) Vérifier chez Paystack
    $data = $paystack->verify($reference);

    if (!$data) {
        return redirect()->route('wallet.index')->with('error', 'Impossible de vérifier la transaction Paystack.');
    }

    if (($data['status'] ?? null) !== 'success') {
        return redirect()->route('wallet.index')->with('error', 'Paiement non validé : ' . ($data['status'] ?? 'unknown'));
    }

    // 2) Retrouver le payment par transaction_id (= reference)
    $payment = Payment::where('transaction_id', $reference)->first();

    if (!$payment) {
        Log::error('PAYSTACK_CALLBACK_PAYMENT_NOT_FOUND', ['reference' => $reference, 'data' => $data]);
        return redirect()->route('wallet.index')->with('error', 'Paiement introuvable en base.');
    }

    // 3) Sécurité: montant (Paystack renvoie amount en "kobo-like")
    $paid = (int) (($data['amount'] ?? 0) / 100); // XOF
    if ($paid <= 0 || $paid !== (int) $payment->amount_paid) {
        Log::error('PAYSTACK_AMOUNT_MISMATCH', [
            'reference' => $reference,
            'db_amount' => (int) $payment->amount_paid,
            'paid' => $paid,
            'raw_amount' => $data['amount'] ?? null,
        ]);
        // tu peux décider de bloquer ou tolérer
        // je bloque pour éviter crédit frauduleux
        return redirect()->route('wallet.index')->with('error', 'Montant Paystack invalide.');
    }

    // 4) Crédit wallet (idempotent)
    DB::transaction(function () use ($payment, $reference, $data) {

        // lock paiement
        $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

        if ($payment->credited_at) {
            // déjà crédité
            return;
        }

        $wallet = VirtualWallet::where('user_id', $payment->user_id)->lockForUpdate()->first();
        if (!$wallet) {
            $wallet = VirtualWallet::create(['user_id' => $payment->user_id, 'balance' => 0]);
        }

        $wallet->balance = (float) $wallet->balance + (float) $payment->amount_virtual;
        $wallet->save();

        VirtualWalletTransaction::create([
            'user_id' => $payment->user_id,
            'type'    => 'topup',
            'amount'  => (float) $payment->amount_virtual,
            'meta'    => [
                'source'    => 'paystack_callback',
                'reference' => $reference,
                'paystack'  => [
                    'channel' => $data['channel'] ?? null,
                    'paid_at' => $data['paid_at'] ?? null,
                ],
            ],
        ]);

        $payment->status = 'SUCCESS';
        $payment->credited_at = now();
        $payment->meta = array_merge((array) ($payment->meta ?? []), [
            'paystack' => [
                'reference' => $reference,
                'channel'   => $data['channel'] ?? null,
                'paid_at'   => $data['paid_at'] ?? null,
            ],
        ]);
        $payment->save();
    });

    return redirect()->route('wallet.index')->with('success', '✅ Paiement Paystack confirmé. Portefeuille crédité.');
}



public function topupPayPaystack(Request $request, PaystackService $paystack)
{
    $request->validate([
        'payment_id' => ['required', 'integer', 'exists:payments,id'],
    ]);

    $user = $request->user();

    $payment = Payment::query()
        ->where('id', $request->payment_id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    // ✅ déjà crédité → stop
    if ($payment->credited_at !== null) {
        return redirect()->route('wallet.index')->with('success', 'Paiement déjà validé ✅');
    }

    // ✅ référence Paystack = transaction_id (important pour webhook)
    if (empty($payment->transaction_id)) {
        $payment->transaction_id = 'WALLET_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6));
    }

    $payment->status = 'PENDING';
    $payment->save();

    $callbackUrl = route('wallet.paystack.callback', [], true);


    $authUrl = $paystack->initialize([
        'email'        => $user->email,
        'amount'       => (int) $payment->amount_paid, // XOF
        'currency'     => 'XOF',
        'reference'    => $payment->transaction_id,
        'callback_url' => $callbackUrl,
        'metadata'     => [
            'purpose'    => 'wallet_topup',
            'payment_id' => $payment->id,
            'user_id'    => $user->id,
        ],
    ]);

    if (!$authUrl) {
        return back()->with('error', "Impossible d'initialiser Paystack.");
    }

    return redirect()->away($authUrl);
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

public function sellRecap(Request $request)
{
    // ✅ Support GET + POST
    $ticker = strtoupper((string) $request->input('ticker', $request->query('ticker', '')));
    $qty    = (int) $request->input('qty', $request->query('qty', 0));

    if (empty($ticker) || $qty < 1) {
        return redirect()
            ->route('wallet.index')
            ->with('error', 'Sélectionne une action et une quantité pour afficher le récapitulatif de vente.');
    }

    // ✅ marché (comme buy)
    $market = app(\App\Services\BrvmMarketAiService::class)->fetchCloseAndChangeFromSite();
    $row = collect($market)->firstWhere('ticker', $ticker);

    if (!$row) {
        return redirect()->route('wallet.index')->with('error', 'Ticker introuvable.');
    }

    // ✅ vente : close en priorité
    $price = $row['close'] ?? $row['buy_price'] ?? null;
    if (!$price) {
        return redirect()->route('wallet.index')->with('error', 'Cours indisponible pour ce ticker.');
    }

    $price = (float) str_replace([' ', ','], ['', '.'], (string) $price);
    if ($price <= 0) {
        return redirect()->route('wallet.index')->with('error', 'Cours invalide pour ce ticker.');
    }

    // ✅ Vérifier la quantité détenue
    $userId = auth()->id();
    $pos = \App\Models\VirtualPosition::where('user_id', $userId)
        ->where('ticker', $ticker)
        ->first();

    $ownedQty = (int) ($pos->qty ?? 0);
    if ($ownedQty < $qty) {
        return redirect()->route('wallet.index')->with('error', 'Quantité insuffisante.');
    }

    $grossAmount = $price * $qty;

    // ✅ Frais SGI (vente aussi, cohérent avec BUY)
    $rate = (float) config('sgi.fee_rate', 0.006);
    $min  = (int)  config('sgi.fee_min', 500);

    $fee = max((int) round($grossAmount * $rate), $min);
    $net = $grossAmount - $fee;

    return view('wallet.sell_recap', [
        'ticker'      => $ticker,
        'name'        => ($row['name'] ?? $ticker),
        'qty'         => $qty,
        'ownedQty'    => $ownedQty,
        'price'       => $price,
        'grossAmount' => $grossAmount,
        'fee'         => $fee,
        'net'         => $net,
        'rate'        => $rate,
        'min'         => $min,
    ]);
}

public function sell(Request $request)
{
    Log::info('SELL:start', [
        'user_id' => optional($request->user())->id,
        'payload' => $request->all(),
        'route'   => optional($request->route())->getName(),
        'method'  => $request->method(),
    ]);

    $data = $request->validate([
        'ticker' => ['required', 'string'],
        'qty'    => ['required', 'integer', 'min:1'],
    ]);

    Log::info('SELL:validated', $data);

    $user   = $request->user();
    $ticker = strtoupper(trim($data['ticker']));
    $qty    = (int) $data['qty'];

    // ✅ Prix du jour via TON service (comme buy)
    $market = app(\App\Services\BrvmMarketAiService::class)->fetchCloseAndChangeFromSite();
    Log::info('SELL:market_count', ['count' => is_array($market) ? count($market) : 0]);

    $row = collect($market)->firstWhere('ticker', $ticker);
    Log::info('SELL:row', [
        'ticker'    => $ticker,
        'row_found' => (bool) $row,
        'keys'      => $row ? array_keys($row) : [],
    ]);

    if (!$row) {
        return back()->with('error', 'Ticker introuvable.');
    }

    // ✅ vente : close en priorité (sinon buy_price fallback)
    $price = $row['close'] ?? $row['buy_price'] ?? null;
    if (!$price) {
        return back()->with('error', 'Prix introuvable pour ce ticker.');
    }

    $price = (float) str_replace([' ', ','], ['', '.'], (string) $price);
    if ($price <= 0) {
        return back()->with('error', 'Prix invalide pour ce ticker.');
    }

    $grossAmount = $price * $qty;

    // ✅ Frais SGI (vente aussi)
    $rate = (float) config('sgi.fee_rate', 0.006);
    $min  = (int)  config('sgi.fee_min', 500);

    $fee = max((int) round($grossAmount * $rate), $min);
    $netCredit = $grossAmount - $fee;

    Log::info('SELL:amounts', [
        'price'     => $price,
        'qty'       => $qty,
        'gross'     => $grossAmount,
        'fee'       => $fee,
        'netCredit' => $netCredit,
        'rate'      => $rate,
        'min'       => $min,
    ]);

    try {
        DB::transaction(function () use ($user, $ticker, $qty, $price, $grossAmount, $fee, $netCredit, $rate, $min, $row) {

            // ✅ Wallet lock
            $wallet = \App\Models\VirtualWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = \App\Models\VirtualWallet::create(['user_id' => $user->id, 'balance' => 0]);
            }

            Log::info('SELL:wallet', [
                'wallet_id' => $wallet->id,
                'balance'   => $wallet->balance,
            ]);

            // ✅ Position lock
            $position = \App\Models\VirtualPosition::where('user_id', $user->id)
                ->where('ticker', $ticker)
                ->lockForUpdate()
                ->first();

            Log::info('SELL:position', [
                'exists' => (bool) $position,
                'qty'    => $position?->qty,
            ]);

            if (!$position) {
                throw new \Exception('Aucune position pour ce ticker.');
            }

            if ((int) $position->qty < $qty) {
                throw new \Exception('Quantité insuffisante.');
            }

            $oldQty = (int) $position->qty;
            $newQty = $oldQty - $qty;

            if ($newQty <= 0) {
                $position->delete();
                Log::info('SELL:position_deleted', ['old_qty' => $oldQty]);
            } else {
                $position->update(['qty' => $newQty]);
                Log::info('SELL:position_updated', ['old_qty' => $oldQty, 'new_qty' => $newQty]);
            }

            // ✅ Crédit wallet = NET (après frais)
            $wallet->balance = (float) $wallet->balance + (float) $netCredit;
            $wallet->save();

            Log::info('SELL:wallet_credited', [
                'new_balance' => $wallet->balance,
                'net_credit'  => $netCredit,
            ]);

            // ✅ Transaction (type minuscule => OK CHECK sqlite)
            $tx = \App\Models\VirtualWalletTransaction::create([
                'user_id' => $user->id,
                'type'    => 'sell',
                'ticker'  => $ticker,
                'qty'     => $qty,
                'price'   => $price,
                'amount'  => $netCredit, // ✅ NET crédité
                'meta'    => [
                    'name'         => ($row['name'] ?? $ticker),
                    'gross_amount' => $grossAmount,
                    'sgi_fee'      => $fee,
                    'sgi_rate'     => $rate,
                    'sgi_min'      => $min,
                ],
            ]);

            Log::info('SELL:tx_created', [
                'tx_id'      => $tx->id ?? null,
                'ticker'     => $ticker,
                'qty'        => $qty,
                'gross'      => $grossAmount,
                'fee'        => $fee,
                'net_credit' => $netCredit,
            ]);
        });

    } catch (\Throwable $e) {
        Log::error('SELL:FAILED', [
            'msg'  => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('wallet.index')->with('success', 'Vente effectuée ✅ (frais SGI inclus)');
}


}
