@extends('layouts.admin')

@section('title', 'Utilisateurs – Admin Coach BRVM')

@section('content')
<div class="container-fluid py-4 px-4" style="max-width:1400px;">

    {{-- En-tête --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">👥 Utilisateurs</h2>
            <p class="text-muted mb-0">Comptes inscrits — achats, téléphone, activité.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary fw-semibold">← Dashboard</a>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">👤 Total inscrits</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['total'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">🆕 7 derniers jours</div>
                    <div class="fs-3 fw-bold text-primary">{{ $stats['new7'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">📱 Tél. vérifiés</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['verified'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">💳 Ont acheté</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['paying'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('admin.users.index') }}">
                <div class="col-md-5">
                    <input type="text" name="q" value="{{ $q }}" class="form-control"
                        placeholder="🔍 Nom, email ou numéro de téléphone...">
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="created_at"        @selected($sort==='created_at')>Tri : Date inscription</option>
                        <option value="name"              @selected($sort==='name')>Tri : Nom</option>
                        <option value="email"             @selected($sort==='email')>Tri : Email</option>
                        <option value="marketplace_count" @selected($sort==='marketplace_count')>Tri : Nb achats</option>
                        <option value="marketplace_total" @selected($sort==='marketplace_total')>Tri : Total payé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="dir" class="form-select">
                        <option value="desc" @selected($dir==='desc')>↓ Décroissant</option>
                        <option value="asc"  @selected($dir==='asc')>↑ Croissant</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary fw-semibold">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($users->isEmpty())
                <div class="text-center text-muted py-5">Aucun utilisateur trouvé.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:200px;">Utilisateur</th>
                            <th style="min-width:200px;">Email</th>
                            <th style="min-width:150px;">Téléphone</th>
                            <th style="min-width:130px;">Inscrit le</th>
                            <th class="text-center" style="min-width:90px;">Achats<br><small class="text-muted fw-normal">Marketplace</small></th>
                            <th class="text-center" style="min-width:90px;">Cours</th>
                            <th class="text-end"    style="min-width:120px;">Total payé</th>
                            <th class="text-center" style="min-width:80px;">Grants</th>
                            <th style="min-width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            {{-- Nom --}}
                            <td>
                                <div class="fw-semibold">{{ $u->name ?? '—' }}</div>
                                @if($u->created_at && $u->created_at->isAfter(now()->subDays(7)))
                                    <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">Nouveau</span>
                                @endif
                            </td>

                            {{-- Email --}}
                            <td>
                                <a href="mailto:{{ $u->email }}" class="text-decoration-none font-monospace text-dark" style="font-size:13px;">
                                    {{ $u->email }}
                                </a>
                            </td>

                            {{-- Téléphone --}}
                            <td>
                                @if($u->phone)
                                    <span class="font-monospace" style="font-size:13px;">{{ $u->phone }}</span>
                                    @if($u->phone_verified_at)
                                        <span class="ms-1 text-success" title="Vérifié le {{ $u->phone_verified_at->format('d/m/Y') }}">✅</span>
                                    @else
                                        <span class="ms-1 text-warning" title="Non vérifié">⚠️</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Inscrit le --}}
                            <td style="font-size:13px;color:#6c757d;">
                                {{ $u->created_at?->format('d/m/Y') }}<br>
                                <small>{{ $u->created_at?->format('H:i') }}</small>
                            </td>

                            {{-- Achats marketplace --}}
                            <td class="text-center">
                                @if($u->marketplace_count > 0)
                                    <span class="badge bg-success-subtle text-success fw-bold" style="font-size:13px;">{{ $u->marketplace_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            {{-- Cours --}}
                            <td class="text-center">
                                @if($u->course_count > 0)
                                    <span class="badge bg-info-subtle text-info fw-bold" style="font-size:13px;">{{ $u->course_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            {{-- Total payé --}}
                            <td class="text-end fw-semibold">
                                @if($u->marketplace_total > 0)
                                    {{ number_format($u->marketplace_total, 0, ',', ' ') }} XOF
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Grants --}}
                            <td class="text-center">
                                @if($u->grants_count > 0)
                                    <span class="badge bg-warning-subtle text-warning fw-bold" style="font-size:13px;">{{ $u->grants_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td>
                                <a href="{{ route('admin.users.show', $u) }}"
                                   class="btn btn-sm btn-outline-primary fw-semibold">
                                    Voir →
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-top">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
