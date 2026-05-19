<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BocStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockHistoryController extends Controller
{
    public function history(Request $request, string $ticker): JsonResponse
    {
        $ticker = strtoupper(trim($ticker));

        $allowed = ['1w', '1m', '1y', 'all'];
        $range   = in_array($request->query('range'), $allowed, true)
            ? $request->query('range')
            : 'all';

        $startDate = match ($range) {
            '1w'    => now()->subDays(7),
            '1m'    => now()->subMonth(),
            '1y'    => now()->subYear(),
            default => null,
        };

        $query = BocStock::where('ticker', $ticker);

        if ($startDate) {
            $query->where('date_boc', '>=', $startDate->toDateString());
        }

        $rows = $query->orderBy('date_boc', 'asc')
                      ->select('date_boc', 'price', 'change')
                      ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'ticker'  => $ticker,
                'range'   => $range,
                'name'    => null,
                'data'    => [],
                'message' => 'Aucune donnée historique pour ce ticker',
            ], 404);
        }

        $latestName = BocStock::where('ticker', $ticker)
            ->orderBy('date_boc', 'desc')
            ->value('name');

        $data = $rows->map(fn ($row) => [
            'date'   => $row->date_boc->format('Y-m-d'),
            'price'  => $row->price,
            'change' => $row->change,
        ])->values();

        $payload = [
            'ticker'     => $ticker,
            'name'       => $latestName,
            'range'      => $range,
            'count'      => $data->count(),
            'first_date' => $data->first()['date'],
            'last_date'  => $data->last()['date'],
            'data'       => $data,
        ];

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=60');
    }
}
