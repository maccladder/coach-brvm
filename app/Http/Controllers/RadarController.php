<?php

// app/Http/Controllers/RadarController.php
namespace App\Http\Controllers;

use App\Models\DailyBoc;
use App\Models\BocStock;
use Illuminate\Http\JsonResponse;

class RadarController extends Controller
{
    public function bubblesLatest(): JsonResponse
    {
        $latest = DailyBoc::orderByDesc('date_boc')->first();

        if (!$latest) {
            return response()->json([
                'date' => null,
                'bubbles' => [],
            ]);
        }

        $rows = BocStock::where('daily_boc_id', $latest->id)
            ->orderByDesc('change') // option: tri par perf
            ->get(['ticker','name','price','change']);

        return response()->json([
            'date' => (string) $latest->date_boc,
            'bubbles' => $rows,
        ]);
    }
}
