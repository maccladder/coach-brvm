<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Phone Verification — Récompense Abidjan Run
    |--------------------------------------------------------------------------
    */

    // Slug du produit offert en récompense (résolu via MarketplaceProduct::where('slug'))
    'reward_product_slug' => env('OTP_REWARD_PRODUCT_SLUG', 'abidjan-run-le-jeu-run-a-livoirienne'),

    // Date limite de l'offre (inclusive). Après cette date, plus aucune récompense distribuée.
    'reward_deadline' => env('OTP_REWARD_DEADLINE', '2026-05-20'),

    // Throttle envoi d'OTP : max N tentatives par heure par user
    'throttle_send_max'    => (int) env('OTP_THROTTLE_SEND_MAX', 3),
    'throttle_send_decay'  => (int) env('OTP_THROTTLE_SEND_DECAY', 60), // minutes

    // Throttle vérification de code : max N tentatives par fenêtre par user
    'throttle_verify_max'   => (int) env('OTP_THROTTLE_VERIFY_MAX', 3),
    'throttle_verify_decay' => (int) env('OTP_THROTTLE_VERIFY_DECAY', 10), // minutes

    // Pays UEMOA supportés — code => [nom, préfixe E.164, longueur du numéro local]
    'countries' => [
        'CI' => ['name' => 'Côte d\'Ivoire', 'prefix' => '+225', 'digits' => 10],
        'SN' => ['name' => 'Sénégal',        'prefix' => '+221', 'digits' => 9],
        'BF' => ['name' => 'Burkina Faso',   'prefix' => '+226', 'digits' => 8],
        'ML' => ['name' => 'Mali',            'prefix' => '+223', 'digits' => 8],
        'BJ' => ['name' => 'Bénin',           'prefix' => '+229', 'digits' => 8],
        'TG' => ['name' => 'Togo',            'prefix' => '+228', 'digits' => 8],
        'NE' => ['name' => 'Niger',           'prefix' => '+227', 'digits' => 8],
        'GW' => ['name' => 'Guinée-Bissau',   'prefix' => '+245', 'digits' => 9],
    ],

    /*
    |--------------------------------------------------------------------------
    | Promo 2 — Bonus portefeuille virtuel (Mai–Juin 2026)
    |--------------------------------------------------------------------------
    */

    // Montant du bonus en FCFA virtuels
    'wallet_bonus_amount'  => (int) env('WALLET_BONUS_AMOUNT', 50000),

    // Début de la période promo (Segment B via OTP)
    'wallet_bonus_start'   => env('WALLET_BONUS_START',  '2026-05-21 00:00:00'),

    // Fin de la période promo
    'wallet_bonus_end'     => env('WALLET_BONUS_END',    '2026-06-05 23:59:59'),

    // Cutoff d'inscription : seuls les users inscrits AVANT cette date sont éligibles
    'eligibility_cutoff'   => env('WALLET_ELIGIBILITY_CUTOFF', '2026-05-21 00:00:00'),

    // Libellé de la transaction dans virtual_wallet_transactions.motif
    'wallet_bonus_libelle' => env('WALLET_BONUS_LIBELLE', 'Bonus de bienvenue dans le simulateur — Mai 2026'),

];
