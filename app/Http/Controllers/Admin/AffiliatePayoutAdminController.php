<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePayoutRequest;
use App\Notifications\AffiliatePayoutStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AffiliatePayoutAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = AffiliatePayoutRequest::query()
            ->with('affiliate.user')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate(20)->withQueryString();

        return view('admin.affiliate-payouts.index', compact('payouts'));
    }

    public function approve(Request $request, AffiliatePayoutRequest $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('warning', 'Statut invalide pour approbation.');
        }

        $payout->status      = 'approved';
        $payout->approved_at = now();
        $payout->approved_by = $request->user()->id;
        $payout->save();

        $this->notifyAffiliate($payout, 'approved',
            'Ta demande de reversement de ' . number_format((int) $payout->amount, 0, ',', ' ') . ' FCFA a été approuvée. Paiement en cours de traitement.'
        );

        return back()->with('success', '✅ Demande approuvée.');
    }

    public function reject(Request $request, AffiliatePayoutRequest $payout)
    {
        if (!in_array($payout->status, ['pending', 'approved'], true)) {
            return back()->with('warning', 'Statut invalide pour rejet.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $payout->status     = 'rejected';
        $payout->admin_note = $data['admin_note'];
        $payout->save();

        $this->notifyAffiliate($payout, 'rejected',
            'Ta demande de reversement de ' . number_format((int) $payout->amount, 0, ',', ' ') . ' FCFA a été rejetée. Motif : ' . $payout->admin_note
        );

        return back()->with('success', '⛔ Demande rejetée.');
    }

    public function markPaid(Request $request, AffiliatePayoutRequest $payout)
    {
        if ($payout->status !== 'approved') {
            return back()->with('warning', 'Approuve la demande avant de la marquer payée.');
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
            'receipt'   => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'], // 5 Mo
        ]);

        $meta = (array) ($payout->meta ?? []);

        // Upload du reçu si fourni
        if ($request->hasFile('receipt') && $request->file('receipt')->isValid()) {
            $path = $request->file('receipt')->store(
                'affiliate-receipts/' . $payout->id,
                'local'
            );
            $meta['receipt_path'] = $path;
        }

        $payout->status    = 'paid';
        $payout->paid_at   = now();
        $payout->paid_by   = $request->user()->id;
        $payout->reference = $data['reference'] ?? null;
        $payout->meta      = $meta;
        $payout->save();

        $msg = 'Ton reversement de ' . number_format((int) $payout->amount, 0, ',', ' ') . ' FCFA a été effectué.';
        if (!empty($payout->reference)) {
            $msg .= ' Référence : ' . $payout->reference;
        }

        $this->notifyAffiliate($payout, 'paid', $msg);

        return back()->with('success', '💸 Reversement marqué comme payé.');
    }

    public function cancelCommission(Request $request, \App\Models\AffiliateCommission $commission)
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        app(\App\Services\Affiliate\AffiliateCommissionService::class)
            ->cancelCommission($commission->id, $data['cancel_reason']);

        return back()->with('success', '🚫 Commission annulée.');
    }

    // ─── Téléchargement du reçu (fichier privé, hors public/) ────────────────

    public function downloadReceipt(AffiliatePayoutRequest $payout)
    {
        $path = data_get($payout->meta, 'receipt_path');

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Aucun reçu disponible pour ce reversement.');
        }

        return Storage::disk('local')->download($path);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function notifyAffiliate(AffiliatePayoutRequest $payout, string $status, string $message): void
    {
        $user = $payout->affiliate?->user;
        if (!$user) {
            return;
        }

        $user->notify(new AffiliatePayoutStatusNotification(
            payoutId: (int) $payout->id,
            amount:   (int) $payout->amount,
            status:   $status,
            message:  $message,
            url:      route('affiliate.dashboard')
        ));
    }
}
