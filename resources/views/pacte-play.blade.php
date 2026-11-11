{{-- ════════════════════════════════════════════════
     resources/views/pacte-play.blade.php
     Page hôte publique du Pacte avec les caïmans : le jeu (iframe, même origine) est servi par
     /jeux/le-pacte/v/{version}/index.html, ses images par /jeux/le-pacte/v/{version}/img/...
     Même structure que akwaba-play.blade.php (barre, plein écran, navigateurs intégrés,
     chargement, pixel différé).
════════════════════════════════════════════════ --}}
@php
    $title    = 'Le Pacte avec les caïmans de Yamoussoukro';
    $tagline  = 'Ce que le lac reçoit, il ne le rend jamais.';
    $desc     = "Un jeu d'énigmes à Yamoussoukro. Gratuit pour commencer.";
    $pageUrl  = 'https://boursiv.com/jeux/le-pacte';
    // Image de partage 1200×630 ; la couverture du catalogue tant qu'elle n'est pas livrée
    $hasShare = is_file(public_path('img/jeux/le-pacte/le-pacte-partage.jpg'));
    $shareImg = 'https://boursiv.com/img/jeux/le-pacte/' . ($hasShare ? 'le-pacte-partage.jpg' : 'le-pacte-hero.jpg');
    $pixelId  = config('services.facebook_pixel.id');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Le Pacte">
    <meta name="theme-color" content="#070d17">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $desc }}">

    {{-- Partage (WhatsApp, Facebook, X…) : URLs absolues de production, quel que soit le domaine d'arrivée --}}
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Boursiv">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="« {{ $tagline }} » {{ $desc }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $shareImg }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="{{ $hasShare ? 1200 : 943 }}">
    <meta property="og:image:height" content="{{ $hasShare ? 630 : 900 }}">
    <meta property="og:image:alt" content="{{ $title }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="« {{ $tagline }} » {{ $desc }}">
    <meta name="twitter:image" content="{{ $shareImg }}">
    <title>🎮 {{ $title }} · Boursiv</title>

    <style>
        :root {
            --pc-nuit: #070d17;
            --pc-nuit-2: #111a28;
            --pc-or: #e2b85c;
            --pc-creme: #f4e8cd;
            --pc-ink: #ece6d8;
            --pc-muted: #9a937f;
            --pc-danger: #E2675A;
            --pc-ok: #6fbf8e;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; height: -webkit-fill-available; height: 100dvh; }
        body { background: var(--pc-nuit); height: 100vh; height: 100dvh; overflow: hidden;
               position: fixed; width: 100%;
               font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; }

        /* ── Barre de contrôle ── */
        #game-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 44px;
            background: rgba(7, 13, 23, .96);
            border-bottom: 1px solid rgba(226, 184, 92, .18);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; gap: 12px;
            transition: transform .3s ease;
        }
        #game-bar.hidden { transform: translateY(-100%); }
        .game-bar-left  { display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden; }
        .game-bar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .game-bar-title { font-size: 12px; font-weight: 700; color: var(--pc-ink); letter-spacing: .06em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .game-bar-chip  { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: rgba(226,184,92,.1); color: var(--pc-or); border: 1px solid rgba(226,184,92,.3); padding: 2px 8px; border-radius: 100px; white-space: nowrap; }

        .bar-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-decoration: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; border: 1px solid; transition: all .2s; background: transparent; white-space: nowrap; font-family: inherit; }
        .bar-btn-outline { color: var(--pc-muted) !important; border-color: rgba(255,255,255,.12); }
        .bar-btn-outline:hover { border-color: var(--pc-or); color: var(--pc-or) !important; }
        .bar-btn-gold { background: rgba(226,184,92,.1); color: var(--pc-or) !important; border-color: rgba(226,184,92,.28); }
        .bar-btn-gold:hover { background: rgba(226,184,92,.18); }

        #bar-toggle { position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 99; width: 60px; height: 6px; background: rgba(226,184,92,.3); border-radius: 0 0 4px 4px; cursor: pointer; transition: background .2s; }
        #bar-toggle:hover { background: rgba(226,184,92,.6); }
        #bar-toggle.up { top: 44px; }

        /* ── Iframe : sous la barre et jusqu'au bas visible de l'écran ── */
        #game-frame { position: fixed; top: 44px; left: 0; right: 0; width: 100%; height: calc(100vh - 44px); height: calc(100dvh - 44px); border: none; background: var(--pc-nuit); transition: top .3s, height .3s; }
        #game-frame.bar-hidden { top: 0; height: 100vh; height: 100dvh; }

        /* ── Bandeau d'information (paiement, erreurs) ── */
        #pc-notice {
            display: none; position: fixed; z-index: 1003;
            left: 50%; transform: translateX(-50%);
            top: calc(52px + env(safe-area-inset-top, 0px));
            width: min(92vw, 440px); padding: 12px 38px 12px 14px; border-radius: 8px;
            background: rgba(17, 26, 40, .97); border: 1px solid rgba(226,184,92,.45);
            color: var(--pc-ink); font-size: 13px; line-height: 1.45;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
        }
        #pc-notice.show { display: block; }
        #pc-notice.ok    { border-color: rgba(111,191,142,.6); }
        #pc-notice.error { border-color: rgba(226,103,90,.6); }
        #pc-notice a { color: var(--pc-or); }
        #pc-notice strong { display: block; margin-bottom: 2px; }
        .pc-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .pc-btn { flex: 1 1 auto; min-height: 40px; padding: 8px 12px; border-radius: 6px; cursor: pointer;
                  font-family: inherit; font-size: 12px; font-weight: 700; line-height: 1.2; letter-spacing: .03em; color: var(--pc-ink);
                  background: transparent; border: 1px solid rgba(255,255,255,.3); }
        .pc-btn-gold { background: var(--pc-or); color: var(--pc-nuit); border-color: var(--pc-or); }
        #pc-notice .spinner { display: inline-block; width: 12px; height: 12px; margin-right: 8px; vertical-align: -1px; border: 2px solid rgba(226,184,92,.3); border-top-color: var(--pc-or); border-radius: 50%; animation: pc-spin .8s linear infinite; }
        #pc-notice-close { position: absolute; top: 8px; right: 10px; background: none; border: 0; color: var(--pc-muted); font-size: 16px; cursor: pointer; }
        @keyframes pc-spin { to { transform: rotate(360deg); } }

        @media (max-width: 560px) {
            .game-bar-title { display: none; }
            .bar-btn { padding: 6px 9px; letter-spacing: .04em; }
        }
        @media (max-width: 400px) {
            .game-bar-chip { display: none; }
        }
        @media (hover: none) and (pointer: coarse) {
            #game-bar { height: 36px; padding: 0 10px; }
            .game-bar-title { display: none; }
            #game-frame { top: 36px; height: calc(100vh - 36px); height: calc(100dvh - 36px); }
            #bar-toggle.up { top: 36px; }
            #pc-notice { top: calc(44px + env(safe-area-inset-top, 0px)); }
        }

        /* ── Mobile paysage et pseudo plein écran (iPhone) : le jeu occupe tout l'écran ── */
        @media (pointer: coarse) and (orientation: landscape) {
            #game-bar, #bar-toggle { display: none !important; }
            #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }
            #pc-notice { top: max(8px, env(safe-area-inset-top, 0px)); }
        }
        body.pseudo-fs #game-bar, body.pseudo-fs #bar-toggle { display: none !important; }
        body.pseudo-fs #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }

        /* ── Écran de chargement : retiré quand le jeu a fini de charger ── */
        #pc-loading {
            position: fixed; inset: 0; z-index: 1001;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 14px; padding: 24px; text-align: center;
            background: var(--pc-nuit); color: var(--pc-ink);
            transition: opacity .45s ease, visibility .45s;
        }
        #pc-loading.done { opacity: 0; visibility: hidden; pointer-events: none; }
        .pc-load-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(38px, 11vw, 64px); letter-spacing: .18em; color: var(--pc-or); line-height: 1; padding-left: .18em; }
        .pc-load-sub { font-family: Georgia, 'Times New Roman', serif; font-style: italic; font-size: clamp(15px, 4.2vw, 20px); color: var(--pc-creme); }
        .pc-load-bar { position: relative; width: min(240px, 70vw); height: 4px; margin-top: 18px; border-radius: 4px; overflow: hidden; background: rgba(226,184,92,.18); }
        .pc-load-bar span { position: absolute; top: 0; left: -40%; width: 40%; height: 100%; border-radius: 4px; background: var(--pc-or); animation: pc-load 1.4s ease-in-out infinite; }
        .pc-load-text { font-size: 14px; color: var(--pc-ink); margin-top: 4px; }
        .pc-load-text small { display: block; margin-top: 4px; font-size: 12px; color: var(--pc-muted); }
        @keyframes pc-load { 0% { left: -40%; } 100% { left: 100%; } }
        @media (prefers-reduced-motion: reduce) {
            .pc-load-bar span { animation: none; left: 0; width: 100%; opacity: .55; }
        }

        #fs-exit {
            display: none; position: fixed; z-index: 1001;
            top: max(6px, env(safe-area-inset-top, 0px)); left: 50%; transform: translateX(-50%);
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(7,13,23,.7); color: var(--pc-ink); border: 1px solid rgba(255,255,255,.25);
            font-size: 15px; line-height: 1; cursor: pointer;
        }
        body.pseudo-fs #fs-exit { display: block; }
        @media (orientation: portrait) {
            #fs-exit { left: auto; right: max(8px, env(safe-area-inset-right, 0px)); transform: none; }
        }

        #ios-tip {
            display: none; position: fixed; z-index: 1002;
            left: 50%; transform: translateX(-50%);
            bottom: max(12px, env(safe-area-inset-bottom, 0px));
            width: min(92vw, 420px); padding: 10px 36px 10px 14px; border-radius: 8px;
            background: rgba(7,13,23,.95); border: 1px solid rgba(226,184,92,.4);
            color: var(--pc-ink); font-size: 12px; line-height: 1.45;
        }
        #ios-tip.show { display: block; }
        #ios-tip b { color: var(--pc-or); }
        #ios-tip button { position: absolute; top: 6px; right: 8px; background: none; border: 0; color: var(--pc-muted); font-size: 16px; cursor: pointer; }

        /* ── Bandeau navigateur intégré (Facebook, Instagram…) : par-dessus la page, ne décale rien ── */
        #inapp-banner {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1004;
            display: flex; align-items: center; gap: 10px;
            padding: 8px 40px 8px 12px; padding-top: max(8px, env(safe-area-inset-top, 0px));
            background: rgba(17, 26, 40, .97); border-bottom: 1px solid rgba(226,184,92,.45);
            color: var(--pc-ink); font-size: 12.5px; line-height: 1.35;
            box-shadow: 0 6px 18px rgba(0,0,0,.35);
        }
        #inapp-banner[hidden] { display: none; }
        #inapp-banner .inapp-text { flex: 1 1 auto; min-width: 0; }
        #inapp-banner .inapp-btn {
            flex: 0 0 auto; display: inline-flex; align-items: center; min-height: 34px; padding: 6px 12px;
            border-radius: 6px; background: var(--pc-or); color: var(--pc-nuit); font-weight: 700;
            font-size: 12px; text-decoration: none; white-space: nowrap;
        }
        #inapp-close {
            position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
            width: 30px; height: 30px; border: 0; background: none; color: var(--pc-muted);
            font-size: 18px; line-height: 1; cursor: pointer;
        }
    </style>

    {{-- Pont avec le jeu : window.PacteHost, défini ici, AVANT l'iframe. Le jeu le reprend au
         chargement (script ajouté en tête du jeu par PacteController::HOST_BRIDGE).
         Invité : estDebloque (toujours faux), debloquer / acheterIndices (qui mènent à la
         connexion) ; le jeu garde son localStorage pour la sauvegarde et l'indice gratuit.
         Connecté : tout passe par le serveur. « test » : raccourcis de test du jeu, en local seulement. --}}
    <script nonce="{{ $nonce }}">
    (function () {
        const URLS = {
            state:    @json(route('pacte.state', [], false)),
            progress: @json(route('pacte.progress', [], false)),
            fin:      @json(route('pacte.fin', [], false)),
            hint:     @json(route('pacte.hint.use', [], false)),
            freeHint: @json(route('pacte.hint.free', [], false)),
            buy:      @json(route('pacte.buy', [], false)),
            login:    @json(route('pacte.login', [], false)),
        };
        const LOGGED     = @json(auth()->check());
        // Raccourcis de test du jeu (#test-…, ?chap=…, #debug) : en local seulement
        const TEST_MODE  = @json(app()->environment('local'));
        const CSRF_TOKEN = @json(csrf_token());
        const INTENT_KEY = 'pacte_purchase_intent';
        const LOCAL_HINTS_KEY = 'pacte-caimans-indices'; // CLE_IND du jeu

        // ── Bandeau d'information (éléments du body, cherchés au moment de l'appel) ──
        let noticeTimer = null;
        function showNotice(html, kind, autoHideMs) {
            const notice = document.getElementById('pc-notice');
            if (!notice) return;
            clearTimeout(noticeTimer);
            document.getElementById('pc-notice-text').innerHTML = html;
            notice.className = 'show' + (kind ? ' ' + kind : '');
            if (autoHideMs) noticeTimer = setTimeout(hideNotice, autoHideMs);
        }
        function hideNotice() {
            const notice = document.getElementById('pc-notice');
            if (notice) notice.className = '';
        }

        // ── Échanges avec le serveur ────────────────────────────
        // keepalive : la sauvegarde part même si la page s'en va juste après (paiement)
        function api(url, body, keepalive) {
            return fetch(url, {
                method: body === undefined ? 'GET' : 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                keepalive: !!keepalive,
                headers: Object.assign(
                    { 'Accept': 'application/json' },
                    body === undefined ? {} : { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                ),
                body: body === undefined ? undefined : JSON.stringify(body),
            }).then(r => r.json().catch(() => ({})).then(data => ({ status: r.status, ok: r.ok, data })));
        }

        let state = null; // dernier état serveur connu (connecté seulement)
        function refreshState() {
            return api(URLS.state).then(res => {
                if (res.ok && res.data && typeof res.data.logged === 'boolean') state = res.data;
                return state;
            }).catch(() => state);
        }
        const firstState = LOGGED ? refreshState() : Promise.resolve(null);

        // Invité qui avait déjà utilisé son indice gratuit sur ce téléphone (jeu sans compte) :
        // on le signale au compte, pour qu'il n'en retrouve pas un second
        const freeHintSync = firstState.then(s => {
            let localUsed = false;
            try { localUsed = localStorage.getItem(LOCAL_HINTS_KEY) === '0'; } catch (e) {}
            if (!s || !s.logged || s.freeUsed || !localUsed) return;
            return api(URLS.freeHint, {}).then(refreshState).catch(() => {});
        });

        // Sauvegardes envoyées une par une, dans l'ordre ; un paiement attend la dernière
        let saveChain = Promise.resolve();
        function saveGame(data) {
            const body = { save: data };
            const small = JSON.stringify(body).length < 60000; // limite de keepalive : 64 Ko
            saveChain = saveChain.then(() => api(URLS.progress, body, small).catch(() => {}));
            return saveChain;
        }

        function goLogin() {
            window.location.href = URLS.login;
            return new Promise(() => {}); // la page s'en va
        }

        // Paiement : la page entière part vers le checkout (pas l'iframe), comme Akwaba.
        // Résout false / 0 si le paiement n'a pas pu démarrer ; ne résout jamais sinon.
        let buying = false;
        function buy(product, classic) {
            if (product !== 'full' && product !== 'hints5') return Promise.resolve(null);
            if (!LOGGED) return goLogin();
            if (buying) return new Promise(() => {});
            buying = true;
            showNotice('<span class="spinner"></span>Ouverture du paiement Mobile Money…');

            return saveChain.then(() => api(URLS.buy, classic ? { product: product, classic: 1 } : { product: product })).then(res => {
                if (res.ok && res.data && res.data.url) {
                    try {
                        sessionStorage.setItem(INTENT_KEY, JSON.stringify({ product: product, hints: state ? state.hints : 0, at: Date.now() }));
                    } catch (e) {}
                    window.location.href = res.data.url;
                    return new Promise(() => {});
                }
                buying = false;
                if (res.status === 409) { // déjà débloquée (achat validé entre-temps)
                    hideNotice();
                    return refreshState().then(() => 'already');
                }
                if (res.status === 401 || res.status === 419) {
                    showNotice('Ta session a expiré. <a href="' + URLS.login + '">Reconnecte-toi</a> pour continuer.', 'error');
                } else {
                    showNotice("Le paiement n'a pas pu démarrer. Réessaie dans un instant.", 'error', 6000);
                }
                return null;
            }).catch(() => {
                buying = false;
                showNotice('Connexion impossible. Vérifie ton réseau et réessaie.', 'error', 6000);
                return null;
            });
        }

        const hintsNow = () => (state && Number.isInteger(state.hints) ? state.hints : 0);

        // Gelé : impossible de remplacer estDebloque (ou autre) depuis la console
        window.PacteHost = Object.freeze(LOGGED ? {
            test:        TEST_MODE,
            charger:     () => firstState.then(s => (s && s.save ? s.save : null)),
            sauver:      data => { saveGame(data); },
            estDebloque: () => refreshState().then(s => !!(s && s.unlocked)),
            debloquer:   () => buy('full').then(r => r === 'already'),
            indices:     () => freeHintSync.then(refreshState).then(hintsNow),
            utiliserIndice: () => api(URLS.hint, {}).then(res => {
                if (!res.ok || !res.data || !Number.isInteger(res.data.hints)) throw new Error('indice');
                if (state) state.hints = res.data.hints;
                return res.data.hints;
            }),
            acheterIndices: () => buy('hints5').then(r => (r === 'already' ? hintsNow() : 0)),
            fin: (cle, choix) => { api(URLS.fin, { cle: cle, choix: Array.isArray(choix) ? choix : [] }, true).catch(() => {}); },
        } : {
            test:           TEST_MODE,
            // Un invité n'a jamais la suite : une sauvegarde locale « chap3 » … ne se reprend pas
            estDebloque:    () => Promise.resolve(false),
            debloquer:      goLogin,
            acheterIndices: goLogin,
        });

        // Pour le script de la page (retour de paiement)
        window.__pacte = { firstState, refreshState, buy, showNotice, hideNotice, INTENT_KEY, getState: () => state };
    })();
    </script>
</head>
<body>

    <div id="game-bar">
        <div class="game-bar-left">
            <span class="game-bar-chip">🎮 Gratuit pour commencer</span>
            <span class="game-bar-title">{{ $title }}</span>
        </div>
        <div class="game-bar-right">
            <button id="btn-fullscreen" class="bar-btn bar-btn-gold" type="button" title="Plein écran">⛶ Plein écran</button>
            @guest
                <a href="{{ route('pacte.login') }}" class="bar-btn bar-btn-outline">Se connecter</a>
            @endguest
            <a href="{{ route('jeux') }}" class="bar-btn bar-btn-outline">← Jeux</a>
        </div>
    </div>

    <div id="bar-toggle" title="Afficher/masquer la barre"></div>

    @if($inApp['in_app'])
        <div id="inapp-banner" role="region" aria-label="Ouvrir dans le navigateur">
            <span class="inapp-text">
                Pour une meilleure expérience, ouvre le jeu dans ton navigateur.
                @if($inApp['platform'] === 'ios')
                    <br><strong>Appuie sur ··· puis « Ouvrir dans le navigateur ».</strong>
                @elseif($inApp['platform'] !== 'android')
                    <br><strong>boursiv.com/jeux/le-pacte</strong>
                @endif
            </span>
            @if($inApp['platform'] === 'android')
                <a class="inapp-btn" id="inapp-open"
                   href="intent://boursiv.com/jeux/le-pacte#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=https%3A%2F%2Fboursiv.com%2Fjeux%2Fle-pacte;end">Ouvrir dans Chrome</a>
            @endif
            <button type="button" id="inapp-close" aria-label="Fermer">×</button>
        </div>
    @endif

    {{-- Même origine, sans sandbox : le jeu a besoin de localStorage et de Web Audio --}}
    <iframe
        id="game-frame"
        allow="autoplay; fullscreen"
        allowfullscreen
        src="{{ route('pacte.file', ['version' => $version], false) }}"
        title="Jeu : {{ $title }}"
    ></iframe>

    <div id="pc-loading" role="status" aria-live="polite">
        <div class="pc-load-title" aria-hidden="true">LE PACTE</div>
        <div class="pc-load-sub">avec les caïmans de Yamoussoukro</div>
        <div class="pc-load-bar" aria-hidden="true"><span></span></div>
        <p class="pc-load-text">
            Chargement du jeu…
            <small>(le premier chargement peut prendre quelques secondes)</small>
        </p>
    </div>

    <div id="pc-notice" role="status" aria-live="polite">
        <span id="pc-notice-text"></span>
        <button type="button" id="pc-notice-close" aria-label="Fermer">✕</button>
    </div>

    <button id="fs-exit" type="button" aria-label="Quitter le plein écran">✕</button>

    <div id="ios-tip" role="status">
        Astuce : pour jouer sans les barres de Safari, touche <b>Partager</b> puis
        <b>« Sur l'écran d'accueil »</b> et lance le jeu depuis l'icône Boursiv.
        <button type="button" id="ios-tip-close" aria-label="Fermer">✕</button>
    </div>

    <script nonce="{{ $nonce }}">
    (function () {
        const bar    = document.getElementById('game-bar');
        const frame  = document.getElementById('game-frame');
        const toggle = document.getElementById('bar-toggle');
        const btnFs  = document.getElementById('btn-fullscreen');

        // ── Barre ──────────────────────────────────────────────
        let barVisible = true;
        function toggleBar() {
            barVisible = !barVisible;
            bar.classList.toggle('hidden', !barVisible);
            frame.classList.toggle('bar-hidden', !barVisible);
            toggle.classList.toggle('up', !barVisible);
        }
        toggle.addEventListener('click', toggleBar);
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !barVisible) toggleBar(); });

        // ── Plein écran (iPhone : pas d'API Fullscreen sur une iframe → pseudo plein écran CSS) ──
        const fsExit = document.getElementById('fs-exit');
        const iosTip = document.getElementById('ios-tip');
        const isIOS  = /iPhone|iPad|iPod/.test(navigator.userAgent)
            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isStandalone = window.navigator.standalone === true
            || window.matchMedia('(display-mode: standalone)').matches;

        function setPseudoFullscreen(on) {
            document.body.classList.toggle('pseudo-fs', on);
            if (on && isIOS && !isStandalone) iosTip.classList.add('show');
            if (!on) iosTip.classList.remove('show');
        }
        btnFs.addEventListener('click', () => {
            const req = frame.requestFullscreen || frame.webkitRequestFullscreen;
            if (req) {
                const p = req.call(frame);
                if (p && p.catch) p.catch(() => setPseudoFullscreen(true));
            } else {
                setPseudoFullscreen(true);
            }
        });
        fsExit.addEventListener('click', () => setPseudoFullscreen(false));
        document.getElementById('ios-tip-close').addEventListener('click', () => iosTip.classList.remove('show'));

        // ── Bandeau navigateur intégré (Facebook, Instagram…) ───
        const inappBanner = document.getElementById('inapp-banner');
        if (inappBanner) {
            try {
                if (sessionStorage.getItem('pacte_inapp_closed')) inappBanner.hidden = true;
            } catch (e) {}
            document.getElementById('inapp-close').addEventListener('click', () => {
                inappBanner.hidden = true;
                try { sessionStorage.setItem('pacte_inapp_closed', '1'); } catch (e) {}
            });
        }

        // ── Bandeau d'information (fonctions du pont, dans le head) ──
        const P = window.__pacte;
        document.getElementById('pc-notice-close').addEventListener('click', P.hideNotice);

        @if(app()->environment('local'))
        // Local seulement : raccourcis de test du jeu (#test-chap3…, #debug) transmis à l'iframe
        if (/^#(test-[\w]+|debug)$/.test(window.location.hash)) {
            frame.src = frame.getAttribute('src').split('#')[0] + window.location.hash;
        }
        @endif

        // ── Retour de paiement : le webhook peut arriver quelques secondes après ──
        const PAID     = @json($paid);
        const WHATSAPP = @json($whatsapp);
        const PRODUCT_LABELS = { full: 'Le Pacte · Histoire complète', hints5: 'Le Pacte · 5 indices' };
        const okText = product => product === 'hints5'
            ? '✅ Paiement validé : 5 indices ajoutés.'
            : "✅ Paiement validé : la suite est débloquée. Touche « Continuer » pour jouer le chapitre 3.";

        function readIntent() {
            try {
                const raw = sessionStorage.getItem(P.INTENT_KEY);
                sessionStorage.removeItem(P.INTENT_KEY);
                const intent = raw ? JSON.parse(raw) : null;
                return intent && Date.now() - intent.at < 3600 * 1000 ? intent : null;
            } catch (e) { return null; }
        }

        function watchPayment() {
            const intent = readIntent();
            history.replaceState(null, '', window.location.pathname);

            const isDone = s => {
                if (!s || !intent) return false;
                return intent.product === 'full' ? !!s.unlocked : s.hints > intent.hints;
            };
            const success = () => P.showNotice(okText(intent && intent.product), 'ok', 7000);

            P.firstState.then(initial => {
                if (!initial || !initial.logged) return;
                if (isDone(initial)) return success();

                P.showNotice('<span class="spinner"></span>Paiement en cours de validation…');
                const baseline = JSON.stringify(initial);
                const started  = Date.now();

                const timer = setInterval(() => {
                    P.refreshState().then(s => {
                        if (isDone(s) || (!intent && JSON.stringify(s) !== baseline)) {
                            clearInterval(timer);
                            success();
                        } else if (Date.now() - started >= 30000) {
                            clearInterval(timer);
                            P.showNotice(
                                "Le paiement n'est pas encore confirmé. S'il a bien été débité, il s'affichera automatiquement dans quelques minutes."
                                + (WHATSAPP ? ' Besoin d\'aide ? <a href="https://wa.me/' + WHATSAPP + '" target="_blank" rel="noopener">WhatsApp</a>' : ''),
                                'error'
                            );
                        }
                    });
                }, 2000);
            });
        }

        // Retour du checkout Mobile Money sans paiement validé (?paiement=echec|annule)
        function showRetry(product, kind) {
            P.showNotice('', 'error');
            const text = document.getElementById('pc-notice-text');
            text.textContent = '';

            const title = document.createElement('strong');
            title.textContent = kind === 'echec' ? "Le paiement n'est pas passé." : 'Paiement interrompu.';
            const detail = document.createElement('div');
            detail.textContent = PRODUCT_LABELS[product] + (kind === 'echec'
                ? " : si tu as validé sur ton téléphone, l'achat s'activera tout seul dans quelques minutes."
                : ' : tu peux réessayer quand tu veux.');

            const actions = document.createElement('div');
            actions.className = 'pc-actions';
            [['Réessayer', 'pc-btn pc-btn-gold', false], ['Payer par la page Paystack', 'pc-btn', true]].forEach(([label, cls, classic]) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = cls;
                b.textContent = label;
                b.addEventListener('click', () => P.buy(product, classic));
                actions.appendChild(b);
            });

            text.append(title, detail, actions);
        }

        function watchFailedPayment(product, kind) {
            const intent = readIntent();
            history.replaceState(null, '', window.location.pathname);

            // Validé entre-temps (webhook en retard, ancienne tentative) : on le dit plutôt
            P.firstState.then(s => {
                const done = s && (product === 'full' ? !!s.unlocked : (intent && s.hints > intent.hints));
                if (done) P.showNotice(okText(product), 'ok', 7000);
                else if (s && s.logged) showRetry(product, kind);
            });
        }

        const query = new URLSearchParams(window.location.search);
        const failKind = query.get('paiement');
        const failProduct = query.get('produit');

        if (PAID) {
            watchPayment();
        } else if ((failKind === 'echec' || failKind === 'annule') && (failProduct === 'full' || failProduct === 'hints5')) {
            watchFailedPayment(failProduct, failKind);
        }

        // ── Pixel Meta : chargé 3 s après le chargement du jeu (comme Akwaba) ──
        const PIXEL_ID = @json(!empty($pixelId) ? (string) $pixelId : null);
        const NONCE    = @json($nonce);
        let pixelArmed = false;
        function loadPixel() {
            if (!PIXEL_ID || pixelArmed) return;
            pixelArmed = true;
            @if(!empty($pixelId))
            setTimeout(function () {
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.nonce=NONCE;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', PIXEL_ID);
                fbq('track', 'PageView');
            }, 3000);
            @endif
        }

        // ── Écran de chargement : retiré au chargement complet du jeu (HTML et premières images),
        //    même s'il était déjà chargé avant ce script (cache), et dans tous les cas au bout de 60 s ──
        const loading = document.getElementById('pc-loading');
        let loaded = false;
        function gameLoaded() {
            if (loaded) return;
            loaded = true;
            loading.classList.add('done');
            loading.setAttribute('aria-hidden', 'true');
            loadPixel();
        }
        frame.addEventListener('load', gameLoaded);
        try {
            const doc = frame.contentDocument;
            if (doc && doc.readyState === 'complete' && doc.URL !== 'about:blank') gameLoaded();
        } catch (e) {}
        setTimeout(gameLoaded, 60000);
    })();
    </script>
</body>
</html>
