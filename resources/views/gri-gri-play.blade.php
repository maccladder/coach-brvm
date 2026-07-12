{{-- ════════════════════════════════════════════════
     resources/views/gri-gri-play.blade.php
     Wrapper dédié pour GRI-GRI — La Danse des Perles.
     Le jeu parle son propre dialecte postMessage (GRIGRI_SCORE / GRIGRI_PREMIUM_REQUEST /
     GRIGRI_PREMIUM_GRANTED) plutôt que le SCORE/CHARS_UNLOCKED générique de my-products-play.
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
        .game-bar-chip  { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; background: rgba(232,185,60,.12); color: #E8B93C; border: 1px solid rgba(232,185,60,.25); padding: 2px 8px; border-radius: 100px; }

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
        @media (hover: none) and (pointer: coarse) {
            #score-toast      { display: none !important; }
            #saving-indicator { display: none !important; }
            #game-bar { height: 36px; padding: 0 10px; }
            .game-bar-title { display: none; }
            #game-frame { top: 36px; height: calc(100% - 36px); }
            #bar-toggle.up { top: 36px; }
        }
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
            <span class="game-bar-chip">🔮 Jeu</span>
            <span class="game-bar-title">{{ $product->title }}</span>
        </div>
        <div class="game-bar-right">
            <a href="{{ route('grigri.leaderboard') }}" class="bar-btn bar-btn-purple" title="Classement" target="_blank">
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

    {{-- Toast score (affiché au game over) --}}
    <div id="score-toast">
        <div class="toast-title">🔮 Partie terminée</div>
        <div id="toast-score-val" class="toast-score">0</div>
        <div id="toast-best-badge" class="toast-best-badge" style="display:none;">🏆 Nouveau record !</div>
        <div class="toast-meta">
            <div class="toast-meta-item"><strong id="toast-level">0</strong>Niveau atteint</div>
            <div class="toast-meta-item"><strong id="toast-coins">0</strong>Cauris</div>
        </div>
        <div id="toast-rank" class="toast-rank" style="display:none;"></div>
        <div class="toast-actions">
            <a id="toast-lb-btn" href="{{ route('grigri.leaderboard') }}" class="toast-btn toast-btn-lb" target="_blank">
                🏆 Classement
            </a>
            <button onclick="document.getElementById('score-toast').classList.remove('show','new-best')" class="toast-btn toast-btn-cls">
                Fermer
            </button>
        </div>
    </div>

    <script>
    (function () {
        const SCORE_URL        = '{{ route('grigri.score.store') }}';
        const INIT_PAYMENT_URL = '{{ route('games.init-premium-payment', $product) }}';
        const CSRF_TOKEN       = document.querySelector('meta[name="csrf-token"]').content;
        const PREMIUM_FEATURE  = 'unlimited_continue';
        const PREMIUM_KEY      = 'grigri_premium_{{ $product->id }}';

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

        // ── Déverrouillage premium (Continuer illimité) ────────
        const PREMIUM_UNLOCKED_DB = {{ $isPremiumUnlocked ? 'true' : 'false' }};

        function notifyGameOfUnlock() {
            frame.contentWindow.postMessage({ type: 'GRIGRI_PREMIUM_GRANTED' }, '*');
        }

        // Si déjà déverrouillé en DB → on notifie dès que l'iframe est chargée
        if (PREMIUM_UNLOCKED_DB || localStorage.getItem(PREMIUM_KEY) === '1') {
            if (PREMIUM_UNLOCKED_DB) localStorage.setItem(PREMIUM_KEY, '1');
            frame.addEventListener('load', notifyGameOfUnlock);
        }

        function openPaystackFromParent() {
            fetch(INIT_PAYMENT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ feature: PREMIUM_FEATURE }),
            })
            .then(r => r.json())
            .then(res => {
                if (res.already_unlocked) {
                    localStorage.setItem(PREMIUM_KEY, '1');
                    notifyGameOfUnlock();
                    return;
                }
                if (res.url) {
                    window.location.href = res.url;
                }
            })
            .catch(() => {});
        }

        // ── Réception des messages du jeu ──────────────────────
        window.addEventListener('message', function (event) {
            if (event.source !== frame.contentWindow) return;

            const data = event.data;
            if (!data || !data.type) return;

            if (data.type === 'GRIGRI_PREMIUM_REQUEST') {
                openPaystackFromParent();
                return;
            }

            if (data.type === 'GRIGRI_LEADERBOARD_REQUEST') {
                window.open('{{ route('grigri.leaderboard') }}', '_blank');
                return;
            }

            if (data.type !== 'GRIGRI_SCORE') return;

            const score = parseInt(data.score ?? 0, 10);
            const level = parseInt(data.level ?? 1, 10);
            const coins = parseInt(data.coins ?? 0, 10);
            const final = !!data.final;

            // On n'affiche le toast qu'au game over pour ne pas interrompre la partie à chaque niveau.
            if (final) showToast(score, level, coins, null, false);

            saving.classList.add('show');

            fetch(SCORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ score, level, coins, final }),
            })
            .then(r => r.json())
            .then(res => {
                saving.classList.remove('show');
                if (res.saved && final) {
                    showToast(score, level, coins, res.rank, res.is_new_best);
                }
            })
            .catch(() => { saving.classList.remove('show'); });
        });

        function showToast(score, level, coins, rank, isNewBest) {
            document.getElementById('toast-score-val').textContent = score.toLocaleString('fr-FR');
            document.getElementById('toast-level').textContent = level.toLocaleString('fr-FR');
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

            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => toast.classList.remove('show', 'new-best'), 12000);
        }
    })();
    </script>

</body>
</html>
