{{-- resources/views/performances/index.blade.php --}}
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
    .cb-btn-outline.active { border-color: #C9A84C; color: #C9A84C !important; background: rgba(201,168,76,.08); }

    .cb-btn-green {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(15,207,164,.1); color: #0FCFA4 !important;
        font-family: 'Syne', sans-serif; font-weight: 700;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(15,207,164,.2);
        border-radius: 3px; cursor: pointer; transition: all .3s;
    }
    .cb-btn-green:hover { background: rgba(15,207,164,.16); border-color: rgba(15,207,164,.4); }

    /* ── Style toggle ── */
    .style-toggle {
        display: inline-flex;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 4px;
        overflow: hidden;
    }
    .style-toggle-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px;
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: #6B7590; background: transparent; border: none;
        cursor: pointer; transition: all .2s;
    }
    .style-toggle-btn:hover { color: #E8EAF0; background: rgba(255,255,255,.04); }
    .style-toggle-btn.active {
        color: #C9A84C;
        background: rgba(201,168,76,.1);
        border-left: 1px solid rgba(201,168,76,.2);
        border-right: 1px solid rgba(201,168,76,.2);
    }
    .style-toggle-btn:first-child { border-right: 1px solid rgba(255,255,255,.06); }

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
        transition: background .3s;
    }

    /* Mode crypto : fond noir pur comme cryptobubbles */
    #brvm-bubbles.mode-crypto {
        background: #000;
    }

    #brvm-bubbles:fullscreen,
    #brvm-bubbles.mode-crypto:fullscreen {
        background: #000;
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

    /* ═══════════════════════════════
       STOCK HISTORY MODAL
    ═══════════════════════════════ */
    .stock-modal {
        position: fixed; inset: 0; z-index: 9999;
        align-items: center; justify-content: center; padding: 16px;
    }
    .stock-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.75); }
    .stock-modal-content {
        position: relative; z-index: 1;
        width: 100%; max-width: 900px; max-height: 90vh;
        background: #0C1120; border: 1px solid rgba(201,168,76,.2);
        border-radius: 12px; overflow-y: auto;
        display: flex; flex-direction: column;
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
        background: #121A2C; border-radius: 12px 12px 0 0;
        border-bottom: 1px solid rgba(255,255,255,.05);
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
        border: 3px solid rgba(201,168,76,.1); border-top-color: #C9A84C;
        animation: spin 1s linear infinite;
    }
    @media (max-width: 767px) {
        .stock-modal-content { max-width: 95vw; }
        .stock-modal-header  { flex-direction: column; gap: 12px; padding-right: 48px; }
        .stock-modal-stats   { gap: 16px; }
        .stock-modal-stat    { align-items: flex-start; }
        .stock-modal-chart   { height: 280px; }
    }
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
                <div class="d-flex align-items-center gap-2 flex-wrap">

                    {{-- Toggle style --}}
                    <div class="style-toggle" title="Changer le style des bulles">
                        <button class="style-toggle-btn active" id="btn-style-solid" title="Bulles solides (style BRVM)">
                            <i class="bi bi-circle-fill" style="font-size:11px;"></i> Solide
                        </button>
                        <button class="style-toggle-btn" id="btn-style-crypto" title="Bulles transparentes (style CryptoBubbles)">
                            <i class="bi bi-circle" style="font-size:11px;"></i> Crypto
                        </button>
                    </div>

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

{{-- ── STOCK HISTORY MODAL ── --}}
<div id="stockHistoryModal" class="stock-modal" style="display:none;">
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
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
const btnSolid      = document.getElementById('btn-style-solid');
const btnCrypto     = document.getElementById('btn-style-crypto');

let lastBubbleData = null;
let currentStyle   = 'solid'; // 'solid' | 'crypto'

/* ── Couleurs selon style ── */
function colorFn(d) {
    const c = Number(d.change ?? 0);
    if (currentStyle === 'crypto') {
        // Style CryptoBubbles : transparent avec bordure colorée
        if (c > 0.1)  return 'rgba(31,191,74,0)';
        if (c < -0.1) return 'rgba(229,57,53,0)';
        return 'rgba(80,80,80,0)';
    }
    // Style solide classique
    if (c > 0.1)  return '#1fbf4a';
    if (c < -0.1) return '#e53935';
    return '#444';
}

function strokeFn(d) {
    const c = Number(d.change ?? 0);
    if (c > 0.1)  return '#1fbf4a';
    if (c < -0.1) return '#e53935';
    return '#555';
}

function strokeWidthFn(d) {
    return currentStyle === 'crypto' ? 2 : 1.5;
}

function fillOpacityFn(d) {
    if (currentStyle === 'crypto') {
        const abs = Math.abs(Number(d.change ?? 0));
        // Plus la variation est forte, plus la bulle est légèrement colorée
        return Math.min(0.18, abs * 0.03);
    }
    return 0.88;
}

