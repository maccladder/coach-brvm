<?php

/*
|--------------------------------------------------------------------------
| Modules masquables (rien n'est supprimé : code, routes, tables et vues restent)
|--------------------------------------------------------------------------
| À false (défaut) : plus aucun lien vers le module, ses routes publiques renvoient 404
| (middleware « feature:nom »), l'admin garde la lecture des données existantes
| (« feature:nom,admin ») sans pouvoir en créer. Réactivation : variable à true dans .env,
| puis php artisan config:cache.
*/

return [

    // Analyse de BOC envoyée par le client (client-bocs), y compris la BOC gratuite J-1
    'boc_analysis' => (bool) env('FEATURE_BOC_ANALYSIS', false),

    // Analyse d'états financiers envoyés par le client (client-financials)
    'financial_statements' => (bool) env('FEATURE_FINANCIAL_STATEMENTS', false),

    // Paiements CinetPay (remplacés par Paystack) : boutons, achats et retours/notifications
    'cinetpay' => (bool) env('FEATURE_CINETPAY', false),

];
