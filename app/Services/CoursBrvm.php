<?php

namespace App\Services;

use App\Models\BocStock;
use Illuminate\Support\Carbon;

/**
 * Règle unique des cours affichés (marché en direct, bandeau) et utilisés par
 * le simulateur, à partir des lignes brvm.org (BrvmActionsAiService).
 *
 * - cours        : clôture du jour → ouverture → clôture précédente
 * - cloture_prec : ⚠️ jamais la colonne « Cours veille » de brvm.org, qui ne
 *                  contient plus la clôture précédente pendant la séance.
 *                  1. prix de l'OPV le premier jour d'une cotation (config/cotations.php)
 *                  2. clôture de la BOC de la séance précédente
 *                  3. déduite de la variation officielle : cours / (1 + variation / 100)
 *                  4. dernière clôture BOC connue
 * - variation    : variation officielle de brvm.org, sauf le premier jour
 *                  d'une cotation (calculée par rapport au prix de l'OPV).
 */
class CoursBrvm
{
    /** Écart relatif en dessous duquel deux cours sont considérés égaux. */
    private const TOLERANCE = 0.0005;

    public function __construct(
        private BrvmActionsAiService $site,
        private BrvmCalendarService $calendrier,
    ) {}

    /** Lignes brvm.org en direct, enrichies (appel HTTP : pages d'action uniquement). */
    public function marcheEnDirect(): array
    {
        return $this->enrichir($this->site->fetchMarketTableFromSite());
    }

    public function ligne(array $lignesEnrichies, string $ticker): ?array
    {
        return collect($lignesEnrichies)->firstWhere('ticker', strtoupper($ticker));
    }

    /**
     * @param  array $lignes lignes brvm.org (ticker, open, close, change, maj…)
     * @return array mêmes lignes + cours, cloture_prec, source_cloture_prec, variation, maj
     */
    public function enrichir(array $lignes): array
    {
        if (empty($lignes)) {
            return [];
        }

        $seance         = $this->dateSeance($lignes);
        $seancePrec     = $this->seancePrecedente($seance);
        $cloturesPrec   = $this->cloturesBoc($seancePrec);
        $premiersJours  = collect(config('cotations.nouvelles', []))
            ->filter(fn ($c) => Carbon::parse($c['premiere_cotation'])->isSameDay($seance))
            ->keyBy(fn ($c) => strtoupper($c['ticker']));

        return array_map(function (array $l) use ($cloturesPrec, $premiersJours, $seance) {
            $ticker   = strtoupper($l['ticker'] ?? '');
            $close    = $this->positif($l['close'] ?? null);
            $open     = $this->positif($l['open'] ?? null);
            // Variation officielle conservée telle quelle. Elle peut différer du
            // calcul clôture / veille, ex. SHEC le 24/09/2026 : -7,27 % affiché
            // pour -3,97 % calculé (cours de référence ajusté par la BRVM,
            // probablement un détachement de dividende).
            $variation = isset($l['change']) && is_numeric($l['change']) ? (float) $l['change'] : null;

            $coursSeance = $close ?? $open;
            $source      = null;
            $prec        = null;

            if ($premier = $premiersJours->get($ticker)) {
                // 1er jour de cotation : pas de veille, base = prix de l'OPV
                $prec      = (float) $premier['prix_opv'];
                $source    = 'opv';
                $variation = $coursSeance !== null ? round(($coursSeance - $prec) / $prec * 100, 2) : 0.0;
            } else {
                $boc = $cloturesPrec[$ticker] ?? null;

                if ($boc !== null && $this->coherent($coursSeance, $boc, $variation)) {
                    [$prec, $source] = [$boc, 'boc'];
                } elseif ($coursSeance !== null && $variation !== null && $variation > -100) {
                    [$prec, $source] = [round($coursSeance / (1 + $variation / 100)), 'deduite'];
                } elseif ($boc !== null) {
                    [$prec, $source] = [$boc, 'boc'];
                } elseif ($derniere = $this->derniereClotureBoc($ticker)) {
                    [$prec, $source] = [$derniere, 'boc_ancienne'];
                }
            }

            return array_merge(['maj' => null], $l, [
                'cours'               => $coursSeance ?? $prec,
                'cloture_prec'        => $prec,
                'source_cloture_prec' => $source,
                'variation'           => $variation,
                'variation_calculee'  => $source === 'opv',
                'seance'              => $seance->toDateString(),
            ]);
        }, $lignes);
    }

    /**
     * La clôture BOC est retenue si elle va dans le même sens que la variation
     * officielle. Sinon (BOC manquante / décalée, page du site pas encore
     * remise à zéro le matin) on la déduit de la variation.
     */
    private function coherent(?float $cours, float $prec, ?float $variation): bool
    {
        if ($cours === null || $variation === null) {
            return true;
        }

        $ecart    = ($cours - $prec) / $prec;
        $sensBoc  = abs($ecart) < self::TOLERANCE ? 0 : ($ecart <=> 0);
        $sensSite = abs($variation) < self::TOLERANCE * 100 ? 0 : ($variation <=> 0);

        return $sensBoc === $sensSite;
    }

    /** Séance affichée : date de « Dernière mise à jour » du site, sinon la séance du jour. */
    private function dateSeance(array $lignes): Carbon
    {
        $maj = collect($lignes)->pluck('maj')->filter()->first();
        $jour = $maj ? Carbon::parse($maj)->startOfDay() : now()->startOfDay();

        while (!$this->calendrier->isTradingDay($jour)) {
            $jour->subDay();
        }

        return $jour;
    }

    private function seancePrecedente(Carbon $seance): Carbon
    {
        $jour = $seance->copy()->subDay();
        for ($i = 0; $i < 15 && !$this->calendrier->isTradingDay($jour); $i++) {
            $jour->subDay();
        }

        return $jour;
    }

    /** @return array<string, float> clôtures BOC de la séance, par ticker */
    private function cloturesBoc(Carbon $jour): array
    {
        return BocStock::where('date_boc', '>=', $jour->copy()->startOfDay())
            ->where('date_boc', '<', $jour->copy()->addDay()->startOfDay())
            ->where('price', '>', 0)
            ->pluck('price', 'ticker')
            ->map(fn ($p) => (float) $p)
            ->all();
    }

    private function derniereClotureBoc(string $ticker): ?float
    {
        $prix = BocStock::where('ticker', $ticker)->where('price', '>', 0)->orderByDesc('date_boc')->value('price');

        return $prix !== null ? (float) $prix : null;
    }

    private function positif($v): ?float
    {
        return is_numeric($v) && (float) $v > 0 ? (float) $v : null;
    }
}
