{{-- resources/views/layouts/admin.blade.php --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>@yield('title', 'Admin – Boursiv')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon-boursiv.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- ✅ Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Style global --}}
    <style>
        body{
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
            Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand span.logo-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;margin-right:.35rem;
            background: linear-gradient(135deg, #0d6efd, #20c997);
        }
        table thead th{ white-space:nowrap; }
        .dropdown-menu{ border-radius:.5rem; }

        /* ✅ NOTIFS (ADMIN) — mobile friendly */
        .notif-menu{
            width:380px;
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

    @stack('styles')
</head>

<body>

{{-- NAVBAR ADMIN --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container" style="max-width: 1200px;">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('admin.dashboard') }}">
            <span class="logo-dot"></span>
            <span class="fw-semibold">Boursiv – Admin</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#adminNavbar" aria-controls="adminNavbar"
                aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">

            {{-- MENU GAUCHE --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Menu
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('admin.bocs.index') }}">📊 BOC journalières</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}">📄 États financiers</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.courses.dashboard') }}">🎓 Formations – Dashboard</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.courses.purchases') }}">🧾 Formations – Achats</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.courses.index') }}">🎥 Formations – Cours</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.books.index') }}">📚 Livres</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.grants.index') }}">🎁 Attributions</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.garba-master.index') }}">🎮 Garba Master</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.wallets.index') }}">💼 Portefeuilles</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.packs.index') }}">📦 Pack Boursiv</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.donations.index') }}">🤝 Dons</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.emails.index') }}">📧 Envoyer un email</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.announcements.index') }}">📢 Annonces</a></li>
                        @php $pendingNewsCount = \App\Models\News::where('is_published', false)->count(); @endphp
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('admin.news.index') }}">
                                📰 Actualités
                                @if($pendingNewsCount > 0)
                                    <span class="badge rounded-pill text-bg-danger ms-2">{{ $pendingNewsCount > 99 ? '99+' : $pendingNewsCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header" style="font-size:.7rem;letter-spacing:.05em;">AFFILIATION</h6></li>
                        <li><a class="dropdown-item" href="{{ route('admin.affiliates.index') }}">🤝 Apporteurs d'affaires</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.affiliate-payouts.index') }}">💳 Reversements affiliation</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">← Site public</a>
                </li>

            </ul>

            {{-- MENU DROITE --}}
            <div class="d-flex align-items-center gap-2">

                {{-- 🔔 Cloche ADMIN (AdminNotification table) --}}
                @if(session('is_admin'))
                    @php
                        $adminUnread = \App\Models\AdminNotification::unread()->count();
                        $adminLatest = \App\Models\AdminNotification::latest()->take(8)->get();
                    @endphp

                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm position-relative"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            @if($adminUnread > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">
                                    {{ $adminUnread > 99 ? '99+' : $adminUnread }}
                                </span>
                            @endif
                        </button>

                        {{-- ✅ notif-menu + notif-scroll --}}
                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm notif-menu">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Notifications</div>

                                <form method="POST" action="{{ route('admin.notifications.readAll') }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Tout lire</button>
                                </form>
                            </div>

                            <div class="notif-scroll">
                                @forelse($adminLatest as $n)
                                    <div class="px-3 py-2 border-bottom {{ $n->read_at ? '' : 'bg-light' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">

                                            @if($n->url)
                                                <a href="{{ $n->url }}" class="text-decoration-none text-dark">
                                                    <div class="{{ $n->read_at ? '' : 'fw-semibold' }}">{{ $n->title }}</div>
                                                    @if($n->message)<div class="text-muted small">{{ $n->message }}</div>@endif
                                                    <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
                                                </a>
                                            @else
                                                <div>
                                                    <div class="{{ $n->read_at ? '' : 'fw-semibold' }}">{{ $n->title }}</div>
                                                    @if($n->message)<div class="text-muted small">{{ $n->message }}</div>@endif
                                                    <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
                                                </div>
                                            @endif

                                            @if(!$n->read_at)
                                                <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
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
                                <a class="btn btn-sm btn-dark w-100" href="{{ route('admin.notifications.index') }}">
                                    Voir tout
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Logout admin --}}
                    <form action="{{ route('admin.logout') }}" method="POST" class="d-flex">
                        @csrf
                        <button class="btn btn-outline-light btn-sm">
                            Se déconnecter
                        </button>
                    </form>
                @elseif(session('is_stagiaire'))
                    <span class="badge bg-warning text-dark me-1">Stagiaire</span>
                    {{-- Logout stagiaire --}}
                    <form action="{{ route('stagiaire.logout') }}" method="POST" class="d-flex">
                        @csrf
                        <button class="btn btn-outline-light btn-sm">
                            Se déconnecter
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>
</nav>

{{-- CONTENU ADMIN --}}
@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
