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

    {{-- ✅ Bootstrap Icons (AJOUT ICI) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

        .dropdown-menu { border-radius: 14px; }

        .dropdown-menu-scroll {
            max-height: 360px;
            overflow: auto;
        }

        .nav-link { font-weight: 500; }
    </style>

    {{-- Styles spécifiques pages --}}
    @stack('styles')

    {{-- Google Analytics --}}
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
                data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">Accueil</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
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
                        <li><a class="dropdown-item disabled">🎧 Analyses audio (bientôt)</a></li>
                        <li><a class="dropdown-item disabled">🎥 Analyses vidéo (bientôt)</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
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

                        <li>
  <a class="dropdown-item" href="{{ route('chocs.index') }}">
      ⚡ Chocs de marché (par secteur)
  </a>
</li>
                        {{-- <li><a class="dropdown-item disabled">📅 Calendrier AG (bientôt)</a></li>
                        <li><a class="dropdown-item disabled">📈 Indices BRVM (bientôt)</a></li> --}}
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Sociétés
                    </a>
                    <ul class="dropdown-menu dropdown-menu-scroll">
                        <li>
                            <a class="dropdown-item" href="{{ route('societes.index') }}">
                                🏢 Annuaire des sociétés
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('sgis.index') }}">
                                🏦 Courtiers (SGI)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item disabled">
                                🔍 Rechercher une société (bientôt)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item fw-semibold"
                               href="{{ route('dividendes.index', ['year' => 2025]) }}">
                                🏆 Classement par dividendes (2025)
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ✅ Formations (dropdown) + Livres --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Formations
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('formations.brvm') }}">
                                🎓 Formations BRVM
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="{{ route('books.index') }}">
                                📚 Livres instructifs (mini-cours)
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item disabled">🧠 Parcours guidé (bientôt)</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Aide
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('faq') }}">
                                ❓ Foire aux questions (FAQ)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('aide.glossaire') }}">
                                📘 Glossaire BRVM
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('contact') }}">
                                📩 Contact
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item" href="{{ route('formations.brvm') }}">
                                🎓 Se former à la BRVM
                            </a>
                        </li>

                        {{-- Optionnel : lien livres aussi dans Aide --}}
                        <li>
                            <a class="dropdown-item" href="{{ route('books.index') }}">
                                📚 Mini-cours en livre (gratuit)
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>

            {{-- ✅ Zone Auth Breeze --}}
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">Beta privée</span>

                @auth
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            👤 {{ Auth::user()->name }}
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('dashboard') }}">
                                    🧭 Dashboard
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('courses.my') }}">
                                    🎓 Mes cours
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('wallet.index') }}">
                                    💼 Mon portefeuille
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#">
                                    📄 Mes analyses
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        🚪 Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth

                @guest
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">
                        Se connecter
                    </a>
                    <a class="btn btn-sm btn-primary" href="{{ route('register') }}">
                        S’inscrire
                    </a>
                @endguest
            </div>
        </div>
    </div>
</nav>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Scripts spécifiques pages --}}
@stack('scripts')

{{-- ========================= --}}
{{-- tawk.to – Support client --}}
{{-- ========================= --}}
@if(config('services.tawk.widget_id'))
<script type="text/javascript">
    var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
    (function(){
        var s1 = document.createElement("script"),
            s0 = document.getElementsByTagName("script")[0];
        s1.async = true;
        s1.src = "https://embed.tawk.to/{{ config('services.tawk.widget_id') }}";
        s1.charset = "UTF-8";
        s1.setAttribute("crossorigin", "*");
        s0.parentNode.insertBefore(s1, s0);
    })();
</script>
@endif

</body>
</html>
