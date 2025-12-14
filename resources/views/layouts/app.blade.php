<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>{{ config('app.name', 'Coach BRVM') }}</title>

    {{-- Favicon Coach BRVM --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Style global léger --}}
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
            Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            background-color: #f8f9fa;
        }

        .navbar-brand span.logo-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            margin-right: .35rem;
            background: linear-gradient(135deg, #0d6efd, #20c997);
        }

        table thead th { white-space: nowrap; }

        /* dropdown un peu plus “pro” */
        .dropdown-menu {
            border-radius: 14px;
        }

        /* si demain tu mets beaucoup d’items (ex: sociétés) */
        .dropdown-menu-scroll {
            max-height: 360px;
            overflow: auto;
        }

        .nav-link { font-weight: 500; }
    </style>

    {{-- ✅ Google Analytics (GA4) --}}
    @php
        $gaId = config('services.ga.measurement_id') ?? env('GA_MEASUREMENT_ID');
    @endphp

    @if(!empty($gaId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container" style="max-width: 1100px;">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('landing') }}">
            <span class="logo-dot"></span>
            <span class="fw-semibold">Coach BRVM</span>
        </a>

        {{-- Toggler mobile --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar"
                aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Liens --}}
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                {{-- Accueil --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">Accueil</a>
                </li>

                {{-- Analyses (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Analyses
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('client-bocs.create') }}">
                                📄 Analyser une BOC
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('client-financials.create') }}">
                                📊 Analyser un état financier
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>

                        {{-- placeholders routes futures --}}
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                🎧 Analyses audio (bientôt)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                🎥 Analyses vidéo (bientôt)
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Marché (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Marché
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('radar.index') }}">
                                📡 Radar (7 jours)
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="{{ route('announcements.index') }}">
                                📢 Annonces
                            </a>
                        </li>

                        {{-- placeholders --}}
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                📅 Calendrier AG (bientôt)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                📈 Indices BRVM (bientôt)
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Sociétés (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Sociétés
                    </a>
                    <ul class="dropdown-menu dropdown-menu-scroll">
                        {{-- quand tu crées la route, tu replaces # par route('societes.index') --}}
                        <li>
                            <a class="dropdown-item" href="{{ route('societes.index') }}">
    🏢 Annuaire des sociétés
</a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                🔍 Rechercher une société (bientôt)
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        {{-- Exemples (juste UI pour le moment) --}}
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                AIR LIQUIDE CI (profil bientôt)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                SONATEL SN (profil bientôt)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true">
                                SODECI CI (profil bientôt)
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Formations --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('formations.brvm') }}">Formations</a>
                </li>

                {{-- Contact --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contact') }}">Contact</a>
                </li>
            </ul>

            {{-- Côté droit --}}
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">Beta privée</span>

                {{-- placeholder futur --}}
                <a href="#" class="btn btn-sm btn-outline-primary disabled" tabindex="-1" aria-disabled="true">
                    Se connecter (bientôt)
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- Contenu des pages --}}
@yield('content')

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

</body>
</html>
