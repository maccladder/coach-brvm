{{-- ════════════════════════════════════════════════
     resources/views/akwaba-play.blade.php
     Page hôte publique d'Akwaba : le jeu (iframe, même origine) parle à Boursiv par
     postMessage ; ce script relaie vers /jeux/akwaba/{state,progress,hint/use,buy}.
     Contrat : messages « à plat » portant akwaba: 1 (voir AUDIT_AKWABA.md).
════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Akwaba">
    <meta name="theme-color" content="#0B1426">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Akwaba : le mystère de la lagune Ébrié. Gratuit pour commencer, histoire complète 1 000 FCFA.">
    @if(!$product || $product->status !== 'published')
        <meta name="robots" content="noindex">
    @endif
    <title>🎮 Akwaba : le mystère de la lagune Ébrié · Boursiv</title>
    <style>
        :root {
            --ak-navy: #0B1426;
            --ak-navy-2: #132038;
            --ak-gold: #C9A84C;
            --ak-teal: #1FB5A6;
            --ak-ink: #E8EAF0;
            --ak-muted: #8A93AB;
            --ak-danger: #E2675A;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; height: -webkit-fill-available; }
        body { background: var(--ak-navy); height: 100%; overflow: hidden;
               /* Bloque le scroll rebond iOS Safari */
               position: fixed; width: 100%;
               font-family: 'Syne', system-ui, -apple-system, 'Segoe UI', sans-serif; }

        /* ── Barre de contrôle ── */
        #game-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 44px;
            background: rgba(11, 20, 38, .96);
            border-bottom: 1px solid rgba(201, 168, 76, .18);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; gap: 12px;
            transition: transform .3s ease;
        }
        #game-bar.hidden { transform: translateY(-100%); }

        .game-bar-left  { display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden; }
        .game-bar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

        .game-bar-title { font-size: 12px; font-weight: 700; color: var(--ak-ink); letter-spacing: .06em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .game-bar-chip  { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: rgba(31,181,166,.12); color: var(--ak-teal); border: 1px solid rgba(31,181,166,.3); padding: 2px 8px; border-radius: 100px; white-space: nowrap; }

        .bar-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-decoration: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; border: 1px solid; transition: all .2s; background: transparent; white-space: nowrap; font-family: inherit; }
        .bar-btn-outline { color: var(--ak-muted) !important; border-color: rgba(255,255,255,.12); }
        .bar-btn-outline:hover { border-color: var(--ak-gold); color: var(--ak-gold) !important; }
        .bar-btn-gold { background: rgba(201,168,76,.1); color: var(--ak-gold) !important; border-color: rgba(201,168,76,.28); }
        .bar-btn-gold:hover { background: rgba(201,168,76,.18); }

        #bar-toggle { position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 99; width: 60px; height: 6px; background: rgba(201,168,76,.3); border-radius: 0 0 4px 4px; cursor: pointer; transition: background .2s; }
        #bar-toggle:hover { background: rgba(201,168,76,.6); }
        #bar-toggle.up { top: 44px; }

        /* ── Iframe ── */
        #game-frame { position: fixed; top: 44px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 44px); border: none; background: var(--ak-navy); transition: top .3s, height .3s; }
        #game-frame.bar-hidden { top: 0; height: 100%; }

        /* ── Bandeau d'information (paiement, erreurs) ── */
        #ak-notice {
            display: none; position: fixed; z-index: 1003;
            left: 50%; transform: translateX(-50%);
            top: calc(52px + env(safe-area-inset-top, 0px));
            width: min(92vw, 440px); padding: 12px 38px 12px 14px; border-radius: 8px;
            background: rgba(19, 32, 56, .97); border: 1px solid rgba(201,168,76,.45);
            color: var(--ak-ink); font-size: 13px; line-height: 1.45;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
        }
        #ak-notice.show { display: block; }
        #ak-notice.ok    { border-color: rgba(31,181,166,.6); }
        #ak-notice.error { border-color: rgba(226,103,90,.6); }
        #ak-notice a { color: var(--ak-gold); }
        #ak-notice .spinner { display: inline-block; width: 12px; height: 12px; margin-right: 8px; vertical-align: -1px; border: 2px solid rgba(201,168,76,.3); border-top-color: var(--ak-gold); border-radius: 50%; animation: ak-spin .8s linear infinite; }
        #ak-notice-close { position: absolute; top: 8px; right: 10px; background: none; border: 0; color: var(--ak-muted); font-size: 16px; cursor: pointer; }
        @keyframes ak-spin { to { transform: rotate(360deg); } }

        /* Téléphone en portrait : les trois boutons d'abord, la pastille seulement si la place le permet */
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
            #game-frame { top: 36px; height: calc(100% - 36px); }
            #bar-toggle.up { top: 36px; }
            #ak-notice { top: calc(44px + env(safe-area-inset-top, 0px)); }
        }

        /* ── Mobile paysage et pseudo plein écran (iPhone) : le jeu occupe tout l'écran ── */
        @media (pointer: coarse) and (orientation: landscape) {
            #game-bar, #bar-toggle { display: none !important; }
            #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }
            #ak-notice { top: max(8px, env(safe-area-inset-top, 0px)); }
        }
        body.pseudo-fs #game-bar, body.pseudo-fs #bar-toggle { display: none !important; }
        body.pseudo-fs #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }

        #fs-exit {
            display: none; position: fixed; z-index: 1001;
            top: max(6px, env(safe-area-inset-top, 0px)); left: 50%; transform: translateX(-50%);
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(11,20,38,.7); color: var(--ak-ink); border: 1px solid rgba(255,255,255,.25);
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
            background: rgba(11,20,38,.95); border: 1px solid rgba(201,168,76,.4);
            color: var(--ak-ink); font-size: 12px; line-height: 1.45;
        }
        #ios-tip.show { display: block; }
        #ios-tip b { color: var(--ak-gold); }
        #ios-tip button { position: absolute; top: 6px; right: 8px; background: none; border: 0; color: var(--ak-muted); font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>

    <div id="game-bar">
        <div class="game-bar-left">
            <span class="game-bar-chip">🎮 Gratuit pour commencer</span>
            <span class="game-bar-title">Akwaba : le mystère de la lagune Ébrié</span>
        </div>
        <div class="game-bar-right">
            <button id="btn-fullscreen" class="bar-btn bar-btn-gold" type="button" title="Plein écran">⛶ Plein écran</button>
            @guest
                <a href="{{ route('akwaba.login') }}" class="bar-btn bar-btn-outline">Se connecter</a>
            @endguest
            <a href="{{ route('jeux') }}" class="bar-btn bar-btn-outline">← Jeux</a>
        </div>
    </div>

    <div id="bar-toggle" title="Afficher/masquer la barre"></div>

    {{-- Même origine, sans sandbox : le jeu a besoin de localStorage et de Web Audio --}}
    <iframe
        id="game-frame"
        allow="autoplay; fullscreen"
        allowfullscreen
        src="{{ route('akwaba.file', ['v' => $version], false) }}"
        title="Jeu : Akwaba, le mystère de la lagune Ébrié"
    ></iframe>

    <div id="ak-notice" role="status" aria-live="polite">
        <span id="ak-notice-text"></span>
        <button type="button" id="ak-notice-close" aria-label="Fermer">✕</button>
    </div>

    <button id="fs-exit" type="button" aria-label="Quitter le plein écran">✕</button>

    <div id="ios-tip" role="status">
        Astuce : pour jouer sans les barres de Safari, touche <b>Partager</b> puis
        <b>« Sur l'écran d'accueil »</b> et lance le jeu depuis l'icône Boursiv.
        <button type="button" id="ios-tip-close" aria-label="Fermer">✕</button>
    </div>

    <script>
    (function () {
        // URLs relatives : même origine que la page, que l'on arrive par boursiv.com ou www.
        const URLS = {
            state:    @json(route('akwaba.state', [], false)),
            progress: @json(route('akwaba.progress', [], false)),
            hint:     @json(route('akwaba.hint.use', [], false)),
            buy:      @json(route('akwaba.buy', [], false)),
            login:    @json(route('akwaba.login', [], false)),
        };
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        const ORIGIN     = window.location.origin;
        const PAID       = @json($paid);
        const WHATSAPP   = @json($whatsapp);
        const INTENT_KEY = 'akwaba_purchase_intent';

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

        // ── Bandeau d'information ───────────────────────────────
        const notice     = document.getElementById('ak-notice');
        const noticeText = document.getElementById('ak-notice-text');
        let noticeTimer  = null;
        function showNotice(html, kind, autoHideMs) {
            clearTimeout(noticeTimer);
            noticeText.innerHTML = html;
            notice.className = 'show' + (kind ? ' ' + kind : '');
            if (autoHideMs) noticeTimer = setTimeout(hideNotice, autoHideMs);
        }
        function hideNotice() { notice.className = ''; }
        document.getElementById('ak-notice-close').addEventListener('click', hideNotice);

        // ── Échanges avec le serveur ────────────────────────────
        function api(url, body) {
            return fetch(url, {
                method: body === undefined ? 'GET' : 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: Object.assign(
                    { 'Accept': 'application/json' },
                    body === undefined ? {} : { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                ),
                body: body === undefined ? undefined : JSON.stringify(body),
            }).then(r => r.json().catch(() => ({})).then(data => ({ status: r.status, ok: r.ok, data })));
        }

        // ── Échanges avec le jeu ────────────────────────────────
        function send(message) {
            message.akwaba = 1;
            frame.contentWindow.postMessage(message, ORIGIN);
        }

        let state = null;      // dernier état serveur connu
        let gameReady = false;

        function sameState(a, b) { return JSON.stringify(a) === JSON.stringify(b); }

        function pushState() {
            if (!state || !gameReady) return;
            send({
                type: 'state',
                logged: !!state.logged,
                unlocked: !!state.unlocked,
                hints: Number.isInteger(state.hints) ? state.hints : 0,
                progress: state.progress || { save: null, fins: [] },
            });
        }

        function refreshState() {
            return api(URLS.state).then(res => {
                if (res.ok && res.data && typeof res.data.logged === 'boolean') {
                    const changed = !sameState(state, res.data);
                    state = res.data;
                    if (changed) pushState();
                }
                return state;
            }).catch(() => state);
        }

        // Chargé en parallèle de l'iframe, envoyé au jeu dès son message « ready »
        const firstState = refreshState();

        // Progression : envoi 1 s après le dernier changement (le jeu peut en émettre plusieurs d'affilée)
        let progressTimer = null;
        let pendingProgress = null;
        function queueProgress(save, fins) {
            if (!state || !state.logged) return;
            pendingProgress = { save: save, fins: fins };
            clearTimeout(progressTimer);
            progressTimer = setTimeout(() => {
                const body = pendingProgress;
                pendingProgress = null;
                api(URLS.progress, body).then(res => {
                    if (res.ok && res.data && res.data.progress) state.progress = res.data.progress;
                }).catch(() => {});
            }, 1000);
        }

        function useHint(id) {
            const fail = () => send({ type: 'hintResult', id: id, ok: false, hints: state ? state.hints : 0 });
            if (!state || !state.logged) return fail();

            api(URLS.hint, { id: id }).then(res => {
                if (!res.ok || !res.data || typeof res.data.ok !== 'boolean') return fail();
                state.hints = res.data.hints;
                send({ type: 'hintResult', id: id, ok: res.data.ok, hints: res.data.hints });
            }).catch(fail);
        }

        let buying = false;
        function buy(product) {
            if (product !== 'full' && product !== 'hints5') return;

            if (!state || !state.logged) {
                window.location.href = URLS.login;
                return;
            }
            if (buying) return;
            buying = true;
            showNotice('<span class="spinner"></span>Ouverture du paiement Mobile Money…');

            api(URLS.buy, { product: product }).then(res => {
                if (res.ok && res.data && res.data.url) {
                    // Pour savoir au retour si le paiement attendu est arrivé
                    try {
                        sessionStorage.setItem(INTENT_KEY, JSON.stringify({ product: product, hints: state.hints, at: Date.now() }));
                    } catch (e) {}
                    window.location.href = res.data.url; // la page parente, pas l'iframe
                    return;
                }
                buying = false;
                if (res.status === 409) {
                    hideNotice();
                    refreshState();
                } else if (res.status === 401 || res.status === 419) {
                    showNotice('Ta session a expiré. <a href="' + URLS.login + '">Reconnecte-toi</a> pour continuer.', 'error');
                } else {
                    showNotice("Le paiement n'a pas pu démarrer. Réessaie dans un instant.", 'error', 6000);
                }
            }).catch(() => {
                buying = false;
                showNotice('Connexion impossible. Vérifie ton réseau et réessaie.', 'error', 6000);
            });
        }

        // ── Retour de paiement : le webhook peut arriver quelques secondes après ──
        function readIntent() {
            try {
                const raw = sessionStorage.getItem(INTENT_KEY);
                sessionStorage.removeItem(INTENT_KEY);
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
            const success = () => showNotice(
                intent && intent.product === 'hints5' ? '✅ Paiement validé : 5 indices ajoutés.' : "✅ Paiement validé : la suite de l'histoire est débloquée.",
                'ok', 5000
            );

            firstState.then(initial => {
                if (isDone(initial)) return success();

                showNotice('<span class="spinner"></span>Paiement en cours de validation…');
                const baseline = JSON.stringify(initial);
                const started  = Date.now();

                const timer = setInterval(() => {
                    refreshState().then(s => {
                        if (isDone(s) || (!intent && JSON.stringify(s) !== baseline)) {
                            clearInterval(timer);
                            success();
                        } else if (Date.now() - started >= 30000) {
                            clearInterval(timer);
                            showNotice(
                                "Le paiement n'est pas encore confirmé. S'il a bien été débité, il s'affichera automatiquement dans quelques minutes."
                                + (WHATSAPP ? ' Besoin d\'aide ? <a href="https://wa.me/' + WHATSAPP + '" target="_blank" rel="noopener">WhatsApp</a>' : ''),
                                'error'
                            );
                        }
                    });
                }, 2000);
            });
        }

        // ── Messages du jeu ────────────────────────────────────
        window.addEventListener('message', function (event) {
            if (event.source !== frame.contentWindow || event.origin !== ORIGIN) return;

            const data = event.data;
            if (!data || data.akwaba !== 1 || typeof data.type !== 'string') return;

            switch (data.type) {
                case 'ready':
                    gameReady = true;
                    firstState.then(pushState);
                    break;
                case 'progress':
                    queueProgress(
                        typeof data.save === 'string' ? data.save : null,
                        Array.isArray(data.fins) ? data.fins.filter(f => typeof f === 'string') : []
                    );
                    break;
                case 'buy':
                    buy(data.product);
                    break;
                case 'useHint':
                    if (typeof data.id === 'string' && data.id.length <= 64) useHint(data.id);
                    break;
            }
        });

        // Revenu sur l'onglet (ex. paiement terminé dans une autre fenêtre) : état à jour
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && gameReady) refreshState();
        });

        if (PAID) watchPayment();
    })();
    </script>
</body>
</html>
