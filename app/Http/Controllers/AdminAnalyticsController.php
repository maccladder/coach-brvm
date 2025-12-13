<?php

namespace App\Http\Controllers;

use App\Services\GoogleAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class AdminAnalyticsController extends Controller
{
    /**
     * Page dashboard Analytics (Blade)
     */
    public function index(GoogleAnalyticsService $ga): View
    {
        try {
            $data = $this->buildAnalyticsData($ga);
        } catch (Throwable $e) {
            // Sécurité : en cas d’erreur GA, on ne casse pas l’admin
            $data = $this->emptyAnalyticsData();
        }

        return view('admin.analytics.index', $data);
    }

    /**
     * Endpoint JSON (AJAX / refresh auto)
     */
    public function data(GoogleAnalyticsService $ga): JsonResponse
    {
        try {
            return response()->json(
                $this->buildAnalyticsData($ga)
            );
        } catch (Throwable $e) {
            return response()->json(
                $this->emptyAnalyticsData(),
                200
            );
        }
    }

    /**
     * Centralise la récupération GA
     */
    private function buildAnalyticsData(GoogleAnalyticsService $ga): array
    {
        return [
            'todayUsers'        => $ga->todayUsers() ?? 0,
            'realtimeUsers'     => $ga->realtimeUsers() ?? 0,
            'topCountries'      => $ga->topCountries(5) ?? [],
            'topPages'          => $ga->topPages(7) ?? [],
            'avgEngagementSecs' => $ga->avgEngagementTime() ?? 0,
        ];
    }

    /**
     * Valeurs par défaut (fallback)
     */
    private function emptyAnalyticsData(): array
    {
        return [
            'todayUsers'        => 0,
            'realtimeUsers'     => 0,
            'topCountries'      => [],
            'topPages'          => [],
            'avgEngagementSecs' => 0,
        ];
    }
}
