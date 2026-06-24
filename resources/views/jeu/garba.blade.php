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

        /* ── Canvas Phaser ── */
        #jeu { flex-shrink: 0; width: 420px; height: 640px; }
        #jeu canvas { display: block; }

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

    {{-- Canvas Phaser --}}
    <div id="jeu"></div>

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

        btnCont.addEventListener('click', () => demarrer());

        btnNouvelle.addEventListener('click', async () => {
            if (aProgression) {
                const ok = confirm('Tu vas perdre ta progression actuelle. Recommencer à zéro ?');
                if (!ok) return;
                await fetch('/api/jeu/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                }).catch(() => {});
            }
            demarrer();
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
