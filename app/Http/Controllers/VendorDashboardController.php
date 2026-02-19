<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        // ✅ Stats produits marketplace
        $stats = MarketplaceProduct::query()
            ->where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pendings,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        // ✅ Derniers produits
        $latest = MarketplaceProduct::query()
            ->with('category')
            ->where('user_id', $userId)
            ->latest()
            ->take(8)
            ->get();

        // ✅ NOUVEAU : Dernières études / documents du vendeur
        $latestDocuments = Document::query()
            ->where('vendor_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        return view('vendor.dashboard', compact('stats', 'latest', 'latestDocuments'));
    }
}
