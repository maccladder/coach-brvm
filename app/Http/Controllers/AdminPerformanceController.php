<?php

namespace App\Http\Controllers;

use App\Models\BocStock;
use Illuminate\Http\Request;

class AdminPerformanceController extends Controller
{
    public function index()
    {
        $companies = BocStock::query()
            ->selectRaw('ticker, MAX(name) as name')
            ->groupBy('ticker')
            ->orderBy('ticker')
            ->get();

        return view('admin.performances.index', compact('companies'));
    }

    public function data(Request $request)
    {
        $tickers = (array) $request->input('tickers', []);
        $tickers = array_values(array_filter(array_map(fn($t) => strtoupper(trim($t)), $tickers)));

        $dates = BocStock::query()
            ->select('date_boc')
            ->distinct()
            ->orderByDesc('date_boc')
            ->limit(7)
            ->pluck('date_boc')
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return response()->json(['labels' => [], 'datasets' => []]);
        }

        // default : top 5 du dernier jour
        if (empty($tickers)) {
            $lastDate = $dates->last();
            $tickers = BocStock::where('date_boc', $lastDate)
                ->whereNotNull('change')
                ->orderByDesc('change')
                ->limit(5)
                ->pluck('ticker')
                ->toArray();
        }

        $rows = BocStock::query()
            ->whereIn('ticker', $tickers)
            ->whereIn('date_boc', $dates)
            ->get(['ticker', 'name', 'date_boc', 'change']);

        $labels = $dates->map(fn($d) => (string) $d)->toArray();

        $datasets = [];
        foreach ($tickers as $ticker) {
            $points = array_fill(0, count($labels), null);
            $companyName = null;

            foreach ($rows->where('ticker', $ticker) as $r) {
                $companyName = $companyName ?? ($r->name ?? $ticker);
                $idx = array_search((string)$r->date_boc, $labels, true);
                if ($idx !== false) $points[$idx] = (float) $r->change;
            }

            $datasets[] = [
                'label' => $ticker . ($companyName ? ' — ' . $companyName : ''),
                'data'  => $points,
            ];
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => $datasets,
        ]);
    }
}
