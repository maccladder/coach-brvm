<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\VendorPayoutRequest;
use App\Services\Marketplace\VendorEarningsService;
use Illuminate\Http\Request;

class VendorEarningsController extends Controller
{
    public function index(Request $request, VendorEarningsService $svc)
    {
        $vendorId = $request->user()->id;

        $totals = $svc->totalsForVendor($vendorId);

        $payouts = VendorPayoutRequest::query()
            ->where('vendor_id', $vendorId)
            ->latest()
            ->paginate(10);

        return view('vendor.earnings.index', compact('totals', 'payouts'));
    }

    public function requestPayout(Request $request, VendorEarningsService $svc)
    {
        $user = $request->user();
        $vendorId = $user->id;

        $data = $request->validate([
            'amount'         => ['required', 'integer', 'min:' . VendorEarningsService::MIN_PAYOUT],
            'payout_method'  => ['required', 'string', 'max:30'],
            'payout_account' => ['required', 'string', 'max:80'],
        ]);

        $totals = $svc->totalsForVendor($vendorId);

        if ((int) $data['amount'] > (int) ($totals['available'] ?? 0)) {
            return back()->with('error', "❌ Montant demandé supérieur au disponible.");
        }

        $payout = VendorPayoutRequest::create([
            'vendor_id'      => $vendorId,
            'amount'         => (int) $data['amount'],
            'status'         => 'pending',
            'payout_method'  => $data['payout_method'],
            'payout_account' => $data['payout_account'],
            'requested_at'   => now(),
        ]);

        // ✅ OPTION A : notif ADMIN via table AdminNotification
        if (class_exists(AdminNotification::class)) {
            AdminNotification::create([
                'type'    => 'vendor_payout_requested',
                'title'   => '💸 Demande de reversement',
                'message' => ($user->name ?? 'Un vendeur') . ' — '
                    . number_format((int) $payout->amount, 0, ',', ' ') . ' FCFA'
                    . ' — ' . ($payout->payout_method ?? 'méthode') . ' (' . ($payout->payout_account ?? '') . ')',
                'url'     => route('admin.payouts.index'),
                'read_at' => null,
            ]);
        }

        return back()->with('success', '✅ Demande de reversement envoyée. En attente de validation.');
    }
}
