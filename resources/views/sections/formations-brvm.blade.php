@extends('layouts.app')

@section('content')
<section class="py-4 bg-white">
    <div class="container">

        {{-- Titre principal --}}
        <h2 class="h2 fw-bold mb-3">
            🎓 Formations BRVM pour monter en niveau
        </h2>

        {{-- Sous-titre --}}
        <p class="lead mb-3">
            En parallèle des analyses IA, tu peux te former sérieusement sur la BRVM :
            cours débutant, cours intermédiaire, exemples concrets, cas pratiques…
        </p>

        {{-- Phrase sur les 2 options --}}
        <div class="alert alert-light border mb-4">
            <div class="fw-semibold mb-1">✅ Deux façons d’acheter (accès à vie dans les deux cas)</div>
            <div class="text-muted">
                • <strong>Sur Udemy</strong> : paiement plutôt par <strong>carte bancaire</strong> (plateforme internationale).<br>
                • <strong>Sur Coach BRVM</strong> : paiement plutôt par <strong>Mobile Money</strong> (Wave, Orange Money, MTN Money, Moov Money).<br>
                <span class="small">Dans les deux cas, l’accès est <strong>à vie</strong> après achat.</span>
            </div>
        </div>

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
                        <h3 class="h4 mb-1">🚀 Niveau Débutant</h3>

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

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="btn btn-primary">
                                Acheter sur Coach BRVM (Mobile Money)
                            </a>

                            <a href="https://www.udemy.com/course/investir-a-la-brvm-le-guide-du-debutant/?couponCode=E71A16F6B15F7654FC27"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-success">
                                Acheter sur Udemy (Carte bancaire)
                            </a>
                        </div>
                    </div>

                    {{-- Vidéo --}}
                    <div class="col-md-4 text-md-end text-center">
                        <video class="img-fluid rounded shadow-sm"
                               style="max-width:260px;"
                               controls preload="metadata">
                            <source src="{{ asset('previews/brvm-debutant-preview.mp4') }}" type="video/mp4">
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

                    <div class="col-md-8">
                        <h3 class="h4 mb-1">📈 Niveau Intermédiaire</h3>

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

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="btn btn-primary">
                                Acheter sur Coach BRVM (Mobile Money)
                            </a>

                            <a href="https://www.udemy.com/course/brvm-strategies-dinvestissement-intermediaire/?couponCode=77B14D32720FB58FCF1C"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-success">
                                Acheter sur Udemy (Carte bancaire)
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4 text-md-end text-center">
                        <video class="img-fluid rounded shadow-sm"
                               style="max-width:260px;"
                               controls preload="metadata">
                            <source src="{{ asset('previews/brvm-intermediare-preview.mp4') }}" type="video/mp4">
                        </video>
                    </div>

                </div>
            </div>
        </div>

        {{-- ============================ --}}
        {{--      BRVM PRATIQUE            --}}
        {{-- ============================ --}}
        <div class="card mb-4 shadow-sm border-primary">
            <div class="card-body">
                <div class="row align-items-start g-3">

                    <div class="col-md-8">
                        <h3 class="h4 mb-1">🧰 BRVM Pratique</h3>

                        <p class="text-success fw-semibold mb-2">
                            « BRVM pratique : outils d’analyse et portefeuille virtuel »
                        </p>

                        <p class="mb-3">
                            Un cours orienté action : outils simples, lecture rapide des infos clés,
                            et surtout simulation via un portefeuille virtuel avant d’investir en réel.
                        </p>

                        <ul class="mb-3">
                            <li>✔ Outils d’analyse concrets</li>
                            <li>✔ Portefeuille virtuel (sans risque)</li>
                            <li>✔ Démo réelle sur la plateforme Coach-BRVM</li>
                        </ul>

                        <p class="mb-2 text-muted">
                            🎬 Ci-contre : <strong>démo / introduction</strong> du cours BRVM pratique.
                        </p>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="btn btn-primary">
                                Acheter sur Coach BRVM (Mobile Money)
                            </a>

                            <a href="https://www.udemy.com/course/brvm-pratique-outils-danalyse-et-portefeuille-virtuel/?referralCode=187E6FB3DA9B0BF308AE"
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-success">
                                Acheter sur Udemy (Carte bancaire)
                            </a>
                        </div>

                        <div class="small text-muted mt-2">
                            💡 Conseil : fais la démo, puis choisis le mode de paiement qui te convient.
                        </div>
                    </div>

                    <div class="col-md-4 text-md-end text-center">
                        <video class="img-fluid rounded shadow-sm"
                               style="max-width:260px;"
                               controls preload="metadata">
                            <source src="{{ asset('previews/brvm-pratique-preview.mp4') }}" type="video/mp4">
                        </video>
                    </div>

                </div>
            </div>
        </div>

    </div>
</section>
@endsection
