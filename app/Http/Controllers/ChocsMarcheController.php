<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChocsMarcheController extends Controller
{
    private function sectors(): array
{
    return [
        // ✅ Déjà fait chez toi (je le laisse complet)
        'telecoms' => [
            'title' => 'Télécommunications',
            'up' => [
                'Croissance abonnés et data',
                'Explosion du Mobile Money',
                'Résultats trimestriels solides',
                'Dividende en hausse / régulier',
                'Extension réseau (4G/5G) avec gains de parts de marché',
            ],
            'down' => [
                'Nouvelles taxes / décisions de l\'État',
                'Concurrence agressive (prix, promos)',
                'Investissements lourds (capex) qui réduisent les bénéfices',
                'Problèmes réglementaires (licences, sanctions)',
                'Pannes majeures / incidents réputationnels',
            ],
            'examples' => [
                ['label' => 'SONATEL', 'note' => 'Souvent portée par dividendes + solidité, mais peut corriger lors de chocs fiscaux/réglementaires ou pression concurrentielle.'],
            ],
        ],

        'banques' => [
            'title' => 'Banques',
            'up' => [
                'Baisse des créances douteuses (NPL) + meilleure qualité d\'actifs',
                'Croissance du crédit bien maîtrisée',
                'Résultats en hausse (PNB, bénéfice net)',
                'Dividendes attractifs / régularité',
                'Amélioration des ratios de solvabilité',
            ],
            'down' => [
                'Hausse des impayés / créances douteuses',
                'Crise économique / ralentissement (baisse activité)',
                'Exposition trop forte à quelques gros clients / à l\'État',
                'Hausse des charges, provisionnements massifs',
                'Choc de change / taux (selon contextes)',
            ],
            'examples' => [
                ['label' => 'SGBCI / BICICI', 'note' => 'Le marché réagit fortement aux résultats annuels, aux provisions et au niveau de dividendes.'],
                ['label' => 'BOA / Ecobank', 'note' => 'Sensibles au cycle économique : quand l\'économie ralentit, les risques de défaut augmentent.'],
            ],
        ],

        // ✅ NOUVEAUX SECTEURS CI-DESSOUS
        'agro' => [
            'title' => 'Agro-industrie (cacao, palmier, caoutchouc, sucre…)',
            'up' => [
                'Hausse des prix internationaux (matières premières)',
                'Bonne campagne agricole (rendement/volume)',
                'Amélioration des marges (meilleure transformation, coûts maîtrisés)',
                'Nouveaux contrats export / expansion',
                'Baisse des coûts logistiques/énergie',
            ],
            'down' => [
                'Chute des prix mondiaux (commodities)',
                'Mauvaise récolte / aléas climatiques (pluies, sécheresse)',
                'Maladies/parasites sur plantations',
                'Hausse des intrants (engrais, carburant) qui écrase la marge',
                'Instabilité sociale / logistique (transport, ports)',
            ],
            'examples' => [
                ['label' => 'PALMCI', 'note' => 'Très sensible au prix de l\'huile de palme et aux volumes. Forte volatilité en période de cycle des matières premières.'],
                ['label' => 'SAPH / SOGB', 'note' => 'Réagit souvent au prix du caoutchouc/palmier + à la qualité de la campagne agricole.'],
            ],
        ],

        'utilities' => [
            'title' => 'Eau & Électricité (services publics)',
            'up' => [
                'Révision des tarifs (hausse réglementée)',
                'Subventions / apurements de dettes par l\'État',
                'Baisse des pertes techniques / amélioration recouvrement',
                'Investissements réseau qui améliorent la qualité de service',
                'Croissance de la demande (urbanisation)',
            ],
            'down' => [
                'Blocage politique des tarifs (pression sociale)',
                'Retards de paiement de l\'État / arriérés',
                'Hausse des coûts d\'exploitation (énergie, maintenance)',
                'Dégradation des infrastructures (pannes majeures)',
                'Endettement élevé / pression sur la trésorerie',
            ],
            'examples' => [
                ['label' => 'SODECI', 'note' => 'Souvent stable, mais sensible aux décisions tarifaires et aux arriérés (cash).'],
                ['label' => 'CIE', 'note' => 'Impact des coûts de production/achat d\'énergie + décisions de régulation.'],
            ],
        ],

        'industrie' => [
            'title' => 'Industrie & Matériaux (ciment, fabrication, BTP)',
            'up' => [
                'Boom immobilier / grands projets publics (BTP)',
                'Baisse du coût des intrants (clinker, énergie) ou meilleure efficacité',
                'Augmentation des prix de vente (si marché porteur)',
                'Capacité de production accrue (nouvelles lignes)',
                'Protection/avantage compétitif local (logistique, proximité)',
            ],
            'down' => [
                'Hausse du coût énergie/carburant (marges compressées)',
                'Baisse du pouvoir d\'achat (demande en recul)',
                'Concurrence/importations plus agressives',
                'Arrêts d\'usine / incidents industriels',
                'Tensions sur l\'approvisionnement (matières, transport)',
            ],
            'examples' => [
                ['label' => 'CIMAF/valeurs ciment (selon cote)', 'note' => 'Le marché réagit aux cycles BTP et aux marges liées à l\'énergie.'],
                ['label' => 'Industries locales', 'note' => 'Quand l\'État lance des projets, le secteur peut accélérer fortement.'],
            ],
        ],

        'distribution' => [
            'title' => 'Distribution & Consommation (retail, boissons, etc.)',
            'up' => [
                'Hausse de la consommation (croissance économique)',
                'Nouvelles ouvertures / expansion géographique',
                'Amélioration de la marge (mix produits, coûts logistiques)',
                'Baisse de l\'inflation / stabilisation des prix',
                'Stratégies marketing efficaces (parts de marché)',
            ],
            'down' => [
                'Inflation (baisse du pouvoir d\'achat)',
                'Hausse du coût import/logistique (fx, transport)',
                'Ruptures de stock / problèmes d\'approvisionnement',
                'Concurrence forte (guerre des prix)',
                'Taxes sur produits (boissons, alcool, sucre…) selon pays',
            ],
            'examples' => [
                ['label' => 'Distribution (ex: CFAO/retail selon cote)', 'note' => 'Très lié au pouvoir d\'achat : quand ça se tend, le marché peut corriger vite.'],
                ['label' => 'Biens de conso', 'note' => 'Les résultats trimestriels déclenchent souvent des mouvements rapides.'],
            ],
        ],

        'assurance' => [
            'title' => 'Assurances',
            'up' => [
                'Croissance des primes (nouveaux clients/segments)',
                'Bonne gestion des sinistres (ratio combiné meilleur)',
                'Hausse des revenus financiers (placements)',
                'Dividende stable / progression',
                'Amélioration solvabilité / gouvernance',
            ],
            'down' => [
                'Sinistres exceptionnels (catastrophes, gros dossiers)',
                'Baisse des revenus de placements (marchés difficiles)',
                'Durcissement réglementaire / exigences de solvabilité',
                'Concurrence sur les prix (marge qui baisse)',
                'Hausse des frais de gestion',
            ],
            'examples' => [
                ['label' => 'Assurances BRVM (ex: NSIA / autres selon cote)', 'note' => 'Sensibles au ratio sinistres/primes et au résultat financier des placements.'],
            ],
        ],

        'transport' => [
            'title' => 'Transport & Logistique',
            'up' => [
                'Hausse des volumes (commerce, export/import)',
                'Contrats logistiques long terme',
                'Amélioration efficacité (coûts carburant, maintenance)',
                'Investissements portuaires / infrastructures',
                'Stabilité sociale (fluidité des flux)',
            ],
            'down' => [
                'Hausse du carburant / coûts transport',
                'Perturbations ports/frontières (grèves, blocages)',
                'Baisse des volumes (ralentissement économique)',
                'Accidents/incidents majeurs',
                'Pression concurrentielle sur les prix',
            ],
            'examples' => [
                ['label' => 'Logistique/transport coté (selon BRVM)', 'note' => 'Bouge souvent quand les volumes d\'échanges et la stabilité logistique changent.'],
            ],
        ],
    ];
}


    public function index()
    {
        $sectors = $this->sectors();
        return view('chocs.index', compact('sectors'));
    }

    public function show(string $sector)
    {
        $sectors = $this->sectors();

        abort_unless(isset($sectors[$sector]), 404);

        $data = $sectors[$sector];
        $key = $sector;

        return view('chocs.show', compact('data', 'key', 'sectors'));
    }
}
