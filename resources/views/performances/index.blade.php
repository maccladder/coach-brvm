@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Performances (7 derniers jours)</h2>
        <a href="{{ route('landing') }}" class="btn btn-outline-secondary">← Retour à l'accueil</a>
    </div>

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

    <div class="card">
        <div class="card-body">
            {{-- ✅ Conteneur qui donne une vraie hauteur sur mobile --}}
            <div class="chart-wrap">
                <canvas id="perfChart"></canvas>
            </div>

            <div class="small text-muted mt-2">
                Astuce : tu peux pincer/zoomer si besoin. (Mais normalement plus besoin de tourner le téléphone)
            </div>
        </div>
    </div>

</div>

<style>
    .chart-wrap{
        position: relative;
        width: 100%;
        /* hauteur confortable mobile */
        min-height: 320px;
    }
    @media (min-width: 992px){
        .chart-wrap{ min-height: 420px; }
    }

    /* Sur mobile, le multi-select peut devenir trop haut */
    #tickers{ max-height: 220px; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;

function resizeChartSoon(){
    // double tick pour laisser le layout finir (menu, fonts, etc.)
    setTimeout(() => { if(chart) chart.resize(); }, 50);
    setTimeout(() => { if(chart) chart.resize(); }, 250);
}

async function loadData() {
    const select = document.getElementById('tickers');
    const tickers = Array.from(select.selectedOptions).map(o => o.value);

    // ⚠️ IMPORTANT : si tu crées un PerformanceController public,
    // remplace cette route par route('performances.data')
    const url = new URL("{{ route('admin.performances.data') }}", window.location.origin);
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
            // ✅ clé du fix mobile (canvas suit la hauteur du conteneur)
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
</script>
@endpush
