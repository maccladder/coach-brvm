<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dernier relevé des cours brvm.org, rafraîchi par la tâche planifiée
 * brvm:refresh-market (jamais pendant le rendu d'une page).
 *
 * Les pages (bandeau, accueil) ne font que lire ce relevé : elles ne
 * dépendent donc jamais du temps de réponse de brvm.org.
 */
class BrvmMarketSnapshot
{
    public const CACHE_KEY = 'brvm_market_snapshot';

    public function __construct(private BrvmActionsAiService $svc) {}

    /**
     * Interroge brvm.org et remplace le relevé. Un échec (site lent, vide,
     * HTML modifié) conserve le relevé précédent.
     *
     * @return int nombre de lignes enregistrées (0 = relevé précédent conservé)
     */
    public function refresh(): int
    {
        $rows = $this->svc->fetchMarketTableFromSite();

        if (empty($rows)) {
            Log::warning('BrvmMarketSnapshot: brvm.org sans données, relevé précédent conservé');
            return 0;
        }

        Cache::forever(self::CACHE_KEY, [
            'fetched_at' => now()->toIso8601String(),
            'rows'       => array_values($rows),
        ]);

        return count($rows);
    }

    /** Lignes du dernier relevé (vide si aucun relevé). */
    public function rows(): array
    {
        return Cache::get(self::CACHE_KEY)['rows'] ?? [];
    }

    public function row(string $ticker): ?array
    {
        return collect($this->rows())->firstWhere('ticker', strtoupper($ticker));
    }

    public function fetchedAt(): ?Carbon
    {
        $at = Cache::get(self::CACHE_KEY)['fetched_at'] ?? null;

        return $at ? Carbon::parse($at) : null;
    }
}
