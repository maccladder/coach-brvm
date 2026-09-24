<?php

namespace App\Http\Controllers;

use App\Models\BrvmDividende;
use App\Models\Societe;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SocieteController extends Controller
{
    /**
     * Annuaire = sociétés cotées de la table `societes` (source de vérité).
     * app/Data/brvm_societes.php ne sert qu'à enrichir les fiches (description,
     * logo, contacts) et fixe le slug des fiches existantes.
     *
     * @return Collection<string, array> indexée par slug
     */
    protected function societes(): Collection
    {
        $enrichments = collect(require app_path('Data/brvm_societes.php'))
            ->map(fn ($data, $slug) => $data + ['slug' => $slug])
            ->keyBy(fn ($data) => strtoupper($data['ticker']));

        return Societe::where('is_listed', true)
            ->get()
            ->map(function (Societe $s) use ($enrichments) {
                $extra = $enrichments->get(strtoupper($s->code), []);

                return array_merge([
                    'slug'        => Str::slug($s->name),
                    'name'        => $s->name,
                    'logo'        => null,
                    'description' => '',
                    'telephone'   => null,
                    'adresse'     => null,
                    'dirigeants'  => [],
                ], $extra, [
                    'ticker'  => $s->code,
                    'sector'  => $s->sector,
                    'country' => $s->country,
                ]);
            })
            ->keyBy('slug');
    }

    public function index(Request $request)
{
    $q = trim((string) $request->query('q', ''));

    $items = $this->societes();

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
    $societe = $this->societes()->get($slug);
    abort_unless($societe, 404);

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
