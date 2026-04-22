<?php

return [

    'system_prompt' => "Tu es un rédacteur administratif expert du contexte ivoirien. Tu rédiges des lettres formelles en français, respectant les conventions administratives de Côte d'Ivoire. Ton français est impeccable, formel mais naturel. Tu retournes UNIQUEMENT le texte de la lettre, sans commentaire ni balise markdown, sans explication.",

    'categories' => [
        'professionnel' => ['label' => 'Vie professionnelle', 'icon' => '💼', 'color' => '#0FCFA4'],
        'administration' => ['label' => 'Administration',     'icon' => '🏛️', 'color' => '#C9A84C'],
        'personnel'      => ['label' => 'Vie personnelle',    'icon' => '👤', 'color' => '#6B7590'],
    ],

    'types' => [

        // ═══════════════════════════════════════
        // PROFESSIONNEL (5)
        // ═══════════════════════════════════════

        'demande-conge' => [
            'slug'        => 'demande-conge',
            'name'        => 'Demande de congé',
            'category'    => 'professionnel',
            'icon'        => '🏖️',
            'description' => 'Demandez un congé payé ou exceptionnel à votre employeur',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',              'type' => 'text',     'required' => true],
                ['name' => 'emetteur_fonction',   'label' => 'Votre fonction / poste',         'type' => 'text',     'required' => true],
                ['name' => 'destinataire_nom',    'label' => 'Nom du supérieur hiérarchique',  'type' => 'text',     'required' => true],
                ['name' => 'destinataire_fonction','label'=> 'Fonction du supérieur',          'type' => 'text',     'required' => true],
                ['name' => 'entreprise',          'label' => 'Nom de l\'entreprise',           'type' => 'text',     'required' => true],
                ['name' => 'date_debut',          'label' => 'Date de début de congé',         'type' => 'date',     'required' => true],
                ['name' => 'date_fin',            'label' => 'Date de fin de congé',           'type' => 'date',     'required' => true],
                ['name' => 'motif',               'label' => 'Motif du congé (optionnel)',     'type' => 'textarea', 'required' => false],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                   'type' => 'select',   'required' => true, 'options' => ['Formel', 'Cordial']],
            ],
            'prompt_template' => "Rédige une lettre de demande de congé.

Émetteur : {emetteur_nom}, {emetteur_fonction}
Entreprise : {entreprise}
Destinataire : {destinataire_nom}, {destinataire_fonction}
Dates du congé : du {date_debut} au {date_fin}
Motif : {motif}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur (haut gauche, 3 lignes max)
- Lieu et date (droite)
- Destinataire (gauche, après un saut de ligne)
- Objet : Demande de congé
- Formule d'appel (Monsieur/Madame selon contexte)
- Corps : 2-3 paragraphes (annonce du congé, dates précises, engagement de prise en charge du travail)
- Formule de politesse classique ivoirienne
- Signature",
        ],

        'lettre-demission' => [
            'slug'        => 'lettre-demission',
            'name'        => 'Lettre de démission',
            'category'    => 'professionnel',
            'icon'        => '🚪',
            'description' => 'Quittez votre poste de façon professionnelle et conforme au droit ivoirien',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_fonction',   'label' => 'Votre fonction / poste',        'type' => 'text',     'required' => true],
                ['name' => 'destinataire_nom',    'label' => 'Nom du DRH ou directeur',       'type' => 'text',     'required' => true],
                ['name' => 'entreprise',          'label' => 'Nom de l\'entreprise',          'type' => 'text',     'required' => true],
                ['name' => 'date_depart',         'label' => 'Date de départ souhaitée',      'type' => 'date',     'required' => true],
                ['name' => 'preavis_semaines',    'label' => 'Durée du préavis (semaines)',   'type' => 'number',   'required' => true],
                ['name' => 'motif',               'label' => 'Motif (optionnel)',             'type' => 'textarea', 'required' => false],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Cordial', 'Neutre']],
            ],
            'prompt_template' => "Rédige une lettre de démission professionnelle.

