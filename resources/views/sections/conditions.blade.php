@extends('layouts.app')

@section('title', 'Conditions d’utilisation – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 900px;">

        <h1 class="fw-bold mb-4">Conditions d’utilisation</h1>

        <p class="text-muted">
            Bienvenue sur Coach BRVM. En utilisant ce site, vous acceptez les conditions ci-dessous.
        </p>

        <h4 class="mt-4">1. Nature du service</h4>
        <p>
            Coach BRVM fournit des analyses automatisées et des interprétations assistées par IA
            concernant les Bulletins Officiels de Cote (BOC) et d'autres documents financiers.
            Les résultats fournis sont des <strong>informations à but éducatif</strong> et ne constituent
            en aucun cas une recommandation d’investissement.
        </p>

        <h4 class="mt-4">2. Absence de conseil financier</h4>
        <p>
            Les contenus proposés ne doivent pas être interprétés comme des conseils en investissement,
            en trading ou en gestion de portefeuille.
            Vous êtes seul responsable de vos décisions financières.
        </p>

        <div class="alert alert-warning mt-3">
            <strong>Disclaimer :</strong> Coach BRVM, ses créateurs et ses partenaires <strong>n’endossent aucune responsabilité</strong>
            en cas de perte financière ou d’interprétation erronée des informations générées.
        </div>

        <h4 class="mt-4">3. Exactitude des données</h4>
        <p>
            Nous faisons de notre mieux pour fournir des analyses fiables, mais nous ne garantissons
            pas l’exactitude, l’exhaustivité ni l’actualité des informations.
        </p>

        <h4 class="mt-4">4. Accès au service</h4>
        <p>
            L’accès aux outils et analyses peut être interrompu en cas de maintenance ou de mise à jour.
        </p>

        <h4 class="mt-4">5. Utilisation interdite</h4>
        <p>
            Vous vous engagez à ne pas utiliser Coach BRVM pour des activités illégales,
            frauduleuses ou contraires aux règles de marché.
        </p>

        <h4 class="mt-4">6. Modification des conditions</h4>
        <p>
            Nous pouvons mettre à jour ces conditions à tout moment. La version affichée sur le site
            constitue la version en vigueur.
        </p>

        <p class="text-muted mt-4">Dernière mise à jour : {{ date('Y') }}</p>

    </div>
</div>
@endsection
