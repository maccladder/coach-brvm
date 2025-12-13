@extends('layouts.admin')

@section('title', 'Analytics – Coach BRVM')

@section('content')
<div class="container py-4" style="max-width:1200px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="fw-bold mb-1">📊 Analytics</h2>
            <div class="text-muted">Résumé des visiteurs (Google Analytics).</div>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            ← Retour dashboard
        </a>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">👀 Visiteurs aujourd’hui</div>
                    <div class="fs-3 fw-bold">{{ number_format($todayUsers) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">👥 Temps réel</div>
                    <div class="fs-3 fw-bold">{{ number_format($realtimeUsers) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">⏱️ Durée moyenne</div>
                    <div class="fs-3 fw-bold">
                        {{ gmdate('i:s', (int) $avgEngagementSecs) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">🌍 Pays (Top)</div>
                    <div class="fs-6 fw-semibold">
                        {{ $topCountries[0]['country'] ?? '—' }}
                    </div>
                    <div class="text-muted small">
                        {{ $topCountries[0]['users'] ?? 0 }} utilisateurs
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Top pays --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">🌍 Pays principaux (aujourd’hui)</h5>
                    @if(empty($topCountries))
                        <div class="text-muted">Aucune donnée.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Pays</th>
                                        <th class="text-end">Utilisateurs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topCountries as $c)
                                        <tr>
                                            <td>{{ $c['country'] }}</td>
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

        {{-- Top pages --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">📄 Pages les plus visitées (aujourd’hui)</h5>
                    @if(empty($topPages))
                        <div class="text-muted">Aucune donnée.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th class="text-end">Vues</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topPages as $p)
                                        <tr>
                                            <td><code>{{ $p['path'] }}</code></td>
                                            <td class="text-end fw-semibold">{{ number_format($p['views']) }}</td>
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

</div>
@endsection
