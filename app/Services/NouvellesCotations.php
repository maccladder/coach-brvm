<?php

namespace App\Services;

use App\Models\BocStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Encart « Nouvelle cotation » de l'accueil, piloté par config/cotations.php.
 *
 * Le cours affiché est le dernier connu : relevé brvm.org en cache
 * (BrvmMarketSnapshot) ou clôture de la dernière BOC en base, le plus
 * récent des deux. Jamais d'appel direct à brvm.org.
 */
class NouvellesCotations
{
    public function __construct(private BrvmMarketSnapshot $snapshot) {}

    /**
     * Cotations à afficher aujourd'hui, enrichies du dernier cours connu.
     *
     * @return Collection<int, array>
     */
    public function actives(): Collection
    {
        $aujourdhui = now();

        return collect(config('cotations.nouvelles', []))
            ->filter(fn (array $c) => $aujourdhui->between(
                Carbon::parse($c['premiere_cotation'])->startOfDay(),
                Carbon::parse($c['fin_affichage'])->endOfDay()
            ))
            ->map(fn (array $c) => $c + $this->dernierCours($c['ticker'], (float) $c['prix_opv']))
            ->values();
    }

    /**
     * @return array{cours: ?float, date_cours: ?Carbon, source_cours: ?string, variation_opv: ?float}
     */
    private function dernierCours(string $ticker, float $prixOpv): array
    {
        $candidats = [];

        $releve = $this->snapshot->row($ticker);
        if ($releve && ($releve['close'] ?? 0) > 0 && $this->snapshot->fetchedAt()) {
            $candidats[] = ['cours' => (float) $releve['close'], 'date_cours' => $this->snapshot->fetchedAt(), 'source_cours' => 'releve'];
        }

        $boc = BocStock::where('ticker', strtoupper($ticker))
            ->where('price', '>', 0)
            ->orderByDesc('date_boc')
            ->first();
        if ($boc) {
            // Cours de clôture : daté de la fin de séance de la BOC
            $candidats[] = ['cours' => (float) $boc->price, 'date_cours' => $boc->date_boc->copy()->setTime(15, 30), 'source_cours' => 'boc'];
        }

        $dernier = collect($candidats)->sortByDesc(fn ($c) => $c['date_cours']->timestamp)->first();

        if (!$dernier) {
            return ['cours' => null, 'date_cours' => null, 'source_cours' => null, 'variation_opv' => null];
        }

        return $dernier + [
            'variation_opv' => $prixOpv > 0 ? ($dernier['cours'] - $prixOpv) / $prixOpv * 100 : null,
        ];
    }
}
