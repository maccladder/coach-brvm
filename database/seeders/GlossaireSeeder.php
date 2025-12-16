<?php

namespace Database\Seeders;

use App\Models\Glossaire;
use Illuminate\Database\Seeder;


class GlossaireSeeder extends Seeder
{
    public function run()
    {
        $data = [

            // A
            ['A','Action','Titre représentant une part du capital d’une entreprise cotée à la BRVM.'],
            ['A','Analyse fondamentale','Étude des états financiers et de la santé économique d’une entreprise pour évaluer sa valeur.'],
            ['A','Analyse technique','Analyse des graphiques de cours et volumes pour anticiper les tendances du marché.'],

            // B
            ['B','BRVM','Bourse Régionale des Valeurs Mobilières, marché financier commun à 8 pays de l’UEMOA.'],
            ['B','Billet de trésorerie','Titre de créance à court terme émis par une entreprise.'],
            ['B','BSA','Bon de Souscription d’Actions donnant droit à acheter des actions à un prix fixé.'],

            // C
            ['C','Capitalisation boursière','Valeur totale d’une entreprise cotée (actions × prix).'],
            ['C','Cours de clôture','Dernier prix d’un titre à la fin de la séance.'],
            ['C','Capital social','Apports initiaux réalisés par les actionnaires.'],

            // D
            ['D','Dividende','Part du bénéfice distribuée aux actionnaires.'],
            ['D','Demande et offre','Quantité de titres demandés et proposés sur le marché.'],

            // F
            ['F','Flux de trésorerie','Entrées et sorties de liquidités d’une entreprise.'],
            ['F','FCP','Fonds Commun de Placement géré par une société de gestion.'],

            // I
            ['I','Indice boursier','Indicateur de performance d’un groupe d’actions (BRVM Composite, BRVM 30).'],
            ['I','Investisseur','Personne ou institution qui place son argent pour générer un rendement.'],

            // L
            ['L','Liquidité','Facilité avec laquelle un titre peut être acheté ou vendu.'],

            // M
            ['M','Marché primaire','Marché des nouvelles émissions de titres.'],
            ['M','Marché secondaire','Marché d’échange des titres existants.'],

            // O
            ['O','Obligation','Titre de créance rémunéré par intérêts.'],

            // P
            ['P','PER','Ratio cours/bénéfice mesurant la valorisation d’une action.'],
            ['P','Prix plafond','Limite maximale de hausse autorisée sur une séance.'],

            // R
            ['R','Rendement','Gain généré par un investissement.'],

            // S
            ['S','Split','Division du nombre d’actions pour rendre le titre plus accessible.'],

            // V
            ['V','Volatilité','Amplitude des variations de prix d’un titre.'],

            // 🔥 BONUS PRO
            ['R','Risque','Possibilité de perte liée à un investissement.'],
            ['D','Diversification','Répartition des investissements pour réduire le risque.'],
            ['T','Taux de rendement','Rapport entre gain et capital investi.'],
        ];

        foreach ($data as [$lettre, $terme, $definition]) {
            Glossaire::create(compact('lettre','terme','definition'));
        }
    }
}
