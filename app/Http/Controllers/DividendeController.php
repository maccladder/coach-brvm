<?php

namespace App\Http\Controllers;

use App\Models\BrvmDividende;
use Illuminate\Http\Request;

class DividendeController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', 2025); // par défaut 2025

        $dividendes = BrvmDividende::query()
            ->whereNotNull('dividende_net')
            ->where('dividende_net', '>', 0)
            ->whereNotNull('date_paiement')
            ->whereYear('date_paiement', $year)          // ✅ FILTRE ANNÉE
            ->orderByDesc('dividende_net')
            ->get();

        $total = $dividendes->count();
        $max   = (float) ($dividendes->max('dividende_net') ?? 0);

        $dividendes = $dividendes->values()->map(function ($row, $index) use ($total, $max) {
            $row->rank = $index + 1;
            $row->total = $total;
            $row->bar_percent = $max > 0 ? round(((float)$row->dividende_net / $max) * 100, 2) : 0;
            return $row;
        });

        return view('dividendes.index', compact('dividendes', 'total', 'year'));
    }
}
