@extends('layouts.admin')
@section('title', '🎮 Garba Master — Stats Admin')
@section('content')
<div class="container py-4" style="max-width:1300px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <h2 class="mb-0">🎮 Garba Master — Tableau de bord</h2>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <div style="font-size:2rem;font-weight:900;color:#0d6efd;">{{ $totalClaims }}</div>
                    <div class="text-muted small">Claims gratuits</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <div style="font-size:2rem;font-weight:900;color:#198754;">{{ $totalJoueurs }}</div>
                    <div class="text-muted small">Joueurs (saves)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <div style="font-size:2rem;font-weight:900;color:#fd7e14;">{{ $joueursActifs7j }}</div>
                    <div class="text-muted small">Actifs 7 jours</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <div style="font-size:2rem;font-weight:900;color:#dc3545;">{{ number_format($revenuTotal, 0, ',', ' ') }} F</div>
                    <div class="text-muted small">Revenus cauris</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Répartition par niveau --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold">📊 Joueurs par niveau de maquis</div>
                <div class="card-body">
                    @foreach([1 => 'Niveau 1 — Maquis de quartier', 2 => 'Niveau 2 — Maquis stylé', 3 => 'Niveau 3 — Restaurant haut de gamme'] as $n => $label)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $label }}</span>
                            <strong>{{ $parNiveau[$n] ?? 0 }}</strong>
                        </div>
                        @php $pct = $totalJoueurs > 0 ? round((($parNiveau[$n] ?? 0) / $totalJoueurs) * 100) : 0; @endphp
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-{{ $n === 1 ? 'success' : ($n === 2 ? 'warning' : 'danger') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                    <hr>
                    <div class="small text-muted">Solde moyen : <strong>{{ number_format($soldeMoyen ?? 0, 0, ',', ' ') }} cauris</strong></div>
                    <div class="small text-muted">Actifs 30j : <strong>{{ $joueursActifs30j }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Achats de cauris --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold">💰 Achats de cauris</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">Total transactions</div>
                        <strong>{{ $totalAchatsAcceptes }}</strong>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted">Revenu total</div>
                        <strong>{{ number_format($revenuTotal, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted">Panier moyen</div>
                        <strong>{{ number_format($revenuMoyen ?? 0, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <hr>
                    @foreach($achatsPack as $pack)
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-capitalize">Pack {{ $pack->pack }}</span>
                        <span><strong>{{ $pack->nb }}</strong> · {{ number_format($pack->total, 0, ',', ' ') }} F</span>
                    </div>
                    @endforeach
                    @if($achatsPack->isEmpty())
                        <div class="text-muted small">Aucun achat pour l'instant</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Derniers achats cauris --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold">🛒 Derniers achats cauris</div>
                <div class="card-body p-0">
                    @if($derniersAchats->isEmpty())
                        <div class="p-3 text-muted small">Aucun achat</div>
                    @else
                    <ul class="list-group list-group-flush">
                        @foreach($derniersAchats as $a)
                        <li class="list-group-item py-2 px-3">
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold">{{ $a->user->name ?? '—' }}</span>
                                <span class="small text-success">{{ number_format($a->prix, 0, ',', ' ') }} F</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted text-capitalize">Pack {{ $a->pack }}</span>
                                <span class="small text-muted">{{ \Carbon\Carbon::parse($a->credited_at)->diffForHumans() }}</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Derniers claims --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header fw-bold">🆓 Derniers claims gratuits ({{ $totalClaims }} total)</div>
        <div class="card-body p-0">
            @if($derniersClaims->isEmpty())
                <div class="p-3 text-muted">Aucun claim pour l'instant.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($derniersClaims as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td class="text-muted small">{{ $c->email }}</td>
                            <td><span class="badge bg-secondary">{{ $c->provider ?? 'free' }}</span></td>
                            <td class="small text-muted">{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
