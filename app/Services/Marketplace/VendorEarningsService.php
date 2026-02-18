<?php

namespace App\Services\Marketplace;

use App\Models\VendorPayoutRequest;
use Illuminate\Support\Facades\DB;

class VendorEarningsService
{
    public const COMMISSION_RATE = 0.15;      // 15%
    public const SECURITY_DELAY_HOURS = 72;   // 72h
    public const MIN_PAYOUT = 10000;          // 10 000 FCFA

    public function totalsForVendor(int $vendorId): array
    {
        $delayCutoff = now()->subHours(self::SECURITY_DELAY_HOURS);

        // 1) Total brut payé (peu importe 72h)
        $grossTotal = (int) DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->sum('mp.amount');

        // 2) Total brut éligible (>=72h)
        $grossEligible = (int) DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->whereNotNull('mp.paid_at')
            ->where('mp.paid_at', '<=', $delayCutoff)
            ->sum('mp.amount');

        // Commission
        $feeTotal     = (int) round($grossTotal * self::COMMISSION_RATE);
        $feeEligible  = (int) round($grossEligible * self::COMMISSION_RATE);

        $netTotal     = max(0, $grossTotal - $feeTotal);
        $netEligible  = max(0, $grossEligible - $feeEligible);

        // 3) Locked (moins de 72h)
        $lockedGross72h = max(0, $grossTotal - $grossEligible);
        $lockedFee72h   = (int) round($lockedGross72h * self::COMMISSION_RATE);
        $lockedNet72h   = max(0, $lockedGross72h - $lockedFee72h);

        // 4) Reversements (réservé + payé)
        $reserved = (int) VendorPayoutRequest::query()
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $paidOut = (int) VendorPayoutRequest::query()
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->sum('amount');

        // Disponible = netEligible - réservé - payé
        $available = max(0, $netEligible - $reserved - $paidOut);

        // Optionnel: prochaine date de déblocage (la vente la plus proche des 72h)
        $nextUnlockPaidAt = DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->whereNotNull('mp.paid_at')
            ->where('mp.paid_at', '>', $delayCutoff)
            ->min('mp.paid_at');

        $nextUnlockAt = $nextUnlockPaidAt ? \Carbon\Carbon::parse($nextUnlockPaidAt)->addHours(self::SECURITY_DELAY_HOURS) : null;

        return [
            'gross_total'      => $grossTotal,
            'fee_total'        => $feeTotal,
            'net_total'        => $netTotal,

            'gross_eligible'   => $grossEligible,
            'fee_eligible'     => $feeEligible,
            'net_eligible'     => $netEligible,

            'locked_gross_72h' => $lockedGross72h,
            'locked_net_72h'   => $lockedNet72h,
            'next_unlock_at'   => $nextUnlockAt, // nullable

            'reserved'         => $reserved,
            'paid_out'         => $paidOut,
            'available'        => $available,

            'min_payout'       => self::MIN_PAYOUT,
            'delay_hours'      => self::SECURITY_DELAY_HOURS,
        ];
    }
}
