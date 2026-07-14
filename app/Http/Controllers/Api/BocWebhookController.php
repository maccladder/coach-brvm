<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyBoc;
use App\Services\BrvmCalendarService;
use App\Services\DailyBocIngestionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BocWebhookController extends Controller
{
    /**
     * Liste les dates de BOC manquantes (jours ouvrés BRVM sans DailyBoc en base),
     * de la plus récente à la plus ancienne. Le jour J n'est inclus qu'après la
     * clôture + marge de publication (cf. BrvmCalendarService::isTodayPublishable).
     */
    public function missing(Request $request, BrvmCalendarService $calendar)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        $floor  = $calendar->earliestBocDate();
        $cursor = Carbon::today();

        if (!$calendar->isTodayPublishable()) {
            $cursor->subDay();
        }

        $existingDates = DailyBoc::where('date_boc', '>=', $floor->toDateString())
            ->pluck('date_boc')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $missing = [];

        while ($cursor->gte($floor) && count($missing) < $limit) {
            if ($calendar->isTradingDay($cursor)) {
                $dateString = $cursor->toDateString();

                if (!isset($existingDates[$dateString])) {
                    $ymd = $cursor->format('Ymd');

                    $missing[] = [
                        'date'              => $dateString,
                        'filename_expected' => "boc_{$ymd}_2.pdf",
                        'source_url'        => "https://www.brvm.org/sites/default/files/boc_{$ymd}_2.pdf",
                    ];
                }
            }

            $cursor->subDay();
        }

        return response()->json(['missing' => $missing]);
    }

    /**
     * Reçoit une BOC (date + PDF) depuis l'agent n8n et l'ingère exactement
     * comme l'upload manuel de /admin/bocs (même disque, même pipeline
     * extraction + publication publique).
     */
    public function store(Request $request, BrvmCalendarService $calendar, DailyBocIngestionService $ingestion)
    {
        // Validator manuel (au lieu de $request->validate()) pour garantir une
        // réponse JSON même quand l'appelant n'envoie pas d'en-tête Accept adapté
        // (sinon Laravel redirige en HTML sur échec de validation).
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'validation_failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data       = $validator->validated();
        $date       = Carbon::parse($data['date']);
        $dateString = $date->toDateString();

        if (!$calendar->isTradingDay($date)) {
            return response()->json([
                'error'   => 'not_trading_day',
                'message' => "Pas de BOC attendue le {$dateString} (week-end ou jour férié BRVM).",
            ], 422);
        }

        $existing = DailyBoc::whereDate('date_boc', $dateString)->first();

        if ($existing) {
            Log::info('BOC webhook n8n — déjà existante', [
                'date' => $dateString,
                'id'   => $existing->id,
            ]);

            return response()->json([
                'created' => false,
                'date'    => $dateString,
                'path'    => $existing->file_path,
            ], 200);
        }

        $result   = $ingestion->ingest($dateString, $request->file('file'));
        $dailyBoc = $result['boc'];

        Log::info('BOC webhook n8n — créée', [
            'date'      => $dateString,
            'id'        => $dailyBoc->id,
            'extracted' => $result['extracted'],
        ]);

        return response()->json([
            'created' => true,
            'date'    => $dateString,
            'path'    => $dailyBoc->file_path,
        ], 201);
    }
}
