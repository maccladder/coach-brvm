<?php

namespace App\Http\Controllers;

use App\Models\StagiaireLog;
use Illuminate\Http\Request;

class StagiaireLogController extends Controller
{
    public function index(Request $request)
    {
        $query = StagiaireLog::query()->latest();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        $logs  = $query->paginate(50)->withQueryString();
        $total = StagiaireLog::count();

        $todayCount = StagiaireLog::whereDate('created_at', today())->count();

        return view('admin.stagiaire.logs', compact('logs', 'total', 'todayCount'));
    }

    public function clear()
    {
        StagiaireLog::truncate();
        return back()->with('success', 'Logs effacés.');
    }
}
