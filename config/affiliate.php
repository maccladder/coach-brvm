<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Emails admin destinataires des notifications affiliation
    | (même liste que le reste de la codebase — peut être surchargée via .env)
    |--------------------------------------------------------------------------
    */
    'admin_emails' => array_values(array_filter(
        array_map('trim', explode(',', env('ADMIN_EMAILS', 'maccladder@gmail.com,boursivoire@gmail.com')))
    )),

    /*
    |--------------------------------------------------------------------------
    | Taux de commission et de remise (décimaux, ex. 0.10 = 10%)
    |--------------------------------------------------------------------------
    */
    'commission_rate' => (float) env('AFFILIATE_COMMISSION_RATE', 0.10),
    'discount_rate'   => (float) env('AFFILIATE_DISCOUNT_RATE',   0.10),

    /*
    |--------------------------------------------------------------------------
    | Période de sûreté avant qu'une commission pending devienne retirable
    | (en jours). À 0 : la commission est validée immédiatement.
    |--------------------------------------------------------------------------
    */
    'security_delay_days' => (int) env('AFFILIATE_SECURITY_DELAY_DAYS', 10),

    /*
    |--------------------------------------------------------------------------
    | Montant minimum de reversement (en FCFA)
    |--------------------------------------------------------------------------
    */
    'min_payout' => (int) env('AFFILIATE_MIN_PAYOUT', 10000),

    /*
    |--------------------------------------------------------------------------
    | Validation admin requise pour activer un apporteur
    | true  → statut pending à la demande, admin approuve manuellement
    | false → activation automatique à la demande
    |--------------------------------------------------------------------------
    */
    'require_approval' => (bool) env('AFFILIATE_REQUIRE_APPROVAL', true),

    /*
    |--------------------------------------------------------------------------
    | Pack BRVM toujours éligible à l'affiliation
    | (pas de modèle en base — géré comme cas spécial dans le service)
    |--------------------------------------------------------------------------
    */
    'pack_brvm_eligible' => (bool) env('AFFILIATE_PACK_BRVM_ELIGIBLE', true),

    // Prix de référence du Pack BRVM — doit rester synchronisé avec PackController::PRICE
    'pack_brvm_price' => (int) env('PACK_BRVM_PRICE', 30000),

    /*
    |--------------------------------------------------------------------------
    | Méthodes de reversement acceptées
    |--------------------------------------------------------------------------
    */
    'payout_methods' => ['wave', 'orange_money', 'mtn', 'moov'],

    /*
    |--------------------------------------------------------------------------
    | Codes réservés / interdits pour les codes personnalisés
    | Comparaison insensible à la casse (tout est normalisé en majuscules)
    |--------------------------------------------------------------------------
    */
    'reserved_codes' => [
        // Routes et navigation
        'ADMIN', 'API', 'APP', 'WEB', 'WWW',
        'LOGIN', 'REGISTER', 'LOGOUT', 'PASSWORD', 'RESET',
        'HOME', 'INDEX', 'DASHBOARD', 'ACCOUNT', 'PROFILE',
        // Marque
        'COACH', 'BRVM', 'COACHBRVM', 'BOURSIV',
        // Support / contact
        'SUPPORT', 'CONTACT', 'HELP', 'FAQ', 'INFO', 'MAIL', 'EMAIL',
        // Commerce
        'PAY', 'PAYMENT', 'CHECKOUT', 'ORDER', 'CART', 'WALLET',
        'PROMO', 'CODE', 'REF', 'REFERRAL', 'AFFILIATE', 'PARRAIN',
        'APPORTEUR', 'EARN', 'REWARD',
        // Système
        'NULL', 'UNDEFINED', 'SYSTEM', 'ROOT', 'TEST', 'DEMO',
        'USER', 'USERS', 'ME', 'MY', 'VENDOR', 'VENDEUR',
        // Modules
        'PACK', 'COURS', 'COURSE', 'FORMATION', 'MARKETPLACE',
        'DONATION', 'DON', 'LETTRECI', 'BOURSE', 'INVESTIR',
    ],

];