Émetteur : {emetteur_nom}, {emetteur_fonction}
Entreprise : {entreprise}
Destinataire : {destinataire_nom} (DRH ou directeur)
Date de départ souhaitée : {date_depart}
Préavis : {preavis_semaines} semaines
Motif mentionné : {motif}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date
- Destinataire + entreprise
- Objet : Lettre de démission
- Formule d'appel
- Corps : annonce claire de la démission, date effective de départ, respect du préavis, remerciements pour l'expérience acquise
- Formule de politesse
- Signature",
        ],

        'demande-augmentation' => [
            'slug'        => 'demande-augmentation',
            'name'        => 'Demande d\'augmentation',
            'category'    => 'professionnel',
            'icon'        => '📈',
            'description' => 'Argumentez votre demande de revalorisation salariale',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_fonction',   'label' => 'Votre fonction / poste',        'type' => 'text',     'required' => true],
                ['name' => 'destinataire_nom',    'label' => 'Nom du responsable RH/directeur','type'=> 'text',     'required' => true],
                ['name' => 'entreprise',          'label' => 'Nom de l\'entreprise',          'type' => 'text',     'required' => true],
                ['name' => 'anciennete',          'label' => 'Années d\'ancienneté',          'type' => 'number',   'required' => true],
                ['name' => 'salaire_actuel',      'label' => 'Salaire actuel (FCFA)',         'type' => 'number',   'required' => false],
                ['name' => 'augmentation_souhaitee','label'=> 'Augmentation souhaitée (%)',   'type' => 'number',   'required' => false],
                ['name' => 'realisations',        'label' => 'Principales réalisations',      'type' => 'textarea', 'required' => true],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Assertif', 'Cordial']],
            ],
            'prompt_template' => "Rédige une lettre de demande d'augmentation de salaire convaincante.

Émetteur : {emetteur_nom}, {emetteur_fonction}
Entreprise : {entreprise}
Destinataire : {destinataire_nom}
Ancienneté : {anciennete} ans
Salaire actuel : {salaire_actuel} FCFA
Augmentation souhaitée : {augmentation_souhaitee}%
Réalisations clés : {realisations}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date
- Destinataire
- Objet : Demande de revalorisation salariale
- Formule d'appel
- Corps : rappel de l'ancienneté et des responsabilités, mise en avant des réalisations concrètes, demande précise et argumentée
- Formule de politesse
- Signature",
        ],

        'lettre-motivation' => [
            'slug'        => 'lettre-motivation',
            'name'        => 'Lettre de motivation',
            'category'    => 'professionnel',
            'icon'        => '✨',
            'description' => 'Postulez à un emploi avec une lettre percutante et personnalisée',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_profil',     'label' => 'Votre profil / formation',      'type' => 'text',     'required' => true],
                ['name' => 'entreprise',          'label' => 'Entreprise ciblée',             'type' => 'text',     'required' => true],
                ['name' => 'poste_vise',          'label' => 'Poste visé',                    'type' => 'text',     'required' => true],
                ['name' => 'competences_cles',    'label' => 'Compétences clés (3-5)',        'type' => 'textarea', 'required' => true],
                ['name' => 'experience',          'label' => 'Expériences pertinentes',       'type' => 'textarea', 'required' => true],
                ['name' => 'motivation',          'label' => 'Pourquoi cette entreprise ?',   'type' => 'textarea', 'required' => true],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Dynamique', 'Professionnel']],
            ],
            'prompt_template' => "Rédige une lettre de motivation professionnelle et convaincante.

