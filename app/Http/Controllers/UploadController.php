<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\FinancialStatement;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function index()
    {
        return view('uploads.index', [
            'analyses'   => Analysis::latest('as_of_date')->limit(10)->get(),
            // 🔁 trier par la date métier (et pas created_at)
            'statements' => FinancialStatement::latest('published_at')->limit(10)->get(),
        ]);
    }

    public function storeAnalysis(Request $r)
    {
        $data = $r->validate([
            'as_of_date' => ['required','date'],
            'title'      => ['required','string','max:255'],
            'notes'      => ['nullable','string'],
            'file'       => ['nullable','file','max:20480'],
        ]);

        $path = $r->file('file')?->store('analyses','public');

        Analysis::create([
            'as_of_date' => $data['as_of_date'],
            'title'      => $data['title'],
            'notes'      => $data['notes'] ?? null,
            'file_path'  => $path,
            'tags'       => [],
        ]);

        return back()->with('ok','Analyse enregistrée.');
    }

    public function storeStatement(Request $r)
    {
        $data = $r->validate([
            'issuer'         => ['required','string','max:255'],
            'period'         => ['required','string','max:50'],
            'statement_type' => ['required','in:income,balance,cashflow'],
            // ✅ la date métier est REQUISE
            'published_at'   => ['required','date'],
            'file'           => ['required','file','max:30720'],
        ]);

        $path = $r->file('file')->store('statements','public');

        FinancialStatement::create([
            'issuer'         => $data['issuer'],
            'period'         => $data['period'],
            'statement_type' => $data['statement_type'],
            'file_path'      => $path,
            // ✅ on enregistre exactement la date saisie
            'published_at'   => $data['published_at'],
        ]);

        return back()->with('ok','État financier importé.');
    }
}
