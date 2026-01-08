<?php

namespace App\Http\Controllers;

use App\Models\VirtualWallet;
use App\Services\BrvmActionsAiService;
use App\Models\VirtualPosition;
use App\Models\VirtualWalletTransaction;
use App\Services\BrvmMarketAiService; // adapte au bon service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function topup(Request $r)
    {
        $d = $r->validate(['amount' => 'required|numeric|min:1000']);

        DB::transaction(function () use ($d) {
            $userId = auth()->id();

            $wallet = VirtualWallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            $wallet->balance += $d['amount'];
            $wallet->save();

            VirtualWalletTransaction::create([
                'user_id' => $userId,
                'type' => 'topup',
                'amount' => $d['amount'],
            ]);
        });

        return back()->with('success', 'Recharge effectuée');
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
