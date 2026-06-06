<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePayoutRequest;

class AffiliateEarningsService
{
    public const MIN_PAYOUT = 10000; // FCFA (valeur de secours si config absente)

    /**
     * Calcule le solde complet d'un apporteur depuis le ledger.
     *
     * Formule :
     *   en_attente  = SUM(commission_amount) WHERE status = 'pending'
     *   validated   = SUM(commission_amount) WHERE status = 'validated'
     *   reserved    = SUM(amount)            WHERE status IN ('pending','approved')  [payouts en cours]
     *   paid_out    = SUM(amount)            WHERE status = 'paid'                   [payouts payés]
     *   available   = max(0, validated - reserved - paid_out)
     *
     * Les commissions ne passent jamais à 'paid' — seuls les affiliate_payout_requests
     * trackent les sorties de caisse (ledger pur, pas de colonne balance mutable).
     */
    public function totalsForAffiliate(int $affiliateId): array
    {
        // ── Commissions ───────────────────────────────────────────────────────

        $pending   = (int) AffiliateCommission::where('affiliate_id', $affiliateId)
            ->where('status', 'pending')
            ->sum('commission_amount');

        $validated = (int) AffiliateCommission::where('affiliate_id', $affiliateId)
            ->where('status', 'validated')
            ->sum('commission_amount');

        $cancelled = (int) AffiliateCommission::where('affiliate_id', $affiliateId)
            ->where('status', 'cancelled')
            ->sum('commission_amount');

        // ── Payouts ───────────────────────────────────────────────────────────

        // Montant verrouillé par des demandes en cours (pending/approved)
        $reserved = (int) AffiliatePayoutRequest::where('affiliate_id', $affiliateId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        // Total déjà versé
        $paidOut = (int) AffiliatePayoutRequest::where('affiliate_id', $affiliateId)
            ->where('status', 'paid')
            ->sum('amount');

        // ── Solde disponible ─────────────────────────────────────────────────

        $available = max(0, $validated - $reserved - $paidOut);

        $minPayout = (int) config('affiliate.min_payout', self::MIN_PAYOUT);

        return [
            'pending'    => $pending,    // en attente de validation (non retirable)
            'validated'  => $validated,  // total commissions validées brut
            'cancelled'  => $cancelled,  // annulées (pour information)
            'reserved'   => $reserved,   // verrouillé par payouts en cours
            'paid_out'   => $paidOut,    // total reversements déjà payés
            'available'  => $available,  // retirable maintenant
            'min_payout' => $minPayout,
        ];
    }
}
