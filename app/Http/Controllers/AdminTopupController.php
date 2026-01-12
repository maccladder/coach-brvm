<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;

class AdminTopupController extends Controller
{
    public function index(Request $request)
    {
        /*
         * IMPORTANT (selon ton Payment.php):
         * - Champs: user_id, transaction_id, amount_paid, amount_virtual, purpose, status, credited_at, meta
         * - Donc on filtre par purpose (ex: "topup")
         */

        // Tu peux passer ?purpose=topup ou ?purpose=wallet_topup
        $purpose = $request->get('purpose', 'wallet_topup');


        $q = Payment::query()
            ->with('user')
            ->where('purpose', $purpose);

        // ✅ Filtres
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('user')) {
            $needle = trim($request->user);
            $q->whereHas('user', function ($u) use ($needle) {
                $u->where('name', 'like', "%{$needle}%")
                  ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->to);
        }

        // ✅ Stats globales (sur la requête filtrée)
        $base = (clone $q);

        $totalCount     = (clone $base)->count();
        $acceptedCount  = (clone $base)->where('status', 'ACCEPTED')->count();
        $pendingCount   = (clone $base)->whereIn('status', ['PENDING', 'INITIATED'])->count();
        $failedCount    = (clone $base)->whereIn('status', ['REFUSED', 'CANCELLED', 'FAILED'])->count();

        // Montant encaissé (validé) - argent réel
        $totalAcceptedAmountPaid = (clone $base)
            ->where('status', 'ACCEPTED')
            ->sum('amount_paid');

        // Montant virtuel crédité (validé) - si tu utilises amount_virtual
        $totalAcceptedAmountVirtual = (clone $base)
            ->where('status', 'ACCEPTED')
            ->sum('amount_virtual');

        // Users uniques ayant fait topup (validé)
        $uniqueBuyers = (clone $base)
            ->where('status', 'ACCEPTED')
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        // ✅ Listing
        $topups = $q->latest()->paginate(25)->withQueryString();

        $stats = [
            'count_total'       => $totalCount,
            'count_accepted'    => $acceptedCount,
            'count_pending'     => $pendingCount,
            'count_failed'      => $failedCount,
            'amount_accepted'   => $totalAcceptedAmountPaid,     // pour affichage principal (FCFA encaissés)
            'virtual_accepted'  => $totalAcceptedAmountVirtual,  // optionnel (crédit virtuel)
            'unique_buyers'     => $uniqueBuyers,
            'purpose'           => $purpose,
        ];

        return view('admin.topups.index', compact('topups', 'stats'));
    }
}
