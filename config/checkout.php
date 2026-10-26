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
            'enabled'  => env('CHECKOUT_MM_MARKETPLACE', true),
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
    ],

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
