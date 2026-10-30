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

    // Taille max par fichier, en Mo (PHP upload_max_filesize / post_max_size et nginx
    // client_max_body_size doivent suivre).
    'files_max_mb' => (int) env('MARKETPLACE_FILES_MAX_MB', 500),

];