Candidat : {emetteur_nom}, {emetteur_profil}
Entreprise : {entreprise}
Poste visé : {poste_vise}
Compétences clés : {competences_cles}
Expériences : {experience}
Motivation pour l'entreprise : {motivation}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête candidat (nom, coordonnées fictives si non fournies)
- Lieu et date
- Destinataire (Monsieur/Madame le Directeur des Ressources Humaines, {entreprise})
- Objet : Candidature au poste de {poste_vise}
- Formule d'appel
- Paragraphe 1 : accroche et contexte de la candidature
- Paragraphe 2 : compétences et expériences en lien avec le poste
- Paragraphe 3 : motivation pour l'entreprise et valeur ajoutée
- Formule de politesse valorisante
- Signature",
        ],

        'demande-stage' => [
            'slug'        => 'demande-stage',
            'name'        => 'Demande de stage',
            'category'    => 'professionnel',
            'icon'        => '🎓',
            'description' => 'Obtenez un stage académique ou professionnel',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',              'type' => 'text',     'required' => true],
                ['name' => 'formation',           'label' => 'Formation suivie / école',        'type' => 'text',     'required' => true],
                ['name' => 'niveau',              'label' => 'Niveau d\'études (ex: Licence 3)','type' => 'text',     'required' => true],
                ['name' => 'entreprise',          'label' => 'Entreprise ciblée',               'type' => 'text',     'required' => true],
                ['name' => 'domaine',             'label' => 'Domaine / département souhaité',  'type' => 'text',     'required' => true],
                ['name' => 'duree',               'label' => 'Durée du stage',                  'type' => 'text',     'required' => true],
                ['name' => 'date_debut',          'label' => 'Date de début souhaitée',         'type' => 'date',     'required' => true],
                ['name' => 'motivation',          'label' => 'Motivation pour cette entreprise','type' => 'textarea', 'required' => true],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                    'type' => 'select',   'required' => true, 'options' => ['Formel', 'Dynamique']],
            ],
            'prompt_template' => "Rédige une lettre de demande de stage.

Stagiaire : {emetteur_nom}, {niveau} en {formation}
Entreprise : {entreprise}
Domaine / département : {domaine}
Durée : {duree}, à partir du {date_debut}
Motivation : {motivation}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête étudiant
- Lieu et date
- Destinataire (Responsable des Ressources Humaines ou Directeur, {entreprise})
- Objet : Demande de stage en {domaine}
- Formule d'appel
- Corps : présentation cursus et niveau, domaine d'intérêt, motivation pour l'entreprise, disponibilité
- Formule de politesse
- Signature",
        ],

        // ═══════════════════════════════════════
        // ADMINISTRATION (5)
        // ═══════════════════════════════════════

        'demande-logement-social' => [
            'slug'        => 'demande-logement-social',
            'name'        => 'Demande de logement social',
            'category'    => 'administration',
            'icon'        => '🏠',
            'description' => 'Faites une demande de logement social auprès des autorités',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_profession', 'label' => 'Votre profession',              'type' => 'text',     'required' => true],
                ['name' => 'situation_familiale', 'label' => 'Situation familiale',           'type' => 'select',   'required' => true, 'options' => ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf/Veuve']],
                ['name' => 'nb_enfants',          'label' => 'Nombre d\'enfants à charge',   'type' => 'number',   'required' => true],
                ['name' => 'revenu_mensuel',      'label' => 'Revenu mensuel (FCFA)',         'type' => 'number',   'required' => false],
                ['name' => 'situation_actuelle',  'label' => 'Situation de logement actuelle','type' => 'textarea', 'required' => true],
                ['name' => 'commune',             'label' => 'Commune ou zone souhaitée',    'type' => 'text',     'required' => false],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Respectueux']],
            ],
            'prompt_template' => "Rédige une lettre de demande de logement social adressée aux autorités ivoiriennes compétentes.

Demandeur : {emetteur_nom}, {emetteur_profession}
Situation familiale : {situation_familiale}, {nb_enfants} enfant(s) à charge
Revenu mensuel : {revenu_mensuel} FCFA
Situation actuelle : {situation_actuelle}
Zone souhaitée : {commune}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête demandeur
- Lieu et date
- Destinataire : Monsieur le Directeur de l'Office Ivoirien de la Propriété Foncière ou autorité compétente
- Objet : Demande d'attribution de logement social
- Formule d'appel
- Corps : présentation situation personnelle et familiale, situation de logement précaire, justification du besoin, engagements
- Formule de politesse respectueuse
- Signature",
        ],

        'lettre-maire-prefet' => [
            'slug'        => 'lettre-maire-prefet',
            'name'        => 'Lettre au Maire / Préfet',
            'category'    => 'administration',
            'icon'        => '🏛️',
            'description' => 'Adressez une demande ou réclamation à votre Maire ou Préfet',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'destinataire',        'label' => 'Destinataire',                  'type' => 'select',   'required' => true, 'options' => ['Maire', 'Préfet', 'Sous-préfet']],
                ['name' => 'commune',             'label' => 'Commune / département',         'type' => 'text',     'required' => true],
                ['name' => 'objet_demande',       'label' => 'Objet de votre demande',        'type' => 'text',     'required' => true],
                ['name' => 'situation',           'label' => 'Description de la situation',   'type' => 'textarea', 'required' => true],
                ['name' => 'demande_precise',     'label' => 'Ce que vous demandez précisément','type'=> 'textarea', 'required' => true],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Respectueux', 'Ferme']],
            ],
            'prompt_template' => "Rédige une lettre formelle adressée à un {destinataire} de Côte d'Ivoire.

