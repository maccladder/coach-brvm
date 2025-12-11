@extends('layouts.app')

@section('title', 'Politique de confidentialité – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 900px;">

        <h1 class="fw-bold mb-4">Politique de confidentialité</h1>

        <p class="text-muted">
            Cette politique décrit comment nous collectons, utilisons et protégeons les données que
            vous nous confiez lors de l’utilisation de Coach BRVM.
        </p>

        <h4 class="mt-4">1. Données collectées</h4>
        <p>
            Nous pouvons collecter des données telles que :
        </p>
        <ul>
            <li>Votre nom et votre adresse e-mail</li>
            <li>Les documents que vous téléchargez (BOC, états financiers…)</li>
            <li>Les logs techniques nécessaires au fonctionnement du site</li>
        </ul>

        <h4 class="mt-4">2. Utilisation des données</h4>
        <p>
            Vos données sont utilisées uniquement pour :
        </p>
        <ul>
            <li>Générer vos analyses IA</li>
            <li>Améliorer la qualité du service</li>
            <li>Vous contacter si nécessaire</li>
        </ul>

        <h4 class="mt-4">3. Partage des données</h4>
        <p>
            Nous ne vendons ni ne partageons vos informations personnelles avec des tiers,
            sauf obligation légale.
        </p>

        <h4 class="mt-4">4. Sécurité des données</h4>
        <p>
            Nous mettons en place des mesures raisonnables pour protéger vos données.
            Cependant, aucune transmission sur Internet n’est totalement sécurisée.
        </p>

        <div class="alert alert-warning mt-3">
            <strong>Important :</strong> Vous restez responsable des documents que vous nous transmettez.
            Coach BRVM ne pourra être tenu responsable d’une mauvaise utilisation des analyses générées.
        </div>

        <h4 class="mt-4">5. Cookies</h4>
        <p>
            Nous utilisons des cookies techniques pour améliorer votre expérience utilisateur.
        </p>

        <h4 class="mt-4">6. Vos droits</h4>
        <p>
            Vous pouvez demander la suppression de vos données en nous contactant à :
            <strong>coachbrvm@gmail.com</strong>.
        </p>

        <p class="text-muted mt-4">Dernière mise à jour : {{ date('Y') }}</p>

    </div>
</div>
@endsection
