<?php

namespace App\Policies;

use App\Models\MarketplaceProduct;
use App\Models\User;

class MarketplaceProductPolicy
{
    /**
     * Gestion des fichiers téléchargeables côté vendeur : uniquement ses propres produits,
     * ou les produits officiels (user_id null) pour le propriétaire de la plateforme
     * (même règle que VendorProductController::ownsProduct). L'admin passe par ses
     * propres routes protégées par admin.code.
     */
    public function manageFiles(User $user, MarketplaceProduct $product): bool
    {
        if ($product->user_id !== null) {
            return (int) $product->user_id === (int) $user->id;
        }

        return strcasecmp((string) $user->email, (string) config('app.owner_email')) === 0;
    }
}
