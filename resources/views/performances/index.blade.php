{{-- resources/views/performances/index.blade.php (ou radar/index.blade.php) --}}
@extends('layouts.app')

@push('styles')
<style>
    /* ── Base ── */
    .radar-page { background: #060910; min-height: 100vh; }

    /* ── Hero ── */
    .radar-hero {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px;
        position: relative; overflow: hidden;
    }
    .radar-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .radar-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #0FCFA4;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .radar-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .radar-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 5vw, 48px); font-weight: 900;
        color: #E8EAF0; line-height: 1.08; margin-bottom: 10px;
    }
    .radar-hero-title em { font-style: italic; color: #C9A84C; }
    .radar-hero-desc { font-size: 14px; color: #6B7590; font-weight: 300; line-height: 1.7; }

    /* ── Boutons ── */
    .cb-btn-gold {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, #C9A84C, #9B6B15);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 12px; letter-spacing: .07em; text-transform: uppercase;
        padding: 10px 20px; border: none; border-radius: 3px;
        cursor: pointer; text-decoration: none; transition: all .3s;
    }
    .cb-btn-gold:hover { box-shadow: 0 6px 20px rgba(201,168,76,.3); transform: translateY(-1px); }

    .cb-btn-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: transparent; color: #E8EAF0 !important;
        font-family: 'Syne', sans-serif; font-weight: 600;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(255,255,255,.12);
        border-radius: 3px; text-decoration: none; transition: all .3s; cursor: pointer;
    }
    .cb-btn-outline:hover { border-color: #C9A84C; color: #C9A84C !important; background: rgba(201,168,76,.05); }

    .cb-btn-green {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(15,207,164,.1); color: #0FCFA4 !important;
        font-family: 'Syne', sans-serif; font-weight: 700;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(15,207,164,.2);
        border-radius: 3px; cursor: pointer; transition: all .3s;
    }
    .cb-btn-green:hover { background: rgba(15,207,164,.16); border-color: rgba(15,207,164,.4); }

    /* ── Card de base ── */
    .radar-card {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.08);
        border-radius: 4px;
        overflow: hidden;
    }
    .radar-card-header {
        background: #121A2C;
        border-bottom: 1px solid rgba(255,255,255,.05);
        padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        flex-wrap: wrap;
    }
    .radar-card-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
        color: #E8EAF0; display: flex; align-items: center; gap: 8px;
    }
    .radar-card-sub { font-size: 12px; color: #6B7590; margin-top: 2px; }
    .radar-card-body { padding: 20px; }

    /* ── Selector sociétés ── */
    .radar-select {
        background: rgba(6,9,16,.9) !important;
        border: 1px solid rgba(255,255,255,.1) !important;
        color: #E8EAF0 !important;
        border-radius: 3px !important;
        font-family: 'DM Sans', sans-serif !important;
        font-size: 13px !important;
        width: 100%;
        transition: border-color .25s;
        outline: none;
    }
    .radar-select:focus {
        border-color: rgba(201,168,76,.4) !important;
        box-shadow: 0 0 0 3px rgba(201,168,76,.07) !important;
    }
    .radar-select option { background: #0C1120; color: #E8EAF0; }
    .radar-select-label {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .12em; text-transform: uppercase; color: #6B7590;
        display: block; margin-bottom: 8px;
    }
    .radar-select-hint { font-size: 12px; color: #6B7590; margin-top: 6px; }

    /* ── Chart wrapper ── */
    .chart-wrap {
        position: relative; width: 100%;
        min-height: 320px;
        background: rgba(6,9,16,.6);
        border-radius: 3px;
        padding: 4px;
    }
    @media (min-width: 992px) { .chart-wrap { min-height: 420px; } }

    /* ── Bubbles section ── */
    .bubbles-section { margin-top: 28px; }

    .bubbles-header {
        background: #121A2C;
        border-bottom: 1px solid rgba(255,255,255,.05);
        padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 12px; flex-wrap: wrap;
    }
    .bubbles-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: #E8EAF0;
    }
    .bubbles-sub { font-size: 12px; color: #6B7590; margin-top: 3px; }

    #brvm-bubbles {
        width: 100%;
        height: 78vh;
        min-height: 600px;
        background: #060910;
        border-radius: 3px;
        overflow: hidden;
    }

    #brvm-bubbles:fullscreen {
        background: #060910;
        padding: 12px;
    }

    .bubbles-legend {
        display: flex; gap: 20px; flex-wrap: wrap;
        margin-top: 14px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .08em; text-transform: uppercase;
    }
    .bubbles-legend-item { display: flex; align-items: center; gap: 7px; color: #6B7590; }
    .bubbles-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

    /* Loader bubbles */
    .bubbles-loader {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        height: 100%; gap: 16px; color: #6B7590;
    }
    .bubbles-loader-ring {
        width: 48px; height: 48px; border-radius: 50%;
        border: 3px solid rgba(201,168,76,.1);
        border-top-color: #C9A84C;
        animation: spin 1s linear infinite;
    }
    .bubbles-loader-text {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .1em; text-transform: uppercase;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Sections */
    .radar-section { padding: clamp(32px, 4vw, 48px) 0; }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
</style>
@endpush

@section('content')
<div class="radar-page">

    {{-- ── HERO ── --}}
    <div class="radar-hero">
        <div class="radar-hero-grid"></div>
        <div class="container" style="max-width:1200px;position:relative;z-index:1;">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <p class="radar-hero-tag">Marché BRVM</p>
                    <h1 class="radar-hero-title">
                        Radar <em>Performances</em><br>7 derniers jours
                    </h1>
                    <p class="radar-hero-desc">
                        Suis les variations des sociétés cotées sur la BRVM en un coup d'œil.
                        Graphe linéaire + vue bulles interactive.
                    </p>
                </div>
                <div class="col-lg-5 d-flex justify-content-lg-end">
                    <a href="{{ route('landing') }}" class="cb-btn-outline">
                        ← Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container radar-section" style="max-width:1200px;">

        {{-- ── LIGNE CHART ── --}}
        <div class="radar-card cbr mb-4">
            <div class="radar-card-header">
                <div>
                    <div class="radar-card-title">📈 Performances — 7 jours</div>
                    <div class="radar-card-sub">Sélectionne des sociétés ou laisse vide pour voir le Top 5 automatique</div>
                </div>
                <button id="btnLoad" class="cb-btn-gold">
                    <i class="bi bi-bar-chart-line"></i> Afficher le graphe
                </button>
            </div>

            <div class="radar-card-body">
                {{-- Selector --}}
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-lg-10">
                        <label class="radar-select-label">Choisir une ou plusieurs sociétés</label>
                        <select id="tickers" class="radar-select" multiple size="5">
                            @foreach($companies as $c)
                                <option value="{{ $c->ticker }}">
                                    {{ $c->ticker }} — {{ $c->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="radar-select-hint">
                            💡 Ctrl + clic pour sélectionner plusieurs sociétés. Sans sélection → Top 5 du dernier jour.
                        </div>
                    </div>
                    <div class="col-lg-2 d-grid">
                        <button id="btnLoad2" class="cb-btn-gold" style="height:42px;">
                            <i class="bi bi-funnel"></i> Filtrer
                        </button>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="chart-wrap">
                    <canvas id="perfChart"></canvas>
                </div>

                <div style="margin-top:12px;font-size:12px;color:#6B7590;font-family:'Syne',sans-serif;letter-spacing:.06em;">
                    💡 Pinch/zoom disponible sur mobile
                </div>
            </div>
        </div>

        {{-- ── BULLES MARCHÉ ── --}}
        <div class="radar-card bubbles-section cbr cbr2">
            <div class="bubbles-header">
                <div>
                    <div class="bubbles-title">🌐 Vue d'ensemble du marché — Bulles interactives</div>
                    <div class="bubbles-sub" id="marketBocDate">
                        Variations du jour · Chargement en cours…
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button id="btn-bubbles-reload" class="cb-btn-green">
                        <i class="bi bi-arrow-clockwise"></i> Rafraîchir
                    </button>
                    <button id="btn-bubbles-fullscreen" class="cb-btn-outline">
                        <i class="bi bi-fullscreen"></i> Plein écran
                    </button>
                </div>
            </div>

            <div style="padding:16px 20px 20px;">
                <div id="brvm-bubbles">
                    <div class="bubbles-loader" id="bubbles-loader">
                        <div class="bubbles-loader-ring"></div>
                        <div class="bubbles-loader-text">Chargement des données marché…</div>
                    </div>
                </div>

                <div class="bubbles-legend">
                    <div class="bubbles-legend-item">
                        <span class="bubbles-legend-dot" style="background:#1fbf4a;"></span> Hausse
                    </div>
                    <div class="bubbles-legend-item">
                        <span class="bubbles-legend-dot" style="background:#e53935;"></span> Baisse
                    </div>
                    <div class="bubbles-legend-item">
                        <span class="bubbles-legend-dot" style="background:#555;"></span> Stable
                    </div>
                    <div class="bubbles-legend-item" style="margin-left:auto;color:#6B7590;">
                        Glisse les bulles pour les déplacer
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
/* ═══════════════════════════════
   LINE CHART — 7 jours
═══════════════════════════════ */
let chart;

const PALETTE = [
    '#C9A84C','#0FCFA4','#63B3ED','#FC814A',
    '#B090FF','#FF6B6B','#FFC850','#4AE3B5',
    '#F687B3','#68D391'
];

function resizeChartSoon(){
    setTimeout(() => { if(chart) chart.resize(); }, 50);
    setTimeout(() => { if(chart) chart.resize(); }, 300);
}

async function loadData() {
    const select = document.getElementById('tickers');
    const tickers = Array.from(select.selectedOptions).map(o => o.value);
    const url = new URL("{{ route('radar.data') }}", window.location.origin);
    tickers.forEach(t => url.searchParams.append('tickers[]', t));

    try {
        const res  = await fetch(url);
        const data = await res.json();
        const ctx  = document.getElementById('perfChart').getContext('2d');

        if (chart) chart.destroy();

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: (data.datasets || []).map((ds, i) => ({
                    label:           ds.label,
                    data:            ds.data,
                    tension:         0.3,
                    spanGaps:        true,
                    borderColor:     PALETTE[i % PALETTE.length],
                    backgroundColor: PALETTE[i % PALETTE.length] + '18',
                    borderWidth:     2.5,
                    pointRadius:     4,
                    pointHoverRadius:7,
                    fill:            false,
                }))
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                animation:           { duration: 600 },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#6B7590',
                            font:  { family: 'Syne', size: 11, weight: '600' },
                            padding: 16,
                            boxWidth: 14,
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0C1120',
                        borderColor:     'rgba(201,168,76,.2)',
                        borderWidth:     1,
                        titleColor:      '#E8EAF0',
                        bodyColor:       '#6B7590',
                        titleFont:       { family: 'Syne', weight: '700' },
                        bodyFont:        { family: 'DM Sans' },
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y ?? 'NC'} %`
                        }
                    }
                },
                scales: {
                    x: {
                        grid:   { color: 'rgba(255,255,255,.04)' },
                        ticks:  { color: '#6B7590', font: { family: 'Syne', size: 10 } }
                    },
                    y: {
                        grid:   { color: 'rgba(255,255,255,.04)' },
                        ticks:  { color: '#6B7590', font: { family: 'Syne', size: 10 }, callback: v => v + ' %' },
                        title:  { display: true, text: 'Variation (%)', color: '#6B7590', font: { family: 'Syne', size: 11 } }
                    }
                }
            }
        });

        resizeChartSoon();

    } catch(e) {
        console.error('Chart error:', e);
    }
}

document.getElementById('btnLoad').addEventListener('click', loadData);
document.getElementById('btnLoad2').addEventListener('click', loadData);
window.addEventListener('resize', resizeChartSoon);
window.addEventListener('orientationchange', resizeChartSoon);
document.addEventListener('visibilitychange', () => { if(!document.hidden) resizeChartSoon(); });
loadData();


/* ═══════════════════════════════
   D3 BUBBLES — dernier BOC
═══════════════════════════════ */
const bubblesDiv    = document.getElementById('brvm-bubbles');
const loader        = document.getElementById('bubbles-loader');
const fullscreenBtn = document.getElementById('btn-bubbles-fullscreen');
const reloadBtn     = document.getElementById('btn-bubbles-reload');
const bocDateLabel  = document.getElementById('marketBocDate');

let lastBubbleData = null;

function colorFn(d){
    const c = Number(d.change ?? 0);
    if (c > 0.1)  return '#1fbf4a';
    if (c < -0.1) return '#e53935';
    return '#444';
}

function drawBubbles(data){
    // Supprimer SVG précédent mais garder le loader masqué
    bubblesDiv.querySelectorAll('svg').forEach(s => s.remove());

    if (!Array.isArray(data) || data.length === 0) {
        bubblesDiv.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:center;height:100%;
                        font-family:'Syne',sans-serif;font-size:13px;color:#6B7590;">
                Aucune donnée de marché disponible.
            </div>`;
        return;
    }

    const width  = bubblesDiv.clientWidth  || 800;
    const height = bubblesDiv.clientHeight || 600;

    const svg = d3.select('#brvm-bubbles')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    const maxAbs = d3.max(data, d => Math.abs(Number(d.change ?? 0))) || 1;

    const radiusScale = d3.scaleSqrt()
        .domain([0, maxAbs])
        .range(data.length >= 40 ? [22, 88] : [32, 110]);

    const nodes = data.map(d => ({
        ...d,
        change: Number(d.change ?? 0),
        radius: Math.max(24, radiusScale(Math.abs(Number(d.change ?? 0)))),
        x: width  / 2 + (Math.random() - 0.5) * 80,
        y: height / 2 + (Math.random() - 0.5) * 80,
    }));

    const node = svg.append('g')
        .selectAll('g.node')
        .data(nodes)
        .enter().append('g')
        .attr('class', 'node')
        .style('cursor', 'grab')
        .call(
            d3.drag()
                .on('start', (e,d) => { if(!e.active) sim.alphaTarget(.3).restart(); d.fx=d.x; d.fy=d.y; })
                .on('drag',  (e,d) => { d.fx=e.x; d.fy=e.y; })
                .on('end',   (e,d) => { if(!e.active) sim.alphaTarget(0); d.fx=null; d.fy=null; })
        );

    /* Cercle */
    node.append('circle')
        .attr('r', d => d.radius)
        .attr('fill', d => colorFn(d))
        .attr('stroke', 'rgba(255,255,255,.15)')
        .attr('stroke-width', 1.5)
        .attr('fill-opacity', .88);

    /* Ticker */
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '-0.25em')
        .style('fill', '#fff')
        .style('font-family', "'Syne', sans-serif")
        .style('font-weight', '700')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(11, Math.min(d.radius / 2.8, 20)) + 'px')
        .text(d => (d.ticker || '').toString().toUpperCase());

    /* Variation */
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '1.2em')
        .style('fill', 'rgba(255,255,255,.85)')
        .style('font-family', "'DM Sans', sans-serif")
        .style('font-weight', '500')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(9, Math.min(d.radius / 3.5, 14)) + 'px')
        .text(d => `${d.change >= 0 ? '+' : ''}${d.change.toFixed(1)}%`);

    /* Tooltip */
    node.append('title')
        .text(d => `${(d.name || d.ticker || '')}\nVariation : ${d.change >= 0 ? '+' : ''}${d.change.toFixed(2)} %`);

    const sim = d3.forceSimulation(nodes)
        .force('center',    d3.forceCenter(width / 2, height / 2))
        .force('charge',    d3.forceManyBody().strength(8))
        .force('collision', d3.forceCollide().radius(d => d.radius + 5))
        .force('x',         d3.forceX(width  / 2).strength(0.025))
        .force('y',         d3.forceY(height / 2).strength(0.025))
        .alpha(1).alphaDecay(0.02)
        .on('tick', () => node.attr('transform', d => `translate(${d.x},${d.y})`));
}

