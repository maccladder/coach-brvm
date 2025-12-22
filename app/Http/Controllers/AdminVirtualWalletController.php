<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\BrvmMarketAiService;

class AdminVirtualWalletController extends Controller
{
    private function wallet()
    {
        return session('virtual_wallet', [
            'cash' => 1_000_000,
            'positions' => [],
            'history' => [],
        ]);
    }

    public function index()
    {
        // On consomme le market via le controller market (propre)
        $market = app(AdminMarketController::class)->api(app(\App\Services\BrvmMarketAiService::class))->getData(true);

        $wallet = $this->wallet();
        $prices = collect($market['stocks'])->keyBy('ticker');

        $positions = [];
        $totalValue = 0;

        foreach ($wallet['positions'] as $ticker => $pos) {
            $price = $prices[$ticker]['close'] ?? null;
            $value = $price ? $price * $pos['qty'] : null;

            if ($value) $totalValue += $value;

            $positions[] = [
                'ticker' => $ticker,
                'name' => $pos['name'],
                'qty' => $pos['qty'],
                'avg' => $pos['avg'],
                'price' => $price,
                'value' => $value,
            ];
        }

        return view('admin.wallet.index', [
            'wallet' => $wallet,
            'positions' => $positions,
            'market' => $market['stocks'],
            'totalValue' => $totalValue,
            'netWorth' => $wallet['cash'] + $totalValue,
        ]);
    }

    public function buy(Request $r)
    {
        $d = $r->validate([
            'ticker' => 'required',
            'name' => 'required',
            'price' => 'required|numeric',
            'qty' => 'required|integer|min:1',
        ]);

        $wallet = $this->wallet();
        $cost = $d['price'] * $d['qty'];

        if ($wallet['cash'] < $cost) {
            return back()->with('error', 'Cash insuffisant');
        }

        $pos = $wallet['positions'][$d['ticker']] ?? [
            'qty' => 0,
            'avg' => 0,
            'name' => $d['name'],
        ];

        $newQty = $pos['qty'] + $d['qty'];
        $pos['avg'] = round((($pos['qty'] * $pos['avg']) + ($d['qty'] * $d['price'])) / $newQty, 2);
        $pos['qty'] = $newQty;

        $wallet['cash'] -= $cost;
        $wallet['positions'][$d['ticker']] = $pos;

        $wallet['history'][] = [
            'type' => 'BUY',
            'ticker' => $d['ticker'],
            'qty' => $d['qty'],
            'price' => $d['price'],
            'at' => now(),
        ];

        session(['virtual_wallet' => $wallet]);
        return back()->with('success', 'Achat effectué');
    }

    public function sell(Request $r)
    {
        $d = $r->validate([
            'ticker' => 'required',
            'price' => 'required|numeric',
            'qty' => 'required|integer|min:1',
        ]);

        $wallet = $this->wallet();
        $pos = $wallet['positions'][$d['ticker']] ?? null;

        if (!$pos || $pos['qty'] < $d['qty']) {
            return back()->with('error', 'Quantité insuffisante');
        }

        $wallet['cash'] += $d['price'] * $d['qty'];
        $pos['qty'] -= $d['qty'];

        if ($pos['qty'] <= 0) {
            unset($wallet['positions'][$d['ticker']]);
        } else {
            $wallet['positions'][$d['ticker']] = $pos;
        }

        $wallet['history'][] = [
            'type' => 'SELL',
            'ticker' => $d['ticker'],
            'qty' => $d['qty'],
            'price' => $d['price'],
            'at' => now(),
        ];

        session(['virtual_wallet' => $wallet]);
        return back()->with('success', 'Vente effectuée');
    }
}
