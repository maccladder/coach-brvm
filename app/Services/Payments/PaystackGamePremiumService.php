<?php

namespace App\Services\Payments;

use App\Http\Controllers\GameScoreController;
use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Déblocages premium de jeux payés via Paystack (game_premium_unlocks).
 * - GAME{productId}_{FEATURE}_… : flux actuel (init serveur ou checkout Mobile Money)
 * - ABRUN_PREM_… : ancien déblocage Abidjan Run (avant juillet 2026), rattrapé par le webhook
 * Idempotent : une référence ou un trio (user, jeu, feature) n'est enregistré qu'une fois.
 */
class PaystackGamePremiumService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';

    public const ABIDJAN_RUN_SLUG  = 'abidjan-run-le-jeu-run-a-livoirienne';
    public const ABIDJAN_RUN_PRICE = 1000;

    /**
     * Déblocage premium actuel : user, jeu et feature viennent des métadonnées posées
     * côté serveur à l'initialisation ; le prix attendu vient de
     * GameScoreController::FEATURE_PRICES (jamais du client).
     */
    public function handleGamePremium(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));
        $metadata   = $this->metadata($data);

        if (GamePremiumUnlock::where('paystack_ref', $reference)->exists()) {
            return self::ALREADY;
        }

        $user    = User::find((int) ($metadata['user_id'] ?? 0));
        $product = MarketplaceProduct::find((int) ($metadata['product_id'] ?? 0));
        $feature = (string) ($metadata['feature'] ?? '');

        if (!$user || !$product || $feature === '') {
            Log::warning('Paystack GAME premium: joueur, jeu ou feature introuvable', [
                'reference' => $reference,
                'metadata'  => $metadata,
            ]);
            return self::NOT_FOUND;
        }

        $expected = GameScoreController::featurePrice($product->slug, $feature);

        if ($expected === null || $amountPaid !== $expected || $currency !== 'XOF') {
            Log::error('Paystack GAME premium: montant/devise incorrect', [
                'reference' => $reference,
                'expected'  => $expected,
                'paid'      => $amountPaid,
                'currency'  => $currency,
            ]);
            return self::MISMATCH;
        }

        return $this->unlock($user, $product, $feature, $reference, $amountPaid);
    }

    public function handleLegacyAbidjanRun(array $data): string
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));
        $email      = (string) data_get($data, 'customer.email');
        $metadata   = $this->metadata($data);

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

        return $this->unlock($user, $product, $feature, $reference, $amountPaid);
    }

    private function unlock(User $user, MarketplaceProduct $product, string $feature, string $reference, int $amountPaid): string
    {
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
        } catch (UniqueConstraintViolationException) {
            // Débloqué en parallèle par le callback navigateur
            return self::ALREADY;
        }

        Log::info('Paystack game premium: débloqué', [
            'reference'  => $reference,
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'feature'    => $feature,
        ]);

        return self::GRANTED;
    }

    /** Paystack renvoie metadata en objet, parfois en chaîne JSON. */
    private function metadata(array $data): array
    {
        $metadata = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        return (array) $metadata;
    }
}
