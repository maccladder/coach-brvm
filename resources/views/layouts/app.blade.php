<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>{{ config('app.name', 'Coach BRVM') }}</title>

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
    </style>
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

                {{-- <li class="nav-item">
                    <a class="nav-link" href="{{ url('/summaries/today') }}">
                        Résumé du jour
                    </a>
                </li> --}}

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('summaries.generate.form') }}">
                        Analyser un état financier
                    </a>
                </li>

                {{-- Tu pourras activer / changer plus tard --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('formations.brvm') }}">Formations BRVM</a>
                </li>
            </ul>

            {{-- Côté droit (badge beta / futur compte utilisateur) --}}
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light border">
                    Beta privée
                </span>
            </div>
        </div>
    </div>
</nav>

{{-- Contenu des pages --}}
@yield('content')

{{-- Scripts dynamiques nécessaires pour le lecteur audio et Bootstrap --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')

</body>
</html>
