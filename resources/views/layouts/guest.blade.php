<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#060910">

    <title>{{ isset($title) ? trim($title) . ' · Boursiv' : config('app.name', 'Boursiv') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Tailwind : encore utilisé par les autres pages invitées (mot de passe oublié, vérification…) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Styles des pages d'authentification : ici, sans étape de build --}}
    <style>
        :root {
            --ba-bg: #060910;
            --ba-card: #0E1424;
            --ba-card-2: #121A2E;
            --ba-input: #080C17;
            --ba-line: #56617C;          /* bordure des champs : contraste 3:1 sur la carte */
            --ba-line-soft: rgba(255,255,255,.08);
            --ba-ink: #EEF0F5;
            --ba-text: #C9CEDA;
            --ba-muted: #9AA3B8;         /* AA sur la carte et le fond */
            --ba-gold: #D4B25A;
            --ba-gold-2: #9B6B15;
            --ba-gold-soft: rgba(212,178,90,.16);
            --ba-green: #2BD9AE;
            --ba-error: #FF8A8A;
            --ba-error-line: #FF6B6B;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            color: var(--ba-text);
            background: var(--ba-bg);
            overflow-x: hidden;
        }

        /* ── Fond ─────────────────────────────────────────────── */
        .ba-shell {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 24px 16px 20px;
            background:
                radial-gradient(ellipse 70% 50% at 15% 0%, rgba(212,178,90,.13) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 95% 100%, rgba(43,217,174,.07) 0%, transparent 60%),
                var(--ba-bg);
        }
        .ba-shell::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(212,178,90,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,178,90,.045) 1px, transparent 1px);
            background-size: 56px 56px;
            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 30% 40%, #000 0%, transparent 70%);
                    mask-image: radial-gradient(ellipse 80% 70% at 30% 40%, #000 0%, transparent 70%);
            pointer-events: none;
        }

        .ba-grid {
            position: relative; z-index: 1;
            flex: 1;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-content: start;
            gap: 20px;
        }

        /* ── Panneau d'accroche ───────────────────────────────── */
        .ba-pitch { min-width: 0; }
        .ba-logo {
            display: inline-block;
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 900; letter-spacing: -.01em;
            color: var(--ba-gold);
            text-decoration: none;
        }
        .ba-logo:hover { color: #E9CB7A; }
        .ba-tagline {
            margin: 6px 0 0;
            font-family: 'Syne', sans-serif;
            font-size: 11px; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--ba-muted);
        }
        .ba-pitch-title {
            margin: 14px 0 0;
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 900; line-height: 1.2;
            color: var(--ba-ink);
        }
        .ba-pitch-title em { font-style: normal; color: var(--ba-gold); }
        .ba-benefits { display: none; list-style: none; margin: 0; padding: 0; }
        .ba-trust {
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px;
            margin: 12px 0 0;
            font-size: 12.5px; color: var(--ba-muted);
        }
        .ba-trust span { display: inline-flex; align-items: center; white-space: nowrap; }
        .ba-trust span + span::before { content: '·'; margin-right: 6px; color: var(--ba-gold); }
        @media (max-width: 1023px) {
            .ba-trust { gap: 4px 6px; font-size: 12px; letter-spacing: -.005em; }
            .ba-trust svg { width: 14px; height: 14px; }
        }
        .ba-trust svg { width: 15px; height: 15px; color: var(--ba-green); flex: 0 0 auto; }

        /* ── Carte du formulaire ──────────────────────────────── */
        .ba-panel { min-width: 0; display: flex; flex-direction: column; }
        .ba-card {
            position: relative;
            width: 100%;
            background: linear-gradient(180deg, var(--ba-card-2) 0%, var(--ba-card) 100%);
            border: 1px solid rgba(212,178,90,.22);
            border-radius: 10px;
            padding: 24px 20px;
            box-shadow: 0 24px 60px rgba(0,0,0,.45);
        }
        .ba-card::before {
            content: '';
            position: absolute; top: -1px; left: 16px; right: 16px; height: 2px;
            background: linear-gradient(90deg, transparent, var(--ba-gold), rgba(43,217,174,.7), transparent);
            border-radius: 2px;
        }

        .ba-head { margin-bottom: 20px; }
        .ba-title {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 900; line-height: 1.15;
            color: var(--ba-ink);
        }
        .ba-sub { margin: 6px 0 0; font-size: 15px; line-height: 1.5; color: var(--ba-muted); }

        /* Bouton Google */
        .ba-google {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; min-height: 48px;
            padding: 10px 16px;
            background: #fff;
            color: #1F2430;
            border: 1px solid #fff;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 600;
            text-decoration: none;
            transition: background .2s, box-shadow .2s;
        }
        .ba-google:hover { background: #F1F3F7; color: #1F2430; box-shadow: 0 6px 18px rgba(0,0,0,.25); }
        .ba-google svg { width: 20px; height: 20px; flex: 0 0 auto; }

        .ba-sep { display: flex; align-items: center; gap: 12px; margin: 20px 0; }
        .ba-sep::before, .ba-sep::after { content: ''; flex: 1; height: 1px; background: var(--ba-line-soft); }
        .ba-sep span {
            font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
            letter-spacing: .16em; text-transform: uppercase; color: var(--ba-muted);
        }

        /* Champs */
        .ba-field { margin-bottom: 16px; }
        .ba-label {
            display: block; margin-bottom: 6px;
            font-size: 14px; font-weight: 600; color: var(--ba-text);
        }
        .ba-input {
            display: block; width: 100%; min-height: 48px;
            padding: 12px 14px;
            background: var(--ba-input);
            color: var(--ba-ink);
            border: 1px solid var(--ba-line);
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;               /* 16 px : pas de zoom automatique sur iPhone */
            line-height: 1.4;
            transition: border-color .2s, box-shadow .2s;
            -webkit-appearance: none; appearance: none;
        }
        .ba-input::placeholder { color: #7D869C; }
        .ba-input:focus {
            outline: none;
            border-color: var(--ba-gold);
            box-shadow: 0 0 0 3px var(--ba-gold-soft);
        }
        .ba-input:-webkit-autofill,
        .ba-input:-webkit-autofill:hover,
        .ba-input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ba-ink);
            -webkit-box-shadow: 0 0 0 1000px var(--ba-input) inset;
            caret-color: var(--ba-ink);
        }
        .ba-input.is-invalid { border-color: var(--ba-error-line); }
        .ba-input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(255,107,107,.2); }

        .ba-pass { position: relative; }
        .ba-pass .ba-input { padding-right: 56px; }
        .ba-eye {
            position: absolute; top: 0; right: 0;
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            background: transparent; border: 0; border-radius: 0 6px 6px 0;
            color: var(--ba-muted); cursor: pointer;
        }
        .ba-eye:hover, .ba-eye:focus-visible { color: var(--ba-gold); }
        .ba-eye:focus-visible { outline: 2px solid var(--ba-gold); outline-offset: -4px; }
        .ba-eye svg { width: 22px; height: 22px; }
        .ba-eye .ba-eye-off { display: none; }
        .ba-eye[aria-pressed="true"] .ba-eye-on { display: none; }
        .ba-eye[aria-pressed="true"] .ba-eye-off { display: block; }

        .ba-error {
            display: flex; align-items: flex-start; gap: 6px;
            margin: 6px 0 0; padding: 0; list-style: none;
            font-size: 14px; line-height: 1.4; color: var(--ba-error);
        }
        .ba-error::before { content: '!'; flex: 0 0 auto; width: 18px; height: 18px; margin-top: 1px; border-radius: 50%;
            background: var(--ba-error-line); color: #1a0505; font-size: 12px; font-weight: 800; text-align: center; line-height: 18px; }
        .ba-hint { margin: 6px 0 0; font-size: 13px; color: var(--ba-muted); }

        .ba-status {
            margin-bottom: 16px; padding: 12px 14px;
            border: 1px solid rgba(43,217,174,.35); border-radius: 6px;
            background: rgba(43,217,174,.08);
            font-size: 14px; color: var(--ba-green);
        }

        .ba-row {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 4px 12px; margin: 4px 0 18px;
        }
        .ba-check {
            display: inline-flex; align-items: center; gap: 10px;
            min-height: 48px; cursor: pointer;
            font-size: 15px; color: var(--ba-text);
        }
        .ba-check input { width: 20px; height: 20px; margin: 0; accent-color: var(--ba-gold); }

        .ba-link {
            display: inline-flex; align-items: center; min-height: 48px;
            color: var(--ba-gold); font-weight: 600; font-size: 15px;
            text-decoration: none;
        }
        .ba-link:hover { color: #E9CB7A; text-decoration: underline; }

        /* hCaptcha : 303 px de large, réduit si la carte est plus étroite */
        .ba-captcha { margin: 4px 0 18px; min-height: 78px; }
        .ba-captcha .h-captcha { transform-origin: 0 0; }
        @media (max-width: 374px) {
            .ba-captcha .h-captcha { transform: scale(.86); margin-bottom: -11px; }
        }

        .ba-submit {
            position: relative;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; min-height: 52px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #E2C26A 0%, var(--ba-gold) 45%, var(--ba-gold-2) 100%);
            color: #120C00;
            border: 0; border-radius: 6px;
            font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 800; letter-spacing: .04em;
            cursor: pointer;
            box-shadow: 0 10px 26px rgba(212,178,90,.22);
            transition: transform .2s, box-shadow .2s, filter .2s;
        }
        .ba-submit:hover { transform: translateY(-1px); box-shadow: 0 14px 32px rgba(212,178,90,.32); }
        .ba-submit:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
        .ba-submit[disabled] { cursor: progress; filter: saturate(.7); transform: none; }
        .ba-spinner {
            display: none; width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(18,12,0,.35); border-top-color: #120C00;
            animation: ba-spin .8s linear infinite;
        }
        .ba-submit.is-loading .ba-spinner { display: inline-block; }
        @keyframes ba-spin { to { transform: rotate(360deg); } }

        .ba-alt { margin: 18px 0 0; text-align: center; font-size: 15px; color: var(--ba-muted); }
        .ba-legal { margin: 14px 0 0; text-align: center; font-size: 13px; line-height: 1.5; color: var(--ba-muted); }

        .ba-footer {
            position: relative; z-index: 1;
            margin: 20px 0 0; text-align: center;
            font-size: 12px; color: var(--ba-muted);
        }

        /* ── Autres pages invitées (composants Breeze) ────────── */
        .ba-card input:not(.ba-input):not([type="checkbox"]):not([type="hidden"]) {
            width: 100%; min-height: 48px; padding: 12px 14px;
            background: var(--ba-input) !important; color: var(--ba-ink) !important;
            border: 1px solid var(--ba-line) !important; border-radius: 6px !important;
            font-size: 16px !important; box-shadow: none !important;
        }
        .ba-card input:not(.ba-input):focus { border-color: var(--ba-gold) !important; box-shadow: 0 0 0 3px var(--ba-gold-soft) !important; outline: none; }
        .ba-card label:not(.ba-label):not(.ba-check) { color: var(--ba-text) !important; font-size: 14px !important; font-weight: 600 !important; }
        .ba-card button[type="submit"]:not(.ba-submit) {
            width: 100%; min-height: 48px; justify-content: center;
            background: linear-gradient(135deg, #E2C26A, var(--ba-gold-2)) !important;
            color: #120C00 !important; border: 0 !important; border-radius: 6px !important;
            font-family: 'Syne', sans-serif !important; font-weight: 800 !important; font-size: 14px !important;
        }
        .ba-card .text-gray-600, .ba-card .text-gray-500, .ba-card .text-gray-700 { color: var(--ba-muted) !important; }
        .ba-card [class*="text-red"] { color: var(--ba-error) !important; }
        .ba-card [class*="text-green"] { color: var(--ba-green) !important; }
        .ba-card a:not(.ba-google):not(.ba-link) { color: var(--ba-gold); }

        /* ── Desktop : deux colonnes ──────────────────────────── */
        @media (min-width: 1024px) {
            .ba-shell { padding: 48px 32px 24px; justify-content: center; }
            .ba-grid {
                flex: 0 0 auto;
                grid-template-columns: minmax(0, 1.05fr) minmax(0, 460px);
                align-items: center; align-content: center;
                gap: 72px;
            }
            .ba-logo { font-size: 34px; }
            .ba-pitch-title { margin-top: 28px; font-size: 44px; line-height: 1.12; max-width: 520px; }
            .ba-pitch-lead { display: block; margin: 16px 0 0; max-width: 480px; font-size: 17px; line-height: 1.6; color: var(--ba-muted); }
            .ba-benefits { display: grid; gap: 14px; margin-top: 30px; max-width: 500px; }
            .ba-benefit {
                display: flex; align-items: flex-start; gap: 14px;
                padding: 14px 16px;
                background: rgba(255,255,255,.025);
                border: 1px solid var(--ba-line-soft);
                border-radius: 8px;
            }
            .ba-benefit-icon {
                flex: 0 0 auto; width: 42px; height: 42px; border-radius: 8px;
                display: flex; align-items: center; justify-content: center;
                background: var(--ba-gold-soft); color: var(--ba-gold);
            }
            .ba-benefit-icon svg { width: 22px; height: 22px; }
            .ba-benefit strong { display: block; color: var(--ba-ink); font-size: 16px; font-weight: 600; }
            .ba-benefit div > span { display: block; margin-top: 2px; font-size: 14px; line-height: 1.45; color: var(--ba-muted); }
            .ba-trust { margin-top: 26px; font-size: 14px; }
            .ba-card { padding: 34px 34px 30px; }
            .ba-footer { margin-top: 36px; }
        }
        .ba-pitch-lead { display: none; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation: none !important; }
        }
    </style>
</head>

<body>
<div class="ba-shell">
    <main class="ba-grid">

        {{-- Accroche : complète sur ordinateur, courte sur mobile --}}
        <section class="ba-pitch" aria-label="Boursiv">
            <a href="{{ route('landing') }}" class="ba-logo">Boursiv</a>
            <p class="ba-tagline">Comprends · Analyse · Progresse</p>

            <h2 class="ba-pitch-title">S'éduquer aujourd'hui, <em>prospérer</em> demain.</h2>
            <p class="ba-pitch-lead">Un seul compte pour jouer, t'entraîner sur la BRVM sans risque et te former à ton rythme.</p>

            <ul class="ba-benefits">
                <li class="ba-benefit">
                    <span class="ba-benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="11" rx="4"/><path d="M7 11v3M5.5 12.5h3"/><circle cx="16" cy="11.5" r=".9" fill="currentColor"/><circle cx="18" cy="13.8" r=".9" fill="currentColor"/></svg>
                    </span>
                    <div>
                        <strong>Joue à Akwaba et sauvegarde ta progression</strong>
                        <span>Reprends ton enquête où tu l'as laissée, sur téléphone ou ordinateur.</span>
                    </div>
                </li>
                <li class="ba-benefit">
                    <span class="ba-benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/><path d="M15 8h4v4"/></svg>
                    </span>
                    <div>
                        <strong>Portefeuille virtuel BRVM</strong>
                        <span>Achète et vends des actions de la BRVM avec de l'argent fictif.</span>
                    </div>
                </li>
                <li class="ba-benefit">
                    <span class="ba-benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5.5A2.5 2.5 0 0 1 4.5 3H10a2 2 0 0 1 2 2v15a2 2 0 0 0-2-2H2z"/><path d="M22 5.5A2.5 2.5 0 0 0 19.5 3H14a2 2 0 0 0-2 2v15a2 2 0 0 1 2-2h8z"/></svg>
                    </span>
                    <div>
                        <strong>Analyses et formations</strong>
                        <span>Actualités du marché, fiches sociétés et formations pour progresser.</span>
                    </div>
                </li>
            </ul>

            <p class="ba-trust">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                <span>Gratuit</span><span>30 secondes</span><span>Paiement Mobile Money sécurisé</span>
            </p>
        </section>

        {{-- Formulaire --}}
        <section class="ba-panel">
            <div class="ba-card">
                {{ $slot }}
            </div>
        </section>

    </main>

    <p class="ba-footer">© {{ date('Y') }} Boursiv · CHENGGONG SARL</p>
</div>

<script>
(function () {
    // Afficher / masquer les mots de passe
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    });

    // Bouton « chargement » au submit : évite le double envoi
    document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.submitting === '1') { e.preventDefault(); return; }
            form.dataset.submitting = '1';
            var btn = form.querySelector('.ba-submit');
            if (!btn) return;
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
            var label = btn.querySelector('.ba-submit-label');
            if (label && btn.dataset.loadingText) label.textContent = btn.dataset.loadingText;
            // Désactivé après l'envoi (le bouton n'a pas de name : rien ne manque au serveur)
            setTimeout(function () { btn.disabled = true; }, 0);
        });
    });

    // Retour arrière (cache du navigateur) : le bouton redevient utilisable
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
            form.dataset.submitting = '';
            var btn = form.querySelector('.ba-submit');
            if (!btn) return;
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            var label = btn.querySelector('.ba-submit-label');
            if (label && btn.dataset.label) label.textContent = btn.dataset.label;
        });
    });
})();
</script>
</body>
</html>
