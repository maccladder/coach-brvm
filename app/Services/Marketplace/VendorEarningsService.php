<?php

namespace App\Services\Marketplace;

use App\Models\VendorPayoutRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VendorEarningsService
{
    public const COMMISSION_RATE = 0.15;      // 15%
    public const SECURITY_DELAY_HOURS = 72;   // 72h
    public const MIN_PAYOUT = 10000;          // 10 000 FCFA

    public function totalsForVendor(int $vendorId): array
    {
        $delayCutoff = now()->subHours(self::SECURITY_DELAY_HOURS);

        /* =========================
         | A) Marketplace purchases
         ========================= */
        $mpGrossTotal = (int) DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->sum('mp.amount');

        $mpGrossEligible = (int) DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->whereNotNull('mp.paid_at')
            ->where('mp.paid_at', '<=', $delayCutoff)
            ->sum('mp.amount');

        $mpNextUnlockPaidAt = DB::table('marketplace_purchases as mp')
            ->join('marketplace_products as p', 'p.id', '=', 'mp.product_id')
            ->where('p.user_id', $vendorId)
            ->where('mp.status', 'paid')
            ->whereNotNull('mp.paid_at')
            ->where('mp.paid_at', '>', $delayCutoff)
            ->min('mp.paid_at');

        /* =========================
         | B) Document purchases
         |   (études / business plans)
         ========================= */
        $docGrossTotal = (int) DB::table('document_purchases as dp')
            ->join('documents as d', 'd.id', '=', 'dp.document_id')
            ->where('d.vendor_id', $vendorId)
            ->where('dp.status', 'paid')
            ->sum('dp.amount');

        $docGrossEligible = (int) DB::table('document_purchases as dp')
            ->join('documents as d', 'd.id', '=', 'dp.document_id')
            ->where('d.vendor_id', $vendorId)
            ->where('dp.status', 'paid')
            ->whereNotNull('dp.paid_at')
            ->where('dp.paid_at', '<=', $delayCutoff)
            ->sum('dp.amount');

        $docNextUnlockPaidAt = DB::table('document_purchases as dp')
            ->join('documents as d', 'd.id', '=', 'dp.document_id')
            ->where('d.vendor_id', $vendorId)
            ->where('dp.status', 'paid')
            ->whereNotNull('dp.paid_at')
            ->where('dp.paid_at', '>', $delayCutoff)
            ->min('dp.paid_at');

        /* =========================
         | Totaux (Marketplace + Docs)
         ========================= */
        $grossTotal    = $mpGrossTotal + $docGrossTotal;
        $grossEligible = $mpGrossEligible + $docGrossEligible;

        // Commission
        $feeTotal     = (int) round($grossTotal * self::COMMISSION_RATE);
        $feeEligible  = (int) round($grossEligible * self::COMMISSION_RATE);

        $netTotal     = max(0, $grossTotal - $feeTotal);
        $netEligible  = max(0, $grossEligible - $feeEligible);

        // Locked (<72h)
        $lockedGross72h = max(0, $grossTotal - $grossEligible);
        $lockedFee72h   = (int) round($lockedGross72h * self::COMMISSION_RATE);
        $lockedNet72h   = max(0, $lockedGross72h - $lockedFee72h);

        // Reversements (réservé + payé)
        $reserved = (int) VendorPayoutRequest::query()
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $paidOut = (int) VendorPayoutRequest::query()
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->sum('amount');

        $available = max(0, $netEligible - $reserved - $paidOut);

        // Prochaine date de déblocage (min paid_at > cutoff, marketplace ou docs)
        $candidates = array_filter([$mpNextUnlockPaidAt, $docNextUnlockPaidAt]);
        $minNextPaidAt = $candidates ? min($candidates) : null;
        $nextUnlockAt = $minNextPaidAt ? Carbon::parse($minNextPaidAt)->addHours(self::SECURITY_DELAY_HOURS) : null;

        return [
            'gross_total'      => $grossTotal,
            'fee_total'        => $feeTotal,
            'net_total'        => $netTotal,

            'gross_eligible'   => $grossEligible,
            'fee_eligible'     => $feeEligible,
            'net_eligible'     => $netEligible,

            'locked_gross_72h' => $lockedGross72h,
            'locked_net_72h'   => $lockedNet72h,
            'next_unlock_at'   => $nextUnlockAt,

            'reserved'         => $reserved,
            'paid_out'         => $paidOut,
            'available'        => $available,

            'min_payout'       => self::MIN_PAYOUT,
            'delay_hours'      => self::SECURITY_DELAY_HOURS,

            // (Optionnel) debug
            'breakdown' => [
                'marketplace_gross_total' => $mpGrossTotal,
                'docs_gross_total'        => $docGrossTotal,
            ],
        ];
    }
}
