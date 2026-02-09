@extends('layouts.app')

@section('content')
<div class="bg-light">

    {{-- HERO --}}
    <section class="py-5 border-bottom bg-white">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge rounded-pill bg-dark-subtle text-dark fw-semibold">Guide pratique</span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">BRVM</span>
                        <span class="badge rounded-pill bg-success-subtle text-success fw-semibold">Coach BRVM</span>
                    </div>

                    <h1 class="fw-bold mb-2" style="font-size: 2.2rem;">
                        Stratégies d’investissement : comprendre, appliquer, éviter les pièges
                    </h1>
                    <p class="text-muted mb-0" style="max-width: 820px;">
                        Ce module résume les grandes techniques utilisées par les investisseurs.
                        Pour chaque stratégie : <strong>principe</strong>, <strong>avantages</strong>, <strong>dangers</strong>,
                        puis <strong>comment Coach BRVM peut t’aider</strong> avec des liens directs vers les outils.
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('radar.index') }}" class="btn btn-dark fw-semibold">
                        📡 Radar marché
                    </a>
                    <a href="{{ route('dividendes.index', ['year' => 2025]) }}" class="btn btn-outline-success fw-semibold">
                        🏆 Dividendes 2025
                    </a>
                    <a href="{{ route('chocs.index') }}" class="btn btn-outline-dark fw-semibold">
                        ⚡ Chocs de marché
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- CONTENU --}}
    <section class="py-5">
        <div class="container" style="max-width:1100px;">

            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <div class="fw-semibold">⚠️ Important</div>
                <div class="small opacity-75">
                    Ce contenu est pédagogique. Il ne constitue pas un conseil d’investissement personnalisé.
                    À la BRVM, la liquidité peut être faible : toujours entrer progressivement et éviter le “tout d’un coup”.
                </div>
            </div>

            <div class="accordion" id="strategiesAccordion">

                {{-- 1) Mean Reversion --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="h1">
                        <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#c1">
                            🧠 Mean Reversion (Retour à la moyenne)
                        </button>
                    </h2>
                    <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#strategiesAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-semibold mb-2">📌 Principe</h6>
                                    <p class="text-muted mb-3">
                                        Une action chute fortement : le marché a parfois <strong>surréagi</strong>.
                                        La stratégie consiste à acheter après une <strong>baisse excessive</strong>,
                                        en pariant sur un <strong>rebond</strong> vers un prix plus “normal”.
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">✅ Avantages</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Très efficace sur marchés calmes (souvent la BRVM)</li>
                                                    <li>Achat à prix cassé</li>
                                                    <li>Bonne stratégie “contrarienne”</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">⚠️ Dangers</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Une action peut rester rouge longtemps</li>
                                                    <li>Si la baisse est fondamentale → piège</li>
                                                    <li>Entrer trop tôt sans confirmation</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-light border rounded-3">
                                        <div class="fw-semibold mb-1">✅ Bon réflexe</div>
                                        <div class="text-muted small">
                                            Entrer en <strong>2–3 fois</strong> (fractionner), et vérifier “pourquoi ça baisse”.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border-0 bg-white shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-2">🔎 Coach BRVM peut t’aider avec :</div>

                                            <div class="d-grid gap-2">
                                                <a class="btn btn-dark fw-semibold" href="https://coach-brvm.com/radar-marche">
                                                    📡 Radar du marché : repérer les bulles rouges
                                                </a>
                                                <a class="btn btn-outline-dark fw-semibold" href="https://coach-brvm.com/chocs-marche">
                                                    ⚡ Chocs de marché : comprendre si c’est un “vrai choc”
                                                </a>
                                                @auth
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('wallet.index') }}">
                                                        💼 Portefeuille virtuel : tester la stratégie sans risque
                                                    </a>
                                                @else
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('login') }}">
                                                        💼 Portefeuille virtuel : se connecter pour tester
                                                    </a>
                                                @endauth
                                            </div>

                                            <hr class="my-3">

                                            <div class="small text-muted">
                                                Astuce : si une bulle rouge est énorme, ce n’est pas “achat automatique”.
                                                Cherche d’abord la cause → puis entrée progressive.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2) Dividend Investing --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="h2">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                            💰 Dividend Investing (Stratégie dividendes)
                        </button>
                    </h2>
                    <div id="c2" class="accordion-collapse collapse" data-bs-parent="#strategiesAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-semibold mb-2">📌 Principe</h6>
                                    <p class="text-muted mb-3">
                                        Investir dans des entreprises qui versent régulièrement des <strong>dividendes</strong>
                                        pour générer un <strong>revenu passif</strong> (souvent annuel).
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">✅ Avantages</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Revenus réguliers</li>
                                                    <li>Moins stressant</li>
                                                    <li>Très adapté au long terme</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">⚠️ Dangers</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Dividende peut baisser / être supprimé</li>
                                                    <li>Rendement “trop beau” parfois trompeur</li>
                                                    <li>Le cours peut baisser malgré le dividende</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-light border rounded-3">
                                        <div class="fw-semibold mb-1">✅ Bon réflexe</div>
                                        <div class="text-muted small">
                                            Regarder l’<strong>historique</strong> (régularité) plutôt qu’une seule année.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border-0 bg-white shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-2">🔎 Coach BRVM peut t’aider avec :</div>
                                            <div class="d-grid gap-2">
                                                <a class="btn btn-success fw-semibold" href="https://coach-brvm.com/dividendes?year=2025">
                                                    🏆 Classement par dividendes (2025)
                                                </a>
                                                <a class="btn btn-outline-dark fw-semibold" href="{{ route('societes.index') }}">
                                                    🏢 Annuaire sociétés : choisir des entreprises solides
                                                </a>
                                                @auth
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('wallet.index') }}">
                                                        💼 Portefeuille virtuel : simuler “revenu dividendes”
                                                    </a>
                                                @else
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('login') }}">
                                                        💼 Portefeuille virtuel : se connecter
                                                    </a>
                                                @endauth
                                            </div>
                                            <hr class="my-3">
                                            <div class="small text-muted">
                                                Astuce : un bon investisseur dividendes regarde la stabilité + la capacité à continuer de payer.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3) Trend Following --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="h3">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                            📈 Trend Following (Suivre la tendance)
                        </button>
                    </h2>
                    <div id="c3" class="accordion-collapse collapse" data-bs-parent="#strategiesAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-semibold mb-2">📌 Principe</h6>
                                    <p class="text-muted mb-3">
                                        Acheter ce qui <strong>monte déjà</strong> (bulles vertes fortes),
                                        et éviter ce qui baisse. Objectif : surfer la vague.
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">✅ Avantages</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Simple à appliquer</li>
                                                    <li>Profite des phases de hausse</li>
                                                    <li>Psychologiquement “confortable”</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">⚠️ Dangers</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Risque d’acheter trop tard</li>
                                                    <li>Retour violent si la tendance se casse</li>
                                                    <li>Besoin de règles de sortie (discipline)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-light border rounded-3">
                                        <div class="fw-semibold mb-1">✅ Bon réflexe</div>
                                        <div class="text-muted small">
                                            Ne jamais acheter une bulle verte “juste parce qu’elle est verte”.
                                            Chercher confirmation sur plusieurs jours + entrée progressive.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border-0 bg-white shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-2">🔎 Coach BRVM peut t’aider avec :</div>
                                            <div class="d-grid gap-2">
                                                <a class="btn btn-dark fw-semibold" href="https://coach-brvm.com/radar-marche">
                                                    📡 Radar : repérer les bulles vertes dominantes
                                                </a>
                                                <a class="btn btn-outline-dark fw-semibold" href="https://coach-brvm.com/chocs-marche">
                                                    ⚡ Chocs : éviter les “fausses” hausses
                                                </a>
                                                @auth
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('wallet.index') }}">
                                                        💼 Portefeuille virtuel : tester des règles d’entrée/sortie
                                                    </a>
                                                @else
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('login') }}">
                                                        💼 Portefeuille virtuel : se connecter
                                                    </a>
                                                @endauth
                                            </div>
                                            <hr class="my-3">
                                            <div class="small text-muted">
                                                Astuce : sur BRVM, la liquidité compte. Attention aux hausses “vides” (sans volume).
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4) Buy & Hold --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="h4">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#c4">
                            🧱 Buy & Hold (Acheter et conserver)
                        </button>
                    </h2>
                    <div id="c4" class="accordion-collapse collapse" data-bs-parent="#strategiesAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-semibold mb-2">📌 Principe</h6>
                                    <p class="text-muted mb-3">
                                        Acheter des entreprises solides et les conserver longtemps
                                        (années), sans se laisser perturber par les variations courtes.
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">✅ Avantages</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Simple & efficace sur long terme</li>
                                                    <li>Moins de stress</li>
                                                    <li>Moins d’erreurs émotionnelles</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">⚠️ Dangers</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Si mauvaise entreprise → erreur longue</li>
                                                    <li>Capital immobilisé</li>
                                                    <li>Ignorer les signaux de dégradation</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border-0 bg-white shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-2">🔎 Coach BRVM peut t’aider avec :</div>
                                            <div class="d-grid gap-2">
                                                <a class="btn btn-outline-dark fw-semibold" href="{{ route('societes.index') }}">
                                                    🏢 Annuaire : choisir des entreprises de qualité
                                                </a>
                                                <a class="btn btn-outline-secondary fw-semibold" href="{{ route('announcements.index') }}">
                                                    📢 Annonces : suivre les événements importants
                                                </a>
                                                @auth
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('wallet.index') }}">
                                                        💼 Portefeuille virtuel : simuler une stratégie long terme
                                                    </a>
                                                @else
                                                    <a class="btn btn-outline-primary fw-semibold" href="{{ route('login') }}">
                                                        💼 Portefeuille virtuel : se connecter
                                                    </a>
                                                @endauth
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5) Sector Rotation --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="h5">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#c5">
                            🧩 Sector Rotation (Rotation sectorielle)
                        </button>
                    </h2>
                    <div id="c5" class="accordion-collapse collapse" data-bs-parent="#strategiesAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-semibold mb-2">📌 Principe</h6>
                                    <p class="text-muted mb-3">
                                        Investir selon les secteurs qui deviennent attractifs
                                        (banques, agro, industrie, services…) en fonction du contexte.
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">✅ Avantages</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Diversification “intelligente”</li>
                                                    <li>Approche macro / stratégique</li>
                                                    <li>Permet d’anticiper des cycles</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded-3 h-100">
                                                <div class="fw-semibold">⚠️ Dangers</div>
                                                <ul class="text-muted small mb-0">
                                                    <li>Mauvais timing sectoriel</li>
                                                    <li>Changer trop souvent</li>
                                                    <li>Se baser sur “bruit” et rumeurs</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="card border-0 bg-white shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-2">🔎 Coach BRVM peut t’aider avec :</div>
                                            <div class="d-grid gap-2">
                                                <a class="btn btn-dark fw-semibold" href="https://coach-brvm.com/radar-marche">
                                                    📡 Radar : voir rapidement quels secteurs dominent
                                                </a>
                                                <a class="btn btn-outline-dark fw-semibold" href="https://coach-brvm.com/chocs-marche">
                                                    ⚡ Chocs : comprendre les montées/chutes par secteur
                                                </a>
                                                <a class="btn btn-outline-secondary fw-semibold" href="{{ route('announcements.index') }}">
                                                    📢 Annonces : suivre les infos sectorielles
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /accordion --}}

            {{-- CTA final --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">🚀 Prêt à appliquer ?</div>
                        <div class="text-muted small">
                            Commence par observer le marché en un coup d’œil, puis teste ta stratégie en simulation.
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="https://coach-brvm.com/radar-marche" class="btn btn-dark fw-semibold">📡 Radar</a>
                        <a href="https://coach-brvm.com/chocs-marche" class="btn btn-outline-dark fw-semibold">⚡ Chocs</a>
                        @auth
                            <a href="{{ route('wallet.index') }}" class="btn btn-outline-primary fw-semibold">💼 Portefeuille virtuel</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary fw-semibold">💼 Se connecter</a>
                        @endauth
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>
@endsection