function textColorFn(d) {
    const c = Number(d.change ?? 0);
    if (currentStyle === 'crypto') {
        if (c > 0.1)  return '#1fbf4a';
        if (c < -0.1) return '#e53935';
        return '#aaa';
    }
    return '#fff';
}

function subTextColorFn(d) {
    const c = Number(d.change ?? 0);
    if (currentStyle === 'crypto') {
        if (c > 0.1)  return 'rgba(31,191,74,.9)';
        if (c < -0.1) return 'rgba(229,57,53,.9)';
        return 'rgba(170,170,170,.8)';
    }
    return 'rgba(255,255,255,.85)';
}

/* ── Draw ── */
function drawBubbles(data) {
    bubblesDiv.querySelectorAll('svg').forEach(s => s.remove());

    // Fond selon style
    bubblesDiv.classList.toggle('mode-crypto', currentStyle === 'crypto');

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

    // Fond dégradé subtil en mode crypto
    if (currentStyle === 'crypto') {
        const defs = svg.append('defs');
        const grad = defs.append('radialGradient')
            .attr('id', 'bgGrad')
            .attr('cx', '50%').attr('cy', '50%').attr('r', '60%');
        grad.append('stop').attr('offset', '0%').attr('stop-color', '#0a0a14');
        grad.append('stop').attr('offset', '100%').attr('stop-color', '#000');
        svg.append('rect').attr('width', width).attr('height', height).attr('fill', 'url(#bgGrad)');
    }

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
        .style('cursor', 'pointer')
        .call(
            d3.drag()
                .on('start', (e,d) => { d._dragMoved=false; d._dragX0=e.x; d._dragY0=e.y; if(!e.active) sim.alphaTarget(.3).restart(); d.fx=d.x; d.fy=d.y; })
                .on('drag',  (e,d) => { if(Math.hypot(e.x-d._dragX0, e.y-d._dragY0)>5) d._dragMoved=true; d.fx=e.x; d.fy=e.y; })
                .on('end',   (e,d) => { if(!e.active) sim.alphaTarget(0); d.fx=null; d.fy=null; })
        )
        .on('click', (_e, d) => { if (!d._dragMoved) openStockModal(d.ticker); });

    /* Halo externe en mode crypto */
    if (currentStyle === 'crypto') {
        node.append('circle')
            .attr('r', d => d.radius + 4)
            .attr('fill', 'none')
            .attr('stroke', d => strokeFn(d))
            .attr('stroke-width', 0.5)
            .attr('stroke-opacity', 0.2);
    }

    /* Cercle principal */
    node.append('circle')
        .attr('r', d => d.radius)
        .attr('fill', d => colorFn(d))
        .attr('stroke', d => strokeFn(d))
        .attr('stroke-width', d => strokeWidthFn(d))
        .attr('fill-opacity', d => fillOpacityFn(d));

    /* Ticker */
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '-0.25em')
        .style('fill', d => textColorFn(d))
        .style('font-family', "'Syne', sans-serif")
        .style('font-weight', '700')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(11, Math.min(d.radius / 2.8, 20)) + 'px')
        .text(d => (d.ticker || '').toString().toUpperCase());

    /* Variation */
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '1.2em')
        .style('fill', d => subTextColorFn(d))
        .style('font-family', "'DM Sans', sans-serif")
        .style('font-weight', currentStyle === 'crypto' ? '600' : '500')
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

/* ── Toggle style ── */
btnSolid.addEventListener('click', () => {
    currentStyle = 'solid';
    btnSolid.classList.add('active');
    btnCrypto.classList.remove('active');
    if (lastBubbleData) drawBubbles(lastBubbleData);
});

btnCrypto.addEventListener('click', () => {
    currentStyle = 'crypto';
    btnCrypto.classList.add('active');
    btnSolid.classList.remove('active');
    if (lastBubbleData) drawBubbles(lastBubbleData);
});

