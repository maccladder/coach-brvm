<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use Throwable;

class GoogleAnalyticsService
{
    protected BetaAnalyticsDataClient $client;
    protected string $property;

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => base_path(env('GA_CREDENTIALS_PATH')),
        ]);

        // IMPORTANT: GA_PROPERTY_ID = 463914808 (pas le stream id)
        $this->property = 'properties/' . trim((string) env('GA_PROPERTY_ID'));
    }

    /** 👥 Visiteurs aujourd’hui */
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
            return 0;
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
            return 0;
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
            return [];
        }
    }

    /** ⏱️ Durée moyenne d’engagement (en secondes, 7 derniers jours) */
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
            return 0;
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
            return [];
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
}
