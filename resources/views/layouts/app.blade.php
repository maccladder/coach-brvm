{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Boursiv') }}</title>

    @stack('meta')

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            /* ── Tokens cibles ── */
            --cb-paper:     #F5F1E8;
            --cb-card:      #FFFFFF;
            --cb-ink:       #1A211D;
            --cb-forest:    #0F5C43;
            --cb-forest-dk: #0E3D2E;
            --cb-gold:      #B0862E;
            --cb-green:     #0FCFA4;
            --cb-up:        #138A5E;
            --cb-down:      #C0392B;
            --cb-red:       #FF6B6B;
            --cb-muted:     #6C7269;
            --cb-text:      #1A211D;
            --cb-border:    rgba(15,92,67,.15);
            /* ── Aliases rétro-compat (tout ce qui référence --cb-dark* suit automatiquement) ── */
            --cb-dark:      var(--cb-paper);
            --cb-dark2:     var(--cb-card);
            --cb-dark3:     #EDE7DA;
        }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', system-ui, sans-serif; background: var(--cb-paper); color: var(--cb-ink); }

        /* ══ TICKER ══ */
        .cb-ticker {
            background: var(--cb-dark);
            border-bottom: 1px solid var(--cb-border);
            height: 34px;
            overflow: hidden;
            display: flex;
            align-items: center;
            cursor: default;
        }
        /* Pause complète quand la souris entre sur la barre */
        .cb-ticker:hover .cb-ticker-track {
            animation-play-state: paused;
        }
        .cb-ticker-track {
            display: flex;
            gap: 56px;
            width: max-content;
            animation: cbTick 90s linear infinite; /* 40s → 90s : défilement bien plus lent */
            padding-left: 100%;
        }
        @keyframes cbTick {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        .cb-ticker-item {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--cb-muted);
            transition: color .2s;
        }
        /* Mise en valeur légère de l'item sous la souris */
        .cb-ticker-item:hover { color: var(--cb-text); }
        .cb-ticker-item .sym { color: var(--cb-gold); }
        .cb-ticker-item .up  { color: var(--cb-up); }
        .cb-ticker-item .dn  { color: var(--cb-down); }

        /* NAVBAR */
        .cb-navbar { background: var(--cb-dark) !important; border-bottom: 1px solid var(--cb-border); padding: 0; min-height: 60px; position: sticky; top: 0; z-index: 1030; box-shadow: 0 1px 0 var(--cb-border), 0 2px 16px rgba(0,0,0,.06); }
        .cb-brand { font-family:'Playfair Display',serif; font-size:20px; font-weight:900; color:var(--cb-forest) !important; text-decoration:none; letter-spacing:-.01em; padding:16px 0; }
        .cb-brand em { font-style:normal; color:var(--cb-ink); }
        .cb-navbar .nav-link { font-family:'Syne',sans-serif; font-size:12px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--cb-muted) !important; padding:20px 14px !important; transition:color .25s; position:relative; }
        .cb-navbar .nav-link:hover { color:var(--cb-forest) !important; }
        .cb-navbar .nav-link::after { content:''; position:absolute; bottom:0; left:14px; right:14px; height:2px; background:var(--cb-forest); transform:scaleX(0); transition:transform .25s; }
        .cb-navbar .nav-link:hover::after { transform:scaleX(1); }
        .cb-navbar .nav-link-market { color:var(--cb-gold) !important; background:rgba(176,134,46,.07); border:1px solid rgba(176,134,46,.2); border-radius:3px; margin:auto 4px; padding:7px 14px !important; }
        .cb-navbar .nav-link-market:hover { background:rgba(176,134,46,.12); color:var(--cb-gold) !important; }
        .cb-navbar .nav-link-market::after { display:none; }
        .cb-navbar .dropdown-toggle::after { border-top-color:var(--cb-muted); margin-left:5px; }
        .cb-navbar .dropdown-menu { background:var(--cb-dark2); border:1px solid var(--cb-border); border-radius:6px; padding:8px; box-shadow:0 8px 32px rgba(0,0,0,.12); min-width:240px; }
        .cb-navbar .dropdown-item { font-family:'DM Sans',sans-serif; font-size:13.5px; color:var(--cb-muted); border-radius:4px; padding:9px 14px; transition:all .2s; }
        .cb-navbar .dropdown-item:hover { background:rgba(15,92,67,.06); color:var(--cb-forest); }
        .cb-navbar .dropdown-item.disabled { color:rgba(107,117,144,.4) !important; cursor:default; }
        .cb-navbar .dropdown-divider { border-color:var(--cb-border); margin:6px 0; }
        .cb-badge-new { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; background:rgba(201,168,76,.15); color:var(--cb-gold); border:1px solid rgba(201,168,76,.25); padding:2px 7px; border-radius:100px; }
        .cb-badge-beta { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; background:rgba(160,120,255,.1); color:#B090FF; border:1px solid rgba(160,120,255,.2); padding:2px 8px; border-radius:100px; }
        .cb-btn-login { font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--cb-ink) !important; background:transparent; border:1px solid rgba(26,33,29,.18); padding:7px 16px; border-radius:3px; text-decoration:none; transition:all .25s; }
        .cb-btn-login:hover { border-color:var(--cb-forest); color:var(--cb-forest) !important; }
        .cb-btn-register { font-family:'Syne',sans-serif; font-size:11px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--cb-paper) !important; background:linear-gradient(135deg,var(--cb-gold),#7A5412); border:none; padding:8px 18px; border-radius:3px; text-decoration:none; transition:all .25s; }
        .cb-btn-register:hover { box-shadow:0 6px 20px rgba(201,168,76,.35); transform:translateY(-1px); }
        .cb-user-btn { font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--cb-ink) !important; background:rgba(15,92,67,.06); border:1px solid rgba(15,92,67,.15); padding:7px 14px; border-radius:3px; transition:all .25s; }
        .cb-user-btn:hover { background:rgba(15,92,67,.1); border-color:var(--cb-border); color:var(--cb-forest) !important; }
        .cb-bell-btn { background:rgba(15,92,67,.06); border:1px solid rgba(15,92,67,.15); color:var(--cb-muted); width:36px; height:36px; border-radius:3px; display:flex; align-items:center; justify-content:center; transition:all .25s; font-size:15px; cursor:pointer; }
        .cb-bell-btn:hover { border-color:var(--cb-border); color:var(--cb-gold); background:rgba(176,134,46,.08); }
        .cb-notif-menu { width:360px; max-width:92vw; background:var(--cb-dark2) !important; border:1px solid var(--cb-border) !important; }
        .cb-notif-header { background:var(--cb-dark3); border-bottom:1px solid var(--cb-border); padding:12px 16px; }
        .cb-notif-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--cb-text); }
        .cb-notif-scroll { max-height:340px; overflow-y:auto; }
        .cb-notif-item { padding:12px 16px; border-bottom:1px solid rgba(26,33,29,.07); transition:background .2s; }
        .cb-notif-item:hover { background:rgba(201,168,76,.04); }
        .cb-notif-item.unread { background:rgba(201,168,76,.05); }
        .cb-notif-item-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:600; color:var(--cb-text); }
        .cb-notif-item-msg { font-size:12px; color:var(--cb-muted); margin-top:2px; }
        .cb-notif-item-time { font-size:11px; color:rgba(107,117,144,.6); margin-top:3px; }
        .cb-navbar .navbar-toggler { border:1px solid rgba(26,33,29,.18); padding:5px 9px; }
        .cb-navbar .navbar-toggler-icon { filter:none; }
        .dropdown-menu-scroll { max-height:360px; overflow-y:auto; }
        .market-badge-owned { position:absolute; top:12px; left:12px; z-index:30; display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .65rem; font-size:.80rem; font-weight:700; border-radius:999px; color:#fff; background:rgba(25,135,84,.95); box-shadow:0 10px 24px rgba(0,0,0,.18); backdrop-filter:blur(6px); }
        .cb-page { min-height:calc(100vh - 92px); }

        /* Floating WhatsApp Diaspora button */
        .cb-wa-float {
            position: fixed; bottom: 24px; right: 20px; z-index: 9990;
            display: flex; align-items: center; gap: 8px;
            background: #25D366; color: #fff !important; text-decoration: none !important;
            padding: 11px 18px 11px 13px; border-radius: 50px;
            font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: .04em;
            box-shadow: 0 4px 18px rgba(37,211,102,.35);
            animation: cbWaFloatPulse 2.8s ease-in-out infinite;
            transition: transform .2s, background .2s;
        }
        .cb-wa-float i { font-size: 19px; line-height: 1; }
        .cb-wa-float:hover { background: #1ebe5a; transform: scale(1.05) translateY(-2px); animation: none; box-shadow: 0 8px 28px rgba(37,211,102,.5); }
        @keyframes cbWaFloatPulse {
            0%, 100% { box-shadow: 0 4px 18px rgba(37,211,102,.35); }
            50% { box-shadow: 0 4px 30px rgba(37,211,102,.6), 0 0 0 8px rgba(37,211,102,.1); }
        }
        @media (max-width: 576px) {
            .cb-wa-float span.cb-wa-float-label { display: none; }
            .cb-wa-float { padding: 12px; border-radius: 50%; }
        }

        /* Navbar diaspora dot */
        .cb-nav-diaspora { color: #25D366 !important; }
        .cb-nav-diaspora::after { background: #25D366 !important; }
        .cb-diaspora-blink {
            display: inline-block; width: 6px; height: 6px; border-radius: 50%;
            background: #25D366; vertical-align: middle; margin-right: 4px;
            animation: cbNavDot 1.4s ease-in-out infinite;
        }
        @keyframes cbNavDot {
            0%, 100% { opacity: 1; }
            50% { opacity: .2; }
        }

        @media (max-width:991px) {
            .cb-navbar .dropdown-menu { background:var(--cb-dark3); border:none; border-left:2px solid var(--cb-border); border-radius:0; margin-left:8px; padding:4px 0; }
            .cb-navbar .nav-link::after { display:none; }
            .cb-navbar .nav-link { padding:12px 16px !important; }
            .cb-navbar .cb-account-menu {
                position: static !important;
                transform: none !important;
                inset: auto !important;
                width: auto;
                max-width: 100%;
                max-height: none;
                box-shadow: none;
                border-left: none;
                margin-left: 0;
            }
        }
        @media (max-width:576px) {
            .cb-notif-menu { width:calc(100vw - 24px) !important; max-width:calc(100vw - 24px) !important; }
            .market-badge-owned { font-size:.75rem; }
        }
    </style>

    @stack('styles')

    @php $gaId = config('services.ga.measurement_id') ?? env('GA_MEASUREMENT_ID'); @endphp
    @if(!empty($gaId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
    @endif

    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init','875478145273445');fbq('track','PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=875478145273445&ev=PageView&noscript=1"/></noscript>
</head>
<body>

{{-- ══ TICKER — VRAIS COURS BRVM ══ --}}
<div class="cb-ticker">
    <div class="cb-ticker-track">
        @if(!empty($tickerData) && count($tickerData) > 0)
            @foreach($tickerData as $s)
                @php
                    $close  = number_format((float)$s['close'], 0, ',', ' ');
                    $change = $s['change'] ?? null;
                    $isUp   = $change !== null && $change >= 0;
                @endphp
                <span class="cb-ticker-item">
                    <span class="sym">{{ $s['ticker'] }}</span>
                    {{ $close }} F
                    @if($change !== null)
                        <span class="{{ $isUp ? 'up' : 'dn' }}">{{ $isUp ? '▲ +' : '▼ ' }}{{ number_format($change, 2) }}%</span>
                    @endif
                </span>
            @endforeach
            {{-- Duplicate pour seamless loop --}}
            @foreach($tickerData as $s)
                @php
                    $close  = number_format((float)$s['close'], 0, ',', ' ');
                    $change = $s['change'] ?? null;
                    $isUp   = $change !== null && $change >= 0;
                @endphp
                <span class="cb-ticker-item">
                    <span class="sym">{{ $s['ticker'] }}</span>
                    {{ $close }} F
                    @if($change !== null)
                        <span class="{{ $isUp ? 'up' : 'dn' }}">{{ $isUp ? '▲ +' : '▼ ' }}{{ number_format($change, 2) }}%</span>
                    @endif
                </span>
            @endforeach
        @else
            <span class="cb-ticker-item" style="color:var(--cb-muted);padding-left:20px;">
                Cours BRVM — mise à jour en cours...
            </span>
        @endif
    </div>
</div>

{{-- ══ NAVBAR ══ --}}
<nav class="navbar navbar-expand-lg cb-navbar">
    <div class="container" style="max-width:1100px;">
        <a class="cb-brand" href="{{ route('landing') }}">Boursiv</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">Accueil</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Marché</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('market.live') }}">📈 Marché en direct</a></li>
                        <li><hr class="dropdown-divider" style="border-color:rgba(26,33,29,.08);margin:4px 8px;"></li>
                        <li><a class="dropdown-item" href="{{ route('radar.index') }}">📡 Radar (7 jours)</a></li>
                        <li><a class="dropdown-item" href="{{ route('announcements.index') }}">📢 Annonces</a></li>
                        <li><a class="dropdown-item" href="{{ route('news.index') }}">📰 Actualités</a></li>
                        <li><a class="dropdown-item" href="{{ route('docs.public.index') }}">📄 Études & Business plans</a></li>
                        <li><a class="dropdown-item" href="{{ route('chocs.index') }}">⚡ Chocs de marché</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Analyses</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('client-bocs.create') }}">📄 Analyser une BOC</a></li>
                        <li><a class="dropdown-item" href="{{ route('client-financials.create') }}">📊 Analyser un état financier</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('pnd.brvm') }}">
                                <span>🏛️ PND 2026-2030 × BRVM</span><span class="cb-badge-new">Nouveau</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Sociétés</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('societes.index') }}">🏢 Annuaire des sociétés</a></li>
                        <li><a class="dropdown-item" href="{{ route('sgis.index') }}">🏦 Courtiers (SGI)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('dividendes.index', ['year' => 2025]) }}">🏆 Classement dividendes (2025)</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Apprendre</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('formations.brvm') }}">🎓 Formations en ligne</a></li>
                        <li><a class="dropdown-item" href="{{ route('books.index') }}">📚 Livres instructifs</a></li>
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('marketplace.index') }}">
                                <span>🛍️ Marketplace</span><span class="cb-badge-new">Nouveau</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('formation.presentielle') }}">🏫 Formation en présentiel</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('faq') }}">❓ FAQ</a></li>
                        <li><a class="dropdown-item" href="{{ route('aide.glossaire') }}">📘 Glossaire BRVM</a></li>
                        <li><a class="dropdown-item" href="{{ route('contact') }}">📩 Contact</a></li>
                        <li><a class="dropdown-item" href="{{ route('aide.strategies') }}">🧠 Stratégies d'investissement</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('forum.index') }}">Forum</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link cb-nav-diaspora" href="{{ route('diaspora') }}">
                        <span class="cb-diaspora-blink"></span>Diaspora
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link cb-nav-diaspora" href="{{ route('financement') }}">
                        <span class="cb-diaspora-blink"></span>Financement
                    </a>
                </li>
                @auth
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('donation.show') }}" style="color:var(--cb-gold) !important;opacity:.85;">🤝 Soutenir</a>
                </li>
                @endauth
            </ul>

            <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                @auth
                    @php
                        $unreadCount  = auth()->user()->unreadNotifications()->count();
                        $latestNotifs = auth()->user()->notifications()->latest()->limit(8)->get();
                    @endphp
                    <div class="dropdown">
                        <button class="cb-bell-btn position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            @if($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;padding:3px 5px;">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 cb-notif-menu">
                            <div class="cb-notif-header d-flex justify-content-between align-items-center">
                                <span class="cb-notif-title">Notifications</span>
                                <form method="POST" action="{{ route('notifications.readAll') }}">
                                    @csrf
                                    <button class="btn btn-sm" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--cb-gold);background:transparent;border:none;padding:0;">
                                        Tout lire
                                    </button>
                                </form>
                            </div>
                            <div class="cb-notif-scroll">
                                @forelse($latestNotifs as $n)
                                    @php
                                        $data  = $n->data ?? [];
                                        $url   = $data['url'] ?? route('notifications.index');
                                        $title = $data['title'] ?? 'Notification';
                                        $msg   = $data['message'] ?? '';
                                    @endphp
                                    <div class="cb-notif-item {{ $n->read_at ? '' : 'unread' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <a href="{{ $url }}" class="text-decoration-none flex-1">
                                                <div class="cb-notif-item-title">{{ $title }}</div>
                                                @if($msg)<div class="cb-notif-item-msg">{{ $msg }}</div>@endif
                                                <div class="cb-notif-item-time">{{ $n->created_at->diffForHumans() }}</div>
                                            </a>
                                            @if(!$n->read_at)
                                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                                    @csrf
                                                    <button style="font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--cb-gold);background:transparent;border:none;white-space:nowrap;padding:0;cursor:pointer;">
                                                        Lire ✓
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-3" style="font-size:13px;color:var(--cb-muted);">Aucune notification.</div>
                                @endforelse
                            </div>
                            <div class="p-2" style="border-top:1px solid var(--cb-border);">
                                <a href="{{ route('notifications.index') }}" class="btn w-100"
                                   style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;background:rgba(201,168,76,.1);color:var(--cb-gold);border:1px solid var(--cb-border);border-radius:3px;padding:8px;">
                                    Voir tout →
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="cb-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            👤 {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end cb-account-menu">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">🧭 Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('courses.my') }}">🎓 Mes cours</a></li>
                            <li><a class="dropdown-item" href="{{ route('marketplace.index') }}">🛍️ Marketplace</a></li>
                            <li><a class="dropdown-item" href="{{ route('my.products') }}">🧾 Mes produits</a></li>
                            <li><a class="dropdown-item" href="{{ route('my.products') }}?type=game">🎮 Mes jeux</a></li>
                            <li><a class="dropdown-item" href="{{ route('documents.mine') }}">🧾 Mes documents</a></li>
                            <li><a class="dropdown-item" href="{{ route('wallet.index') }}">💼 Mon portefeuille</a></li>
                            <li><a class="dropdown-item" href="{{ route('client-financials.index') }}">📄 Mes analyses</a></li>
                            @if(\App\Models\LettreCIAccess::hasActiveAccess(auth()->user()))
                            <li><a class="dropdown-item" href="{{ route('lettreci.dashboard') }}">✉️ Mes lettres</a></li>
                            @endif
                            @php
                                $isVendor = (bool)(auth()->user()->is_vendor ?? false);
                                $viewMode = session('view_mode', 'user');
                            @endphp
                            <li><hr class="dropdown-divider"></li>
                            @php $isAffiliate = (bool)(auth()->user()->is_affiliate ?? false); @endphp
                            @if(!$isAffiliate)
                                <li>
                                    <a class="dropdown-item" href="{{ route('affiliate.landing') }}">🤝 Devenir apporteur d'affaires</a>
                                </li>
                            @else
                                <li>
                                    <a class="dropdown-item" href="{{ route('affiliate.dashboard') }}">🤝 Mon espace apporteur</a>
                                </li>
                            @endif
                            @if(!$isVendor)
                                <li>
                                    <form method="POST" action="{{ route('vendor.become') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">🛍️ Devenir vendeur</button>
                                    </form>
                                </li>
                            @else
                                @if($viewMode === 'user')
                                    <li>
                                        <form method="POST" action="{{ route('switch.vendor') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">🛍️ Vue vendeur</button>
                                        </form>
                                    </li>
                                @else
                                    <li><a class="dropdown-item" href="{{ route('vendor.dashboard') }}">🧾 Dashboard vendeur</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('switch.user') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">👤 Vue utilisateur</button>
                                        </form>
                                    </li>
                                @endif
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item" style="color:var(--cb-red);">🚪 Déconnexion</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="cb-btn-login">Se connecter</a>
                    <a href="{{ route('register') }}" class="cb-btn-register">S'inscrire</a>
                @endguest
            </div>
        </div>
    </div>
</nav>

<div class="cb-page">
    @yield('content')
</div>

@guest
<div style="background:var(--cb-dark);border-top:1px solid var(--cb-border);padding:14px 0;text-align:center;">
    <span style="font-size:.82rem;color:var(--cb-muted);">
        Vous croyez en notre mission ?
        <a href="{{ route('donation.show') }}" style="color:var(--cb-gold);text-decoration:none;font-weight:600;margin-left:6px;">🤝 Soutenir Boursiv →</a>
    </span>
</div>
@endguest

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

{{-- Floating WhatsApp Diaspora --}}
<a href="{{ route('diaspora') }}"
   class="cb-wa-float"
   title="Investir depuis la diaspora — En savoir plus">
    <i class="bi bi-whatsapp"></i>
    <span class="cb-wa-float-label">Diaspora ?</span>
</a>

{{-- Tawk.to désactivé
@if(config('services.tawk.widget_id'))
<script>
    var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();
    (function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];s1.async=true;s1.src="https://embed.tawk.to/{{ config('services.tawk.widget_id') }}";s1.charset='UTF-8';s1.setAttribute('crossorigin','*');s0.parentNode.insertBefore(s1,s0);})();
</script>
@endif
--}}

</body>
</html>
