<?php

namespace App\Services;

use App\Mail\WalletBonusMail;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Models\VirtualWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VirtualWalletBonusService
{
    public function grant(User $user): array
    {
        $result = DB::transaction(function () use ($user) {
            // Re-fetch avec lock pour éviter race conditions
            $u = User::lockForUpdate()->find($user->id);

            if (!$u) {
                return ['success' => false, 'reason' => 'user_not_found'];
            }

            // Garde 1 — Cutoff d'inscription
            if ($u->created_at >= config('otp.eligibility_cutoff')) {
                return ['success' => false, 'reason' => 'user_created_after_cutoff'];
            }

            // Garde 2 — Phone non vérifié
            if (is_null($u->phone_verified_at)) {
                return ['success' => false, 'reason' => 'phone_not_verified'];
            }

            // Garde 3 — Déjà crédité (idempotence)
            if (!is_null($u->wallet_bonus_claimed_at)) {
                return ['success' => false, 'reason' => 'already_claimed'];
            }

            // Garde 4 — Hors période promo
            $now = now();
            if ($now->lt(config('otp.wallet_bonus_start')) || $now->gt(config('otp.wallet_bonus_end'))) {
                return ['success' => false, 'reason' => 'outside_promo_window'];
            }

            $amount  = (int) config('otp.wallet_bonus_amount');
            $libelle = config('otp.wallet_bonus_libelle');

            // Wallet : firstOrCreate (pattern cohérent avec le reste du codebase)
            $wallet = VirtualWallet::firstOrCreate(
                ['user_id' => $u->id],
                ['balance' => 0]
            );

            // Crédit atomique
            $wallet->increment('balance', $amount);

            // Trace dans virtual_wallet_transactions
            VirtualWalletTransaction::create([
                'user_id' => $u->id,
                'type'    => 'bonus',
                'amount'  => $amount,
                'motif'   => $libelle,
            ]);

            // Flag user (idempotence garantie)
            $u->update(['wallet_bonus_claimed_at' => $now]);

            // Log structuré sans données sensibles
            Log::info('wallet_bonus_granted', [
                'user_id' => $u->id,
                'amount'  => $amount,
            ]);

            return ['success' => true, 'reason' => 'granted', 'amount' => $amount];
        });

        // Mail en queue APRÈS la transaction (non bloquant)
        // Wrappé en try/catch pour ne pas faire échouer le grant si la queue plante
        if ($result['success'] === true) {
            try {
                Mail::to($user->email)->queue(new WalletBonusMail($user));
            } catch (\Throwable $e) {
                Log::warning('wallet_bonus_mail_failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }
}
