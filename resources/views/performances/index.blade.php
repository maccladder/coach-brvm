@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Performances (7 derniers jours)</h2>
        <a href="{{ route('landing') }}" class="btn btn-outline-secondary">← Retour à l'accueil</a>
    </div>

    {{-- Sélecteur sociétés --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Choisir une ou plusieurs sociétés</label>
                    <select id="tickers" class="form-select" multiple size="6">
                        @foreach($companies as $c)
                            <option value="{{ $c->ticker }}">
                                {{ $c->ticker }} — {{ $c->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Si tu ne sélectionnes rien : Top 5 du dernier jour automatiquement.</div>
                </div>
                <div class="col-md-3 d-grid">
                    <button id="btnLoad" class="btn btn-primary">Afficher le graphe</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphe perf 7 jours --}}
    <div class="card">
        <div class="card-body">
            <div class="chart-wrap">
                <canvas id="perfChart"></canvas>
            </div>

            <div class="small text-muted mt-2">
                Astuce : tu peux pincer/zoomer si besoin. (Mais normalement plus besoin de tourner le téléphone)
            </div>
        </div>
    </div>

    {{-- 🌐 BUBBLES comme sur ton BOC (dernier BOC auto) --}}
    <div class="mt-4" id="market-bubbles-wrapper">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">🌐 Vue d’ensemble du marché</h6>
                    <small class="text-muted" id="marketBocDate">
                        Variations du jour des actions BRVM (d’après le dernier BOC) — Chargement…
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <button id="btn-bubbles-reload" class="btn btn-sm btn-outline-secondary">
                        ↻ Rafraîchir
                    </button>
                    <button id="btn-bubbles-fullscreen" class="btn btn-sm btn-outline-secondary">
                        ⛶ Plein écran
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div id="brvm-bubbles"
                     style="width:100%;height:80vh;min-height:650px;background:#111;border-radius:12px;overflow:hidden;">
                </div>

                <div class="small text-muted mt-2">
                    Vert = hausse · Rouge = baisse · Gris = quasi stable. (Tu peux déplacer les bulles)
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .chart-wrap{
        position: relative;
        width: 100%;
        min-height: 320px;
    }
    @media (min-width: 992px){
        .chart-wrap{ min-height: 420px; }
    }
    #tickers{ max-height: 220px; }

    /* Plein écran: garder fond sombre */
    #brvm-bubbles:fullscreen{
        background:#111;
        padding: 12px;
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
/* =======================
   LINE CHART (7 jours)
======================= */
let chart;

function resizeChartSoon(){
    setTimeout(() => { if(chart) chart.resize(); }, 50);
    setTimeout(() => { if(chart) chart.resize(); }, 250);
}

async function loadData() {
    const select = document.getElementById('tickers');
    const tickers = Array.from(select.selectedOptions).map(o => o.value);

    const url = new URL("{{ route('radar.data') }}", window.location.origin);
    tickers.forEach(t => url.searchParams.append('tickers[]', t));

    const res = await fetch(url);
    const data = await res.json();

    const ctx = document.getElementById('perfChart').getContext('2d');
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: (data.datasets || []).map(ds => ({
                label: ds.label,
                data: ds.data,
                tension: 0.25,
                spanGaps: true
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y ?? 'NC'} %`
                    }
                }
            },
            scales: {
                y: { title: { display: true, text: 'Variation (%)' } }
            }
        }
    });

    resizeChartSoon();
}

document.getElementById('btnLoad').addEventListener('click', loadData);
window.addEventListener('resize', resizeChartSoon);
window.addEventListener('orientationchange', resizeChartSoon);
document.addEventListener('visibilitychange', () => { if(!document.hidden) resizeChartSoon(); });

loadData();


/* =======================
   D3 BUBBLES (dernier BOC)
   Attend un JSON du type :
   { date: "2025-12-12", bubbles: [ {ticker,name,change,price}, ... ] }
   OU directement un array [{ticker,name,change}, ...]
======================= */
const bubblesDiv    = document.getElementById('brvm-bubbles');
const fullscreenBtn = document.getElementById('btn-bubbles-fullscreen');
const reloadBtn     = document.getElementById('btn-bubbles-reload');
const bocDateLabel  = document.getElementById('marketBocDate');

let lastBubbleData = null;

function colorFn(d){
    const c = Number(d.change ?? 0);
    if (c > 0.1)  return '#1fbf4a';
    if (c < -0.1) return '#e53935';
    return '#555555';
}

function drawBubbles(data){
    bubblesDiv.innerHTML = '';

    if (!Array.isArray(data) || data.length === 0) {
        bubblesDiv.innerHTML = '<p class="text-white p-3">Aucune donnée de marché disponible.</p>';
        return;
    }

    const width  = bubblesDiv.clientWidth;
    const height = bubblesDiv.clientHeight || 650;

    const svg = d3.select('#brvm-bubbles')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    const maxAbs = d3.max(data, d => Math.abs(Number(d.change ?? 0))) || 1;

    const radiusScale = d3.scaleSqrt()
        .domain([0, maxAbs])
        .range(data.length >= 40 ? [20, 90] : [30, 120]);

    const nodes = data.map(d => ({
        ...d,
        change: Number(d.change ?? 0),
        radius: radiusScale(Math.abs(Number(d.change ?? 0))),
        x: width  / 2 + (Math.random() - 0.5) * 50,
        y: height / 2 + (Math.random() - 0.5) * 50
    }));

    const node = svg.append('g')
        .selectAll('g.node')
        .data(nodes)
        .enter()
        .append('g')
        .attr('class', 'node')
        .style('cursor', 'grab')
        .call(
            d3.drag()
                .on('start', dragstarted)
                .on('drag', dragged)
                .on('end', dragended)
        );

    node.append('circle')
        .attr('r', d => d.radius)
        .attr('fill', d => colorFn(d))
        .attr('stroke', '#ffffff')
        .attr('stroke-width', 2)
        .attr('fill-opacity', 0.85);

    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '-0.2em')
        .style('fill', '#ffffff')
        .style('font-weight', 'bold')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(12, d.radius / 3) + 'px')
        .text(d => (d.ticker || d.label || '').toString().toUpperCase());

    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '1.2em')
        .style('fill', '#ffffff')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(10, d.radius / 4) + 'px')
        .text(d => `${d.change > 0 ? '+' : ''}${d.change.toFixed(1)} %`);

    node.append('title')
        .text(d => `${(d.name || d.ticker || '').toString()}\nVariation jour : ${d.change.toFixed(2)} %`);

    const simulation = d3.forceSimulation(nodes)
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('charge', d3.forceManyBody().strength(10))
        .force('collision', d3.forceCollide().radius(d => d.radius + 4))
        .force('x', d3.forceX(width / 2).strength(0.02))
        .force('y', d3.forceY(height / 2).strength(0.02))
        .alpha(1)
        .alphaDecay(0.02)
        .on('tick', () => node.attr('transform', d => `translate(${d.x},${d.y})`));

    function dragstarted(event, d) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
    }

    function dragged(event, d) {
        d.fx = event.x;
        d.fy = event.y;
    }

    function dragended(event, d) {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
    }
}

async function loadLatestBubbles(){
    try {
        bocDateLabel.textContent = 'Variations du jour des actions BRVM (d’après le dernier BOC) — Chargement…';

        const res = await fetch("{{ route('radar.bubblesLatest') }}");
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const payload = await res.json();

        const date = payload?.date ?? null;
        const data = Array.isArray(payload) ? payload : (payload?.bubbles ?? payload?.data ?? []);

        lastBubbleData = data;

        bocDateLabel.textContent = date
            ? `Variations du jour des actions BRVM (d’après le dernier BOC : ${date})`
            : `Variations du jour des actions BRVM (d’après le dernier BOC)`;

        drawBubbles(data);

    } catch (e) {
        console.error('Bubbles latest error:', e);
        bubblesDiv.innerHTML = '<p class="text-white p-3">Impossible de charger les bulles du marché.</p>';
        bocDateLabel.textContent = 'Variations du jour des actions BRVM — Erreur de chargement';
    }
}

// FULLSCREEN
if (fullscreenBtn) {
    fullscreenBtn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            bubblesDiv.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        fullscreenBtn.textContent = isFs ? '❌ Quitter le plein écran' : '⛶ Plein écran';
        if (lastBubbleData) drawBubbles(lastBubbleData);
    });
}

// reload + resize
reloadBtn?.addEventListener('click', loadLatestBubbles);

window.addEventListener('resize', () => {
    if (lastBubbleData) drawBubbles(lastBubbleData);
});
window.addEventListener('orientationchange', () => {
    if (lastBubbleData) drawBubbles(lastBubbleData);
});

// init bubbles
loadLatestBubbles();
</script>
@endpush
