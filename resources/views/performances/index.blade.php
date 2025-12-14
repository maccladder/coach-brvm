@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Performances (7 derniers jours)</h2>
        <a href="{{ route('landing') }}" class="btn btn-outline-secondary">← Retour BOC</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Choisir une ou plusieurs sociétés</label>
                    <select id="tickers" class="form-select" multiple>
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
            <canvas id="perfChart" height="110"></canvas>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;

async function loadData() {
    const select = document.getElementById('tickers');
    const tickers = Array.from(select.selectedOptions).map(o => o.value);

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
}

document.getElementById('btnLoad').addEventListener('click', loadData);
loadData();
</script>
@endpush
