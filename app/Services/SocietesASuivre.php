<?php

namespace App\Services;

use App\Models\BocStock;
use Illuminate\Support\Collection;

/**
 * Cartes « À suivre » de l'accueil (config/vitrine.php) enrichies du dernier
 * cours de clôture en base (boc_stocks) et de sa variation par rapport à la veille.
 */
class SocietesASuivre
{
    /**
     * @return Collection<int, array>
     */
    public function cartes(): Collection
    {
        return collect(config('vitrine.societes', []))
            ->map(fn (array $s) => $s + $this->dernierCours($s['ticker']))
            ->values();
    }

    /**
     * @return array{cours: ?float, date_cours: ?\Illuminate\Support\Carbon, variation: ?float}
     */
    private function dernierCours(string $ticker): array
    {
        $derniers = BocStock::where('ticker', strtoupper($ticker))
            ->where('price', '>', 0)
            ->orderByDesc('date_boc')
            ->limit(2)
            ->get(['date_boc', 'price', 'change']);

        $dernier = $derniers->first();

        if (!$dernier) {
            return ['cours' => null, 'date_cours' => null, 'variation' => null];
        }

        // Variation publiée dans la BOC (ajustée par la BRVM en cas de fractionnement) ;
        // à défaut, calculée par rapport à la clôture précédente en base
        $variation = $dernier->change;
        if ($variation === null && ($veille = $derniers->get(1))) {
            $variation = ($dernier->price - $veille->price) / $veille->price * 100;
        }

        return [
            'cours'      => (float) $dernier->price,
            'date_cours' => $dernier->date_boc,
            'variation'  => $variation === null ? null : (float) $variation,
        ];
    }
}
