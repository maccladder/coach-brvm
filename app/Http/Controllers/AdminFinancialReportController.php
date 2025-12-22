<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use App\Models\Societe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFinancialReportController extends Controller
{
    public function index(int $year)
{
    $societes = Societe::query()
        ->where('is_listed', true)
        ->orderBy('name')
        ->withCount([
            'financialReports as published_count' => function ($q) use ($year) {
                $q->where('year', $year)->where('status', 'published');
            },
            'financialReports as not_published_count' => function ($q) use ($year) {
                $q->where('year', $year)->where('status', 'not_published');
            },
        ])
        ->get();

    return view('admin.financial_reports.index', compact('year', 'societes'));
}


    public function showSociete(int $year, Societe $societe)
{
    $periods = FinancialReport::PERIODS;

    $reports = FinancialReport::query()
        ->where('societe_id', $societe->id)
        ->where('year', $year)
        ->get()
        ->keyBy('period'); // ['Q1'=>..., 'S1'=>...]

    return view('admin.financial_reports.societe', compact('year', 'societe', 'periods', 'reports'));
}


    public function upload(Request $request, int $year, Societe $societe, string $period)
    {
        $this->validatePeriod($period);

        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20MB
            'published_at' => ['nullable', 'date'],
        ]);

        $report = FinancialReport::firstOrCreate(
            ['societe_id' => $societe->id, 'year' => $year, 'period' => $period],
            ['status' => 'pending']
        );

        // Supprimer l’ancien fichier
        if ($report->file_path && Storage::disk('public')->exists($report->file_path)) {
            Storage::disk('public')->delete($report->file_path);
        }

        $safeCode = preg_replace('/[^A-Z0-9_]/', '_', strtoupper($societe->code));
        $dir = "financials/{$year}/{$safeCode}";
        $filename = "{$period}.pdf";

        $path = $request->file('pdf')->storeAs($dir, $filename, 'public');

        $report->update([
            'status' => 'published',
            'file_path' => $path,
            'published_at' => $request->input('published_at'),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', "Upload OK : {$societe->name} / {$period} / {$year}");
    }

    public function markNotPublished(int $year, Societe $societe, string $period)
    {
        $this->validatePeriod($period);

        $report = FinancialReport::firstOrCreate(
            ['societe_id' => $societe->id, 'year' => $year, 'period' => $period],
            ['status' => 'pending']
        );

        if ($report->file_path && Storage::disk('public')->exists($report->file_path)) {
            Storage::disk('public')->delete($report->file_path);
        }

        $report->update([
            'status' => 'not_published',
            'file_path' => null,
            'published_at' => null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', "Marqué NON PUBLIÉ : {$societe->name} / {$period} / {$year}");
    }

    private function validatePeriod(string $period): void
    {
        if (!in_array($period, FinancialReport::PERIODS, true)) {
            abort(404, 'Période invalide');
        }
    }
}
