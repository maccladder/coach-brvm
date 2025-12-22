<?php

namespace App\Http\Controllers;

use App\Services\BrvmActionsAiService;

class AdminMarketController extends Controller
{
    public function index(BrvmActionsAiService $svc)
    {
        $stocks = $svc->fetchMarketTableFromSite();

        return view('admin.market.index', [
            'count'  => count($stocks),
            'stocks' => $stocks,
        ]);
    }

    public function api(BrvmActionsAiService $svc)
    {
        $stocks = $svc->fetchMarketTableFromSite();

        return response()->json([
            'count'  => count($stocks),
            'stocks' => $stocks,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
