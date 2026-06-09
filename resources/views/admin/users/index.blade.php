@extends('layouts.admin')

@section('title', 'Utilisateurs – Admin Boursiv')

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

    {{-- KPIs cliquables --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>''])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='' ? 'border-primary border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">👤 Total</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($stats['total'], 0, ',', ' ') }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'paying'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='paying' ? 'border-success border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">💳 Ont acheté</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['paying'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'never_bought'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='never_bought' ? 'border-secondary border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">🚶 Jamais acheté</div>
                    <div class="fs-3 fw-bold text-secondary">{{ $stats['never_bought'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'has_phone'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='has_phone' ? 'border-primary border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">📱 Tél. enregistré</div>
                    <div class="fs-3 fw-bold text-primary">{{ $stats['has_phone'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'phone_verified'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='phone_verified' ? 'border-success border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">✅ Tél. vérifié</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['phone_verified'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'reward_claimed'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='reward_claimed' ? 'border-warning border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">🎁 Cadeau activé</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['reward_claimed'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'unpaid_attempt'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='unpaid_attempt' ? 'border-danger border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">⚠️ Achat non abouti</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['unpaid_attempt'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['filter'=>'new30'])) }}"
               class="card border-0 shadow-sm h-100 text-decoration-none {{ $filter==='new30' ? 'border-info border-2' : '' }}">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">🆕 30 derniers jours</div>
                    <div class="fs-3 fw-bold text-info">{{ $stats['new7'] }}</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Filtre actif --}}
    @if($filter !== '')
    @php
        $filterLabels = [
            'paying'         => '💳 Ont acheté au moins une fois',
            'has_phone'      => '📱 Téléphone enregistré',
            'phone_verified' => '✅ Téléphone vérifié',
            'reward_claimed' => '🎁 Cadeau Abidjan Run activé',
            'unpaid_attempt' => '⚠️ Au moins un achat non abouti',
            'never_bought'   => '🚶 Jamais acheté (ni marketplace, ni cours, ni grant)',
            'new30'          => '🆕 Inscrits dans les 30 derniers jours',
        ];
    @endphp
    <div class="alert alert-info d-flex align-items-center gap-3 py-2 mb-3">
        <span>🔍 Filtre actif : <strong>{{ $filterLabels[$filter] ?? $filter }}</strong></span>
        <a href="{{ route('admin.users.index', array_merge(request()->except('filter'), ['q' => $q, 'sort' => $sort, 'dir' => $dir])) }}"
           class="btn btn-sm btn-outline-secondary ms-auto">✕ Retirer</a>
    </div>
    @endif

    {{-- Barre de recherche + tri --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('admin.users.index') }}">
                @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
                <div class="col-md-5">
                    <input type="text" name="q" value="{{ $q }}" class="form-control"
                        placeholder="🔍 Nom, email ou numéro de téléphone...">
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="created_at"        @selected($sort==='created_at')>Tri : Date inscription</option>
                        <option value="name"              @selected($sort==='name')>Tri : Nom</option>
                        <option value="email"             @selected($sort==='email')>Tri : Email</option>
                        <option value="marketplace_count" @selected($sort==='marketplace_count')>Tri : Achats marketplace</option>
                        <option value="course_count"      @selected($sort==='course_count')>Tri : Cours achetés</option>
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
                <div class="text-center text-muted py-5">Aucun utilisateur trouvé pour ce filtre.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:190px;">Utilisateur</th>
                            <th style="min-width:200px;">Email</th>
                            <th style="min-width:160px;">Téléphone</th>
                            <th style="min-width:120px;">Inscrit le</th>
                            <th class="text-center" style="min-width:80px;">Achats<br><small class="fw-normal text-muted">Marketplace</small></th>
                            <th class="text-center" style="min-width:70px;">Cours</th>
                            <th class="text-end"    style="min-width:130px;">Total payé</th>
                            <th class="text-center" style="min-width:70px;">Grants</th>
                            <th style="min-width:70px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $u->name ?? '—' }}</div>
                                <div class="d-flex gap-1 flex-wrap mt-1">
                                    @if($u->created_at?->isAfter(now()->subDays(7)))
                                        <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">Nouveau</span>
                                    @endif
                                    @if($u->phone_reward_claimed)
                                        <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">🎁 Cadeau</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="mailto:{{ $u->email }}" class="text-decoration-none text-dark font-monospace" style="font-size:13px;">
                                    {{ $u->email }}
                                </a>
                            </td>
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
                            <td style="font-size:13px;color:#6c757d;">
                                {{ $u->created_at?->format('d/m/Y') }}<br>
                                <small>{{ $u->created_at?->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                @if($u->marketplace_count > 0)
                                    <span class="badge bg-success-subtle text-success fw-bold" style="font-size:13px;">{{ $u->marketplace_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($u->course_count > 0)
                                    <span class="badge bg-info-subtle text-info fw-bold" style="font-size:13px;">{{ $u->course_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">
                                @if($u->marketplace_total > 0)
                                    {{ number_format($u->marketplace_total, 0, ',', ' ') }} <small class="text-muted fw-normal">XOF</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($u->grants_count > 0)
                                    <span class="badge bg-warning-subtle text-warning fw-bold" style="font-size:13px;">{{ $u->grants_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.show', $u) }}"
                                   class="btn btn-sm btn-outline-primary fw-semibold">Voir →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted small">{{ $users->total() }} utilisateur(s) trouvé(s)</span>
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
