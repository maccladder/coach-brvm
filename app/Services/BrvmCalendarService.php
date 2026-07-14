<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Source unique du calendrier BRVM (jours fériés + jours ouvrés).
 * Utilisée par /admin/bocs et par les endpoints n8n pour éviter que
 * deux listes de fériés divergent silencieusement.
 */
class BrvmCalendarService
{
    /**
     * Heure (Africa/Abidjan, UTC toute l'année, pas de DST) à partir de laquelle
     * il est raisonnable de considérer la BOC du jour comme "manquante" si elle
     * n'a pas encore été reçue. La clôture BRVM est ~15h10 ; on laisse une marge
     * de publication avant de la compter comme manquante.
     */
    private const TODAY_CUTOFF_HOUR = 18;

    /**
     * Jours fériés BRVM / Côte d'Ivoire (YYYY-MM-DD).
     */
    public function holidays(): array
    {
        return [
            // 2025
            '2025-01-01',
            '2025-03-27',
            '2025-03-31',
            '2025-04-21',
            '2025-05-01',
            '2025-05-29',
            '2025-06-06',
            '2025-06-09',
            '2025-08-07',
            '2025-08-15',
            '2025-09-05',
            '2025-12-25',

            // 2026
            '2026-01-01',
            '2026-03-17',
            '2026-03-20',
            '2026-04-06',
            '2026-05-01',
            '2026-05-14',
            '2026-05-25',
            '2026-05-27',
            '2026-08-07',
            '2026-08-15',
            '2026-08-26',
            '2026-11-01',
            '2026-11-15',
            '2026-12-25',
        ];
    }

    /**
     * Première date à partir de laquelle des BOC sont attendues en base
     * (démarrage de la couverture de daily_bocs).
     */
    public function earliestBocDate(): Carbon
    {
        return Carbon::create(2025, 1, 1)->startOfDay();
    }

    public function isHoliday(Carbon $date): bool
    {
        return in_array($date->toDateString(), $this->holidays(), true);
    }

    public function isTradingDay(Carbon $date): bool
    {
        return !$date->isWeekend() && !$this->isHoliday($date);
    }

    /**
     * Le jour J ne doit être compté "manquant" qu'après la clôture + marge de publication.
     */
    public function isTodayPublishable(): bool
    {
        return Carbon::now('Africa/Abidjan')->hour >= self::TODAY_CUTOFF_HOUR;
    }
}
