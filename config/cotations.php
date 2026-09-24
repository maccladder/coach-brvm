<?php

/*
|--------------------------------------------------------------------------
| Nouvelles cotations mises en avant sur l'accueil
|--------------------------------------------------------------------------
|
| Chaque entrée affiche un encart sur /welcome du jour de première cotation
| jusqu'au jour de fin d'affichage inclus, puis disparaît d'elle-même.
| Pour la prochaine introduction en bourse : ajouter une entrée ici.
|
| - prix_opv : prix de l'offre publique de vente (FCFA), base de la variation
| - fiche    : slug de la fiche société (/societes/{fiche})
| - logo     : chemin sous public/ (optionnel)
|
*/

return [

    'nouvelles' => [
        [
            'ticker'            => 'BBGC',
            'nom'               => "Bridge Bank Group Côte d'Ivoire",
            'prix_opv'          => 6750,
            'premiere_cotation' => '2026-09-24',
            'fin_affichage'     => '2026-10-08',
            'fiche'             => 'bridge-bank-ci',
            'logo'              => 'img/logos/societes/bridge-bank.png',
        ],
    ],

];
