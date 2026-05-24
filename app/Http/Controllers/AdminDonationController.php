<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;

class AdminDonationController extends Controller
{
    public function index(Request $request)
    {
        $q = Donation::query();

        if ($request->filled('status')) {
            $q->where('status', strtoupper($request->status));
        }

        if ($request->filled('search')) {
            $needle = trim($request->search);
            $q->where(function ($sub) use ($needle) {
                $sub->where('donor_name', 'like', "%{$needle}%")
                    ->orWhere('donor_email', 'like', "%{$needle}%")
                    ->orWhere('reference', 'like', "%{$needle}%");
            });
        }

        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->to);
        }

        $base = clone $q;

        $stats = [
            'total'           => (clone $base)->count(),
            'accepted'        => (clone $base)->where('status', 'ACCEPTED')->count(),
            'pending'         => (clone $base)->where('status', 'PENDING')->count(),
            'failed'          => (clone $base)->where('status', 'FAILED')->count(),
            'amount_total'    => (clone $base)->where('status', 'ACCEPTED')->sum('amount'),
            'donors_uniq'     => (clone $base)->where('status', 'ACCEPTED')
                                              ->distinct('donor_email')
                                              ->count('donor_email'),
        ];

        $donations = $q->latest()->paginate(30)->withQueryString();

        return view('admin.donations.index', compact('donations', 'stats'));
    }
}
