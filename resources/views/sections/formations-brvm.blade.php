@extends('layouts.app')

@section('content')
<section class="py-4 bg-white">
    <div class="container">

        {{-- Titre principal --}}
        <h2 class="h2 fw-bold mb-3">
            Formations BRVM pour monter en niveau
        </h2>

        {{-- Sous-titre --}}
        <p class="lead mb-3">
            En parallèle des analyses IA, tu peux te former sérieusement sur la BRVM :
            cours débutant, cours intermédiaire, exemples concrets, cas pratiques…
        </p>

        {{-- Phrase sur Udemy --}}
        <p class="mb-4">
            Les formations complètes sont hébergées sur <strong>Udemy</strong>, l’une des plateformes
            d’autoformation les plus connues et sécurisées au monde. Ici tu peux visionner un
            <strong>aperçu d’environ 3 minutes</strong> avant de décider d’acheter le cours complet.
        </p>

        {{-- Points clés --}}
        <ul class="mb-4">
            <li>Cours disponibles 24h/24, à vie une fois achetés</li>
            <li>Accès depuis ton smartphone, ton PC ou ta tablette</li>
            <li>Adaptés aux débutants comme à ceux qui veulent aller plus loin</li>
        </ul>

        {{-- ===================== --}}
        {{--      COURS DÉBUTANT   --}}
        {{-- ===================== --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-start g-3">

                    {{-- Texte --}}
                    <div class="col-md-8">
                        <h3 class="h4 mb-1">
                            🚀 Niveau Débutant
                        </h3>

                        <p class="text-success fw-semibold mb-2">
                            « Investir à la BRVM – Le guide du débutant »
                        </p>

                        <p class="mb-3">
                            Idéal si tu démarres de zéro : comprendre la BRVM, ouvrir ton compte-titres,
                            passer tes premiers ordres en toute confiance.
                        </p>

                        <ul class="mb-3">
                            <li>✔ Durée ~ 1h de vidéo</li>
                            <li>✔ Explications simples, exemples concrets</li>
                            <li>✔ Parfait pour éviter les erreurs de débutant</li>
                        </ul>

                        <p class="mb-2 text-muted">
                            🎬 Ci-contre : <strong>aperçu d’environ 3 minutes</strong> du cours débutant.
                        </p>

                        <a href="https://www.udemy.com/course/investir-a-la-brvm-le-guide-du-debutant/?couponCode=296C61F0DE9D77E3EC8B"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-success">
                            Acheter le cours complet sur Udemy
                        </a>
                    </div>

                    {{-- Vidéo à droite --}}
                    <div class="col-md-4 text-md-end text-center">
                        <video
                            class="img-fluid rounded shadow-sm"
                            style="max-width: 260px;"
                            controls
                            preload="metadata">
                            <source src="{{ asset('previews/brvm-debutant-preview.mp4') }}" type="video/mp4">
                            Ton navigateur ne supporte pas la lecture vidéo.
                        </video>
                    </div>

                </div>
            </div>
        </div>

        {{-- ============================ --}}
        {{--      COURS INTERMÉDIAIRE     --}}
        {{-- ============================ --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row align-items-start g-3">

                    {{-- Texte --}}
                    <div class="col-md-8">
                        <h3 class="h4 mb-1">
                            📈 Niveau Intermédiaire
                        </h3>

                        <p class="text-success fw-semibold mb-2">
                            « BRVM – Stratégies d’investissement intermédiaire »
                        </p>

                        <p class="mb-3">
                            Pour passer au niveau supérieur : analyse plus poussée, stratégies,
                            gestion du risque et construction de portefeuille.
                        </p>

                        <ul class="mb-3">
                            <li>✔ Durée ~ 2h de vidéo</li>
                            <li>✔ Stratégies concrètes &amp; cas pratiques</li>
                            <li>✔ Complément parfait du cours débutant</li>
                        </ul>

                        <p class="mb-2 text-muted">
                            🎬 Ci-contre : <strong>aperçu d’environ 3 minutes</strong> du cours intermédiaire.
                        </p>

                        <a href="https://www.udemy.com/course/brvm-strategies-dinvestissement-intermediaire/?couponCode=77B14D32720FB58FCF1C"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-success">
                            Acheter le cours complet sur Udemy
                        </a>
                    </div>

                    {{-- Vidéo à droite --}}
                    <div class="col-md-4 text-md-end text-center">
                        <video
                            class="img-fluid rounded shadow-sm"
                            style="max-width: 260px;"
                            controls
                            preload="metadata">
                            <source src="{{ asset('previews/brvm-intermediare-preview.mp4') }}" type="video/mp4">
                            Ton navigateur ne supporte pas la lecture vidéo.
                        </video>
                    </div>

                </div>
            </div>
        </div>

        {{-- Bouton global --}}
        {{-- <div class="mt-3">
            <a href="{{ route('formations.brvm') }}"
               class="btn btn-primary rounded-pill">
                🎓 Voir toutes les formations BRVM
            </a>
        </div> --}}

    </div>
</section>
@endsection
