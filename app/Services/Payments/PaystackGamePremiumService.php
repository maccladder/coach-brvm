<?php

namespace App\Services\Payments;

use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Ancien déblocage premium d'Abidjan Run (références ABRUN_PREM_…, avant juillet 2026).
 * Le déblocage passait par GameScoreController (verify-premium / premium-callback) et
 * s'enregistre toujours dans game_premium_unlocks. Ici on rattrape les paiements dont
 * le joueur n'est jamais revenu sur le site.
 */
class PaystackGamePremiumService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';

    public const ABIDJAN_RUN_SLUG  = 'abidjan-run-le-jeu-run-a-livoirienne';
    public const ABIDJAN_RUN_PRICE = 1000;

    public function handleLegacyAbidjanRun(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));
        $email      = (string) data_get($data, 'customer.email');

        // Paystack renvoie metadata en objet, parfois en chaîne JSON
        $metadata = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $metadata = (array) $metadata;

        if (GamePremiumUnlock::where('paystack_ref', $reference)->exists()) {
            return self::ALREADY;
        }

        if ($amountPaid !== self::ABIDJAN_RUN_PRICE || $currency !== 'XOF') {
            Log::error('Paystack webhook ABRUN_PREM: montant/devise incorrect', [
                'reference' => $reference,
                'paid'      => $amountPaid,
                'currency'  => $currency,
            ]);
            return self::MISMATCH;
        }

        // Selon la version, la référence venait du serveur (metadata user_id/product_id)
        // ou de la popup Paystack côté navigateur (metadata sans user_id) : repli sur l'email.
        $user = !empty($metadata['user_id'])
            ? User::find((int) $metadata['user_id'])
            : ($email !== '' ? User::where('email', $email)->first() : null);

        $product = !empty($metadata['product_id'])
            ? MarketplaceProduct::find((int) $metadata['product_id'])
            : MarketplaceProduct::where('slug', self::ABIDJAN_RUN_SLUG)->first();

        if (!$user || !$product) {
            Log::warning('Paystack webhook ABRUN_PREM: joueur ou jeu introuvable', [
                'reference' => $reference,
                'email'     => $email,
                'metadata'  => $metadata,
            ]);
            return self::NOT_FOUND;
        }

        $feature = (string) ($metadata['feature'] ?? 'premium_chars');

        $exists = GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', $feature)
            ->exists();

        if ($exists) {
            return self::ALREADY;
        }

        try {
            GamePremiumUnlock::create([
                'user_id'      => $user->id,
                'product_id'   => $product->id,
                'feature'      => $feature,
                'paystack_ref' => $reference,
                'amount'       => $amountPaid,
                'unlocked_at'  => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Débloqué en parallèle par le callback navigateur
            return self::ALREADY;
        }

        Log::info('Paystack webhook ABRUN_PREM: premium débloqué', [
            'reference'  => $reference,
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'feature'    => $feature,
        ]);

        return self::GRANTED;
    }
}
