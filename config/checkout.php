<?php

/*
|--------------------------------------------------------------------------
| Checkout Mobile Money personnalisé (Paystack /charge)
|--------------------------------------------------------------------------
| Activation progressive, type par type. Drapeau à false = l'ancien flux
| Paystack (page hébergée) reste utilisé tel quel pour ce type.
*/

return [

    'types' => [
        'marketplace'  => [
            // false par défaut comme les autres : un oubli dans .env ne doit jamais activer un checkout
            'enabled'  => env('CHECKOUT_MM_MARKETPLACE', false),
            'provider' => App\Checkout\Providers\MarketplacePayable::class,
        ],
        'course'       => [
            'enabled'  => env('CHECKOUT_MM_COURSE', false),
            'provider' => App\Checkout\Providers\CoursePayable::class,
        ],
        'pack'         => [
            'enabled'  => env('CHECKOUT_MM_PACK', false),
            'provider' => App\Checkout\Providers\PackPayable::class,
        ],
        'wallet'       => [
            'enabled'  => env('CHECKOUT_MM_WALLET', false),
            'provider' => App\Checkout\Providers\WalletPayable::class,
        ],
        'cauris'       => [
            'enabled'  => env('CHECKOUT_MM_CAURIS', false),
            'provider' => App\Checkout\Providers\CaurisPayable::class,
        ],
        'don'          => [
            'enabled'  => env('CHECKOUT_MM_DON', false),
            'provider' => App\Checkout\Providers\DonationPayable::class,
        ],
        'game_premium' => [
            'enabled'  => env('CHECKOUT_MM_GAME_PREMIUM', false),
            'provider' => App\Checkout\Providers\GamePremiumPayable::class,
        ],
        // Akwaba : histoire complète (achat marketplace MP-) et packs de 5 indices (AKW-H-)
        'akwaba'       => [
            'enabled'  => env('CHECKOUT_MM_AKWABA', false),
            'provider' => App\Checkout\Providers\AkwabaPayable::class,
        ],
        // Le Pacte avec les caïmans : histoire complète (MP-) et packs de 5 indices (PCT-H-)
        'pacte'        => [
            'enabled'  => env('CHECKOUT_MM_PACTE', false),
            'provider' => App\Checkout\Providers\PactePayable::class,
        ],
        // Études & Business plans (référence DOC-PS-)
        'document'     => [
            'enabled'  => env('CHECKOUT_MM_DOCUMENT', false),
            'provider' => App\Checkout\Providers\DocumentPayable::class,
        ],
    ],

    // Test en prod réservé : si rempli (emails séparés par des virgules), seuls ces comptes
    // connectés utilisent le nouveau checkout ; les autres gardent l'ancien flux Paystack.
    'allowed_emails' => env('CHECKOUT_MM_ALLOWED_EMAILS', ''),

    // Simulation locale sans appel Paystack (scénarios selon les 4 derniers chiffres du
    // numéro, voir App\Services\Payments\MobileMoneySimulator). Ignorée en production.
    'simulate' => env('CHECKOUT_MM_SIMULATE', false),

    // Wave renvoie "unprocessed_transaction" en prod : masqué tant que ce n'est pas réglé
    'wave_enabled' => env('CHECKOUT_MM_WAVE_ENABLED', false),

    // Opérateurs proposés (clé = code "provider" attendu par Paystack)
    'operators' => [
        'wave'   => ['label' => 'Wave',         'color' => '#1dc8f2', 'text' => '#06222b'],
        'orange' => ['label' => 'Orange Money', 'color' => '#ff7900', 'text' => '#1a0d00'],
        'mtn'    => ['label' => 'MTN MoMo',     'color' => '#ffcc00', 'text' => '#1a1500'],
    ],

    // Indicatif ajouté devant les 10 chiffres saisis
    'phone_prefix' => env('CHECKOUT_MM_PHONE_PREFIX', '+225'),

    // Délai laissé à l'utilisateur pour valider sur son téléphone (secondes)
    'window_seconds' => 180,

    'whatsapp' => '2250767123451',
];
