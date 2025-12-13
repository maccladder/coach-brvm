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

        // Auth admin simple via session
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

    // pour les jours férié

        /**
     * Jours fériés BRVM / Côte d'Ivoire
     * Format : YYYY-MM-DD
     */
    private function getBrvmHolidays(): array
    {
        return [
            // 🔹 2025 (à partir du 1er décembre)
            '2025-12-25', // Noël

            // 🔹 2026 (source : publicholidays.africa & calendriers CI) :contentReference[oaicite:0]{index=0}
            '2026-01-01', // Jour de l'An
            '2026-03-17', // Lendemain de la Nuit du Destin (Laylat al-Qadr)
            '2026-03-20', // Aïd el-Fitr (Korité)
            '2026-04-06', // Lundi de Pâques
            '2026-05-01', // Fête du Travail
            '2026-05-14', // Ascension
            '2026-05-25', // Lundi de Pentecôte
            '2026-05-27', // Tabaski (Aïd el-Adha)
            '2026-08-07', // Fête de l’Indépendance
            '2026-08-15', // Assomption
            '2026-08-26', // Lendemain de la naissance du Prophète (Maouloud)
            '2026-11-01', // Toussaint
            '2026-11-15', // Journée Nationale de la Paix
            '2026-12-25', // Noël
        ];
    }


public function dailyBocsIndex()
{
    $startDate = Carbon::create(2025, 12, 1)->startOfDay();
    $today     = Carbon::today();

    // Jours fériés BRVM
    $holidays = $this->getBrvmHolidays();

    // Récupérer les BOC déjà enregistrées
    $bocs = DailyBoc::whereBetween('date_boc', [$startDate, $today])
        ->get()
        ->keyBy(function ($boc) {
            return Carbon::parse($boc->date_boc)->toDateString();
        });

    $days    = [];
    $current = $startDate->copy();

    while ($current->lte($today)) {

        $key = $current->toDateString();

        // 1️⃣ Sauter les samedis / dimanches
        if ($current->isWeekend()) {
            $current->addDay();
            continue;
        }

        // 2️⃣ Sauter les jours fériés BRVM
        if (in_array($key, $holidays, true)) {
            $current->addDay();
            continue;
        }

        $record  = $bocs->get($key);
        $isToday = $current->isSameDay($today);

        $days[] = [
            'date'       => $current->copy(),
            'record'     => $record,
            'has_boc'    => (bool) $record,
            'is_today'   => $isToday,
            'is_missing' => !$record && !$isToday,
        ];

        $current->addDay();
    }

    // 🔢 Petites stats pour le résumé
    $daysCollection = collect($days);

    $stats = [
        'total_days' => $daysCollection->count(),
        'received'   => $daysCollection->where('has_boc', true)->count(),
        'missing'    => $daysCollection->where('is_missing', true)->count(),
    ];

    return view('admin.daily_bocs', compact('days', 'today', 'stats'));
}





    /** 👉 Traitement de l’upload d’une BOC */
  public function dailyBocsStore(Request $request, BrvmBubbleService $bubble)
{
    $request->validate([
        'date_boc' => ['required', 'date', 'after_or_equal:2025-12-01', 'before_or_equal:today'],
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

    // 1) On enregistre toujours la BOC
    $dailyBoc = DailyBoc::create([
        'date_boc'      => $dateString,
        'file_path'     => $path,
        'original_name' => $request->file('file')->getClientOriginalName(),
    ]);

    // 2) Extraction + insert (si ça échoue, on ne bloque pas)
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
        Log::error("Extraction variations échouée (DailyBoc {$dailyBoc->id}) : ".$e->getMessage());

        return back()->with('success',
            "BOC du {$dateString} enregistrée ✅ (mais extraction variations a échoué — voir logs)"
        );
    }
}


}
