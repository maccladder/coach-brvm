<?php

namespace App\Http\Controllers;

use App\Models\BrvmDividende;
use Illuminate\Http\Request;

class SocieteController extends Controller
{
    protected array $societes;

    public function __construct()
    {
        $this->societes = require app_path('Data/brvm_societes.php');
    }

    public function index(Request $request)
{
    $q = trim((string) $request->query('q', ''));

    $items = collect($this->societes)->map(function ($data, $slug) {
        $data['slug'] = $slug;
        return $data;
    });

    if ($q !== '') {
        $qLower = mb_strtolower($q);
        $items = $items->filter(function ($s) use ($qLower) {
            return str_contains(mb_strtolower($s['name']), $qLower)
                || str_contains(mb_strtolower($s['ticker']), $qLower);
        });
    }

    // ✅ Dividendes 2026 indexés par ticker
    $dividendes = BrvmDividende::where('year', 2026)->get()->keyBy('ticker');

    return view('societes.index', [
        'items'      => $items->sortBy('name'),
        'q'          => $q,
        'dividendes' => $dividendes,
    ]);
}

    public function show(string $slug)
{
    abort_unless(isset($this->societes[$slug]), 404);

    $societe = $this->societes[$slug];
    $societe['slug'] = $slug;

    $rankingYear = 2026;

    $dividende = BrvmDividende::where('ticker', $societe['ticker'])
        ->where('year', $rankingYear)
        ->first();

    $hasDividend = $dividende
        && $dividende->dividende_net !== null
        && (float) $dividende->dividende_net > 0;

    $rank         = null;
    $totalPayeurs = 0;
    $isRanked     = false;

    if ($hasDividend) {
        $isRanked = true;

        $ranking = BrvmDividende::query()
            ->whereNotNull('dividende_net')
            ->where('dividende_net', '>', 0)
            ->where('year', $rankingYear)
            ->orderByDesc('dividende_net')
            ->pluck('ticker')
            ->values();

        $totalPayeurs = $ranking->count();
        $pos  = $ranking->search($societe['ticker']);
        $rank = ($pos !== false) ? ($pos + 1) : null;
    }

    return view('societes.show', compact(
        'societe',
        'dividende',
        'rank',
        'totalPayeurs',
        'hasDividend',
        'rankingYear',
        'isRanked'
    ));
}


}
