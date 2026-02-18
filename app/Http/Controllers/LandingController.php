<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClientBoc;
use App\Models\DailyBoc;
use Carbon\Carbon;

class LandingController extends Controller
{
    public function index()
    {
        // 🔔 Annonces
        $annonces = Announcement::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        // 📅 Dernière date BOC dispo = J-1 (ou avant)
        $lastDaily = DailyBoc::whereDate('date_boc', '<=', Carbon::yesterday())
            ->orderByDesc('date_boc')
            ->first();

        // 📄 Dernière BOC publique liée (ou fallback)
        $latestPublicBoc = ClientBoc::query()
            ->where('is_public', 1)
            ->whereDate('boc_date', '<=', Carbon::yesterday())
            ->when($lastDaily, function ($q) use ($lastDaily) {
                $q->where(function ($qq) use ($lastDaily) {
                    $qq->where('daily_boc_id', $lastDaily->id)
                       ->orWhereNull('daily_boc_id'); // fallback si pas lié
                });
            })
            ->orderByDesc('boc_date')
            ->first();

        return view('welcome', [
            'annonces'        => $annonces,
            'latestPublicBoc' => $latestPublicBoc,
            'exampleVideoUrl' => null,
        ]);
    }
}
