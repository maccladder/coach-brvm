@extends('layouts.admin')

@section('title', 'Analytics – Boursiv')

@push('styles')
<style>
    .analytics-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .analytics-card .card-body { padding: 1.1rem 1.25rem; }
    .analytics-kpi { font-size: 2rem; font-weight: 700; line-height: 1.15; }
    .analytics-kpi-sm { font-size: 1.5rem; font-weight: 700; line-height: 1.15; }
    .analytics-label { font-size: .74rem; color: #6c757d; margin-bottom: .2rem; }
    .analytics-sub { font-size: .74rem; color: #6c757d; margin-top: .15rem; }
    #refresh-badge { font-size: .74rem; }
</style>
@endpush

@section('content')
@php
$channelFr = [
    'Direct'              => 'Direct',
    'Organic Search'      => 'Recherche organique',
    'Organic Social'      => 'Réseaux sociaux',
    'Referral'            => 'Référencement',
    'Paid Search'         => 'Recherche payante',
    'Paid Social'         => 'Social payant',
    'Paid Other'          => 'Publicité autre',
    'Display'             => 'Display',
    'Email'               => 'E-mail',
    'Affiliates'          => 'Affiliés',
    'Unassigned'          => 'Non classifié',
    'Cross-network'       => 'Cross-réseau',
    'Audio'               => 'Audio',
    'SMS'                 => 'SMS',
    'Push Notifications'  => 'Notifications push',
];
$deviceFr = [
    'desktop' => 'Ordinateur',
    'mobile'  => 'Mobile',
    'tablet'  => 'Tablette',
];
@endphp
<div class="container py-4" style="max-width:1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📊 Analytics</h2>
            <div class="text-muted">Données Google Analytics 4 — Property {{ env('GA_PROPERTY_ID') }}</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span id="refresh-badge" class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">
                <span class="spinner-grow spinner-grow-sm me-1" role="status" style="display:none;" id="refresh-spinner"></span>
                Actif
            </span>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                ← Dashboard
            </a>
        </div>
    </div>

    {{-- ── Ligne 1 : KPI temps réel + aujourd'hui ── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Temps réel</div>
                    <div class="analytics-kpi text-danger" id="kpi-realtime">{{ number_format($realtimeUsers) }}</div>
                    <div class="analytics-sub">utilisateurs actifs</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Aujourd'hui</div>
                    <div class="analytics-kpi" id="kpi-today">{{ number_format($todayUsers) }}</div>
                    <div class="analytics-sub">visiteurs uniques</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">30 derniers jours</div>
                    <div class="analytics-kpi">{{ number_format($users30) }}</div>
                    <div class="analytics-sub">{{ number_format($sessions30) }} sessions</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Nouveaux visiteurs (30j)</div>
                    <div class="analytics-kpi text-success">{{ number_format($newUsers30) }}</div>
                    @if($users30 > 0)
                        <div class="analytics-sub">{{ round($newUsers30 / $users30 * 100) }}% du total</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ligne 2 : Durée moy + Bounce rate + Top pays + Top page ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Durée moy. engagement (7j)</div>
                    <div class="analytics-kpi-sm">{{ gmdate('i:s', (int) $avgEngagementSecs) }}</div>
                    <div class="analytics-sub">mm:ss par utilisateur</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Taux de rebond (30j)</div>
                    <div class="analytics-kpi-sm {{ $bounceRate > 70 ? 'text-danger' : ($bounceRate > 50 ? 'text-warning' : 'text-success') }}">
                        {{ $bounceRate }}%
                    </div>
                    <div class="analytics-sub">sessions sans engagement</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Top pays (7j)</div>
                    <div class="fw-bold fs-6">{{ $topCountries[0]['country'] ?? '—' }}</div>
                    <div class="analytics-sub">{{ number_format($topCountries[0]['users'] ?? 0) }} utilisateurs</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <div class="analytics-label">Top page (7j)</div>
                    <div class="fw-bold text-truncate" style="font-size:.85rem;">{{ $topPages[0]['path'] ?? '—' }}</div>
                    <div class="analytics-sub">{{ number_format($topPages[0]['views'] ?? 0) }} vues</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Graphique tendance 7 jours ── --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Visiteurs — 7 derniers jours</h6>
                    @if(empty($timeline))
                        <div class="text-muted py-4 text-center">Aucune donnée disponible.</div>
                    @else
                        <canvas id="chart-timeline" height="110"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="analytics-card h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="fw-semibold mb-3">Appareils (7j)</h6>
                    @if(empty($deviceCategories))
                        <div class="text-muted py-4 text-center">Aucune donnée.</div>
                    @else
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                            <canvas id="chart-devices" style="max-height:180px;max-width:180px;"></canvas>
                        </div>
                        <div class="mt-3">
                            @php $totalDeviceUsers = array_sum(array_column($deviceCategories, 'users')); @endphp
                            @foreach($deviceCategories as $d)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:.82rem;">{{ $deviceFr[strtolower($d['device'])] ?? ucfirst($d['device']) }}</span>
                                    <span class="fw-semibold" style="font-size:.82rem;">
                                        {{ number_format($d['users']) }}
                                        @if($totalDeviceUsers > 0)
                                            <span class="text-muted fw-normal">({{ round($d['users'] / $totalDeviceUsers * 100) }}%)</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sources de trafic + Top pays ── --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Sources de trafic (7j)</h6>
                    @if(empty($trafficSources))
                        <div class="text-muted">Aucune donnée.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Canal</th>
                                        <th class="text-end">Sessions</th>
                                        <th class="text-end">Utilisateurs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalSessions = array_sum(array_column($trafficSources, 'sessions')); @endphp
                                    @foreach($trafficSources as $src)
                                        @php $pct = $totalSessions > 0 ? round($src['sessions'] / $totalSessions * 100) : 0; @endphp
                                        <tr>
                                            <td>
                                                <div>{{ $channelFr[$src['channel']] ?? $src['channel'] }}</div>
                                                <div class="progress mt-1" style="height:3px;">
                                                    <div class="progress-bar" style="width:{{ $pct }}%;"></div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold">{{ number_format($src['sessions']) }}</td>
                                            <td class="text-end text-muted">{{ number_format($src['users']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="analytics-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Pays principaux (7j)</h6>
                    @if(empty($topCountries))
                        <div class="text-muted">Aucune donnée.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pays</th>
                                        <th class="text-end">Utilisateurs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalCountryUsers = array_sum(array_column($topCountries, 'users')); @endphp
                                    @foreach($topCountries as $c)
                                        @php $pct = $totalCountryUsers > 0 ? round($c['users'] / $totalCountryUsers * 100) : 0; @endphp
                                        <tr>
                                            <td>
                                                <div>{{ $c['country'] }}</div>
                                                <div class="progress mt-1" style="height:3px;">
                                                    <div class="progress-bar bg-success" style="width:{{ $pct }}%;"></div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold">{{ number_format($c['users']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top pages ── --}}
    <div class="analytics-card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3">Pages les plus visitées (7j)</h6>
            @if(empty($topPages))
                <div class="text-muted">Aucune donnée.</div>
            @else
                @php $maxViews = max(array_column($topPages, 'views') ?: [1]); @endphp
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:55%">Page</th>
                                <th>Popularité</th>
                                <th class="text-end">Vues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topPages as $p)
                                @php $pct = $maxViews > 0 ? round($p['views'] / $maxViews * 100) : 0; @endphp
                                <tr>
                                    <td><code class="text-break">{{ $p['path'] }}</code></td>
                                    <td style="min-width:120px;">
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar bg-primary" style="width:{{ $pct }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($p['views']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="text-muted text-end" style="font-size:.72rem;">
        Dernière mise à jour : <span id="last-updated">{{ now()->format('H:i:s') }}</span>
        &nbsp;·&nbsp; Rafraîchissement automatique toutes les 60 s
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    // ── Graphique tendance 7j ──────────────────────────────
    const timelineData = @json($timeline);
    if (timelineData.length) {
        const ctx = document.getElementById('chart-timeline');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timelineData.map(d => d.date),
                    datasets: [{
                        label: 'Visiteurs actifs',
                        data: timelineData.map(d => d.users),
                        fill: true,
                        backgroundColor: 'rgba(13,110,253,.08)',
                        borderColor: 'rgba(13,110,253,.8)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,.05)' },
                        },
                        x: { grid: { display: false } },
                    },
                }
            });
        }
    }

    // ── Donut appareils ───────────────────────────────────
    const deviceFrJs = { desktop: 'Ordinateur', mobile: 'Mobile', tablet: 'Tablette' };
    const devicesData = @json($deviceCategories);
    if (devicesData.length) {
        const ctx2 = document.getElementById('chart-devices');
        if (ctx2) {
            const colors = ['#0d6efd', '#20c997', '#fd7e14', '#6f42c1', '#dc3545'];
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: devicesData.map(d => deviceFrJs[d.device.toLowerCase()] ?? d.device),
                    datasets: [{
                        data: devicesData.map(d => d.users),
                        backgroundColor: colors.slice(0, devicesData.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    cutout: '65%',
                }
            });
        }
    }

    // ── Auto-refresh toutes les 60s ───────────────────────
    const spinner = document.getElementById('refresh-spinner');
    const badge   = document.getElementById('refresh-badge');
    const tsEl    = document.getElementById('last-updated');

    setInterval(async () => {
        if (spinner) spinner.style.display = 'inline-block';
        try {
            const res = await fetch('{{ route('admin.analytics.data') }}');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const d = await res.json();

            const rtEl = document.getElementById('kpi-realtime');
            const tdEl = document.getElementById('kpi-today');
            if (rtEl) rtEl.textContent = d.realtimeUsers.toLocaleString('fr-FR');
            if (tdEl) tdEl.textContent = d.todayUsers.toLocaleString('fr-FR');

            if (tsEl) tsEl.textContent = new Date().toLocaleTimeString('fr-FR');
            if (badge) { badge.classList.remove('bg-danger-subtle','text-danger'); badge.classList.add('bg-success-subtle','text-success'); }
        } catch (e) {
            if (badge) { badge.classList.remove('bg-success-subtle','text-success'); badge.classList.add('bg-danger-subtle','text-danger'); }
        } finally {
            if (spinner) spinner.style.display = 'none';
        }
    }, 60000);
})();
</script>
@endpush
