{{-- resources/views/bubbles/index.blade.php --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title>Bulles du marché — BRVM en direct</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon-boursiv.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background: #05060a; overflow: hidden;
            font-family: 'DM Sans', sans-serif;
        }

        #bubbles-stage {
            position: fixed; inset: 0; z-index: 1;
            background:
                radial-gradient(ellipse 70% 60% at 50% 45%, rgba(20,26,38,.55) 0%, transparent 70%),
                #05060a;
        }
        #bubbles-stage svg { display: block; }

        .bubble-tooltip-native { pointer-events: none; }

        /* ── Topbar ── */
        #bubbles-topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 5;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            padding: 12px 22px;
            background: linear-gradient(180deg, rgba(5,6,10,.92) 0%, rgba(5,6,10,.55) 70%, transparent 100%);
            backdrop-filter: blur(6px);
        }
        .bt-brand {
            display: flex; align-items: center; gap: 12px;
        }
        .bt-brand a {
            font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            color: #8a93a8; text-decoration: none; opacity: .8;
            display: inline-flex; align-items: center; gap: 6px;
            transition: opacity .2s, color .2s;
        }
        .bt-brand a:hover { opacity: 1; color: #C9A84C; }
        .bt-title {
            font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 800;
            color: #E8EAF0; letter-spacing: .02em;
            display: flex; align-items: center; gap: 8px;
        }
        .bt-title .dot { width: 7px; height: 7px; border-radius: 50%; background: #1fbf4a; box-shadow: 0 0 8px #1fbf4a; }
        .bt-title .dot.stale { background: #6B7590; box-shadow: none; }

        .bt-right { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }

        .bt-legend { display: flex; align-items: center; gap: 14px; }
        .bt-legend-item {
            display: flex; align-items: center; gap: 6px;
            font-family: 'Syne', sans-serif; font-size: 10.5px; font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase; color: #8a93a8;
        }
        .bt-legend-dot { width: 9px; height: 9px; border-radius: 50%; }

        .bt-date {
            font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
            letter-spacing: .04em; color: #6B7590;
        }
        .bt-date strong { color: #C9A84C; font-weight: 700; }

        .bt-btn {
            font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            color: #E8EAF0; background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1); border-radius: 4px;
            padding: 7px 14px; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all .2s;
        }
        .bt-btn:hover { background: rgba(201,168,76,.12); border-color: rgba(201,168,76,.35); color: #C9A84C; }

        @media (max-width: 860px) {
            #bubbles-topbar { padding: 10px 14px; }
            .bt-legend { display: none; }
        }

        /* ── Loader ── */
        #bubbles-loader {
            position: fixed; inset: 0; z-index: 4;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 14px; color: #6B7590;
            font-family: 'Syne', sans-serif; font-size: 12px; letter-spacing: .08em; text-transform: uppercase;
        }
        .bl-ring {
            width: 42px; height: 42px; border-radius: 50%;
            border: 3px solid rgba(201,168,76,.12); border-top-color: #C9A84C;
            animation: bl-spin 1s linear infinite;
        }
        @keyframes bl-spin { to { transform: rotate(360deg); } }

        #bubbles-empty {
            position: fixed; inset: 0; z-index: 4;
            display: none; flex-direction: column; align-items: center; justify-content: center;
            gap: 12px; color: #6B7590; text-align: center; padding: 24px;
        }
        #bubbles-empty .ic { font-size: 30px; }
        #bubbles-empty .msg {
            font-family: 'Syne', sans-serif; font-size: 12px; letter-spacing: .08em; text-transform: uppercase;
        }

        /* ═══════════════════════════════
           STOCK HISTORY MODAL (repris de /radar-marche)
        ═══════════════════════════════ */
        .stock-modal {
            position: fixed; inset: 0; z-index: 9999; display: none;
            align-items: center; justify-content: center; padding: 16px;
        }
        .stock-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.6); }
        .stock-modal-content {
            position: relative; z-index: 1;
            width: 100%; max-width: 900px; max-height: 90vh;
            background: #0C1120; border: 1px solid rgba(176,134,46,.25);
            border-radius: 12px; overflow-y: auto;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,.6);
        }
        .stock-modal-close {
            position: absolute; top: 14px; right: 16px;
            background: none; border: none; color: #6B7590;
            font-size: 22px; cursor: pointer; line-height: 1;
            padding: 4px 8px; border-radius: 4px; z-index: 2;
            transition: color .2s, background .2s;
        }
        .stock-modal-close:hover { color: #E8EAF0; background: rgba(255,255,255,.06); }
        .stock-modal-header {
            background: rgba(255,255,255,.02); border-radius: 12px 12px 0 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: 20px 56px 20px 24px;
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 20px; flex-wrap: wrap;
        }
        #smTicker {
            font-family: 'Syne', sans-serif; font-size: 24px;
            font-weight: 800; color: #E8EAF0; margin: 0; letter-spacing: .04em;
        }
        .stock-modal-subtitle { font-family: 'DM Sans', sans-serif; font-size: 13px; color: #6B7590; margin: 4px 0 0; }
        .stock-modal-stats    { display: flex; gap: 28px; flex-wrap: wrap; }
        .stock-modal-stat     { display: flex; flex-direction: column; gap: 3px; align-items: flex-end; }
        .stock-stat-label {
            font-family: 'Syne', sans-serif; font-size: 10px;
            font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #6B7590;
        }
        .stock-stat-value { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; }
        .stock-modal-ranges { display: flex; gap: 8px; padding: 16px 24px 0; flex-wrap: wrap; }
        .range-btn {
            font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
            padding: 7px 16px; border-radius: 3px;
            border: 1px solid #C9A84C; background: transparent;
            color: #C9A84C; cursor: pointer; transition: all .2s;
        }
        .range-btn:hover:not(:disabled) { background: rgba(201,168,76,.12); }
        .range-btn.active               { background: #C9A84C; color: #060910; }
        .range-btn:disabled             { opacity: .35; cursor: not-allowed; }
        .stock-modal-chart {
            padding: 16px 24px 24px; position: relative;
            height: 380px; flex-shrink: 0;
        }
        .stock-empty-state, .stock-loading {
            position: absolute; inset: 16px 24px 24px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 12px;
            font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
            color: #6B7590; flex-direction: column; gap: 10px;
        }
        .stock-loading-ring {
            width: 36px; height: 36px; border-radius: 50%;
            border: 3px solid rgba(176,134,46,.1); border-top-color: #C9A84C;
            animation: bl-spin 1s linear infinite;
        }
        @media (max-width: 767px) {
            .stock-modal-content { max-width: 95vw; }
            .stock-modal-header  { flex-direction: column; gap: 12px; padding-right: 48px; }
            .stock-modal-stats   { gap: 16px; }
            .stock-modal-stat    { align-items: flex-start; }
            .stock-modal-chart   { height: 280px; }
        }
    </style>
</head>
<body>

    {{-- ── TOPBAR ── --}}
    <div id="bubbles-topbar">
        <div class="bt-brand">
            <a href="{{ route('landing') }}"><i class="bi bi-arrow-left"></i> Coach BRVM</a>
        </div>
        <div class="bt-title">
            <span class="dot stale" id="bt-live-dot"></span>
            Bulles du marché — BRVM
        </div>
        <div class="bt-right">
            <div class="bt-legend">
                <div class="bt-legend-item"><span class="bt-legend-dot" style="background:#1fbf4a;"></span> Hausse</div>
                <div class="bt-legend-item"><span class="bt-legend-dot" style="background:#e53935;"></span> Baisse</div>
                <div class="bt-legend-item"><span class="bt-legend-dot" style="background:#7a7a85;"></span> Stable</div>
            </div>
            <div class="bt-date" id="bt-date">Chargement…</div>
            <a href="{{ route('radar.index') }}" class="bt-btn"><i class="bi bi-graph-up"></i> Vue radar</a>
        </div>
    </div>

    {{-- ── STAGE ── --}}
    <div id="bubbles-stage"></div>

    <div id="bubbles-loader">
        <div class="bl-ring"></div>
        <div>Chargement des données marché…</div>
    </div>

    <div id="bubbles-empty">
        <span class="ic">⚠️</span>
        <span class="msg">Aucune donnée de marché disponible</span>
    </div>

    {{-- ── STOCK HISTORY MODAL ── --}}
    <div id="stockHistoryModal" class="stock-modal">
        <div class="stock-modal-overlay"></div>
        <div class="stock-modal-content">
            <button class="stock-modal-close" aria-label="Fermer">&times;</button>

            <div class="stock-modal-header">
                <div>
                    <h2 id="smTicker">—</h2>
                    <p id="smName" class="stock-modal-subtitle">—</p>
                </div>
                <div class="stock-modal-stats">
                    <div class="stock-modal-stat">
                        <span class="stock-stat-label">Dernier cours</span>
                        <span class="stock-stat-value" id="smLastPrice">—</span>
                    </div>
                    <div class="stock-modal-stat">
                        <span class="stock-stat-label">Variation</span>
                        <span class="stock-stat-value" id="smLastChange">—</span>
                    </div>
                    <div class="stock-modal-stat">
                        <span class="stock-stat-label">Période</span>
                        <span class="stock-stat-value" id="smDateRange">—</span>
                    </div>
                </div>
            </div>

            <div class="stock-modal-ranges">
                <button class="range-btn" data-range="1w">1 Semaine</button>
                <button class="range-btn" data-range="1m">1 Mois</button>
                <button class="range-btn" data-range="1y">1 An</button>
                <button class="range-btn active" data-range="all">Tout</button>
            </div>

            <div class="stock-modal-chart">
                <canvas id="smChart" style="display:none;"></canvas>
                <div id="smEmptyState" class="stock-empty-state" style="display:none;">
                    Aucune donnée disponible sur cette période
                </div>
                <div id="smLoading" class="stock-loading" style="display:none;">
                    <div class="stock-loading-ring"></div>
                    <span>Chargement…</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>

    <script>
    /* ═══════════════════════════════
       BULLES FLOTTANTES — style CryptoBubbles
    ═══════════════════════════════ */
    const stage      = document.getElementById('bubbles-stage');
    const loaderEl   = document.getElementById('bubbles-loader');
    const emptyEl    = document.getElementById('bubbles-empty');
    const dateEl     = document.getElementById('bt-date');
    const liveDotEl  = document.getElementById('bt-live-dot');

    const TOP_PAD = 84; // évite que les bulles passent sous la topbar

    let width  = window.innerWidth;
    let height = window.innerHeight;

    const svg = d3.select(stage).append('svg')
        .attr('width', width).attr('height', height);

    const defs = svg.append('defs');
    // NB: color-interpolation-filters="sRGB" est indispensable — le défaut SVG (linearRGB)
    // éclaircit/blanchit les couleurs floutées, ce qui produit une surbrillance blanchâtre
    // aux endroits où plusieurs halos se chevauchent.
    const glow = defs.append('filter')
        .attr('id', 'bubbleGlow')
        .attr('x', '-80%').attr('y', '-80%').attr('width', '260%').attr('height', '260%')
        .attr('color-interpolation-filters', 'sRGB');
    glow.append('feGaussianBlur').attr('stdDeviation', 4).attr('result', 'blur');
    const glowMerge = glow.append('feMerge');
    glowMerge.append('feMergeNode').attr('in', 'blur');

    const gRoot = svg.append('g').attr('class', 'bubbles-root');

    let nodesData = [];
    let sim = null;

    function paletteFor(change) {
        if (change > 0.1)  return { fill: 'rgba(31,191,74,.045)',  stroke: 'rgba(31,191,74,.85)',  glowColor: 'rgba(31,191,74,.55)',  text: '#5FE68B' };
        if (change < -0.1) return { fill: 'rgba(229,57,53,.045)',  stroke: 'rgba(229,57,53,.85)',  glowColor: 'rgba(229,57,53,.55)',  text: '#FF7A73' };
        return                     { fill: 'rgba(150,155,168,.035)', stroke: 'rgba(150,155,168,.55)', glowColor: 'rgba(150,155,168,.3)', text: '#A7ADBA' };
    }

    // Rayons calqués sur sqrt(|variation|), mis à l'échelle pour que la somme des
    // surfaces reste sous une fraction raisonnable de l'espace dispo — sinon la
    // collision ne peut physiquement pas empêcher les chevauchements (pas assez de place).
    function radiiFor(data) {
        const changes  = data.map(d => Math.max(Math.abs(Number(d.change ?? 0)), 0.15));
        const availArea = width * Math.max(200, height - TOP_PAD);
        const targetFill = 0.42;
        const sumSquares = changes.reduce((a, c) => a + c, 0) || 1; // sqrt(c)^2 = c
        const k = Math.sqrt((availArea * targetFill) / (Math.PI * sumSquares));

        const minR = Math.max(20, Math.min(width, height) / 30);
        const maxR = Math.max(46, Math.min(width, height) / 6.5);

        return changes.map(c => Math.max(minR, Math.min(maxR, k * Math.sqrt(c))));
    }

    function syncNodes(data) {
        const radii = radiiFor(data);
        const byTicker = new Map(nodesData.map(d => [d.ticker, d]));

        return data.map((d, i) => {
            const change = Number(d.change ?? 0);
            const radius = radii[i];
            const existing = byTicker.get(d.ticker);
            if (existing) {
                existing.change = change;
                existing.radius = radius;
                existing.name   = d.name;
                existing.price  = d.price;
                return existing;
            }
            return {
                ticker: d.ticker, name: d.name, price: d.price, change, radius,
                x: width / 2 + (Math.random() - 0.5) * width * 0.7,
                y: TOP_PAD + (height - TOP_PAD) / 2 + (Math.random() - 0.5) * (height - TOP_PAD) * 0.7,
                vx: 0, vy: 0,
                driftAngle: Math.random() * Math.PI * 2,
            };
        });
    }

    function ticked() {
        const pad = 3;
        nodesData.forEach(d => {
            const minX = d.radius + pad, maxX = width - d.radius - pad;
            const minY = TOP_PAD + d.radius + pad, maxY = height - d.radius - pad;
            if (d.x < minX) { d.x = minX; if (d.vx < 0) d.vx *= -0.5; }
            if (d.x > maxX) { d.x = maxX; if (d.vx > 0) d.vx *= -0.5; }
            if (d.y < minY) { d.y = minY; if (d.vy < 0) d.vy *= -0.5; }
            if (d.y > maxY) { d.y = maxY; if (d.vy > 0) d.vy *= -0.5; }
        });
        gRoot.selectAll('g.bubble').attr('transform', d => `translate(${d.x},${d.y})`);
    }

    // Flottement doux et continu façon lave-lampe : la direction dérive lentement
    // (petit incrément aléatoire, pas un tirage indépendant à chaque frame) pour un
    // mouvement fluide, sans le tremblement d'un bruit blanc pur.
    const REST_ALPHA = 0.12;
    function driftForce() {
        for (const d of nodesData) {
            if (d.fx != null) continue; // en cours de drag
            d.driftAngle += (Math.random() - 0.5) * 0.12;
            const amt = 0.01 + 2.2 / (d.radius + 30);
            d.vx += Math.cos(d.driftAngle) * amt;
            d.vy += Math.sin(d.driftAngle) * amt;
        }
    }

    // Repos = léger flottement continu (REST_ALPHA). Drag = physique un peu plus
    // réactive le temps du geste, puis retour au flottement calme.
    const dragBehavior = d3.drag()
        .on('start', (e, d) => {
            d._dragMoved = false; d._dragX0 = e.x; d._dragY0 = e.y;
            if (sim) sim.alpha(0.35);
            d.fx = d.x; d.fy = d.y;
        })
        .on('drag', (e, d) => {
            if (Math.hypot(e.x - d._dragX0, e.y - d._dragY0) > 5) d._dragMoved = true;
            d.fx = e.x; d.fy = e.y;
        })
        .on('end', (e, d) => {
            if (sim) sim.alpha(REST_ALPHA);
            d.fx = null; d.fy = null;
        });

    function render(data) {
        const firstRender = !sim;
        nodesData = syncNodes(data);

        if (firstRender) {
            sim = d3.forceSimulation(nodesData)
                .alphaDecay(0.04)
                .velocityDecay(0.5)
                .force('charge', d3.forceManyBody().strength(-6))
                .force('collision', d3.forceCollide().radius(d => d.radius + 4).strength(1))
                .force('x', d3.forceX(width / 2).strength(0.03))
                .force('y', d3.forceY(TOP_PAD + (height - TOP_PAD) / 2).strength(0.03));

            // Pré-chauffe silencieuse : la disposition sans chevauchement est calculée
            // avant le premier affichage, pour éviter un effet de "scramble" visible.
            sim.stop();
            for (let i = 0; i < 400; i++) sim.tick();

            // Une fois rangées, on bascule sur un flottement perpétuel très doux :
            // alphaDecay(0) + une petite alpha constante + la force de drift.
            sim.alphaDecay(0);
            sim.force('drift', driftForce);
        } else {
            sim.nodes(nodesData);
            sim.force('x', d3.forceX(width / 2).strength(0.03));
            sim.force('y', d3.forceY(TOP_PAD + (height - TOP_PAD) / 2).strength(0.03));
        }

        const sel = gRoot.selectAll('g.bubble').data(nodesData, d => d.ticker);

        sel.exit().transition().duration(400).style('opacity', 0).remove();

        const enter = sel.enter().append('g')
            .attr('class', 'bubble')
            .style('cursor', 'pointer')
            .style('opacity', 0)
            .attr('transform', d => `translate(${d.x},${d.y})`)
            .call(dragBehavior)
            .on('click', (e, d) => { if (!d._dragMoved) openStockModal(d.ticker); });

        enter.append('circle').attr('class', 'glow').attr('stroke', 'none').style('filter', 'url(#bubbleGlow)');
        enter.append('circle').attr('class', 'main').attr('stroke-opacity', 0.9);
        enter.append('text').attr('class', 'tk-label').attr('text-anchor', 'middle').attr('dy', '-0.2em').style('pointer-events', 'none');
        enter.append('text').attr('class', 'ch-label').attr('text-anchor', 'middle').attr('dy', '1.1em').style('pointer-events', 'none');
        enter.append('title');

        enter.transition().duration(500).style('opacity', 1);

        const merged = enter.merge(sel);

        // Halo doux et flou, en dessous — donne la lueur sans blanchir la couleur
        merged.select('circle.glow')
            .attr('r', d => d.radius * 0.9)
            .attr('fill', d => paletteFor(d.change).glowColor)
            .attr('fill-opacity', 0.45);

        // Bulle nette et transparente au-dessus, façon cryptobubbles.net
        merged.select('circle.main')
            .attr('r', d => d.radius)
            .attr('fill', d => paletteFor(d.change).fill)
            .attr('stroke', d => paletteFor(d.change).stroke)
            .attr('stroke-width', 2);

        merged.select('text.tk-label')
            .style('fill', d => paletteFor(d.change).text)
            .style('font-family', "'Syne', sans-serif")
            .style('font-weight', '800')
            .style('font-size', d => Math.max(11, Math.min(d.radius / 2.5, 28)) + 'px')
            .text(d => (d.ticker || '').toString().toUpperCase());

        merged.select('text.ch-label')
            .style('fill', d => paletteFor(d.change).text)
            .style('font-family', "'DM Sans', sans-serif")
            .style('font-weight', '600')
            .style('font-size', d => Math.max(9, Math.min(d.radius / 3.6, 16)) + 'px')
            .text(d => `${d.change >= 0 ? '+' : ''}${d.change.toFixed(1)}%`);

        merged.select('title')
            .text(d => `${d.name || d.ticker}\nVariation : ${d.change >= 0 ? '+' : ''}${d.change.toFixed(2)} %`);

        if (firstRender) {
            sim.on('tick', ticked);
            sim.alpha(REST_ALPHA).restart();
        } else {
            // Petit sursaut le temps de digérer les nouvelles tailles/tickers,
            // puis retour au flottement calme.
            sim.alpha(0.3).restart();
            settleSoon();
        }
    }

    let settleTimer = null;
    function settleSoon() {
        clearTimeout(settleTimer);
        settleTimer = setTimeout(() => { if (sim) sim.alpha(REST_ALPHA); }, 2000);
    }

    /* ── Resize ── */
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            width  = window.innerWidth;
            height = window.innerHeight;
            svg.attr('width', width).attr('height', height);
            if (sim) {
                sim.force('x', d3.forceX(width / 2).strength(0.03));
                sim.force('y', d3.forceY(TOP_PAD + (height - TOP_PAD) / 2).strength(0.03));
                sim.alpha(0.3).restart();
                settleSoon();
            }
        }, 200);
    });

    /* ── Chargement des données ── */
    let lastData = null;

    async function loadBubbles(isRefresh) {
        if (!isRefresh) {
            loaderEl.style.display = 'flex';
            emptyEl.style.display  = 'none';
        }

        try {
            const res = await fetch("{{ route('radar.bubblesLatest') }}", { cache: 'no-store' });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const payload = await res.json();
            const date = payload?.date ?? null;
            const data = Array.isArray(payload) ? payload : (payload?.bubbles ?? payload?.data ?? []);

            loaderEl.style.display = 'none';
            liveDotEl.classList.remove('stale');

            if (!Array.isArray(data) || data.length === 0) {
                emptyEl.style.display = 'flex';
                dateEl.textContent = 'Aucune donnée disponible';
                return;
            }

            emptyEl.style.display = 'none';
            lastData = data;

            const now = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            dateEl.innerHTML = date
                ? `BOC du <strong>${date}</strong> · actualisé à ${now}`
                : `Dernier BOC disponible · actualisé à ${now}`;

            render(data);

        } catch (e) {
            console.error('Bubbles error:', e);
            loaderEl.style.display = 'none';
            liveDotEl.classList.add('stale');
            if (!lastData) {
                emptyEl.style.display = 'flex';
                dateEl.textContent = 'Erreur de chargement';
            }
        }
    }

    loadBubbles(false);
    setInterval(() => loadBubbles(true), 60000);
    </script>

    <script>
    /* ═══════════════════════════════
       STOCK HISTORY MODAL (repris de /radar-marche)
    ═══════════════════════════════ */
    let smChartInstance    = null;
    let currentModalTicker = null;

    const stockModal     = document.getElementById('stockHistoryModal');
    const smOverlay       = stockModal.querySelector('.stock-modal-overlay');
    const smCloseBtn      = stockModal.querySelector('.stock-modal-close');
    const smTickerEl      = document.getElementById('smTicker');
    const smNameEl        = document.getElementById('smName');
    const smLastPriceEl   = document.getElementById('smLastPrice');
    const smLastChangeEl  = document.getElementById('smLastChange');
    const smDateRangeEl   = document.getElementById('smDateRange');
    const smLoadingEl     = document.getElementById('smLoading');
    const smEmptyEl       = document.getElementById('smEmptyState');
    const smCanvas        = document.getElementById('smChart');
    const smRangeBtns     = document.querySelectorAll('.range-btn');

    function smFormatPrice(v) {
        return Math.round(v).toLocaleString('fr-FR');
    }

    function smFormatDate(iso) {
        const [y, m, d] = iso.split('-');
        const months = ['jan.','fév.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
        return `${parseInt(d)} ${months[parseInt(m)-1]} ${y}`;
    }

    function smTimeUnit(range) {
        return (range === '1w' || range === '1m') ? 'day' : 'month';
    }

    function closeStockModal() {
        stockModal.style.display = 'none';
        if (smChartInstance) { smChartInstance.destroy(); smChartInstance = null; }
        currentModalTicker = null;
    }

    function openStockModal(ticker) {
        currentModalTicker = ticker;
        stockModal.style.display = 'flex';

        smTickerEl.textContent     = ticker;
        smNameEl.textContent       = '—';
        smLastPriceEl.textContent  = '—';
        smLastChangeEl.textContent = '—';
        smLastChangeEl.style.color = '#E8EAF0';
        smDateRangeEl.textContent  = '—';
        smLoadingEl.style.display  = 'none';
        smEmptyEl.style.display    = 'none';
        smCanvas.style.display     = 'none';

        smRangeBtns.forEach(b => {
            b.disabled = false;
            b.removeAttribute('title');
            b.classList.remove('active');
        });
        document.querySelector('.range-btn[data-range="all"]').classList.add('active');

        probeAllRanges(ticker).then(() => {
            if (currentModalTicker === ticker) smLoadRange(ticker, 'all');
        });
    }

    async function probeAllRanges(ticker) {
        const ranges  = ['1w', '1m', '1y', 'all'];
        const results = await Promise.allSettled(
            ranges.map(r =>
                fetch(`/api/stock/${encodeURIComponent(ticker)}/history?range=${r}`)
                    .then(res => ({ range: r, ok: res.ok }))
                    .catch(()  => ({ range: r, ok: false }))
            )
        );
        results.forEach(r => {
            if (r.status !== 'fulfilled') return;
            const { range, ok } = r.value;
            const btn = document.querySelector(`.range-btn[data-range="${range}"]`);
            if (!btn) return;
            btn.disabled = !ok;
            if (!ok) btn.setAttribute('title', 'Pas de données sur cette plage');
            else     btn.removeAttribute('title');
        });
    }

    async function smLoadRange(ticker, range) {
        smLoadingEl.style.display = 'flex';
        smEmptyEl.style.display   = 'none';
        smCanvas.style.display    = 'none';

        smRangeBtns.forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.range-btn[data-range="${range}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        try {
            const res = await fetch(`/api/stock/${encodeURIComponent(ticker)}/history?range=${range}`);

            if (!res.ok) {
                smLoadingEl.style.display = 'none';
                smEmptyEl.style.display   = 'flex';
                if (activeBtn) activeBtn.disabled = true;
                return;
            }

            const payload = await res.json();

            smNameEl.textContent = payload.name || ticker;

            const last = payload.data[payload.data.length - 1];
            smLastPriceEl.textContent = last?.price != null
                ? smFormatPrice(last.price) + ' FCFA' : '—';

            const c = last?.change ?? null;
            if (c !== null) {
                smLastChangeEl.textContent = (c >= 0 ? '+' : '') + c.toFixed(2).replace('.', ',') + ' %';
                smLastChangeEl.style.color = c >= 0 ? '#1fbf4a' : '#e53935';
            }

            smDateRangeEl.textContent = `${smFormatDate(payload.first_date)} → ${smFormatDate(payload.last_date)}`;

            smLoadingEl.style.display = 'none';
            smCanvas.style.display    = 'block';

            if (smChartInstance) { smChartInstance.destroy(); smChartInstance = null; }

            const pts = payload.data.map(p => ({ x: p.date, y: p.price, ch: p.change }));

            smChartInstance = new Chart(smCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    datasets: [{
                        data: pts,
                        borderColor: '#C9A84C',
                        backgroundColor: 'rgba(201,168,76,.1)',
                        fill: true,
                        tension: 0.2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#C9A84C',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0C1120',
                            borderColor: 'rgba(201,168,76,.3)',
                            borderWidth: 1,
                            titleColor: '#E8EAF0',
                            bodyColor: '#6B7590',
                            titleFont: { family: 'Syne', weight: '700', size: 13 },
                            bodyFont: { family: 'DM Sans', size: 12 },
                            padding: 10,
                            callbacks: {
                                title: items => {
                                    const d = new Date(items[0].parsed.x);
                                    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
                                },
                                label: item => ` Cours : ${smFormatPrice(item.parsed.y)} FCFA`,
                                afterLabel: item => {
                                    const ch = pts[item.dataIndex]?.ch;
                                    if (ch == null) return '';
                                    return ` Variation : ${ch >= 0 ? '+' : ''}${ch.toFixed(2).replace('.', ',')} %`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: { unit: smTimeUnit(range), displayFormats: { day: 'd MMM', month: 'MMM yy' } },
                            grid: { color: 'rgba(255,255,255,.04)' },
                            ticks: { color: '#6B7590', font: { family: 'Syne', size: 10 }, maxTicksLimit: 8 }
                        },
                        y: {
                            title: { display: true, text: 'Cours (FCFA)', color: '#6B7590', font: { family: 'Syne', size: 11 } },
                            grid: { color: 'rgba(255,255,255,.04)' },
                            ticks: { color: '#6B7590', font: { family: 'Syne', size: 10 }, callback: v => smFormatPrice(v) }
                        }
                    }
                }
            });

        } catch (err) {
            smLoadingEl.style.display = 'none';
            smEmptyEl.style.display   = 'flex';
            console.error('smLoadRange error:', err);
        }
    }

    smRangeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled || !currentModalTicker) return;
            smLoadRange(currentModalTicker, btn.dataset.range);
        });
    });

    smCloseBtn.addEventListener('click', closeStockModal);
    smOverlay.addEventListener('click',  closeStockModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeStockModal(); });
    </script>
</body>
</html>
