<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fichiers téléchargeables des produits (marketplace_product_files)
    |--------------------------------------------------------------------------
    | Disque privé : jamais servi par /storage, uniquement via la route de
    | téléchargement qui vérifie l'accès au produit.
    */
    'files_disk' => env('MARKETPLACE_FILES_DISK', 'local'),

    'files_extensions' => [
        'exe', 'msi', 'zip', 'rar', '7z', 'dmg', 'pkg', 'apk', 'pdf', 'epub', 'mp4', 'html',
    ],

    // Ajoutés ou remplacés par un VENDEUR sur un produit publié : invisibles pour les
    // acheteurs jusqu'à validation admin. PDF/epub (et mp4) passent sans validation ;
    // html est inclus car c'est du code exécuté par le navigateur.
    'review_extensions' => ['exe', 'msi', 'dmg', 'pkg', 'apk', 'zip', 'rar', '7z', 'html'],

    // Destinataires de l'email « fichier vendeur à valider »
    'admin_emails' => array_filter(array_map('trim', explode(',', env(
        'MARKETPLACE_ADMIN_EMAILS',
        'maccladder@gmail.com,ghislainkouadiodjaha@gmail.com'
    )))),

    // Taille max par fichier, en Mo (PHP upload_max_filesize / post_max_size et nginx
    // client_max_body_size doivent suivre).
    'files_max_mb' => (int) env('MARKETPLACE_FILES_MAX_MB', 500),

];