Émetteur : {emetteur_nom}, résidant à {emetteur_adresse}
Destinataire : Monsieur/Madame le/la {destinataire} de {commune}
Objet de la demande : {objet_demande}
Situation décrite : {situation}
Demande précise : {demande_precise}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date
- Destinataire (titre officiel complet)
- Objet : {objet_demande}
- Formule d'appel (Monsieur le Maire / Monsieur le Préfet)
- Corps : contexte, exposé des faits, demande précise et motivée
- Formule de politesse protocolaire ivoirienne
- Signature",
        ],

        'reclamation-facture' => [
            'slug'        => 'reclamation-facture',
            'name'        => 'Réclamation de facture',
            'category'    => 'administration',
            'icon'        => '🧾',
            'description' => 'Contestez une facture auprès de CIE, SODECI, Orange, MTN ou Moov',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'fournisseur',         'label' => 'Fournisseur concerné',          'type' => 'select',   'required' => true, 'options' => ['CIE', 'SODECI', 'Orange CI', 'MTN CI', 'Moov Africa CI', 'Autre']],
                ['name' => 'numero_contrat',      'label' => 'N° de contrat / abonnement',   'type' => 'text',     'required' => true],
                ['name' => 'montant_conteste',    'label' => 'Montant contesté (FCFA)',       'type' => 'number',   'required' => true],
                ['name' => 'motif',               'label' => 'Motif de la réclamation',       'type' => 'textarea', 'required' => true],
                ['name' => 'demande',             'label' => 'Ce que vous demandez',          'type' => 'select',   'required' => true, 'options' => ['Remboursement', 'Rectification de la facture', 'Explication détaillée', 'Délai de paiement']],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Ferme', 'Cordial']],
            ],
            'prompt_template' => "Rédige une lettre de réclamation de facture adressée au service client de {fournisseur}.

Émetteur : {emetteur_nom}, {emetteur_adresse}
Fournisseur : {fournisseur}
N° de contrat / abonnement : {numero_contrat}
Montant contesté : {montant_conteste} FCFA
Motif de la réclamation : {motif}
Demande : {demande}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date
- Destinataire : Le Directeur du Service Client, {fournisseur}
- Objet : Réclamation relative à la facture N° [référence] — Contrat {numero_contrat}
- Formule d'appel
- Corps : rappel des faits (date de réception, montant, anomalie constatée), demande précise avec délai
- Formule de politesse
- Signature",
        ],

        'demande-acte-naissance' => [
            'slug'        => 'demande-acte-naissance',
            'name'        => 'Demande d\'acte de naissance',
            'category'    => 'administration',
            'icon'        => '📋',
            'description' => 'Demandez un acte ou extrait d\'état civil à la mairie',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'type_acte',           'label' => 'Type d\'acte demandé',          'type' => 'select',   'required' => true, 'options' => ['Extrait d\'acte de naissance', 'Copie intégrale d\'acte de naissance', 'Acte de mariage', 'Acte de décès', 'Certificat de nationalité']],
                ['name' => 'concerne_nom',        'label' => 'Nom de la personne concernée',  'type' => 'text',     'required' => true],
                ['name' => 'lieu_naissance',      'label' => 'Lieu de naissance',             'type' => 'text',     'required' => true],
                ['name' => 'date_naissance',      'label' => 'Date de naissance',             'type' => 'date',     'required' => true],
                ['name' => 'usage',               'label' => 'Usage prévu du document',       'type' => 'select',   'required' => true, 'options' => ['Inscription scolaire', 'Emploi', 'Mariage', 'Voyage / passeport', 'Procédure administrative', 'Autre']],
                ['name' => 'nb_copies',           'label' => 'Nombre de copies souhaitées',   'type' => 'number',   'required' => true],
            ],
            'prompt_template' => "Rédige une lettre de demande d'acte d'état civil adressée à une mairie ivoirienne.

