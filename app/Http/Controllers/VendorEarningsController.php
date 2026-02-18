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
    $user     = $request->user();
    $vendorId = $user->id;

    // 1) Toujours calculer AVANT la validation pour avoir les vrais seuils
    $totals    = $svc->totalsForVendor($vendorId);
    $available = (int) ($totals['available'] ?? 0);
    $minPayout = (int) ($totals['min_payout'] ?? VendorEarningsService::MIN_PAYOUT);

    // 2) Si pas éligible, on refuse (même si bouton “forcé”)
    if ($available < $minPayout) {
        return back()->with('error', "❌ Reversement indisponible (minimum: {$minPayout} FCFA).");
    }

    // 3) Empêcher double demande en cours
    $hasPending = VendorPayoutRequest::query()
        ->where('vendor_id', $vendorId)
        ->whereIn('status', ['pending', 'approved'])
        ->exists();

    if ($hasPending) {
        return back()->with('warning', '⏳ Tu as déjà une demande en cours. Patiente la validation.');
    }

    // 4) Validation (Wave only + téléphone)
    $data = $request->validate([
        'amount'        => ['required', 'integer', 'min:' . $minPayout, 'max:' . $available],
        'payout_method' => ['required', 'in:wave'],
        // accepte: 07xxxxxxxx ou +22507xxxxxxxx ou 22507xxxxxxxx (ajuste si besoin)
        'payout_phone'  => ['required', 'string', 'max:20', 'regex:/^(\+?225)?\d{8,10}$/'],
    ]);

    // (Optionnel) normaliser en stockant sans espaces
    $phone = preg_replace('/\s+/', '', (string) $data['payout_phone']);

    $payout = VendorPayoutRequest::create([
        'vendor_id'      => $vendorId,
        'amount'         => (int) $data['amount'],
        'status'         => 'pending',
        'payout_method'  => 'wave',
        'payout_account' => $phone, // on réutilise payout_account pour stocker le tel
        'requested_at'   => now(),
    ]);

    if (class_exists(AdminNotification::class)) {
        AdminNotification::create([
            'type'    => 'vendor_payout_requested',
            'title'   => '💸 Demande de reversement',
            'message' => ($user->name ?? 'Un vendeur') . ' — '
                . number_format((int) $payout->amount, 0, ',', ' ') . ' FCFA'
                . ' — WAVE (' . ($payout->payout_account ?? '') . ')',
            'url'     => route('admin.payouts.index'),
            'read_at' => null,
        ]);
    }

    return back()->with('success', '✅ Demande de reversement envoyée. En attente de validation.');
}
}