async function loadLatestBubbles(){
    bocDateLabel.textContent = 'Variations du jour · Chargement en cours…';

    // Afficher loader
    bubblesDiv.querySelectorAll('svg').forEach(s => s.remove());
    if (!loader.parentNode) bubblesDiv.prepend(loader);
    loader.style.display = 'flex';

    try {
        const res = await fetch("{{ route('radar.bubblesLatest') }}");
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const payload = await res.json();
        const date    = payload?.date ?? null;
        const data    = Array.isArray(payload)
            ? payload
            : (payload?.bubbles ?? payload?.data ?? []);

        lastBubbleData = data;
        loader.style.display = 'none';

        bocDateLabel.textContent = date
            ? `Variations BRVM — BOC du ${date}`
            : 'Variations BRVM — Dernier BOC disponible';

        drawBubbles(data);

    } catch(e) {
        console.error('Bubbles error:', e);
        loader.style.display = 'none';
        bubblesDiv.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:center;height:100%;
                        flex-direction:column;gap:12px;color:#6B7590;">
                <span style="font-size:28px;">⚠️</span>
                <span style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">
                    Impossible de charger les données
                </span>
            </div>`;
        bocDateLabel.textContent = 'Erreur de chargement';
    }
}

/* Fullscreen */
fullscreenBtn?.addEventListener('click', () => {
    if (!document.fullscreenElement) {
        bubblesDiv.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
});

document.addEventListener('fullscreenchange', () => {
    const isFs = !!document.fullscreenElement;
    fullscreenBtn.innerHTML = isFs
        ? '<i class="bi bi-fullscreen-exit"></i> Quitter'
        : '<i class="bi bi-fullscreen"></i> Plein écran';
    if (lastBubbleData) {
        setTimeout(() => drawBubbles(lastBubbleData), 100);
    }
});

/* Reload + resize */
reloadBtn?.addEventListener('click', loadLatestBubbles);
window.addEventListener('resize', () => { if(lastBubbleData) drawBubbles(lastBubbleData); });
window.addEventListener('orientationchange', () => { if(lastBubbleData) drawBubbles(lastBubbleData); });

/* Init */
loadLatestBubbles();

/* Scroll reveal */
const cbEls = document.querySelectorAll('.cbr');
const cbObs = new IntersectionObserver(e => {
    e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
}, { threshold: 0.07 });
cbEls.forEach(el => cbObs.observe(el));
</script>
@endpush
