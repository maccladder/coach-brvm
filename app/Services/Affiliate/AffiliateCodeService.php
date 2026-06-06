<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate;
use Illuminate\Support\Str;

class AffiliateCodeService
{
    /**
     * Génère un code unique de 7 caractères alphanumériques en majuscules.
     * Boucle jusqu'à trouver un code non utilisé.
     */
    public function generate(): string
    {
        do {
            $code = strtoupper(Str::random(7));
        } while (Affiliate::where('code', $code)->exists());

        return $code;
    }

    /**
     * Valide un code personnalisé saisi par l'utilisateur.
     * Retourne un tableau d'erreurs (vide = code valide).
     */
    public function validateCustom(string $code): array
    {
        $errors = [];
        $code   = strtoupper(trim($code));

        if (strlen($code) < 3 || strlen($code) > 20) {
            $errors[] = 'Le code doit contenir entre 3 et 20 caractères.';
        }

        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            $errors[] = 'Le code ne peut contenir que des lettres (A-Z) et des chiffres (0-9), sans espace ni caractère spécial.';
        }

        if ($this->isReserved($code)) {
            $errors[] = 'Ce code est réservé et ne peut pas être utilisé.';
        }

        if (Affiliate::where('code', $code)->exists()) {
            $errors[] = 'Ce code est déjà pris. Choisis-en un autre.';
        }

        return $errors;
    }

    /**
     * Vérifie si un code (normalisé en majuscules) fait partie de la liste réservée.
     */
    public function isReserved(string $code): bool
    {
        $reserved = array_map('strtoupper', config('affiliate.reserved_codes', []));
        return in_array(strtoupper(trim($code)), $reserved, true);
    }
}
