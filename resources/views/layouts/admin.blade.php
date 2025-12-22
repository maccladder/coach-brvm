<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>@yield('title', 'Admin – Coach BRVM')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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

        table thead th {
            white-space: nowrap;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container" style="max-width: 1100px;">

        {{-- Brand admin --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('admin.dashboard') }}">
            <span class="logo-dot"></span>
            <span class="fw-semibold">Coach BRVM – Admin</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#adminNavbar" aria-controls="adminNavbar"
                aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.bocs.index') }}">BOC journalières</a>
                </li>
                <li class="nav-item">
    <a class="nav-link" href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}">
        États financiers
    </a>
</li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">← Site public</a>
                </li>

            </ul>

            @if(session('is_admin'))
                <form action="{{ route('admin.logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">
                        Se déconnecter
                    </button>
                </form>
            @endif
        </div>
    </div>
</nav>

{{-- Contenu des pages admin --}}
@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

</body>
</html>
