<?php

/*
|--------------------------------------------------------------------------
| Sociétés « à suivre » sur l'accueil (cartes sous le hero)
|--------------------------------------------------------------------------
|
| Le cours affiché est toujours lu en base (dernière clôture BOC, table
| boc_stocks) : rien à mettre à jour ici pour les chiffres.
|
| - accroche          : texte court et factuel (tiré des actualités), jamais un conseil
| - fiche             : slug de la fiche société (/societes/{fiche})
| - logo              : chemin sous public/ (optionnel)
| - prix_introduction : prix d'introduction en FCFA ; null = ligne non affichée
|
*/

return [

    'societes' => [
        [
            'ticker'            => 'SNTS',
            'nom'               => 'Sonatel',
            'secteur'           => 'Télécoms · Sénégal',
            'fiche'             => 'sonatel-senegal',
            'logo'              => 'img/logos/societes/sonatel.jpg',
            'accroche'          => "Fin octobre, l'annonce du fractionnement de l'action a animé la BRVM. Le 29/10, le titre a concentré un volume d'échanges historique de 10 milliards FCFA.",
            'prix_introduction' => null,
        ],
        [
            'ticker'            => 'BBGC',
            'nom'               => 'Bridge Bank Group CI',
            'secteur'           => "Banque · Côte d'Ivoire",
            'fiche'             => 'bridge-bank-ci',
            'logo'              => 'img/logos/societes/bridge-bank.png',
            'accroche'          => "Cotée à la BRVM depuis le 24/09/2026, après une offre publique de vente de 20 % du capital (10 millions d'actions) visée par l'AMF-UMOA.",
            // À renseigner (FCFA, ex. 6750) : vide = la ligne « Prix d'introduction » n'est pas affichée
            'prix_introduction' => null,
        ],
    ],

];
