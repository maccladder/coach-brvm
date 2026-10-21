{{-- ════════════════════════════════════════════════
     resources/views/prince-de-yop-play.blade.php
     Wrapper du Prince de Yop : relaie la progression entre le jeu (iframe) et le serveur.
════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    {{-- Raccourci « Sur l'écran d'accueil » (iPhone) : ouvre le jeu sans les barres de Safari --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Prince de Yop">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🎮 {{ $product->title }} — Boursiv</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; height: -webkit-fill-available; }
        body { background: #07080f; height: 100%; overflow: hidden;
               /* Bloque le scroll rebond iOS Safari */
               position: fixed; width: 100%;
               font-family: 'Syne', system-ui, sans-serif; }

        /* ── Barre de contrôle ── */
        #game-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 44px;
            background: rgba(7, 8, 15, 0.95);
            border-bottom: 1px solid rgba(240, 138, 36, .15);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; gap: 12px;
            transition: transform .3s ease;
        }
        #game-bar.hidden { transform: translateY(-100%); }

        .game-bar-left  { display: flex; align-items: center; gap: 10px; }
        .game-bar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .game-bar-title { font-size: 12px; font-weight: 700; color: #E8EAF0; letter-spacing: .06em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
        .game-bar-chip  { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: rgba(240,138,36,.12); color: #F08A24; border: 1px solid rgba(240,138,36,.25); padding: 2px 8px; border-radius: 100px; }

        .bar-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-decoration: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; border: 1px solid; transition: all .2s; background: transparent; }
        .bar-btn-outline { color: #6B7590 !important; border-color: rgba(255,255,255,.1); }
        .bar-btn-outline:hover { border-color: #C9A84C; color: #C9A84C !important; }
        .bar-btn-gold { background: rgba(201,168,76,.08); color: #C9A84C !important; border-color: rgba(201,168,76,.2); }
        .bar-btn-gold:hover { background: rgba(201,168,76,.16); }

        /* Toggle bar */
        #bar-toggle { position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 99; width: 60px; height: 6px; background: rgba(240,138,36,.3); border-radius: 0 0 4px 4px; cursor: pointer; transition: background .2s; }
        #bar-toggle:hover { background: rgba(240,138,36,.6); }
        #bar-toggle.up { top: 44px; }

        /* ── Iframe ── */
        #game-frame { position: fixed; top: 44px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 44px); border: none; background: #07080f; transition: top .3s, height .3s; }
        #game-frame.bar-hidden { top: 0; height: 100%; }

        #saving-indicator { position: fixed; top: 50px; right: 12px; z-index: 150; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #6B7590; opacity: 0; transition: opacity .3s; }
        #saving-indicator.show { opacity: 1; }

        @media (hover: none) and (pointer: coarse) {
            #saving-indicator { display: none !important; }
            #game-bar { height: 36px; padding: 0 10px; }
            .game-bar-title { display: none; }
            #game-frame { top: 36px; height: calc(100% - 36px); }
            #bar-toggle.up { top: 36px; }
        }

        /* ── Mobile paysage et pseudo plein écran (iPhone) : le jeu occupe tout l'écran ──
           Le jeu gère lui-même sa mise en page paysage à l'intérieur de l'iframe. */
        @media (pointer: coarse) and (orientation: landscape) {
            #game-bar, #bar-toggle, #saving-indicator { display: none !important; }
            #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }
        }
        body.pseudo-fs #game-bar, body.pseudo-fs #bar-toggle, body.pseudo-fs #saving-indicator { display: none !important; }
        body.pseudo-fs #game-frame { position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; border: 0; z-index: 1000; }

        #fs-exit {
            display: none; position: fixed; z-index: 1001;
            top: max(6px, env(safe-area-inset-top, 0px)); left: 50%; transform: translateX(-50%);
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(7,8,15,.7); color: #E8EAF0; border: 1px solid rgba(255,255,255,.25);
            font-size: 15px; line-height: 1; cursor: pointer;
        }
        body.pseudo-fs #fs-exit { display: block; }
        /* Portrait : en haut à droite, à côté du logo du jeu (en paysage, le haut-centre est libre) */
        @media (orientation: portrait) {
            #fs-exit { left: auto; right: max(8px, env(safe-area-inset-right, 0px)); transform: none; }
        }

        #ios-tip {
            display: none; position: fixed; z-index: 1002;
            left: 50%; transform: translateX(-50%);
            bottom: max(12px, env(safe-area-inset-bottom, 0px));
            width: min(92vw, 420px); padding: 10px 36px 10px 14px; border-radius: 8px;
            background: rgba(7,8,15,.94); border: 1px solid rgba(240,138,36,.4);
            color: #E8EAF0; font-size: 12px; line-height: 1.45;
        }
        #ios-tip.show { display: block; }
        #ios-tip b { color: #F08A24; }
        #ios-tip button { position: absolute; top: 6px; right: 8px; background: none; border: 0; color: #6B7590; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>

    {{-- Barre de contrôle --}}
    <div id="game-bar">
        <div class="game-bar-left">
            <span class="game-bar-chip">🎮 Jeu</span>
            <span class="game-bar-title">{{ $product->title }}</span>
        </div>
        <div class="game-bar-right">
            <button id="btn-fullscreen" class="bar-btn bar-btn-gold" title="Plein écran">
                ⛶ Plein écran
            </button>
            <a href="{{ route('my.products') }}" class="bar-btn bar-btn-outline">
                ← Mes produits
            </a>
        </div>
    </div>

    <div id="bar-toggle" title="Afficher/masquer la barre"></div>

    {{-- Indicateur de sauvegarde --}}
    <div id="saving-indicator">💾 Sauvegarde…</div>

    {{-- Iframe jeu --}}
    <iframe
        id="game-frame"
        sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-forms allow-popups allow-popups-to-escape-sandbox"
        allow="fullscreen"
        allowfullscreen
        src="{{ route('my.products.game-html', $product) }}"
        title="Jeu : {{ $product->title }}"
    ></iframe>

    {{-- Sortie du pseudo plein écran (iPhone : pas d'API Fullscreen sur une iframe) --}}
    <button id="fs-exit" type="button" aria-label="Quitter le plein écran">✕</button>

    <div id="ios-tip" role="status">
        Astuce : pour jouer sans les barres de Safari, touche <b>Partager</b> puis
        <b>« Sur l'écran d'accueil »</b> et lance le jeu depuis l'icône Boursiv.
        <button type="button" id="ios-tip-close" aria-label="Fermer">✕</button>
    </div>

    <script>
    (function () {
        const PROGRESS_URL = '{{ route('prince-yop.progress.show') }}';
        const SAVE_URL     = '{{ route('prince-yop.progress.store') }}';
        const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
        const GAME_SOURCE  = 'prince-de-yop';

        const bar    = document.getElementById('game-bar');
        const frame  = document.getElementById('game-frame');
        const toggle = document.getElementById('bar-toggle');
        const btnFs  = document.getElementById('btn-fullscreen');
        const saving = document.getElementById('saving-indicator');
        let barVisible = true;

        // ── Barre toggle ──────────────────────────────────────
        function toggleBar() {
            barVisible = !barVisible;
            bar.classList.toggle('hidden', !barVisible);
            frame.classList.toggle('bar-hidden', !barVisible);
            toggle.classList.toggle('up', !barVisible);
        }
        toggle.addEventListener('click', toggleBar);
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !barVisible) toggleBar(); });

        // ── Plein écran ───────────────────────────────────────
        // Android / ordinateur : API Fullscreen. iPhone (Safari n'a pas cette API pour une iframe) :
        // pseudo plein écran en CSS, avec un bouton ✕ pour en sortir.
        const fsExit  = document.getElementById('fs-exit');
        const iosTip  = document.getElementById('ios-tip');
        const isIOS   = /iPhone|iPad|iPod/.test(navigator.userAgent)
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

        // ── Progression : le serveur fait foi ─────────────────
        // L'état serveur est toujours renvoyé au jeu, même au niveau 0 : il écrase le
        // localStorage de l'iframe (partagé entre comptes sur un même navigateur).
        let state = null;
        let gameReady = false;

        const loaded = fetch(PROGRESS_URL, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(res => { if (!state && res && Number.isInteger(res.level)) state = res; })
            .catch(() => {});

        function pushProgressToGame() {
            if (!state || !gameReady) return;
            frame.contentWindow.postMessage({
                source: 'boursiv',
                type: 'PRINCE_YOP_PROGRESS_LOAD',
                level: state.level,
                bonus: state.bonus,
            }, window.location.origin);
        }

        // File d'attente : les envois partent un par un, dans l'ordre émis par le jeu
        // (GAME_COMPLETE doit arriver avant la remise à 0, PROGRESS avant LEVEL_COMPLETE).
        let queue = Promise.resolve();
        function save(payload) {
            queue = queue.then(() => {
                saving.classList.add('show');
                return fetch(SAVE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json().catch(() => null))
                .then(res => {
                    if (res && Number.isInteger(res.level)) {
                        state = res;
                        pushProgressToGame();
                    }
                })
                .catch(() => {})
                .finally(() => saving.classList.remove('show'));
            });
        }

        // ── Réception des messages du jeu ──────────────────────
        window.addEventListener('message', function (event) {
            if (event.source !== frame.contentWindow) return;

            const data = event.data;
            if (!data || data.source !== GAME_SOURCE || !data.type) return;

            switch (data.type) {
                case 'PRINCE_YOP_READY':
                    gameReady = true;
                    loaded.then(pushProgressToGame);
                    break;
                case 'PRINCE_YOP_PROGRESS':
                    save({ type: 'progress', level: data.level, bonus: data.bonus });
                    break;
                case 'PRINCE_YOP_LEVEL_COMPLETE':
                    save({ type: 'level_complete', level: data.level, time: data.time, deaths: data.deaths });
                    break;
                case 'PRINCE_YOP_GAME_COMPLETE':
                    save({ type: 'game_complete', deaths: data.deaths });
                    break;
            }
        });
    })();
    </script>

</body>
</html>
