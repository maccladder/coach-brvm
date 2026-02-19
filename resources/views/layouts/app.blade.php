{{-- resources/views/layouts/app.blade.php --}}
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

    {{-- ✅ Bootstrap Icons --}}
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

        /* ✅ Marketplace badge "Déjà acheté" */
        .market-badge-owned{
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 30;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .65rem;
            font-size: .80rem;
            font-weight: 700;
            line-height: 1;
            border-radius: 999px;
            white-space: nowrap;
            color: #fff;
            background: rgba(25,135,84,.95);
            box-shadow: 0 10px 24px rgba(0,0,0,.18);
            backdrop-filter: blur(6px);
        }

        @media (max-width: 576px){
            .market-badge-owned{
                top: 10px;
                left: 10px;
                font-size: .75rem;
                padding: .32rem .55rem;
            }
        }

        /* ✅ NOTIFS (APP) — mobile friendly */
        .notif-menu{
            width:360px;
            max-width:92vw;
        }
        .notif-scroll{
            max-height:360px;
            overflow:auto;
            -webkit-overflow-scrolling:touch;
        }
        @media (max-width: 576px){
            .notif-menu{
                width:calc(100vw - 24px)!important;
                max-width:calc(100vw - 24px)!important;
                margin:0 12px!important;
                left:0!important;
                right:0!important;
                transform:none!important;
            }
            .notif-scroll{ max-height:60vh; }
        }
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

    {{-- ✅ Meta Pixel (Coach BRVM) --}}
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '875478145273445');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=875478145273445&ev=PageView&noscript=1"
    /></noscript>
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
                            <a class="dropdown-item" href="{{ route('docs.public.index') }}">
                                📄 Études & Business plans
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="{{ route('chocs.index') }}">
                                ⚡ Chocs de marché (par secteur)
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center"
                               href="{{ route('marketplace.index') }}">
                                <span>🛍️ Marketplace</span>
                                <span class="badge rounded-pill text-bg-warning">Nouveau</span>
                            </a>
                        </li>
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

                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center"
                               href="{{ route('marketplace.index') }}">
                                <span>🛍️ Marketplace</span>
                                <span class="badge rounded-pill text-bg-warning">Nouveau</span>
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

                        <li>
                            <a class="dropdown-item" href="{{ route('aide.strategies') }}">
                                🧠 Stratégies d’investissement
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item" href="{{ route('formations.brvm') }}">
                                🎓 Se former à la BRVM
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="{{ route('books.index') }}">
                                📚 Mini-cours en livre (gratuit)
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="{{ route('marketplace.index') }}">
                                🛍️ Marketplace
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>

            {{-- ✅ Zone Auth Breeze --}}
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">Beta privée</span>

                {{-- 🔔 CLOCHE NOTIFICATIONS (USER/VENDOR) --}}
                @auth
                    @php
                        $unreadCount = auth()->user()->unreadNotifications()->count();
                        $latestNotifs = auth()->user()->notifications()->latest()->limit(8)->get();
                    @endphp

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark position-relative"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>

                            @if($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </button>

                        {{-- ✅ notif-menu + notif-scroll --}}
                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm notif-menu">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Notifications</div>

                                <form method="POST" action="{{ route('notifications.readAll') }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Tout lire</button>
                                </form>
                            </div>

                            <div class="notif-scroll">
                                @forelse($latestNotifs as $n)
                                    @php
                                        $data  = $n->data ?? [];
                                        $url   = $data['url'] ?? route('notifications.index');
                                        $title = $data['title'] ?? 'Notification';
                                        $msg   = $data['message'] ?? '';
                                    @endphp

                                    <div class="px-3 py-2 border-bottom {{ $n->read_at ? '' : 'bg-light' }}">
                                        <div class="d-flex justify-content-between gap-2">
                                            <a href="{{ $url }}" class="text-decoration-none text-dark">
                                                <div class="fw-semibold">{{ $title }}</div>
                                                <div class="text-muted small">{{ $msg }}</div>
                                                <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
                                            </a>

                                            @if(!$n->read_at)
                                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-dark">Lire</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-3 py-3 text-muted">Aucune notification.</div>
                                @endforelse
                            </div>

                            <div class="px-3 py-2">
                                <a class="btn btn-sm btn-dark w-100" href="{{ route('notifications.index') }}">
                                    Voir tout
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth

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
                                <a class="dropdown-item" href="{{ route('marketplace.index') }}">
                                    🛍️ Marketplace
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('my.products') }}">
                                    🧾 Mes produits
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('documents.mine') }}">
                                    🧾 Mes documents
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

                            @php
                                $isVendor = (bool) (auth()->user()->is_vendor ?? false);
                                $viewMode = session('view_mode', 'user'); // user|vendor
                            @endphp

                            <li><hr class="dropdown-divider"></li>

                            @if(!$isVendor)
                                <li>
                                    <form method="POST" action="{{ route('vendor.become') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            🛍️ Devenir vendeur
                                        </button>
                                    </form>
                                </li>
                            @else
                                @if($viewMode === 'user')
                                    <li>
                                        <form method="POST" action="{{ route('switch.vendor') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                🛍️ Passer en vue vendeur
                                            </button>
                                        </form>
                                    </li>
                                @else
                                    <li>
                                        <a class="dropdown-item" href="{{ route('vendor.dashboard') }}">
                                            🧾 Dashboard vendeur
                                        </a>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('switch.user') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                👤 Revenir en vue utilisateur
                                            </button>
                                        </form>
                                    </li>
                                @endif
                            @endif

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

{{-- tawk.to – Support client --}}
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
