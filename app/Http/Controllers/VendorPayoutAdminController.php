<?php

namespace App\Http\Controllers;

use App\Models\VendorPayoutRequest;
use App\Notifications\VendorPayoutStatusNotification;
use Illuminate\Http\Request;

class VendorPayoutAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorPayoutRequest::query()
            ->with('vendor')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate(20)->withQueryString();

        return view('admin.payouts.index', compact('payouts'));
    }

    public function approve(Request $request, VendorPayoutRequest $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('warning', 'Statut invalide.');
        }

        $payout->status      = 'approved';
        $payout->approved_at = now();
        $payout->approved_by = $request->user()->id;
        $payout->save();

        // ✅ Notif vendeur : approuvé
        if ($payout->vendor) {
            $payout->vendor->notify(new VendorPayoutStatusNotification(
                payoutId: (int) $payout->id,
                amount: (int) $payout->amount,
                status: 'approved',
                message: "Ta demande de reversement de " . number_format((int)$payout->amount, 0, ',', ' ') . " FCFA a été approuvée.",
                url: route('vendor.earnings')
            ));
        }

        return back()->with('success', '✅ Demande approuvée.');
    }

    public function reject(Request $request, VendorPayoutRequest $payout)
    {
        if (!in_array($payout->status, ['pending', 'approved'], true)) {
            return back()->with('warning', 'Statut invalide.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $payout->status     = 'rejected';
        $payout->admin_note = $data['admin_note'];
        $payout->save();

        // ✅ Notif vendeur : rejeté (+ raison)
        if ($payout->vendor) {
            $payout->vendor->notify(new VendorPayoutStatusNotification(
                payoutId: (int) $payout->id,
                amount: (int) $payout->amount,
                status: 'rejected',
                message: "Ta demande de reversement de " . number_format((int)$payout->amount, 0, ',', ' ') . " FCFA a été rejetée. Motif : " . $payout->admin_note,
                url: route('vendor.earnings')
            ));
        }

        return back()->with('success', '⛔ Demande rejetée.');
    }

    public function markPaid(Request $request, VendorPayoutRequest $payout)
    {
        if ($payout->status !== 'approved') {
            return back()->with('warning', 'Tu dois approuver avant de marquer payé.');
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payout->status    = 'paid';
        $payout->paid_at   = now();
        $payout->paid_by   = $request->user()->id;
        $payout->reference = $data['reference'] ?? null;
        $payout->save();

        // ✅ Notif vendeur : payé (+ ref si existe)
        if ($payout->vendor) {
            $msg = "Ton reversement de " . number_format((int)$payout->amount, 0, ',', ' ') . " FCFA a été effectué avec succès.";
            if (!empty($payout->reference)) {
                $msg .= " Référence : {$payout->reference}";
            }

            $payout->vendor->notify(new VendorPayoutStatusNotification(
                payoutId: (int) $payout->id,
                amount: (int) $payout->amount,
                status: 'paid',
                message: $msg,
                url: route('vendor.earnings')
            ));
        }

        return back()->with('success', '💸 Reversement marqué comme payé.');
    }
}
