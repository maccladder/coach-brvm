{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="bg-light">

    {{-- ✅ INTRO COACH BRVM (NOUVELLE SECTION AVANT LE HERO IA) --}}
    <section class="py-5 py-lg-6 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="row g-4 align-items-center">

                <div class="col-lg-7">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill bg-dark-subtle text-dark fw-semibold">Plateforme BRVM</span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">Outils + Data</span>
                        <span class="badge rounded-pill bg-success-subtle text-success fw-semibold">Formation + Simulation</span>
                        <span class="badge rounded-pill bg-warning-subtle text-warning fw-semibold">Marketplace</span>
                    </div>

                    <h1 class="fw-bold mb-3" style="font-size: 2.4rem;">
                        Coach BRVM – tout pour apprendre, suivre et progresser à la BRVM.
                    </h1>

                    <p class="lead text-muted mb-4">
                        Coach BRVM n’est pas seulement une IA qui lit les BOC.
                        C’est une plateforme qui regroupe <strong>toutes les infos utiles BRVM</strong> :
                        annonces, radar marché, annuaires sociétés/SGI, <strong>formations</strong>,
                        un <strong>portefeuille virtuel</strong> pour s’entraîner (sans risque),
                        et une <strong>Marketplace</strong> pour acheter (et bientôt vendre) des contenus.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ route('radar.index') }}" class="btn btn-dark btn-lg">
                            📡 Explorer le marché
                        </a>

                        <a href="{{ route('marketplace.index') }}" class="btn btn-warning btn-lg">
                            🛍️ Ouvrir la Marketplace
                        </a>

                        <a href="{{ route('formations.brvm') }}" class="btn btn-outline-success btn-lg">
                            🎓 Voir les formations
                        </a>

                        @auth
                            <a href="{{ route('wallet.index') }}" class="btn btn-outline-primary btn-lg">
                                💼 Tester le portefeuille virtuel
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">
                                💼 Portefeuille virtuel (se connecter)
                            </a>
                        @endauth
                    </div>

                    {{-- ✅ MARKETPLACE --}}
                    <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">🛍️ Marketplace Coach BRVM</div>
                            <div class="small opacity-75">
                                Achète des <strong>livres (PDF)</strong>, <strong>vidéos</strong> et <strong>logiciels</strong> en quelques secondes.
                                <span class="d-block">Paiement sécurisé par <strong>Mobile Money</strong>.</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('marketplace.index') }}" class="btn btn-sm btn-dark fw-semibold">
                                Découvrir →
                            </a>
                            @auth
                                <a href="{{ route('my.products') }}" class="btn btn-sm btn-outline-dark fw-semibold">
                                    Mes achats
                                </a>
                            @endauth
                        </div>
                    </div>

                    {{-- ✅ MINI-COURS EN LIVRE --}}
                    <div class="alert alert-primary border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">📚 Mini-cours en livre (gratuit)</div>
                            <div class="small opacity-75">
                                Apprends des notions BRVM rapidement, page par page, comme un vrai livre.
                            </div>
                        </div>
                        <a href="{{ route('books.index') }}" class="btn btn-sm btn-dark fw-semibold">
                            Ouvrir les livres →
                        </a>
                    </div>

                    <div class="text-muted small">
                        <span class="me-3">✅ Infos & outils BRVM au même endroit</span>
                        <span class="me-3">✅ Formations pour monter en niveau</span>
                        <span class="me-3">✅ Simulation via portefeuille virtuel</span>
                        <span>✅ Marketplace de contenus</span>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Ce que tu peux faire sur Coach BRVM</div>

                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">📢 Annonces BRVM</div>
                                        <div class="text-muted small">Communiqués, AG, infos importantes</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Gratuit</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">📡 Radar Marché</div>
                                        <div class="text-muted small">Performance 7 jours + comparaison</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Gratuit</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">📚 Livres instructifs</div>
                                        <div class="text-muted small">Mini-cours rapides en mode “livre”</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Gratuit</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">🛍️ Marketplace</div>
                                        <div class="text-muted small">PDF, vidéos, logiciels (achat instantané)</div>
                                    </div>
                                    <span class="badge bg-warning-subtle text-warning border">Nouveau</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">🎓 Formations BRVM</div>
                                        <div class="text-muted small">Débutant → Intermédiaire</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Payant</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">💼 Portefeuille virtuel</div>
                                        <div class="text-muted small">Achat/vente en simulation</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Beta</span>
                                </div>

                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold">🤖 IA (BOC & États financiers)</div>
                                        <div class="text-muted small">Analyse texte / audio / vidéo</div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border">IA</span>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-3 mb-0 small">
                                👉 La <strong>dernière BOC disponible (J-1)</strong> est maintenant <strong>gratuite</strong> et visible directement depuis l’accueil.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- HERO --}}
    <section class="py-5 py-lg-6 border-bottom" style="background: radial-gradient(circle at top left, #0d6efd15, #ffffff);">
        <div class="container" style="max-width: 1100px;">
            <div class="row align-items-center g-4">

                {{-- Texte --}}
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">
                            IA & Bourse Ouest-Africaine
                        </span>
                        <span class="badge rounded-pill bg-success-subtle text-success fw-semibold">
                            Gratuit • BOC (J-1)
                        </span>
                    </div>

                    <h1 class="fw-bold mb-3" style="font-size: 2.4rem;">
                        Coach BRVM – l’IA qui résume la dernière BOC disponible (J-1) gratuitement.
                    </h1>

                    <p class="lead text-muted mb-4">
                        Marre de décortiquer seul les <strong>BOC</strong> ?
                        Coach BRVM publie une <strong>interprétation gratuite</strong> de la dernière BOC disponible (J-1) :
                        résumé, points clés, tendances et audio/vidéo (si dispo).
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-3">
                        @if(!empty($latestPublicBoc))
                            <a href="{{ route('client-bocs.latest.public') }}" class="btn btn-primary btn-lg">
                                📄 Voir la BOC du {{ \Carbon\Carbon::parse($latestPublicBoc->boc_date)->format('d/m/Y') }} (J-1)
                            </a>
                        @else
                            <a href="{{ route('landing') }}" class="btn btn-primary btn-lg">
                                📄 Dernière BOC (J-1) bientôt disponible
                            </a>
                        @endif

                        <a href="{{ route('client-financials.create') }}" class="btn btn-outline-secondary btn-lg">
                            📊 Analyser un état financier
                        </a>

                        <a href="{{ route('marketplace.index') }}" class="btn btn-outline-dark btn-lg">
                            🛍️ Marketplace
                        </a>
                    </div>

                    <div class="text-muted small">
                        <span class="me-3">✅ BOC (J-1) : gratuit</span>
                        <span class="me-3">✅ Résumé pédagogique</span>
                        <span>✅ Audio + avatar vidéo (si disponible)</span>
                    </div>

                    <div class="mt-3 small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        La BRVM ne publie pas de BOC le jour même : la dernière BOC disponible est celle de <strong>J-1</strong>.
                    </div>
                </div>

                {{-- Visuel --}}
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ asset('avatars/coach.png') }}"
                                     alt="Coach BRVM"
                                     class="rounded-circle border shadow-sm me-3"
                                     style="width:58px;height:58px;object-fit:cover;">
                                <div>
                                    <div class="fw-semibold">Coach BRVM</div>
                                    <div class="text-muted small">Interprétation IA</div>
                                </div>
                            </div>

                            <div class="ratio ratio-16x9 rounded mb-3" style="background:#000;">
                                <video
                                    src="{{ $exampleVideoUrl ?? '' }}"
                                    poster="{{ asset('img/mock-video-poster.png') }}"
                                    style="width:100%;border-radius:12px;object-fit:cover;"
                                    muted
                                ></video>
                            </div>

                            <div class="border rounded-3 overflow-hidden mb-2">
                                <img
                                    src="{{ asset('img/boc-exemple.png') }}"
                                    alt="Exemple de Bulletin Officiel de la Côte (BOC)"
                                    class="img-fluid"
                                    style="max-height:260px;object-fit:cover;width:100%;">
                            </div>

                            <p class="small text-muted mb-2">
                                Exemple de <strong>BOC</strong> tel que publié par la BRVM.
                                Coach BRVM analyse précisément ce format de PDF (une BOC par jour).
                            </p>

                            <div class="d-flex align-items-center small text-muted">
                                <span class="me-2">Compatible :</span>
                                <span class="badge bg-light text-dark border me-1">Ordinateur</span>
                                <span class="badge bg-light text-dark border me-1">Mobile</span>
                                <span class="badge bg-light text-dark border">Tablette</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-4 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('img/coach-brvm-logo.png') }}"
                         alt="BRVM"
                         style="height:32px;width:auto;">
                    <span class="small text-muted">
                        Coach BRVM est un service indépendant, non affilié officiellement à la BRVM.
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- ✅ CALLOUT : Marketplace --}}
    <section class="py-4 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">🛍️ Marketplace Coach BRVM</div>
                        <div class="text-muted small">
                            PDF, vidéos, logiciels – achat instantané. Accès dans “Mes achats” après paiement.
                        </div>
                    </div>

                    <a href="{{ route('marketplace.index') }}" class="btn btn-warning fw-semibold">
                        Ouvrir la Marketplace
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ✅ CALLOUT : Radar Marché gratuit --}}
    <section class="py-4 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">🔥 Radar Marché gratuit</div>
                        <div class="text-muted small">
                            Performance sur 7 jours + comparaison des sociétés BRVM en un coup d’œil.
                        </div>
                    </div>

                    <a href="{{ route('radar.index') }}" class="btn btn-primary fw-semibold">
                        📡 Ouvrir le Radar Marché
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">⚡ Chocs de marché (par secteur)</div>
                        <div class="text-muted small">
                            Comprends pourquoi une action BRVM peut monter ou chuter subitement, avec des exemples.
                        </div>
                    </div>
                    <a href="{{ route('chocs.index') }}" class="btn btn-outline-dark fw-semibold">
                        Explorer →
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ✅ Module stratégies --}}
    <section class="py-4 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">🧠 Techniques & stratégies d’investissement (BRVM)</div>
                        <div class="text-muted small">
                            Mean reversion, dividendes, suivi de tendance… avec les dangers à éviter
                            et les outils Coach BRVM pour appliquer.
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('aide.strategies') }}" class="btn btn-primary fw-semibold">
                            Ouvrir le module →
                        </a>
                        <a href="https://coach-brvm.com/radar-marche" class="btn btn-outline-dark fw-semibold">
                            📡 Radar
                        </a>
                        <a href="https://coach-brvm.com/dividendes?year=2025" class="btn btn-outline-success fw-semibold">
                            🏆 Dividendes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ✅ ANNONCES BRVM --}}
    <section class="py-5">
        <div class="container" style="max-width: 1100px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="fw-semibold mb-1">📢 Annonces BRVM</h2>
                    <p class="text-muted mb-0">
                        Calendrier des assemblées générales, communiqués, infos importantes.
                    </p>
                </div>

                <a href="{{ route('announcements.index') }}" class="btn btn-outline-primary">
                    Voir toutes les annonces
                </a>
            </div>

            <div class="row g-3">
                @forelse(($annonces ?? []) as $a)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $a->title ?? 'Annonce' }}</div>
                                        <div class="small text-muted">
                                            {{ optional($a->published_at ?? $a->created_at)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    @if(!empty($a->tag))
                                        <span class="badge text-bg-light border">{{ $a->tag }}</span>
                                    @endif
                                </div>

                                @if(!empty($a->excerpt))
                                    <p class="text-muted small mt-2 mb-0">
                                        {{ $a->excerpt }}
                                    </p>
                                @elseif(!empty($a->content))
                                    <p class="text-muted small mt-2 mb-0">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($a->content), 140) }}
                                    </p>
                                @endif

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <a href="{{ route('announcements.show', $a->id) }}" class="btn btn-sm btn-primary">
                                        Lire
                                    </a>

                                    @if(!empty($a->pdf_path))
                                        <a href="{{ asset('storage/'.$a->pdf_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            📄 PDF
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border shadow-sm mb-0">
                            Aucune annonce pour le moment.
                            <span class="text-muted">Les communiqués BRVM apparaîtront ici dès publication.</span>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- COMMENT ÇA MARCHE --}}
    <section class="py-5 bg-white border-top">
        <div class="container" style="max-width: 1100px;">
            <div class="text-center mb-4">
                <h2 class="fw-semibold mb-2">Comment ça marche&nbsp;?</h2>
                <p class="text-muted mb-0">
                    La <strong>dernière BOC disponible (J-1)</strong> est publiée gratuitement. Ensuite, tu peux aller plus loin avec les états financiers.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">1️⃣</div>
                            <h5 class="fw-semibold mb-2">Voir la BOC (J-1)</h5>
                            <p class="text-muted small mb-0">
                                Un clic et tu lis l’interprétation gratuite de la dernière BOC disponible (J-1).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">2️⃣</div>
                            <h5 class="fw-semibold mb-2">Comprendre les mouvements</h5>
                            <p class="text-muted small mb-0">
                                Résumé clair, points clés, et parfois audio/vidéo pour mieux retenir.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">3️⃣</div>
                            <h5 class="fw-semibold mb-2">Aller plus loin (optionnel)</h5>
                            <p class="text-muted small mb-0">
                                Analyse d’états financiers, formations, portefeuille virtuel, marketplace…
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="alert alert-primary border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0">
                    <div class="fw-semibold">
                        📄 La dernière BOC disponible (J-1) est gratuite : consulte-la et partage-la à tes amis investisseurs.
                    </div>
                    @if(!empty($latestPublicBoc))
                        <a href="{{ route('client-bocs.latest.public') }}" class="btn btn-sm btn-dark fw-semibold">
                            Voir la BOC (J-1) →
                        </a>
                    @else
                        <a href="{{ route('landing') }}" class="btn btn-sm btn-dark fw-semibold">
                            BOC (J-1) bientôt →
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- LES DEUX SERVICES --}}
    <section class="py-5 bg-white border-top">
        <div class="container" style="max-width: 1100px;">
            <div class="text-center mb-4">
                <h2 class="fw-semibold mb-2">Deux services pour les investisseurs BRVM</h2>
                <p class="text-muted mb-0">
                    Commence gratuit avec la BOC (J-1), puis passe aux états financiers complets si besoin.
                </p>
            </div>

            <div class="row g-4">
                {{-- BOC --}}
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-semibold mb-1">📄 Dernière BOC disponible (J-1) — gratuit</h5>
                            <p class="text-muted small mb-2">
                                Idéal pour suivre rapidement le marché et les mouvements récents.
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>Interprétation gratuite de la dernière BOC disponible (J-1)</li>
                                <li>Résumé clair en français simple</li>
                                <li>Audio + avatar vidéo (si disponible)</li>
                                <li>Partage facile</li>
                            </ul>

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">0&nbsp;FCFA</div>
                                    <div class="text-muted small">accès gratuit</div>
                                </div>

                                @if(!empty($latestPublicBoc))
                                    <a href="{{ route('client-bocs.latest.public') }}" class="btn btn-outline-primary">
                                        Voir la BOC (J-1)
                                    </a>
                                @else
                                    <a href="{{ route('landing') }}" class="btn btn-outline-primary">
                                        BOC (J-1) bientôt disponible
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- État financier --}}
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-semibold mb-1">📊 Analyse d’un état financier</h5>
                            <p class="text-muted small mb-2">
                                Pour comprendre en profondeur une entreprise cotée : compte de résultat,
                                bilan, cash-flow…
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>Décodage des chiffres clés</li>
                                <li>Points forts / points de vigilance</li>
                                <li>Résumé orienté investisseur de long terme</li>
                                <li>Analyse pédagogique (pas de conseil personnalisé)</li>
                            </ul>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">Payant</div>
                                    <div class="text-muted small">selon le service</div>
                                </div>
                                <a href="{{ route('client-financials.create') }}" class="btn btn-outline-secondary">
                                    Analyser un état financier
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA Marketplace --}}
            <div class="mt-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-semibold">🛍️ Contenus premium & outils</div>
                            <div class="text-muted small">
                                Retrouve des livres PDF, vidéos et logiciels utiles pour progresser sur la BRVM.
                            </div>
                        </div>
                        <a href="{{ route('marketplace.index') }}" class="btn btn-warning fw-semibold">
                            Découvrir la Marketplace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION FORMATIONS --}}
    <section class="py-5 bg-light">
        <div class="container" style="max-width: 1100px;">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-semibold mb-2">Formations BRVM pour monter en niveau</h2>
                    <p class="text-muted mb-3">
                        En parallèle de la BOC (J-1), tu peux te former sérieusement sur la BRVM :
                        cours débutant, cours intermédiaire, exemples concrets, cas pratiques…
                    </p>
                    <ul class="text-muted small mb-3">
                        <li>Cours disponibles 24h/24, à vie une fois achetés</li>
                        <li>Accès depuis ton smartphone, ton PC ou ta tablette</li>
                        <li>Adaptés aux débutants comme à ceux qui veulent aller plus loin</li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('formations.brvm') }}" class="btn btn-success">
                            🎓 Voir les formations BRVM
                        </a>
                        <a href="{{ route('marketplace.index') }}" class="btn btn-outline-dark">
                            🛍️ Marketplace
                        </a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">Exemple</h6>
                            <h5 class="fw-semibold mb-2">« Investir à la BRVM – Guide du débutant »</h5>
                            <p class="small text-muted mb-2">
                                Comprendre les bases de la bourse régionale, ouvrir un compte-titres,
                                placer tes premiers ordres en limitant les erreurs classiques.
                            </p>
                            <div class="d-flex flex-wrap gap-2 small text-muted">
                                <span class="badge bg-light border">Vidéo HD</span>
                                <span class="badge bg-light border">Cas pratiques</span>
                                <span class="badge bg-light border">Mises à jour</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-4 border-top bg-white">
        <div class="container" style="max-width: 1100px;">
            <div class="row g-3 align-items-center">

                {{-- Left --}}
                <div class="col-lg-5">
                    <div class="small text-muted">
                        © {{ date('Y') }} Coach BRVM – Une solution de CHENGGONG SARL.
                    </div>

                    <div class="mt-2 small text-muted">
                        <span class="me-2">Suivre Coach BRVM :</span>

                        <a href="https://t.me/coachbrvm" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted me-3">
                            <i class="bi bi-telegram"></i> Telegram
                        </a>

                        <a href="https://x.com/coachbrvm?s=21" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted me-3">
                            <i class="bi bi-twitter-x"></i> X
                        </a>

                        <a href="https://youtube.com/@coachbrvm?si=gW0gTPH_CP4p41ZP" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted me-3">
                            <i class="bi bi-youtube"></i> YouTube
                        </a>

                        <a href="https://www.linkedin.com/company/coach-brvm/" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted me-3">
                            <i class="bi bi-linkedin"></i> LinkedIn
                        </a>

                        <a href="https://chat.whatsapp.com/JOz4th9OnLnJSFcABUFHPI" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted me-3">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>

                        <a href="https://www.facebook.com/share/17q4KouHax/" target="_blank" rel="noopener"
                           class="text-decoration-none text-muted">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                    </div>
                </div>

                {{-- Right --}}
                <div class="col-lg-7">
                    <div class="small text-muted d-flex flex-wrap justify-content-lg-end gap-3">
                        <a href="{{ route('notre.histoire') }}" class="text-decoration-none text-muted">Notre histoire</a>
                        <a href="{{ route('conditions') }}" class="text-decoration-none text-muted">Conditions d’utilisation</a>
                        <a href="{{ route('confidentialite') }}" class="text-decoration-none text-muted">Confidentialité</a>
                        <a href="{{ route('contact') }}" class="text-decoration-none text-muted">Contact</a>
                    </div>
                </div>

            </div>
        </div>
    </footer>

</div>
@endsection
