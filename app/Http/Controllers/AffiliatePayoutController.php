<?php

namespace App\Http\Controllers;

use App\Mail\AffiliatePayoutRequestAdminMail;
use App\Mail\AffiliatePayoutRequestedConfirmMail;
use App\Models\AdminNotification;
use App\Models\Affiliate;
use App\Models\AffiliatePayoutRequest;
use App\Services\Affiliate\AffiliateEarningsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliatePayoutController extends Controller
{
    public function request(Request $request, AffiliateEarningsService $earningsService)
    {
        $user      = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate || $affiliate->status !== 'active') {
            return back()->with('error', '⛔ Compte apporteur non actif.');
        }

        // ── Étape 1 : validation syntaxique (avant le verrou) ────────────────
        $allowedMethods = config('affiliate.payout_methods', ['wave']);

        $request->validate([
            'amount'        => ['required', 'integer', 'min:1'],
            'payout_method' => ['required', 'string', 'in:' . implode(',', $allowedMethods)],
            'payout_phone'  => ['required', 'string', 'max:20', 'regex:/^(\+?225)?\d{8,10}$/'],
        ]);

        $amount = (int) $request->input('amount');
        $method = (string) $request->input('payout_method');
        $phone  = preg_replace('/\s+/', '', (string) $request->input('payout_phone'));

        // ── Étape 2 : lockForUpdate + re-vérification à l'intérieur du verrou ─
        // Neutralise la race condition : deux requêtes simultanées ne peuvent
        // pas toutes les deux passer les checks ci-dessous.
        $errorMessage = null;

        DB::transaction(function () use (
            $affiliate, $earningsService, $amount, $method, $phone, $user, &$errorMessage
        ) {
            $affiliateLocked = Affiliate::where('id', $affiliate->id)
                ->lockForUpdate()
                ->first();

            // Re-calculer le solde à l'intérieur du verrou
            $totals    = $earningsService->totalsForAffiliate($affiliateLocked->id);
            $available = (int) ($totals['available'] ?? 0);
            $minPayout = (int) config('affiliate.min_payout', 10000);

            if ($available < $minPayout) {
                $errorMessage = '❌ Reversement indisponible (minimum : ' . number_format($minPayout, 0, ',', ' ') . ' FCFA). Solde disponible : ' . number_format($available, 0, ',', ' ') . ' FCFA.';
                return;
            }

            if ($amount < $minPayout) {
                $errorMessage = '❌ Montant minimum : ' . number_format($minPayout, 0, ',', ' ') . ' FCFA.';
                return;
            }

            if ($amount > $available) {
                $errorMessage = '❌ Montant demandé (' . number_format($amount, 0, ',', ' ') . ' FCFA) supérieur au solde disponible (' . number_format($available, 0, ',', ' ') . ' FCFA).';
                return;
            }

            // Pas de double demande — vérifié sous verrou
            $hasPending = AffiliatePayoutRequest::where('affiliate_id', $affiliateLocked->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if ($hasPending) {
                $errorMessage = '⏳ Tu as déjà une demande de reversement en cours. Patiente la validation.';
                return;
            }

            // Créer la demande — entre immédiatement dans le bucket `reserved`
            // → `available` baisse immédiatement pour tous les calculs suivants
            AffiliatePayoutRequest::create([
                'affiliate_id'   => $affiliateLocked->id,
                'amount'         => $amount,
                'status'         => 'pending',
                'payout_method'  => $method,
                'payout_account' => $phone,
                'requested_at'   => now(),
            ]);

            AdminNotification::create([
                'type'    => 'affiliate_payout_requested',
                'title'   => '💸 Reversement apporteur',
                'message' => ($user->name ?? 'Un apporteur')
                    . ' — ' . number_format($amount, 0, ',', ' ') . ' FCFA'
                    . ' — ' . strtoupper($method)
                    . ' (' . $phone . ')',
                'url'     => route('admin.affiliate-payouts.index'),
                'read_at' => null,
            ]);
        });

        if ($errorMessage) {
            return back()->with('error', $errorMessage);
        }

        // ── Emails post-création — non bloquants ──────────────────────────────
        $createdPayout = AffiliatePayoutRequest::where('affiliate_id', $affiliate->id)
            ->where('status', 'pending')
            ->latest()
            ->with('affiliate.user')
            ->first();

        if ($createdPayout) {
            $adminEmails = config('affiliate.admin_emails', []);

            // Email admin
            try {
                if ($adminEmails) {
                    Mail::to($adminEmails)->send(new AffiliatePayoutRequestAdminMail($createdPayout));
                }
            } catch (\Throwable $e) {
                Log::error('AffiliatePayoutController: email admin échec', [
                    'affiliate_id' => $affiliate->id, 'error' => $e->getMessage(),
                ]);
            }

            // Email confirmation apporteur
            try {
                $userEmail = $affiliate->user->email ?? null;
                if ($userEmail) {
                    Mail::to($userEmail)->send(new AffiliatePayoutRequestedConfirmMail($createdPayout));
                }
            } catch (\Throwable $e) {
                Log::error('AffiliatePayoutController: email apporteur échec', [
                    'affiliate_id' => $affiliate->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', '✅ Demande de reversement envoyée. En attente de validation.');
    }
}
