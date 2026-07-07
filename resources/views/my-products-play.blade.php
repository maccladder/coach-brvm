{{-- ════════════════════════════════════════════════
     resources/views/my-products-play.blade.php
════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🎮 {{ $product->title }} — Boursiv</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; height: -webkit-fill-available; }
        body { background: #060910; height: 100%; overflow: hidden;
               /* Bloque le scroll rebond iOS Safari */
               position: fixed; width: 100%;
               font-family: 'Syne', system-ui, sans-serif; }

        /* ── Barre de contrôle ── */
        #game-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 44px;
            background: rgba(6, 9, 16, 0.95);
            border-bottom: 1px solid rgba(201, 168, 76, .12);
            backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; gap: 12px;
            transition: transform .3s ease;
        }
        #game-bar.hidden { transform: translateY(-100%); }

        .game-bar-left  { display: flex; align-items: center; gap: 10px; }
        .game-bar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .game-bar-title { font-size: 12px; font-weight: 700; color: #E8EAF0; letter-spacing: .06em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
        .game-bar-chip  { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: rgba(120,80,255,.12); color: #A070FF; border: 1px solid rgba(120,80,255,.25); padding: 2px 8px; border-radius: 100px; }

        .bar-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-decoration: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; border: 1px solid; transition: all .2s; background: transparent; }
        .bar-btn-outline { color: #6B7590 !important; border-color: rgba(255,255,255,.1); }
        .bar-btn-outline:hover { border-color: #C9A84C; color: #C9A84C !important; }
        .bar-btn-gold { background: rgba(201,168,76,.08); color: #C9A84C !important; border-color: rgba(201,168,76,.2); }
        .bar-btn-gold:hover { background: rgba(201,168,76,.16); }
        .bar-btn-purple { background: rgba(120,80,255,.08); color: #A070FF !important; border-color: rgba(120,80,255,.2); }
        .bar-btn-purple:hover { background: rgba(120,80,255,.16); }

        /* Toggle bar */
        #bar-toggle { position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 99; width: 60px; height: 6px; background: rgba(201,168,76,.3); border-radius: 0 0 4px 4px; cursor: pointer; transition: background .2s; }
        #bar-toggle:hover { background: rgba(201,168,76,.6); }
        #bar-toggle.up { top: 44px; }

        /* ── Iframe ── */
        #game-frame { position: fixed; top: 44px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 44px); border: none; background: #000; transition: top .3s, height .3s; }
        #game-frame.bar-hidden { top: 0; height: 100%; }

        /* ── Toast score ── */
        #score-toast {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(120px);
            z-index: 200; min-width: 280px; max-width: 380px;
            background: rgba(6,9,16,.97);
            border: 1px solid rgba(201,168,76,.3);
            border-radius: 8px; padding: 16px 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 16px 48px rgba(0,0,0,.6);
            transition: transform .4s cubic-bezier(.16,1,.3,1);
            text-align: center;
        }
        #score-toast.show { transform: translateX(-50%) translateY(0); }
        #score-toast.new-best { border-color: rgba(201,168,76,.7); box-shadow: 0 0 32px rgba(201,168,76,.25); }

        .toast-title { font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #6B7590; margin-bottom: 10px; }
        .toast-score { font-size: 36px; font-weight: 900; color: #C9A84C; font-family: 'Playfair Display', serif; line-height: 1; }
        .toast-score.new-best { color: #FFD700; text-shadow: 0 0 20px rgba(255,215,0,.4); }
        .toast-meta { display: flex; justify-content: center; gap: 20px; margin-top: 10px; }
        .toast-meta-item { font-size: 12px; color: #9AA3B8; }
        .toast-meta-item strong { color: #E8EAF0; display: block; font-size: 14px; }
        .toast-rank { margin-top: 10px; font-size: 12px; color: #A070FF; font-weight: 700; }
        .toast-best-badge { display: inline-block; margin-top: 8px; padding: 4px 12px; background: rgba(201,168,76,.15); border: 1px solid rgba(201,168,76,.3); border-radius: 100px; font-size: 11px; font-weight: 700; color: #C9A84C; letter-spacing: .06em; }
        .toast-actions { display: flex; gap: 8px; justify-content: center; margin-top: 14px; }
        .toast-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 7px 14px; border-radius: 3px; border: 1px solid; cursor: pointer; text-decoration: none; background: transparent; transition: all .2s; }
        .toast-btn-lb  { color: #A070FF !important; border-color: rgba(120,80,255,.3); }
        .toast-btn-lb:hover  { background: rgba(120,80,255,.1); }
        .toast-btn-cls { color: #6B7590 !important; border-color: rgba(255,255,255,.1); }
        .toast-btn-cls:hover { border-color: #C9A84C; color: #C9A84C !important; }

        #saving-indicator { position: fixed; top: 50px; right: 12px; z-index: 150; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #6B7590; opacity: 0; transition: opacity .3s; }
        #saving-indicator.show { opacity: 1; }

        /* ── Mobile : le jeu affiche déjà son propre écran de fin ── */
        /* Détection touch (plus fiable que max-width) */
        @media (hover: none) and (pointer: coarse) {
            #score-toast      { display: none !important; }
            #saving-indicator { display: none !important; }
            #game-bar { height: 36px; padding: 0 10px; }
            .game-bar-title { display: none; }
            #game-frame { top: 36px; height: calc(100% - 36px); }
            #bar-toggle.up { top: 36px; }
        }
        /* Fallback largeur écran */
        @media (max-width: 900px) {
            #score-toast      { display: none !important; }
            #saving-indicator { display: none !important; }
        }
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
            <a href="{{ route('games.leaderboard', $product) }}" class="bar-btn bar-btn-purple" title="Classement" target="_blank">
                🏆 Classement
            </a>
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

    {{-- Toast score (affiché après game over) --}}
    <div id="score-toast">
        <div class="toast-title">🎮 Partie terminée</div>
        <div id="toast-score-val" class="toast-score">0</div>
        <div id="toast-best-badge" class="toast-best-badge" style="display:none;">🏆 Nouveau record !</div>
        <div class="toast-meta">
            <div class="toast-meta-item"><strong id="toast-dist">0</strong>Distance (m)</div>
            <div class="toast-meta-item"><strong id="toast-coins">0</strong>Pièces CFA</div>
        </div>
        <div id="toast-rank" class="toast-rank" style="display:none;"></div>
        <div class="toast-actions">
            <a id="toast-lb-btn" href="{{ route('games.leaderboard', $product) }}" class="toast-btn toast-btn-lb" target="_blank">
                🏆 Classement
            </a>
            <button onclick="document.getElementById('score-toast').classList.remove('show','new-best')" class="toast-btn toast-btn-cls">
                Fermer
            </button>
        </div>
    </div>

    <script>
    (function () {
        const SCORE_URL  = '{{ route('games.score.store', $product) }}';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        const bar    = document.getElementById('game-bar');
        const frame  = document.getElementById('game-frame');
        const toggle = document.getElementById('bar-toggle');
        const btnFs  = document.getElementById('btn-fullscreen');
        const saving = document.getElementById('saving-indicator');
        const toast  = document.getElementById('score-toast');
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
        btnFs.addEventListener('click', () => {
            const req = frame.requestFullscreen || frame.webkitRequestFullscreen || frame.mozRequestFullScreen || frame.msRequestFullscreen;
            if (req) req.call(frame);
        });

        // ── Déverrouillage des persos premium ────────────────
        const PREMIUM_KEY      = 'abidjan_run_premium_chars_{{ $product->id }}';
        const VERIFY_PREMIUM_URL = '{{ route('games.verify-premium', $product) }}';
        // Statut DB passé par le contrôleur (source de vérité)
        const PREMIUM_UNLOCKED_DB = {{ $isPremiumUnlocked ? 'true' : 'false' }};
        // Toutes les features VIP débloquées pour ce jeu (générique, plusieurs packs possibles)
        const UNLOCKED_FEATURES = @json($unlockedFeatures ?? []);

        function notifyGameOfUnlock() {
            frame.contentWindow.postMessage({ type: 'CHARS_UNLOCKED' }, '*');
        }
        function notifyGameOfVipUnlocks() {
            frame.contentWindow.postMessage({ type: 'VIP_UNLOCKED', features: UNLOCKED_FEATURES }, '*');
        }

        // Si déjà déverrouillé en DB → on notifie dès que l'iframe est chargée
        // (localStorage comme cache rapide, DB comme source de vérité)
        if (PREMIUM_UNLOCKED_DB || localStorage.getItem(PREMIUM_KEY) === '1') {
            if (PREMIUM_UNLOCKED_DB) localStorage.setItem(PREMIUM_KEY, '1'); // sync cache
            frame.addEventListener('load', notifyGameOfUnlock);
        }
        if (UNLOCKED_FEATURES.length) {
            frame.addEventListener('load', notifyGameOfVipUnlocks);
        }

        // ── Réception du score via postMessage ────────────────
        window.addEventListener('message', function (event) {
            if (event.source !== frame.contentWindow) return;

            const data = event.data;

            // Game demande un paiement premium → on l'ouvre depuis le parent (hors sandbox)
            if (data && data.type === 'GAME_REQUEST_PAYMENT') {
                openPaystackFromParent(data.char);
                return;
            }

            // Paiement Paystack in-game → vérification serveur (legacy)
            if (data && data.type === 'ABIDJAN_RUN_PREMIUM_UNLOCKED' && data.ref) {
                fetch(VERIFY_PREMIUM_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ref: data.ref, feature: 'premium_chars' }),
                })
                .then(r => r.json())
                .then(res => {
                    if (res.unlocked) {
                        localStorage.setItem(PREMIUM_KEY, '1');
                        notifyGameOfUnlock();
                    }
                })
                .catch(() => {});
                return;
            }

            // Déverrouillage sans paiement (flow legacy / test)
            if (data && data.type === 'UNLOCK_PREMIUM_CHARS') {
                localStorage.setItem(PREMIUM_KEY, '1');
                notifyGameOfUnlock();
                return;
            }

            // Accepter 'SCORE' (générique), 'ABIDJAN_RUN_SCORE', 'GBAKA_RUSH_SCORE'
            if (!data || (data.type !== 'SCORE' && data.type !== 'ABIDJAN_RUN_SCORE' && data.type !== 'GBAKA_RUSH_SCORE')) return;

            const score    = parseInt(data.score    ?? 0, 10);
            const distance = parseInt(data.distance ?? 0, 10);
            const coins    = parseInt(data.coins    ?? 0, 10);

            // Pré-afficher le toast immédiatement
            showToast(score, distance, coins, null, false);

            // Envoyer au serveur
            saving.classList.add('show');

            fetch(SCORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ score, distance, coins }),
            })
            .then(r => r.json())
            .then(res => {
                saving.classList.remove('show');
                if (res.saved) {
                    showToast(score, distance, coins, res.rank, res.is_new_best);
                }
            })
            .catch(() => { saving.classList.remove('show'); });
        });

        // ── Paiement Paystack via init serveur (mobile + desktop) ────────
        const INIT_PAYMENT_URL = '{{ route('games.init-premium-payment', $product) }}';

        function openPaystackFromParent(char) {
            fetch(INIT_PAYMENT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ feature: char, char: char }),
            })
            .then(r => r.json())
            .then(res => {
                if (res.already_unlocked) {
                    localStorage.setItem(PREMIUM_KEY, '1');
                    notifyGameOfUnlock();
                    if (!UNLOCKED_FEATURES.includes(char)) UNLOCKED_FEATURES.push(char);
                    notifyGameOfVipUnlocks();
                    return;
                }
                if (res.url) {
                    window.location.href = res.url;
                }
            })
            .catch(() => {});
        }

        function showToast(score, distance, coins, rank, isNewBest) {
            document.getElementById('toast-score-val').textContent = score.toLocaleString('fr-FR');
            document.getElementById('toast-dist').textContent = distance.toLocaleString('fr-FR');
            document.getElementById('toast-coins').textContent = coins.toLocaleString('fr-FR');

            const badge = document.getElementById('toast-best-badge');
            badge.style.display = isNewBest ? 'inline-block' : 'none';

            const rankEl = document.getElementById('toast-rank');
            if (rank !== null) {
                rankEl.textContent = rank === 1 ? '🥇 Tu es 1er du classement !' : `🏅 Tu es ${rank}e du classement`;
                rankEl.style.display = 'block';
            } else {
                rankEl.style.display = 'none';
            }

            document.getElementById('toast-score-val').className = 'toast-score' + (isNewBest ? ' new-best' : '');
            toast.classList.toggle('new-best', isNewBest);
            toast.classList.add('show');

            // Fermeture automatique après 12 secondes
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => toast.classList.remove('show', 'new-best'), 12000);
        }
    })();
    </script>

</body>
</html>