/* ── Load data ── */
async function loadLatestBubbles(){
    bocDateLabel.textContent = 'Variations du jour · Chargement en cours…';

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

reloadBtn?.addEventListener('click', loadLatestBubbles);
window.addEventListener('resize', () => { if(lastBubbleData) drawBubbles(lastBubbleData); });
window.addEventListener('orientationchange', () => { if(lastBubbleData) drawBubbles(lastBubbleData); });

loadLatestBubbles();

/* Scroll reveal */

const cbEls = document.querySelectorAll('.cbr');
const cbObs = new IntersectionObserver(e => {
    e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
}, { threshold: 0.07 });
cbEls.forEach(el => cbObs.observe(el));
</script>

<script>
/* ═══════════════════════════════
   STOCK HISTORY MODAL
═══════════════════════════════ */
let smChartInstance   = null;
let currentModalTicker = null;

const stockModal     = document.getElementById('stockHistoryModal');
const smOverlay      = stockModal.querySelector('.stock-modal-overlay');
const smCloseBtn     = stockModal.querySelector('.stock-modal-close');
const smTickerEl     = document.getElementById('smTicker');
const smNameEl       = document.getElementById('smName');
const smLastPriceEl  = document.getElementById('smLastPrice');
const smLastChangeEl = document.getElementById('smLastChange');
const smDateRangeEl  = document.getElementById('smDateRange');
const smLoadingEl    = document.getElementById('smLoading');
const smEmptyEl      = document.getElementById('smEmptyState');
const smCanvas       = document.getElementById('smChart');
const smRangeBtns    = document.querySelectorAll('.range-btn');

function smFormatPrice(v) {
    /* séparateur d'espace insécable "7 975" */
    return Math.round(v).toLocaleString('fr-FR');
}

function smFormatDate(iso) {
    const [y, m, d] = iso.split('-');
    const months = ['jan.','fév.','mars','avr.','mai','juin',
                    'juil.','août','sept.','oct.','nov.','déc.'];
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

        /* ── header stats ── */
        smNameEl.textContent = payload.name || ticker;

        const last = payload.data[payload.data.length - 1];
        smLastPriceEl.textContent = last?.price != null
            ? smFormatPrice(last.price) + ' FCFA' : '—';

        const c = last?.change ?? null;
        if (c !== null) {
            smLastChangeEl.textContent = (c >= 0 ? '+' : '')
                + c.toFixed(2).replace('.', ',') + ' %';
            smLastChangeEl.style.color = c >= 0 ? '#1fbf4a' : '#e53935';
        }

        smDateRangeEl.textContent =
            `${smFormatDate(payload.first_date)} → ${smFormatDate(payload.last_date)}`;

        /* ── chart ── */
        smLoadingEl.style.display = 'none';
        smCanvas.style.display    = 'block';

        if (smChartInstance) { smChartInstance.destroy(); smChartInstance = null; }

        const pts = payload.data.map(p => ({ x: p.date, y: p.price, ch: p.change }));

        smChartInstance = new Chart(smCanvas.getContext('2d'), {
            type: 'line',
            data: {
                datasets: [{
                    data:                      pts,
                    borderColor:               '#C9A84C',
                    backgroundColor:           'rgba(201,168,76,.1)',
                    fill:                      true,
                    tension:                   0.2,
                    pointRadius:               0,
                    pointHoverRadius:          4,
                    pointHoverBackgroundColor: '#C9A84C',
                    borderWidth:               2,
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                interaction:         { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0C1120',
                        borderColor:     'rgba(201,168,76,.3)',
                        borderWidth:     1,
                        titleColor:      '#E8EAF0',
                        bodyColor:       '#6B7590',
                        titleFont:       { family: 'Syne', weight: '700', size: 13 },
                        bodyFont:        { family: 'DM Sans', size: 12 },
                        padding:         10,
                        callbacks: {
                            title: items => {
                                const d = new Date(items[0].parsed.x);
                                return d.toLocaleDateString('fr-FR', {
                                    day: 'numeric', month: 'long', year: 'numeric'
                                });
                            },
                            label:      item => ` Cours : ${smFormatPrice(item.parsed.y)} FCFA`,
                            afterLabel: item => {
                                const ch = pts[item.dataIndex]?.ch;
                                if (ch == null) return '';
                                return ` Variation : ${ch >= 0 ? '+' : ''}${ch.toFixed(2).replace('.', ',')} %`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: smTimeUnit(range),
                            displayFormats: { day: 'd MMM', month: 'MMM yy' }
                        },
                        grid:  { color: 'rgba(255,255,255,.04)' },
                        ticks: {
                            color:         '#6B7590',
                            font:          { family: 'Syne', size: 10 },
                            maxTicksLimit: 8,
                        }
                    },
                    y: {
                        title: {
                            display: true, text: 'Cours (FCFA)',
                            color: '#6B7590', font: { family: 'Syne', size: 11 }
                        },
                        grid:  { color: 'rgba(255,255,255,.04)' },
                        ticks: {
                            color:    '#6B7590',
                            font:     { family: 'Syne', size: 10 },
                            callback: v => smFormatPrice(v),
                        }
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

/* ── Range buttons ── */
smRangeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.disabled || !currentModalTicker) return;
        smLoadRange(currentModalTicker, btn.dataset.range);
    });
});

/* ── Close handlers ── */
smCloseBtn.addEventListener('click', closeStockModal);
smOverlay.addEventListener('click',  closeStockModal);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeStockModal(); });
</script>
@endpush
