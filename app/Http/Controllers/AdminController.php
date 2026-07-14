<?php

namespace App\Http\Controllers;

use App\Models\BocStock;
use App\Models\ClientBoc;
use App\Models\ClientFinancial;
use App\Models\DailyBoc;
use App\Models\News;
use App\Services\BrvmBubbleService;
use App\Services\BrvmCalendarService;
use App\Services\DailyBocIngestionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /** Page de login admin */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * ✅ Remplacer une BOC + recalculer BocStock + republier la version publique
     */
    public function dailyBocsReplace(
        Request $request,
        DailyBoc $dailyBoc,
        BrvmBubbleService $bubble,
        DailyBocIngestionService $ingestion
    ) {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $dateString = Carbon::parse($dailyBoc->date_boc)->toDateString();

        DB::transaction(function () use ($request, $dailyBoc, $bubble) {

            // 1) Sauvegarder l'ancien chemin
            $oldPath = $dailyBoc->file_path;

            // 2) Stocker le nouveau PDF
            $newPath = $request->file('file')->store('bocs', 'public');

            // 3) Mettre à jour le DailyBoc
            $dailyBoc->previous_file_path = $oldPath;
            $dailyBoc->file_path = $newPath;
            $dailyBoc->original_name = $request->file('file')->getClientOriginalName();
            $dailyBoc->replaced_at = now();
            $dailyBoc->replaced_by = auth()->id(); // si admin est connecté via auth
            $dailyBoc->save();

            // 4) Re-extraction + rewrite BocStock
            $stocks = $bubble->extractFromBoc($dailyBoc->file_path);

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

            // OPTION: supprimer l'ancien fichier
            // if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            //     Storage::disk('public')->delete($oldPath);
            // }
        });

        // ✅ IMPORTANT : on republie APRES la transaction (quand tout est OK)
        $ingestion->publish($dailyBoc);

        return back()->with('success', "✅ BOC du {$dateString} remplacée + données recalculées + publiée (public).");
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
        $pendingNewsCount = News::where('is_published', false)->count();

        return view('admin.dashboard', compact('bocs', 'financials', 'pendingNewsCount'));
    }

    public function dailyBocsIndex(Request $request, BrvmCalendarService $calendar)
    {
        $startDate = $calendar->earliestBocDate();
        $today     = Carbon::today();

        $bocs = DailyBoc::whereBetween('date_boc', [$startDate, $today])
            ->get()
            ->keyBy(fn ($boc) => Carbon::parse($boc->date_boc)->toDateString());

        $daysAll = [];
        $current = $startDate->copy();

        while ($current->lte($today)) {
            $key = $current->toDateString();

            if (!$calendar->isTradingDay($current)) {
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

        $daysCollection = collect($daysAll)
            ->sortByDesc(fn ($d) => $d['date']->timestamp)
            ->values();

        $stats = [
            'total_days' => $daysCollection->count(),
            'received'   => $daysCollection->where('has_boc', true)->count(),
            'missing'    => $daysCollection->where('is_missing', true)->count(),
        ];

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

    /** ✅ Upload d'une BOC + extraction + publication public */
    public function dailyBocsStore(
        Request $request,
        BrvmCalendarService $calendar,
        DailyBocIngestionService $ingestion
    ) {
        $request->validate([
            'date_boc' => ['required', 'date', 'after_or_equal:2025-01-01', 'before_or_equal:today'],
            'file'     => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $date       = Carbon::parse($request->input('date_boc'));
        $dateString = $date->toDateString();

        if ($date->isWeekend()) {
            return back()->with('error', "Il n'y a pas de BOC les samedis et dimanches.");
        }

        if ($calendar->isHoliday($date)) {
            return back()->with('error', "Il n'y a pas de BOC les jours fériés officiels (BRVM / Côte d'Ivoire).");
        }

        if (DailyBoc::whereDate('date_boc', $dateString)->exists()) {
            return back()->with('error', "Une BOC existe déjà pour la date {$dateString}.");
        }

        $result = $ingestion->ingest($dateString, $request->file('file'));

        if ($result['extracted']) {
            return back()->with('success', "BOC du {$dateString} enregistrée + variations extraites + publiée (public) ✅");
        }

        return back()->with('success',
            "BOC du {$dateString} enregistrée ✅ (extraction variations a échoué — voir logs) + publiée (public)."
        );
    }
}
