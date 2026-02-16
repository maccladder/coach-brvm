<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $stats = MarketplaceProduct::query()
            ->where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pendings,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        $latest = MarketplaceProduct::query()
            ->with('category')
            ->where('user_id', $userId)
            ->latest()
            ->take(8)
            ->get();

        return view('vendor.dashboard', compact('stats', 'latest'));
    }
}
