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

        table thead th {
            white-space: nowrap;
        }
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
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('landing') }}">Accueil</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('client-bocs.create') }}">
                        Analyser une BOC
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('client-financials.create') }}">
                        Analyser un état financier
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('formations.brvm') }}">Formations BRVM</a>
                </li>

                <li class="nav-item">
    <a class="nav-link" href="{{ route('announcements.index') }}">📢 Annonces</a>
</li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contact') }}">Contact</a>
                </li>
            </ul>

            {{-- Côté droit --}}
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">Beta privée</span>
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
