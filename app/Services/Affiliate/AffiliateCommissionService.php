<?php

namespace App\Services\Affiliate;

use App\Mail\AffiliateCommissionAdminMail;
use App\Mail\AffiliateCommissionEarnedMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AdminNotification;
use App\Models\Course;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliateCommissionService
{
    /**
     * Noyau de validation partagé entre le checkout réel et l'endpoint de preview JSON.
     *
     * Les deux chemins appellent cette méthode — ils ne peuvent JAMAIS diverger.
     *
     * Retour :
     *   eligible        => bool
     *   reason          => 'eligible'|'invalid_code'|'self_referral'|'not_eligible'
     *   code            => string|null  (null si non éligible)
     *   amount          => int          (prix remisé ou plein)
     *   base_amount     => int          (prix de référence)
     *   affiliate_label => string|null  (prénom uniquement — confidentialité)
     */
    public function checkCode(
        string $code,
        int    $buyerUserId,
        string $purpose,
        ?int   $productId,
        int    $basePrice
    ): array {
        $base = [
            'eligible'        => false,
            'reason'          => 'invalid_code',
            'code'            => null,
            'amount'          => $basePrice,
            'base_amount'     => $basePrice,
            'affiliate_label' => null,
        ];

        $code = strtoupper(trim($code));
        if ($code === '') {
            return $base;
        }

        // ── Trouver l'apporteur actif ─────────────────────────────────────────
        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();
        if (!$affiliate) {
            return $base; // reason = 'invalid_code'
        }

        // Label confidentiel : prénom uniquement pour éviter l'énumération code → identité
        $firstName = explode(' ', trim($affiliate->user?->name ?? ''))[0] ?: $code;
        $base['affiliate_label'] = $firstName;

        // ── Anti-autoparrainage ───────────────────────────────────────────────
        if ($affiliate->user_id === $buyerUserId) {
            return array_merge($base, ['reason' => 'self_referral']);
        }

        $buyerEmail     = User::where('id', $buyerUserId)->value('email');
        $affiliateEmail = User::where('id', $affiliate->user_id)->value('email');
        if ($buyerEmail && $affiliateEmail && strtolower($buyerEmail) === strtolower($affiliateEmail)) {
            return array_merge($base, ['reason' => 'self_referral']);
        }

        // ── Éligibilité du produit ────────────────────────────────────────────
        $eligible = false;
        switch ($purpose) {
            case 'pack_brvm':
                $eligible = (bool) config('affiliate.pack_brvm_eligible', true);
                break;
            case 'course':
                if ($productId) {
                    $eligible = (bool) Course::where('id', $productId)->value('affiliate_eligible');
                }
                break;
            case 'marketplace':
                if ($productId) {
                    $mp = MarketplaceProduct::select('user_id', 'affiliate_eligible')->find($productId);
                    $eligible = $mp && $mp->affiliate_eligible && is_null($mp->user_id);
                }
                break;
        }

        if (!$eligible) {
            return array_merge($base, ['reason' => 'not_eligible']);
        }

        // ── Calcul de la remise ───────────────────────────────────────────────
        $discountRate     = (float) config('affiliate.discount_rate', 0.10);
        $discountedAmount = (int) round($basePrice * (1 - $discountRate));

        return [
            'eligible'        => true,
            'reason'          => 'eligible',
            'code'            => $code,
            'amount'          => $discountedAmount,
            'base_amount'     => $basePrice,
            'affiliate_label' => $firstName,
        ];
    }

    /**
     * Résout le code depuis la requête HTTP (form > cookie) et appelle checkCode().
     * Utilisé par les contrôleurs de paiement — wrapper fin, pas de logique métier ici.
     *
     * Retour supplémentaire par rapport à checkCode() :
     *   is_manual => bool         (true si code saisi dans le formulaire)
     *   warning   => string|null  (message HTML si code manuel non éligible)
     */
    public function resolveForCheckout(
        Request $request,
        string  $purpose,
        ?int    $productId,
        int     $basePrice
    ): array {
        $formCode   = strtoupper(trim((string) $request->input('affiliate_code', '')));
        $cookieCode = strtoupper(trim((string) $request->cookie('affiliate_code', '')));
        $code       = $formCode !== '' ? $formCode : $cookieCode;
        $isManual   = $formCode !== '';

        if ($code === '') {
            return [
                'code' => null, 'amount' => $basePrice, 'base_amount' => $basePrice,
                'eligible' => false, 'is_manual' => false, 'warning' => null,
            ];
        }

        $result = $this->checkCode($code, (int) ($request->user()?->id ?? 0), $purpose, $productId, $basePrice);

        $warning = null;
        if ($isManual && !$result['eligible'] && $result['reason'] !== 'self_referral') {
            $warning = 'Le code <strong>' . e($code) . '</strong> est valide mais ne s\'applique pas à ce produit. L\'achat continuera au tarif normal sans remise.';
        }

        return array_merge($result, ['is_manual' => $isManual, 'warning' => $warning]);
    }

    /**
     * Tente de créer une commission affiliée pour un paiement confirmé.
     *
     * Règles appliquées dans l'ordre :
     *  1. No-op si payment.affiliate_code est vide
     *  2. No-op si l'apporteur n'est pas actif
     *  3. Refus si auto-parrainage (même user_id ou même email)
     *  4. Refus si produit non éligible (type inconnu, flag absent, ou vendeur tiers)
     *  5. Création idempotente (UNIQUE payment_id + lockForUpdate)
     */
    public function tryCreateCommission(Payment $payment): void
    {
        $code = (string) ($payment->affiliate_code ?? '');
        if ($code === '') {
            return;
        }

        $code      = strtoupper(trim($code));
        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();

        if (!$affiliate) {
            Log::info('AffiliateCommission: code inconnu ou inactif', [
                'code'       => $code,
                'payment_id' => $payment->id,
            ]);
            return;
        }

        // ── Anti-autoparrainage ───────────────────────────────────────────────

        $buyerUserId = (int) $payment->user_id;

        if ($affiliate->user_id === $buyerUserId) {
            Log::info('AffiliateCommission: autoparrainage bloqué (user_id)', [
                'affiliate_id' => $affiliate->id,
                'buyer_id'     => $buyerUserId,
            ]);
            return;
        }

        $buyerEmail     = User::where('id', $buyerUserId)->value('email');
        $affiliateEmail = User::where('id', $affiliate->user_id)->value('email');

        if ($buyerEmail && $affiliateEmail && strtolower($buyerEmail) === strtolower($affiliateEmail)) {
            Log::info('AffiliateCommission: autoparrainage bloqué (email)', [
                'affiliate_id' => $affiliate->id,
                'buyer_id'     => $buyerUserId,
            ]);
            return;
        }

        // ── Éligibilité du produit ────────────────────────────────────────────

        $eligible  = false;
        $productId = null;

        switch ($payment->purpose) {

            case 'pack_brvm':
                $eligible = (bool) config('affiliate.pack_brvm_eligible', true);
                break;

            case 'course':
                $courseId = (int) data_get($payment->meta, 'course_id');
                if ($courseId) {
                    $eligible  = (bool) Course::where('id', $courseId)->value('affiliate_eligible');
                    $productId = $courseId;
                }
                break;

            case 'marketplace':
                $mpId = (int) data_get($payment->meta, 'product_id');
                if ($mpId) {
                    $product = MarketplaceProduct::select('id', 'user_id', 'affiliate_eligible')
                        ->find($mpId);
                    // Garde-fou ownership : user_id IS NOT NULL = vendeur tiers → jamais éligible
                    $eligible  = $product
                        && (bool) $product->affiliate_eligible
                        && is_null($product->user_id);
                    $productId = $mpId;
                }
                break;

            default:
                // document, wallet_topup, lettreci, etc. → jamais éligibles
                break;
        }

        if (!$eligible) {
            Log::info('AffiliateCommission: produit non éligible', [
                'purpose'    => $payment->purpose,
                'product_id' => $productId,
                'payment_id' => $payment->id,
            ]);
            return;
        }

        // ── Calcul des montants ───────────────────────────────────────────────

        $baseAmount      = (int) ($payment->affiliate_base_amount ?? $payment->amount_paid);
        $commissionRate  = (float) config('affiliate.commission_rate', 0.10);
        $discountRate    = (float) config('affiliate.discount_rate', 0.10);
        $commissionAmt   = (int) round($baseAmount * $commissionRate);
        $discountAmt     = (int) round($baseAmount * $discountRate);

        // ── Statut initial selon la période de sûreté ────────────────────────

        $delayDays = (int) config('affiliate.security_delay_days', 10);
        $status      = $delayDays === 0 ? 'validated' : 'pending';
        $validatedAt = $delayDays === 0 ? now()       : null;

        // ── Création idempotente (UNIQUE payment_id + lockForUpdate) ──────────

        DB::transaction(function () use (
            $affiliate, $payment, $buyerUserId,
            $baseAmount, $commissionRate, $commissionAmt, $discountAmt,
            $status, $validatedAt, $productId
        ) {
            $existing = AffiliateCommission::where('payment_id', $payment->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                Log::info('AffiliateCommission: déjà créée, skip', [
                    'payment_id'    => $payment->id,
                    'commission_id' => $existing->id,
                ]);
                return;
            }

            $productType = match ($payment->purpose) {
                'pack_brvm'   => 'pack_brvm',
                'course'      => 'course',
                'marketplace' => 'marketplace',
                default       => 'marketplace',
            };

            AffiliateCommission::create([
                'affiliate_id'      => $affiliate->id,
                'payment_id'        => $payment->id,
                'buyer_user_id'     => $buyerUserId,
                'product_type'      => $productType,
                'product_id'        => $productId,
                'base_amount'       => $baseAmount,
                'commission_rate'   => $commissionRate,
                'commission_amount' => $commissionAmt,
                'discount_amount'   => $discountAmt,
                'status'            => $status,
                'validated_at'      => $validatedAt,
            ]);

            Log::info('AffiliateCommission: créée', [
                'affiliate_id'   => $affiliate->id,
                'payment_id'     => $payment->id,
                'status'         => $status,
                'commission_amt' => $commissionAmt,
            ]);
        });

        // ── Emails post-création — hors transaction, non bloquants ───────────
        // On recharge la commission pour avoir l'ID et les relations chargées.
        $createdCommission = AffiliateCommission::where('payment_id', $payment->id)
            ->with(['affiliate.user', 'buyer'])
            ->first();

        if (!$createdCommission) {
            return; // commission non créée (idempotence : déjà existante)
        }

        $adminEmails = config('affiliate.admin_emails', []);

        // Email apporteur : "Vous avez gagné une commission"
        try {
            $affiliateUserEmail = $createdCommission->affiliate?->user?->email;
            if ($affiliateUserEmail) {
                Mail::to($affiliateUserEmail)->send(new AffiliateCommissionEarnedMail($createdCommission));
            }
        } catch (\Throwable $e) {
            Log::error('AffiliateCommission: email apporteur échec', [
                'commission_id' => $createdCommission->id,
                'payment_id'    => $payment->id,
                'error'         => $e->getMessage(),
            ]);
        }

        // Email admin : "Vente affiliée"
        try {
            if ($adminEmails) {
                Mail::to($adminEmails)->send(new AffiliateCommissionAdminMail($createdCommission));
            }
        } catch (\Throwable $e) {
            Log::error('AffiliateCommission: email admin échec', [
                'commission_id' => $createdCommission->id,
                'payment_id'    => $payment->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    /**
     * Annulation manuelle d'une commission par l'admin (V1 — pas de webhook refund).
     *
     * - pending | validated → cancelled
     * - paid → impossible (ne devrait pas arriver en V1), alerte admin
     */
    public function cancelCommission(int $commissionId, string $reason): void
    {
        $commission = AffiliateCommission::findOrFail($commissionId);

        if ($commission->status === 'cancelled') {
            return;
        }

        if ($commission->status === 'paid') {
            AdminNotification::create([
                'type'    => 'affiliate_commission_cancel_blocked',
                'title'   => '⚠️ Annulation commission impossible',
                'message' => "Commission #{$commissionId} est déjà en statut paid — annulation manuelle requise.",
                'url'     => null,
                'read_at' => null,
            ]);
            return;
        }

        $commission->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        Log::info('AffiliateCommission: annulée', [
            'commission_id' => $commissionId,
            'reason'        => $reason,
        ]);
    }

    /**
     * Passe les commissions pending de plus de security_delay_days à validated.
     * Appelée quotidiennement par la commande affiliate:validate-commissions.
     *
     * @return int Nombre de commissions validées
     */
    public function validatePendingCommissions(): int
    {
        $delayDays = (int) config('affiliate.security_delay_days', 10);
        $cutoff    = now()->subDays($delayDays);

        $count = AffiliateCommission::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->update([
                'status'       => 'validated',
                'validated_at' => now(),
            ]);

        Log::info('AffiliateCommission: validation quotidienne', [
            'validated_count' => $count,
            'cutoff'          => $cutoff->toDateTimeString(),
        ]);

        return $count;
    }
}