Demandeur : {emetteur_nom}, résidant à {emetteur_adresse}
Type d'acte : {type_acte}
Personne concernée : {concerne_nom}
Lieu de naissance : {lieu_naissance}
Date de naissance : {date_naissance}
Usage : {usage}
Nombre de copies : {nb_copies}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête demandeur
- Lieu et date
- Destinataire : Monsieur le Maire de {lieu_naissance} ou l'Officier d'État Civil compétent
- Objet : Demande de {type_acte}
- Formule d'appel
- Corps : identité de la personne concernée, acte demandé, usage, nombre de copies
- Formule de politesse
- Signature",
        ],

        'mise-en-demeure' => [
            'slug'        => 'mise-en-demeure',
            'name'        => 'Mise en demeure',
            'category'    => 'administration',
            'icon'        => '⚖️',
            'description' => 'Mettez formellement en demeure une personne ou société défaillante',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'destinataire',        'label' => 'Nom du destinataire',           'type' => 'text',     'required' => true],
                ['name' => 'destinataire_adresse','label' => 'Adresse du destinataire',       'type' => 'text',     'required' => true],
                ['name' => 'motif',               'label' => 'Motif de la mise en demeure',   'type' => 'textarea', 'required' => true],
                ['name' => 'montant',             'label' => 'Montant dû (FCFA, si applicable)','type' => 'number', 'required' => false],
                ['name' => 'delai_execution',     'label' => 'Délai accordé (jours)',         'type' => 'number',   'required' => true],
                ['name' => 'consequences',        'label' => 'Conséquences en cas d\'inaction','type'=> 'textarea', 'required' => true],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Ferme', 'Formel']],
            ],
            'prompt_template' => "Rédige une mise en demeure formelle selon les conventions juridiques ivoiriennes.

Émetteur : {emetteur_nom}, {emetteur_adresse}
Destinataire : {destinataire}, {destinataire_adresse}
Motif : {motif}
Montant dû : {montant} FCFA
Délai accordé : {delai_execution} jours
Conséquences en cas d'inaction : {consequences}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date (en recommandé avec accusé de réception, le mentionner)
- Destinataire complet
- Objet : MISE EN DEMEURE (en majuscules)
- Formule d'appel
- Corps : rappel des faits et des obligations non respectées, montant ou obligation en jeu, délai précis d'exécution, conséquences (saisine des tribunaux ivoiriens, etc.)
- Mention 'La présente vaut mise en demeure'
- Formule de politesse
- Signature",
        ],

        // ═══════════════════════════════════════
        // PERSONNEL (5)
        // ═══════════════════════════════════════

        'lettre-recommandation' => [
            'slug'        => 'lettre-recommandation',
            'name'        => 'Lettre de recommandation',
            'category'    => 'personnel',
            'icon'        => '⭐',
            'description' => 'Rédigez une recommandation chaleureuse pour un collaborateur ou étudiant',
            'fields'      => [
                ['name' => 'redacteur_nom',       'label' => 'Votre nom complet',              'type' => 'text',     'required' => true],
                ['name' => 'redacteur_fonction',  'label' => 'Votre fonction / titre',         'type' => 'text',     'required' => true],
                ['name' => 'redacteur_structure', 'label' => 'Votre entreprise / organisation','type' => 'text',     'required' => true],
                ['name' => 'recommande_nom',      'label' => 'Nom de la personne recommandée', 'type' => 'text',     'required' => true],
                ['name' => 'recommande_profil',   'label' => 'Profil / rôle de la personne',  'type' => 'text',     'required' => true],
                ['name' => 'duree_collaboration', 'label' => 'Durée de collaboration',        'type' => 'text',     'required' => true],
                ['name' => 'qualites',            'label' => 'Qualités et compétences clés',  'type' => 'textarea', 'required' => true],
                ['name' => 'usage',               'label' => 'Usage de la lettre',            'type' => 'select',   'required' => true, 'options' => ['Candidature emploi', 'Admission formation', 'Demande de visa', 'Usage général']],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Chaleureux', 'Formel', 'Enthousiaste']],
            ],
            'prompt_template' => "Rédige une lettre de recommandation {ton}.

Rédacteur : {redacteur_nom}, {redacteur_fonction} chez {redacteur_structure}
Personne recommandée : {recommande_nom}, {recommande_profil}
Durée de collaboration : {duree_collaboration}
Qualités et compétences : {qualites}
Usage : {usage}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête rédacteur (nom, fonction, structure, coordonnées)
- Lieu et date
- Objet : Lettre de recommandation pour {recommande_nom}
- Corps : présentation du rédacteur, contexte de la collaboration, qualités professionnelles et humaines observées, recommandation sans réserve
- Formule de politesse
- Signature + cachet éventuel",
        ],

        'attestation-honneur' => [
            'slug'        => 'attestation-honneur',
            'name'        => 'Attestation sur l\'honneur',
            'category'    => 'personnel',
            'icon'        => '📜',
            'description' => 'Certifiez officiellement un fait sur l\'honneur',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'emetteur_profession', 'label' => 'Votre profession',              'type' => 'text',     'required' => false],
                ['name' => 'fait_certifie',       'label' => 'Fait certifié sur l\'honneur',  'type' => 'textarea', 'required' => true],
                ['name' => 'usage',               'label' => 'Usage du document',             'type' => 'select',   'required' => true, 'options' => ['Administration', 'Banque', 'Assurance', 'Justice', 'Emploi', 'Autre']],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Solennel']],
            ],
            'prompt_template' => "Rédige une attestation sur l'honneur formelle et solennelle.

