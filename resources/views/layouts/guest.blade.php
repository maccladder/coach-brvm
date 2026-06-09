<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Boursiv') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ── Reset page ── */
        body {
            margin: 0;
            font-family: 'DM Sans', 'Figtree', sans-serif;
            background: #060910;
            min-height: 100vh;
        }

        /* ── Background cinématique ── */
        .cb-guest-bg {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 60% at 50% 0%,   rgba(201,168,76,.10) 0%, transparent 55%),
                radial-gradient(ellipse 50% 40% at 90% 90%,  rgba(15,207,164,.06) 0%, transparent 50%),
                #060910;
        }

        /* Grid de fond */
        .cb-guest-bg::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* Orbe gauche */
        .cb-guest-bg::after {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            top: -120px; left: -120px;
            background: rgba(201,168,76,.07);
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
            animation: guestOrb 14s ease-in-out infinite;
        }
        @keyframes guestOrb {
            0%,100% { transform: translate(0,0); }
            50%      { transform: translate(20px,-24px); }
        }

        /* ── Logo zone ── */
        .cb-guest-logo {
            position: relative; z-index: 1;
            text-align: center; margin-bottom: 28px;
        }
        .cb-guest-logo a {
            display: inline-flex; flex-direction: column;
            align-items: center; gap: 10px;
            text-decoration: none;
        }
        .cb-guest-logo img { height: 56px; width: auto; }
        .cb-guest-logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 900;
            color: #C9A84C; letter-spacing: -.01em;
        }
        .cb-guest-logo-text em { font-style: normal; color: #E8EAF0; }
        .cb-guest-logo-sub {
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 600;
            letter-spacing: .2em; text-transform: uppercase;
            color: #6B7590; margin-top: 4px;
        }

        /* ── Card principale ── */
        .cb-guest-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            background: rgba(12,17,32,.95);
            border: 1px solid rgba(201,168,76,.14);
            border-radius: 6px;
            padding: 36px 36px 32px;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 64px rgba(0,0,0,.5);
        }

        /* Ligne dorée en haut de la card */
        .cb-guest-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #C9A84C, rgba(15,207,164,.6), transparent);
            border-radius: 6px 6px 0 0;
        }

        /* ── Tailwind overrides pour les inputs Breeze ── */
        .cb-guest-card input[type="email"],
        .cb-guest-card input[type="password"],
        .cb-guest-card input[type="text"] {
            background: rgba(6,9,16,.8) !important;
            border: 1px solid rgba(255,255,255,.1) !important;
            color: #E8EAF0 !important;
            border-radius: 4px !important;
            font-family: 'DM Sans', sans-serif !important;
            font-size: 14px !important;
            padding: 10px 14px !important;
            width: 100%;
            transition: border-color .25s, box-shadow .25s;
            outline: none;
        }
        .cb-guest-card input:focus {
            border-color: rgba(201,168,76,.5) !important;
            box-shadow: 0 0 0 3px rgba(201,168,76,.08) !important;
        }
        .cb-guest-card input::placeholder { color: #6B7590 !important; }

        /* Labels */
        .cb-guest-card label {
            font-family: 'Syne', sans-serif !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: .1em !important;
            text-transform: uppercase !important;
            color: #6B7590 !important;
        }

        /* Bouton primaire Breeze override */
        .cb-guest-card button[type="submit"],
        .cb-guest-card .cb-btn-primary {
            width: 100% !important;
            background: linear-gradient(135deg, #C9A84C, #9B6B15) !important;
            color: #050810 !important;
            font-family: 'Syne', sans-serif !important;
            font-weight: 800 !important;
            font-size: 13px !important;
            letter-spacing: .07em !important;
            text-transform: uppercase !important;
            padding: 13px 24px !important;
            border: none !important;
            border-radius: 3px !important;
            cursor: pointer !important;
            transition: all .3s !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .cb-guest-card button[type="submit"]:hover {
            box-shadow: 0 8px 28px rgba(201,168,76,.3) !important;
            transform: translateY(-1px) !important;
        }

        /* Checkbox */
        .cb-guest-card input[type="checkbox"] {
            accent-color: #C9A84C;
            width: 15px !important;
            height: 15px !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Liens */
        .cb-guest-card a {
            color: #C9A84C;
            text-decoration: none;
            transition: color .25s;
        }
        .cb-guest-card a:hover { color: #F0D080; text-decoration: underline; }

        /* Textes muted */
        .cb-guest-card .text-gray-600,
        .cb-guest-card .text-gray-500,
        .cb-guest-card .text-sm {
            color: #6B7590 !important;
        }

        /* Séparateur "ou" */
        .cb-sep {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0;
        }
        .cb-sep-line { flex: 1; height: 1px; background: rgba(255,255,255,.08); }
        .cb-sep-text {
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 700;
            letter-spacing: .16em; text-transform: uppercase;
            color: #6B7590;
        }

        /* Bouton Google override */
        .cb-guest-card .cb-google-btn {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: rgba(255,255,255,.04) !important;
            border: 1px solid rgba(255,255,255,.1) !important;
            color: #E8EAF0 !important;
            font-family: 'Syne', sans-serif !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            letter-spacing: .06em !important;
            text-transform: uppercase !important;
            padding: 11px 20px !important;
            border-radius: 3px !important;
            text-decoration: none !important;
            transition: all .25s !important;
        }
        .cb-guest-card .cb-google-btn:hover {
            background: rgba(255,255,255,.08) !important;
            border-color: rgba(255,255,255,.2) !important;
            color: #E8EAF0 !important;
            text-decoration: none !important;
        }

        /* Erreurs */
        .cb-guest-card .text-red-600,
        .cb-guest-card [class*="text-red"] {
            color: #FF6B6B !important;
            font-size: 12px !important;
        }

        /* Section status session */
        .cb-guest-card [class*="text-green"] {
            color: #0FCFA4 !important;
            font-size: 13px !important;
        }

        /* Footer bas de page */
        .cb-guest-footer {
            position: relative; z-index: 1;
            margin-top: 24px; text-align: center;
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 600;
            letter-spacing: .12em; text-transform: uppercase;
            color: #6B7590;
        }

        /* Mobile */
        @media (max-width: 480px) {
            .cb-guest-card { padding: 28px 22px 24px; }
        }
    </style>
</head>

<body>
<div class="cb-guest-bg">

    {{-- Logo --}}
    <div class="cb-guest-logo">
        <a href="{{ route('landing') }}">
            <span class="cb-guest-logo-text">Boursiv</span>
        </a>
        <p class="cb-guest-logo-sub">Comprends · Analyse · Progresse</p>
    </div>

    {{-- Card --}}
    <div class="cb-guest-card">
        {{ $slot }}
    </div>

    {{-- Footer --}}
    <p class="cb-guest-footer">
        © {{ date('Y') }} Boursiv · CHENGGONG SARL
    </p>

</div>
</body>
</html>
