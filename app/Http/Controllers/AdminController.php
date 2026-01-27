<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BocStock;
use App\Models\DailyBoc;
use App\Models\ClientBoc;
use Illuminate\Http\Request;
use App\Models\ClientFinancial;
use Illuminate\Support\Facades\DB;
use App\Services\BrvmBubbleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminController extends Controller
{
    /** Page de login admin */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /** Vérification du code admin */
    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($request->code !== 'Coach-brvm2025') {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect.');
        }

        session(['is_admin' => true]);

        return redirect()->route('admin.dashboard');
    }

    /** Déconnexion admin */
    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');

        return redirect()->route('admin.login.form')
            ->with('success', 'Déconnecté avec succès.');
    }

    /** Dashboard admin */
    public function dashboard()
    {
        $bocs = ClientBoc::orderByDesc('created_at')->limit(50)->get();
        $financials = ClientFinancial::orderByDesc('created_at')->limit(50)->get();

        return view('admin.dashboard', compact('bocs', 'financials'));
    }

    /**
     * Jours fériés BRVM / Côte d'Ivoire
     * Format : YYYY-MM-DD
     */
    private function getBrvmHolidays(): array
{
    return [
        // ✅ 2025 (corrigé et vérifié)
        '2025-01-01', // Jour de l'An

        '2025-03-27', // Nuit du Destin (Lailatou-Kadr) ✅
        '2025-03-31', // Lendemain Ramadan / Aïd el-Fitr ✅

        '2025-09-04', // fête de Maouloud
        '2025-04-21', // Lundi de Pâques
        '2025-05-01', // Fête du Travail
        '2025-05-29', // Ascension
        '2025-06-06', // Tabaski (Aïd el-Kébir)
        '2025-06-09', // Lundi de Pentecôte
        '2025-08-07', // Indépendance
        '2025-08-15', // Assomption
        '2025-09-05', // Maouloud (naissance du Prophète)
        '2025-12-25', // Noël

        // ✅ 2026 (inchangé)
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


    public function dailyBocsIndex(Request $request)
{
    $startDate = Carbon::create(2025, 1, 1)->startOfDay();
    $today     = Carbon::today();

    $holidays = $this->getBrvmHolidays();

    $bocs = DailyBoc::whereBetween('date_boc', [$startDate, $today])
        ->get()
        ->keyBy(fn ($boc) => Carbon::parse($boc->date_boc)->toDateString());

    $daysAll = [];
    $current = $startDate->copy();

    while ($current->lte($today)) {
        $key = $current->toDateString();

        if ($current->isWeekend()) {
            $current->addDay();
            continue;
        }

        if (in_array($key, $holidays, true)) {
            $current->addDay();
            continue;
        }

        $record  = $bocs->get($key);
        $isToday = $current->isSameDay($today);

        $daysAll[] = [
            'date'       => $current->copy(),
            'record'     => $record,
            'has_boc'    => (bool) $record,
            'is_today'   => $isToday,
            'is_missing' => !$record && !$isToday,
        ];

        $current->addDay();
    }

    // ✅ Tri du plus récent au plus ancien
    $daysCollection = collect($daysAll)
        ->sortByDesc(fn ($d) => $d['date']->timestamp)
        ->values();

    // ✅ Stats sur toute la période
    $stats = [
        'total_days' => $daysCollection->count(),
        'received'   => $daysCollection->where('has_boc', true)->count(),
        'missing'    => $daysCollection->where('is_missing', true)->count(),
    ];

    // ✅ Pagination
    $perPage = (int) $request->query('per_page', 60);
    $page    = (int) $request->query('page', 1);

    $itemsForCurrentPage = $daysCollection
        ->slice(($page - 1) * $perPage, $perPage)
        ->values();

    $days = new LengthAwarePaginator(
        $itemsForCurrentPage,
        $daysCollection->count(),
        $perPage,
        $page,
        [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]
    );

    return view('admin.daily_bocs', compact('days', 'today', 'stats', 'perPage'));
}

    /** 👉 Upload d’une BOC */
    public function dailyBocsStore(Request $request, BrvmBubbleService $bubble)
    {
        $request->validate([
            'date_boc' => ['required', 'date', 'after_or_equal:2025-01-01', 'before_or_equal:today'],
            'file'     => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $date = Carbon::parse($request->input('date_boc'));
        $dateString = $date->toDateString();

        $holidays = $this->getBrvmHolidays();

        if ($date->isWeekend()) {
            return back()->with('error', "Il n'y a pas de BOC les samedis et dimanches.");
        }

        if (in_array($dateString, $holidays, true)) {
            return back()->with('error', "Il n'y a pas de BOC les jours fériés officiels (BRVM / Côte d'Ivoire).");
        }

        if (DailyBoc::where('date_boc', $dateString)->exists()) {
            return back()->with('error', "Une BOC existe déjà pour la date {$dateString}.");
        }

        $path = $request->file('file')->store('bocs', 'public');

        $dailyBoc = DailyBoc::create([
            'date_boc'      => $dateString,
            'file_path'     => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
        ]);

        try {
            $stocks = $bubble->extractFromBoc($dailyBoc->file_path);

            DB::transaction(function () use ($dailyBoc, $stocks) {
                BocStock::where('daily_boc_id', $dailyBoc->id)->delete();

                $rows = [];
                foreach ($stocks as $s) {
                    $ticker = strtoupper(trim($s['ticker'] ?? ''));
                    if ($ticker === '') continue;

                    $rows[] = [
                        'daily_boc_id' => $dailyBoc->id,
                        'date_boc'     => $dailyBoc->date_boc,
                        'ticker'       => $ticker,
                        'name'         => $s['name'] ?? null,
                        'price'        => $s['price'] ?? null,
                        'change'       => $s['change'] ?? null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }

                if (!empty($rows)) {
                    BocStock::insert($rows);
                }
            });

            return back()->with('success', "BOC du {$dateString} enregistrée + variations extraites ✅");

        } catch (\Throwable $e) {
            Log::error("Extraction variations échouée (DailyBoc {$dailyBoc->id}) : " . $e->getMessage());

            return back()->with('success',
                "BOC du {$dateString} enregistrée ✅ (mais extraction variations a échoué — voir logs)"
            );
        }
    }
}
