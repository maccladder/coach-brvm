{{-- resources/views/jeu/garba.blade.php — vue autonome (sans @extends) --}}
@php
    $packs  = config('cauris.packs');
    $badges = ['petit' => null, 'moyen' => 'Populaire', 'gros' => 'Meilleur deal'];
    $noms   = ['petit' => 'Petit', 'moyen' => 'Moyen', 'gros' => 'Gros'];
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Le Maquis — Boursiv</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: #1a1410;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: center;
            gap: 28px;
            padding: 24px 16px;
            font-family: system-ui, sans-serif;
        }

        /* ── Canvas Phaser + barre de contrôle ── */
        #jeu-wrapper { display: flex; flex-direction: column; flex-shrink: 0; width: 420px; }
        #jeu { width: 420px; height: 640px; }
        #jeu canvas { display: block; }

        #ctrl-bar {
            display: flex;
            gap: 4px;
            padding: 5px 8px;
            background: rgba(10, 8, 5, 0.82);
            border-radius: 10px 10px 0 0;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            box-sizing: border-box;
        }
        .ctrl-btn {
            flex: 1;
            background: rgba(255,255,255,.08);
            border: 1.5px solid rgba(255,255,255,.18);
            border-radius: 8px;
            color: #f4e3b4;
            font-size: 12px;
            padding: 5px 4px;
            cursor: pointer;
            transition: background .15s, opacity .15s;
            white-space: nowrap;
            text-align: center;
        }
        .ctrl-btn:hover  { background: rgba(255,255,255,.18); }
        .ctrl-btn.actif  { background: rgba(224,160,48,.25); border-color: #e0a030; }
        .ctrl-btn:disabled { opacity: .38; cursor: default; }
        #btn-ctrl-quitter { color: #ff8a80; border-color: rgba(255,138,128,.28); }
        #btn-ctrl-quitter:hover { background: rgba(255,138,128,.15); }

        /* ══════════════════════════════════════
           BOUTIQUE — desktop : flex-child inline
           mobile   : overlay plein-écran caché
        ═══════════════════════════════════════ */

        /* Conteneur (desktop : inline, mobile : overlay) */
        #boutique-overlay {
            flex: 1;
            min-width: 260px;
            max-width: 360px;
        }

        /* Fermer — caché sur desktop */
        .boutique-close { display: none; }

        .boutique-titre {
            font-size: 18px;
            font-weight: 700;
            color: #f4e3b4;
            margin-bottom: 16px;
            letter-spacing: .04em;
        }

        .pack-card {
            background: #261e16;
            border: 1.5px solid #6b4c1e;
            border-radius: 12px;
            padding: 18px 16px;
            margin-bottom: 14px;
            position: relative;
            box-shadow: 0 4px 16px rgba(0,0,0,.4);
            transition: border-color .2s;
        }
        .pack-card:hover { border-color: #e0a030; }

        .pack-badge {
            position: absolute;
            top: -10px; right: 14px;
            background: #e0a030;
            color: #1a1410;
            font-size: 10px; font-weight: 800;
            letter-spacing: .07em; text-transform: uppercase;
            padding: 3px 10px; border-radius: 100px;
        }

        .pack-nom      { font-size: 15px; font-weight: 700; color: #f4e3b4; margin-bottom: 6px; }
        .pack-cauris   { font-size: 26px; font-weight: 900; color: #e0a030; line-height: 1; margin-bottom: 4px; }
        .pack-cauris-label { font-size: 12px; color: #9a8060; margin-bottom: 14px; }
        .pack-prix     { font-size: 13px; color: #c8a060; margin-bottom: 12px; font-weight: 600; }

        .btn-acheter {
            display: block; width: 100%; padding: 10px 0;
            background: #e0a030; color: #1a1410;
            font-size: 14px; font-weight: 800; letter-spacing: .05em;
            border: none; border-radius: 8px; cursor: pointer;
            transition: background .2s, opacity .2s;
        }
        .btn-acheter:hover { background: #f0b840; }
        .btn-acheter:disabled { opacity: .5; cursor: default; }

        .boutique-erreur { color: #e05050; font-size: 12px; margin-top: 8px; min-height: 18px; }

        /* ══════════════════════════════════════
           ÉCRAN D'ACCUEIL
        ═══════════════════════════════════════ */
        #accueil-screen {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1410;
        }
        .accueil-panel {
            position: relative;
            width: 420px;
            max-width: 100vw;
            aspect-ratio: 3 / 4;
            background-image: url('/jeu-assets/img/accueil.jpg');
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.7);
        }
        .accueil-titre {
            position: absolute;
            top: 11%;
            width: 100%;
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            color: #2a1a08;
            letter-spacing: .03em;
            text-shadow: 0 1px 0 rgba(255,255,255,.25);
        }
        .accueil-btns {
            position: absolute;
            bottom: 12%;
            left: 50%;
            transform: translateX(-50%);
            width: 74%;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn-accueil-principal {
            display: block; width: 100%; padding: 14px 0;
            background: #e0a030; color: #1a1410;
            font-size: 16px; font-weight: 800; letter-spacing: .04em;
            border: none; border-radius: 10px; cursor: pointer;
            box-shadow: 0 4px 14px rgba(224,160,48,.45);
            transition: background .2s;
        }
        .btn-accueil-principal:hover { background: #f0b840; }
        .btn-accueil-secondaire {
            display: block; width: 100%; padding: 12px 0;
            background: rgba(0,0,0,.42); color: #f4e3b4;
            font-size: 14px; font-weight: 700;
            border: 1.5px solid rgba(224,160,48,.35);
            border-radius: 10px; cursor: pointer;
            transition: background .2s;
        }
        .btn-accueil-secondaire:hover { background: rgba(224,160,48,.15); }

        /* ═══════════ MOBILE ≤ 900px ═══════════ */
        @media (max-width: 900px) {
            body {
                flex-direction: column;
                align-items: center;
                padding: 0;
                gap: 0;
            }
            #jeu-wrapper { width: 100%; max-width: 420px; }
            .ctrl-btn .cl { display: none; }
            .ctrl-btn { font-size: 16px; padding: 6px 0; }
            #jeu {
                width: 100%;
                max-width: 420px;
                height: auto;
                aspect-ratio: 420 / 640;
            }

            /* L'overlay est caché par défaut sur mobile */
            #boutique-overlay {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 9999;
                background: rgba(0, 0, 0, 0.88);
                align-items: flex-start;
                justify-content: center;
                overflow-y: auto;
                padding: 16px;
                max-width: none;
                min-width: unset;
            }
            /* Ouvert via JS */
            #boutique-overlay.ouvert { display: flex; }

            /* Croix de fermeture visible sur mobile */
            .boutique-close {
                display: flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: 12px; right: 12px;
                width: 36px; height: 36px;
                background: rgba(255,255,255,.1);
                border: none; border-radius: 50%;
                color: #f4e3b4; font-size: 20px; cursor: pointer;
                line-height: 1;
            }
            .boutique-close:hover { background: rgba(255,255,255,.2); }

            /* La boutique elle-même dans la modale */
            .boutique {
                width: 100%;
                max-width: 420px;
                padding-top: 8px;
            }
        }
    </style>
</head>
<body>

    {{-- Écran d'accueil (affiché par-dessus tout jusqu'au clic) --}}
    <div id="accueil-screen">
        <div class="accueil-panel">
            <p class="accueil-titre">🍢 Garba Master</p>
            <div class="accueil-btns">
                <button id="btn-continuer" class="btn-accueil-principal" style="display:none">
                    ▶ Continuer
                </button>
                <button id="btn-nouvelle" class="btn-accueil-secondaire">
                    ✦ Nouvelle partie
                </button>
            </div>
        </div>
    </div>

    {{-- Canvas Phaser + barre de contrôle système --}}
    <div id="jeu-wrapper">
        <div id="ctrl-bar">
            <button class="ctrl-btn" id="btn-ctrl-pause"     title="Pause">    <span class="ci">⏸</span><span class="cl"> Pause</span></button>
            <button class="ctrl-btn" id="btn-ctrl-musique"   title="Musique">  <span class="ci">♪</span><span class="cl"> Musique</span></button>
            <button class="ctrl-btn" id="btn-ctrl-promo"     title="Promo">    <span class="ci">📢</span><span class="cl"> Promo</span></button>
            <button class="ctrl-btn" id="btn-ctrl-recharger" title="Recharger"><span class="ci">🐚</span><span class="cl"> Recharger</span></button>
            <button class="ctrl-btn" id="btn-ctrl-quitter"   title="Quitter">  <span class="ci">✕</span><span class="cl"> Quitter</span></button>
        </div>
        <div id="jeu"></div>
    </div>

    {{-- Overlay boutique (inline sur desktop, modale sur mobile) --}}
    <div id="boutique-overlay">
        <aside class="boutique" style="position:relative;">
            <button class="boutique-close" onclick="window.fermerBoutique()" aria-label="Fermer">×</button>
            <p class="boutique-titre">🐚 Recharger des cauris</p>

            @foreach ($packs as $key => $pack)
            <div class="pack-card">
                @if ($badges[$key])
                    <span class="pack-badge">{{ $badges[$key] }}</span>
                @endif
                <p class="pack-nom">{{ $noms[$key] }}</p>
                <p class="pack-cauris">🐚 {{ number_format($pack['cauris'], 0, ',', ' ') }}</p>
                <p class="pack-cauris-label">cauris</p>
                <p class="pack-prix">{{ number_format($pack['prix'], 0, ',', ' ') }} F</p>
                <button class="btn-acheter" data-pack="{{ $key }}">Acheter</button>
            </div>
            @endforeach

            <p class="boutique-erreur" id="boutique-erreur"></p>
        </aside>
    </div>

    @vite('resources/js/jeu/main.js')

    {{-- JS Écran d'accueil --}}
    <script>
    (function () {
        const csrf       = document.querySelector('meta[name="csrf-token"]').content;
        const screen     = document.getElementById('accueil-screen');
        const btnCont    = document.getElementById('btn-continuer');
        const btnNouvelle = document.getElementById('btn-nouvelle');
        let aProgression = false;

        function demarrer() {
            screen.style.display = 'none';
            if (window.demarrerJeu) window.demarrerJeu();
        }

        // Vérifie si une progression existe
        fetch('/api/jeu/load', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : null)
            .then(save => {
                if (save && (save.solde > 0 || save.tables_actives > 2
                          || save.nb_serveuses > 1 || save.niveau_menu > 1)) {
                    aProgression = true;
                    btnCont.style.display = '';
                }
            })
            .catch(() => {});

        btnCont.addEventListener('click', () => {
            screen.style.display = 'none';
            if (window.demarrerJeu) window.demarrerJeu(false);   // Continuer : reprend
        });

        btnNouvelle.addEventListener('click', async () => {
            if (aProgression) {
                const ok = confirm('Tu vas perdre ta progression actuelle. Recommencer à zéro ?');
                if (!ok) return;
                try {
                    const r = await fetch('/api/jeu/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    });
                    if (!r.ok) {
                        console.warn('Reset échoué', r.status);
                        return;   // n'avance pas si le reset a échoué
                    }
                } catch (e) {
                    console.warn('Reset erreur réseau', e);
                    return;
                }
            }
            screen.style.display = 'none';
            if (window.demarrerJeu) window.demarrerJeu(true);    // Nouvelle partie : relance
        });
    })();
    </script>

    {{-- JS Barre de contrôle système --}}
    <script>
    (function () {
        const btnPause    = document.getElementById('btn-ctrl-pause');
        const btnMusique  = document.getElementById('btn-ctrl-musique');
        const btnPromo    = document.getElementById('btn-ctrl-promo');
        const btnRechg    = document.getElementById('btn-ctrl-recharger');
        const btnQuitter  = document.getElementById('btn-ctrl-quitter');
        const accueil     = document.getElementById('accueil-screen');
        const btnCont     = document.getElementById('btn-continuer');

        // ── Pause ──────────────────────────────────────────────────
        let pauseActif = false;
        btnPause.addEventListener('click', () => {
            if (window.jeuTogglePause) window.jeuTogglePause();
            pauseActif = !pauseActif;
            btnPause.textContent = pauseActif ? '▶ Reprendre' : '⏸ Pause';
            btnPause.classList.toggle('actif', pauseActif);
        });

        // ── Musique ────────────────────────────────────────────────
        btnMusique.addEventListener('click', () => {
            if (window.jeuMusique) window.jeuMusique();
            const on = window.jeuEtatMusique ? window.jeuEtatMusique() : true;
            btnMusique.textContent = on ? '♪ Musique' : '♪ OFF';
            btnMusique.classList.toggle('actif', !on);
        });

        // ── Promo ──────────────────────────────────────────────────
        btnPromo.addEventListener('click', () => {
            if (window.jeuPromo) window.jeuPromo();
            btnPromo.disabled = true;
        });
        // réactive le bouton quand la promo est à nouveau disponible
        window._htmlPromoUpdate = () => {
            btnPromo.disabled = false;
        };
        // polling de sécurité au cas où la callback manque
        setInterval(() => {
            if (window.jeuPromoDispo) btnPromo.disabled = !window.jeuPromoDispo();
        }, 2000);

        // ── Recharger ──────────────────────────────────────────────
        btnRechg.addEventListener('click', () => {
            if (window.ouvrirBoutique) window.ouvrirBoutique();
        });

        // ── Quitter ────────────────────────────────────────────────
        btnQuitter.addEventListener('click', () => {
            const ok = confirm('Quitter le maquis ? Ta progression est sauvegardée.');
            if (!ok) return;
            if (window.jeuQuitter) window.jeuQuitter();
            // Réinitialise l'état du bouton Pause dans la barre
            pauseActif = false;
            btnPause.textContent = '⏸ Pause';
            btnPause.classList.remove('actif');
            // Affiche l'accueil par-dessus (Continuer visible car save existe)
            btnCont.style.display = '';
            accueil.style.display = 'flex';
        });
    })();
    </script>

    {{-- JS Boutique + achat --}}
    <script>
    (function () {
        const overlay = document.getElementById('boutique-overlay');
        const csrf    = document.querySelector('meta[name="csrf-token"]').content;
        const errEl   = document.getElementById('boutique-erreur');

        const isMobile = () => window.innerWidth <= 900;

        /* ── Ouvre/ferme la boutique ── */
        window.ouvrirBoutique = function () {
            if (isMobile()) {
                overlay.classList.add('ouvert');
                if (window.jeuPause) window.jeuPause();
            }
            // Desktop : boutique déjà visible, rien à faire
        };

        window.fermerBoutique = function () {
            overlay.classList.remove('ouvert');
            if (window.jeuReprendre) window.jeuReprendre();
        };

        // Clic sur le fond sombre (pas sur la boutique elle-même) ferme la modale
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) window.fermerBoutique();
        });

        /* ── Achats ── */
        document.querySelectorAll('.btn-acheter').forEach(btn => {
            btn.addEventListener('click', async () => {
                const pack = btn.dataset.pack;
                btn.disabled      = true;
                btn.textContent   = 'Chargement…';
                errEl.textContent = '';

                try {
                    const r = await fetch('/api/jeu/acheter', {
                        method: 'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ pack }),
                    });

                    const data = await r.json();

                    if (data.authorization_url) {
                        window.location.href = data.authorization_url;
                        return;
                    }

                    errEl.textContent = data.error ?? 'Erreur lors du paiement.';
                } catch (e) {
                    errEl.textContent = 'Problème réseau. Réessaie.';
                }

                btn.disabled    = false;
                btn.textContent = 'Acheter';
            });
        });
    })();
    </script>

</body>
</html>