Soussigné : {emetteur_nom}, {emetteur_profession}, résidant à {emetteur_adresse}
Fait certifié : {fait_certifie}
Usage : {usage}
Ton : {ton}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- Titre centré : ATTESTATION SUR L'HONNEUR
- Corps : 'Je soussigné(e), {emetteur_nom}, certifie sur l'honneur que...'
- Description précise et claire du fait certifié
- Clause : 'La présente attestation est établie pour servir et valoir ce que de droit'
- Mention de la conscience des conséquences légales d'une fausse déclaration
- Lieu et date
- Signature",
        ],

        'reconnaissance-dette' => [
            'slug'        => 'reconnaissance-dette',
            'name'        => 'Reconnaissance de dette',
            'category'    => 'personnel',
            'icon'        => '💰',
            'description' => 'Formalisez un prêt entre particuliers de façon légalement solide',
            'fields'      => [
                ['name' => 'creancier_nom',       'label' => 'Nom du créancier (prêteur)',    'type' => 'text',     'required' => true],
                ['name' => 'creancier_adresse',   'label' => 'Adresse du créancier',          'type' => 'text',     'required' => true],
                ['name' => 'debiteur_nom',        'label' => 'Nom du débiteur (emprunteur)',  'type' => 'text',     'required' => true],
                ['name' => 'debiteur_adresse',    'label' => 'Adresse du débiteur',           'type' => 'text',     'required' => true],
                ['name' => 'montant',             'label' => 'Montant emprunté (FCFA)',       'type' => 'number',   'required' => true],
                ['name' => 'date_pret',           'label' => 'Date du prêt',                  'type' => 'date',     'required' => true],
                ['name' => 'echeance',            'label' => 'Date de remboursement',         'type' => 'date',     'required' => true],
                ['name' => 'taux_interet',        'label' => 'Taux d\'intérêt % (0 = sans)',  'type' => 'number',   'required' => false],
                ['name' => 'modalites',           'label' => 'Modalités de remboursement',    'type' => 'textarea', 'required' => false],
            ],
            'prompt_template' => "Rédige une reconnaissance de dette formelle entre particuliers, conforme aux usages juridiques ivoiriens.

Créancier : {creancier_nom}, {creancier_adresse}
Débiteur : {debiteur_nom}, {debiteur_adresse}
Montant : {montant} FCFA (en lettres et en chiffres)
Date du prêt : {date_pret}
Échéance de remboursement : {echeance}
Taux d'intérêt : {taux_interet}%
Modalités : {modalites}
Date de rédaction : Abidjan, le {date_jour}

Structure obligatoire :
- Titre centré : RECONNAISSANCE DE DETTE
- Identité complète du débiteur
- Formule 'Je soussigné(e) reconnais devoir à...'
- Montant en chiffres ET en lettres
- Conditions de remboursement (date, taux si applicable)
- Clause d'engagement
- Lieu et date
- Signature débiteur (+ signature créancier pour acceptation)",
        ],

        'lettre-excuses' => [
            'slug'        => 'lettre-excuses',
            'name'        => 'Lettre d\'excuses',
            'category'    => 'personnel',
            'icon'        => '🙏',
            'description' => 'Présentez vos excuses sincères de façon formelle et appropriée',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'destinataire_nom',    'label' => 'Nom du destinataire',           'type' => 'text',     'required' => true],
                ['name' => 'relation',            'label' => 'Nature de la relation',         'type' => 'select',   'required' => true, 'options' => ['Professionnel', 'Client / Partenaire', 'Famille / Proche', 'Voisin', 'Autre']],
                ['name' => 'faits',               'label' => 'Faits à l\'origine des excuses','type' => 'textarea', 'required' => true],
                ['name' => 'engagements',         'label' => 'Engagements pour l\'avenir',   'type' => 'textarea', 'required' => false],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Sincère et formel', 'Humble', 'Professionnel']],
            ],
            'prompt_template' => "Rédige une lettre d'excuses {ton}.

