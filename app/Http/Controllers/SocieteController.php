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

    // ✅ Dividendes indexés par ticker
    $dividendes = BrvmDividende::all()->keyBy('ticker');

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

        // ✅ Dividendes depuis la table brvm_dividendes (par ticker)
        $dividende = BrvmDividende::where('ticker', $societe['ticker'])->first();

        return view('societes.show', compact('societe', 'dividende'));
    }
}
