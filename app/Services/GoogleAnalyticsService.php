<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleAnalyticsService
{
    protected BetaAnalyticsDataClient $client;
    protected string $property;

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => base_path(config('services.google_analytics.credentials_path')),
        ]);

        // IMPORTANT: GA_PROPERTY_ID = 463914808 (pas le stream id)
        $this->property = 'properties/' . trim((string) config('services.google_analytics.property_id'));
    }

    /** 👥 Visiteurs aujourd'hui */
    public function todayUsers(): int
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property' => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => 'today', 'end_date' => 'today']),
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                ],
            ]));

            return (int) $this->firstMetricValue($response->getRows(), 0, 0);
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, 0);
        }
    }

    /** 🔴 Utilisateurs en temps réel */
    public function realtimeUsers(): int
    {
        try {
            $response = $this->client->runRealtimeReport(new RunRealtimeReportRequest([
                'property' => $this->property,
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                ],
            ]));

            // Realtime peut renvoyer 0 row => on sécurise
            return (int) $this->firstMetricValue($response->getRows(), 0, 0);
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, 0);
        }
    }

    /** 🌍 Top pays (7 derniers jours) */
    public function topCountries(int $limit = 5): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property' => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today']),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'country']),
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                ],
                'limit' => $limit,
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) {
                return [];
            }

            $data = [];
            foreach ($rows as $row) {
                $country = $row->getDimensionValues()[0]->getValue() ?? '';
                $users   = (int) ($row->getMetricValues()[0]->getValue() ?? 0);

                if ($country !== '') {
                    $data[] = ['country' => $country, 'users' => $users];
                }
            }

            return $data;
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, []);
        }
    }

    /** ⏱️ Durée moyenne d'engagement (en secondes, 7 derniers jours) */
    public function avgEngagementTime(): int
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property' => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today']),
                ],
                'metrics' => [
                    // métrique GA4: userEngagementDuration (secondes)
                    new Metric(['name' => 'userEngagementDuration']),
                    new Metric(['name' => 'activeUsers']),
                ],
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) return 0;

            $totalEngagement = (float) ($rows[0]->getMetricValues()[0]->getValue() ?? 0);
            $activeUsers     = (float) ($rows[0]->getMetricValues()[1]->getValue() ?? 0);

            if ($activeUsers <= 0) return 0;

            // moyenne par utilisateur
            return (int) round($totalEngagement / $activeUsers);
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, 0);
        }
    }

    /** 📄 Top pages (7 derniers jours) */
    public function topPages(int $limit = 7): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property' => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today']),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'pagePath']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                ],
                'limit' => $limit,
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) return [];

            $data = [];
            foreach ($rows as $row) {
                $path  = $row->getDimensionValues()[0]->getValue() ?? '';
                $views = (int) ($row->getMetricValues()[0]->getValue() ?? 0);

                if ($path !== '') {
                    $data[] = ['path' => $path, 'views' => $views];
                }
            }

            return $data;
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, []);
        }
    }

    /** 📅 Visiteurs jour par jour sur les 7 derniers jours */
    public function last7DaysTimeline(): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property'    => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '6daysAgo', 'end_date' => 'today']),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'date']),
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                ],
            ]));

            // GA4 ne renvoie aucune ligne pour un jour sans visiteur : on part des 7 jours (0 par
            // défaut) pour que la courbe descende à 0 au lieu de s'arrêter au dernier jour actif,
            // et on trie par vraie date (l'ancien tri texte « JJ/MM » mettait 01/11 avant 29/10)
            $users = [];
            foreach ($response->getRows() ?? [] as $row) {
                $rawDate = (string) ($row->getDimensionValues()[0]->getValue() ?? '');
                if (strlen($rawDate) === 8) {
                    $users[$rawDate] = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                }
            }

            return self::fillDays($users, Carbon::today());
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, []);
        }
    }

    /** 📱 Répartition par type d'appareil (7 derniers jours) */
    public function deviceCategories(): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property'    => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today']),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'deviceCategory']),
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                ],
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) return [];

            $data = [];
            foreach ($rows as $row) {
                $device = $row->getDimensionValues()[0]->getValue() ?? '';
                $users  = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                if ($device !== '') {
                    $data[] = ['device' => $device, 'users' => $users];
                }
            }

            return $data;
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, []);
        }
    }

    /** 🔗 Sources de trafic (7 derniers jours) */
    public function trafficSources(int $limit = 6): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property'    => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today']),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'sessionDefaultChannelGroup']),
                ],
                'metrics' => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'activeUsers']),
                ],
                'limit' => $limit,
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) return [];

            $data = [];
            foreach ($rows as $row) {
                $channel  = $row->getDimensionValues()[0]->getValue() ?? '';
                $sessions = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                $users    = (int) ($row->getMetricValues()[1]->getValue() ?? 0);
                if ($channel !== '') {
                    $data[] = ['channel' => $channel, 'sessions' => $sessions, 'users' => $users];
                }
            }

            return $data;
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, []);
        }
    }

    /** 📆 Utilisateurs sur 30 jours + nouveaux utilisateurs */
    public function monthStats(): array
    {
        try {
            $response = $this->client->runReport(new RunReportRequest([
                'property'    => $this->property,
                'date_ranges' => [
                    new DateRange(['start_date' => '29daysAgo', 'end_date' => 'today']),
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'newUsers']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'bounceRate']),
                ],
            ]));

            $rows = $response->getRows();
            if (!$rows || count($rows) === 0) {
                return ['users30' => 0, 'newUsers30' => 0, 'sessions30' => 0, 'bounceRate' => 0.0];
            }

            $mv = $rows[0]->getMetricValues();

            return [
                'users30'    => (int)   ($mv[0]->getValue() ?? 0),
                'newUsers30' => (int)   ($mv[1]->getValue() ?? 0),
                'sessions30' => (int)   ($mv[2]->getValue() ?? 0),
                'bounceRate' => round((float) ($mv[3]->getValue() ?? 0) * 100, 1),
            ];
        } catch (Throwable $e) {
            return $this->failed(__FUNCTION__, $e, ['users30' => 0, 'newUsers30' => 0, 'sessions30' => 0, 'bounceRate' => 0.0]);
        }
    }

    /**
     * Helper: récupère proprement une valeur métrique [rowIndex][metricIndex]
     */
    private function firstMetricValue($rows, int $rowIndex = 0, int $metricIndex = 0): string
    {
        if (!$rows || !is_countable($rows) || count($rows) === 0) {
            return '0';
        }

        $row = $rows[$rowIndex] ?? null;
        if (!$row) return '0';

        $metricValues = $row->getMetricValues();
        if (!$metricValues || !is_countable($metricValues) || count($metricValues) === 0) {
            return '0';
        }

        return $metricValues[$metricIndex]->getValue() ?? '0';
    }

    /**
     * Les 7 derniers jours (6 jours avant $today jusqu'à $today), triés par date réelle, avec 0
     * pour les jours absents. $users : visiteurs par date GA « YYYYMMDD ».
     *
     * @return array<int, array{date: string, users: int}>
     */
    public static function fillDays(array $users, Carbon $today): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day    = $today->copy()->subDays($i);
            $days[] = ['date' => $day->format('d/m'), 'users' => (int) ($users[$day->format('Ymd')] ?? 0)];
        }

        return $days;
    }

    /** Erreur de l'API GA4 : journalisée (Log::warning), puis valeur par défaut pour ne pas casser l'admin. */
    private function failed(string $method, Throwable $e, mixed $default): mixed
    {
        Log::warning('GoogleAnalyticsService::' . $method . ' : erreur API GA4', [
            'property' => $this->property,
            'error'    => get_class($e) . ' : ' . $e->getMessage(),
        ]);

        return $default;
    }
}