Émetteur : {emetteur_nom}
Destinataire : {destinataire_nom}
Relation : {relation}
Faits : {faits}
Engagements : {engagements}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête émetteur
- Lieu et date
- Destinataire
- Objet : Présentation d'excuses
- Formule d'appel
- Corps : reconnaissance claire des torts, expression de regrets sincères, explication sans justification excessive, engagements concrets
- Formule de politesse adaptée à la relation
- Signature",
        ],

        'demande-bourse' => [
            'slug'        => 'demande-bourse',
            'name'        => 'Demande de bourse',
            'category'    => 'personnel',
            'icon'        => '🎓',
            'description' => 'Postulez à une bourse d\'études nationale ou internationale',
            'fields'      => [
                ['name' => 'emetteur_nom',        'label' => 'Votre nom complet',             'type' => 'text',     'required' => true],
                ['name' => 'emetteur_adresse',    'label' => 'Votre adresse',                 'type' => 'text',     'required' => true],
                ['name' => 'formation_actuelle',  'label' => 'Formation actuelle / niveau',   'type' => 'text',     'required' => true],
                ['name' => 'etablissement',       'label' => 'Établissement actuel',          'type' => 'text',     'required' => true],
                ['name' => 'organisme_bourse',    'label' => 'Organisme octroyant la bourse', 'type' => 'text',     'required' => true],
                ['name' => 'type_bourse',         'label' => 'Type de bourse demandée',       'type' => 'text',     'required' => true],
                ['name' => 'projet_etudes',       'label' => 'Projet d\'études / domaine',    'type' => 'textarea', 'required' => true],
                ['name' => 'situation_financiere','label' => 'Situation financière (optionnel)','type'=> 'textarea', 'required' => false],
                ['name' => 'ton',                 'label' => 'Ton souhaité',                  'type' => 'select',   'required' => true, 'options' => ['Formel', 'Motivé', 'Académique']],
            ],
            'prompt_template' => "Rédige une lettre de demande de bourse {ton}.

Candidat : {emetteur_nom}, {emetteur_adresse}
Formation actuelle : {formation_actuelle} à {etablissement}
Organisme de bourse : {organisme_bourse}
Type de bourse : {type_bourse}
Projet d'études : {projet_etudes}
Situation financière : {situation_financiere}
Lieu et date : Abidjan, le {date_jour}

Structure obligatoire :
- En-tête candidat
- Lieu et date
- Destinataire : Monsieur/Madame le Responsable des Bourses, {organisme_bourse}
- Objet : Demande de bourse — {type_bourse}
- Formule d'appel
- Corps : présentation académique et parcours, projet d'études motivé, impact attendu sur carrière et développement de la Côte d'Ivoire, situation financière si pertinent
- Formule de politesse valorisante
- Signature",
        ],

    ],

];
