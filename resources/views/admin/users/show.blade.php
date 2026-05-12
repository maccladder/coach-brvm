@extends('layouts.admin')

@section('title', 'Utilisateur – {{ $user->name }}')

@section('content')
<div class="container-fluid py-4 px-4" style="max-width:1200px;">

    {{-- Retour --}}
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary fw-semibold mb-4">← Tous les utilisateurs</a>

    {{-- Profil --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:64px;height:64px;font-size:24px;">
                        {{ mb_strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                </div>
                <div class="col">
                    <h3 class="fw-bold mb-1">{{ $user->name ?? '—' }}</h3>
                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size:14px;">
                        <span>✉️ <a href="mailto:{{ $user->email }}" class="text-decoration-none text-dark font-monospace">{{ $user->email }}</a></span>
                        <span>📅 Inscrit le {{ $user->created_at?->format('d/m/Y à H:i') }}</span>
                        @if($user->phone)
                            <span>
                                📱 <span class="font-monospace">{{ $user->phone }}</span>
                                @if($user->phone_verified_at)
                                    <span class="badge bg-success ms-1">✅ Vérifié le {{ $user->phone_verified_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark ms-1">⚠️ Non vérifié</span>
                                @endif
                            </span>
                        @else
                            <span class="text-muted">📱 Aucun téléphone enregistré</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto">
                    <a href="mailto:{{ $user->email }}" class="btn btn-outline-primary fw-semibold">
                        ✉️ Contacter
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Achats Marketplace</div>
                    <div class="fs-3 fw-bold text-success">{{ $user->marketplacePurchases->where('status','paid')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Marketplace</div>
                    <div class="fs-3 fw-bold">{{ number_format($marketplaceTotal, 0, ',', ' ') }} <small class="fs-6 fw-normal text-muted">XOF</small></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Cours achetés</div>
                    <div class="fs-3 fw-bold text-info">{{ $user->coursePurchases->whereNotNull('paid_at')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Contenus offerts</div>
                    <div class="fs-3 fw-bold text-warning">{{ $user->adminGrants->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Achats Marketplace --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
            🛍️ Achats Marketplace ({{ $user->marketplacePurchases->count() }})
        </div>
        <div class="card-body p-0">
            @if($user->marketplacePurchases->isEmpty())
                <p class="text-muted p-4 mb-0">Aucun achat.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th>Type</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>Provider</th>
                            <th>Réf. transaction</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->marketplacePurchases->sortByDesc('created_at') as $purchase)
                        <tr>
                            <td class="fw-semibold">{{ $purchase->product?->title ?? '(supprimé)' }}</td>
                            <td>
                                @php $type = $purchase->product?->type ?? ''; @endphp
                                @if($type === 'game')     <span class="badge bg-purple-subtle text-purple" style="background:rgba(120,80,255,.12);color:#8060dd;">🎮 Jeu</span>
                                @elseif($type === 'video') <span class="badge bg-warning-subtle text-warning">🎬 Vidéo</span>
                                @elseif($type === 'software') <span class="badge bg-info-subtle text-info">🧩 Logiciel</span>
                                @else <span class="badge bg-secondary-subtle text-secondary">📘 Livre</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($purchase->amount, 0, ',', ' ') }} XOF</td>
                            <td>
                                @if($purchase->status === 'paid')
                                    <span class="badge bg-success">✅ Payé</span>
                                @elseif($purchase->status === 'pending')
                                    <span class="badge bg-warning text-dark">⏳ En attente</span>
                                @elseif($purchase->status === 'refunded')
                                    <span class="badge bg-info">↩️ Remboursé</span>
                                @else
                                    <span class="badge bg-danger">❌ Échoué</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $purchase->provider ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="font-monospace text-muted" style="font-size:12px;">{{ $purchase->provider_ref ?? '—' }}</span>
                            </td>
                            <td style="font-size:13px;color:#6c757d;">
                                {{ $purchase->paid_at?->format('d/m/Y H:i') ?? $purchase->created_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="fw-bold">Total payé</td>
                            <td class="text-end fw-bold">{{ number_format($marketplaceTotal, 0, ',', ' ') }} XOF</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- Cours --}}
    @php
        $paidCourses   = $user->coursePurchases->whereNotNull('paid_at');
        $unpaidCourses = $user->coursePurchases->whereNull('paid_at');
    @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3 d-flex align-items-center gap-2">
            🎓 Cours
            <span class="badge bg-success">{{ $paidCourses->count() }} payés</span>
            @if($unpaidCourses->count())
                <span class="badge bg-secondary">{{ $unpaidCourses->count() }} non aboutis</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if($user->coursePurchases->isEmpty())
                <p class="text-muted p-4 mb-0">Aucun cours.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Formation</th>
                            <th>Statut</th>
                            <th class="text-end">Montant</th>
                            <th>Réf. paiement</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->coursePurchases->sortByDesc('created_at') as $cp)
                        <tr class="{{ is_null($cp->paid_at) ? 'text-muted' : '' }}">
                            <td class="fw-semibold">{{ $cp->course?->title ?? '(supprimé)' }}</td>
                            <td>
                                @if($cp->paid_at)
                                    <span class="badge bg-success">✅ Payé</span>
                                @else
                                    <span class="badge bg-secondary">⏳ Non abouti</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cp->paid_at)
                                    <span class="fw-semibold">{{ number_format($cp->amount_fcfa ?? 0, 0, ',', ' ') }} XOF</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="font-monospace" style="font-size:12px;color:#6c757d;">{{ $cp->payment_ref ?? '—' }}</span></td>
                            <td style="font-size:13px;color:#6c757d;">{{ $cp->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- Contenus offerts (AdminGrant) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
            🎁 Contenus offerts / AdminGrant ({{ $user->adminGrants->count() }})
        </div>
        <div class="card-body p-0">
            @if($user->adminGrants->isEmpty())
                <p class="text-muted p-4 mb-0">Aucun contenu offert.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Contenu</th>
                            <th>Raison</th>
                            <th>Offert le</th>
                            <th>Expire le</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->adminGrants->sortByDesc('granted_at') as $grant)
                        <tr>
                            <td class="fw-semibold">{{ $grantNames[$grant->id] ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $grant->reason ?? '—' }}</td>
                            <td style="font-size:13px;color:#6c757d;">{{ $grant->granted_at?->format('d/m/Y') ?? '—' }}</td>
                            <td style="font-size:13px;color:#6c757d;">{{ $grant->expires_at?->format('d/m/Y') ?? 'Permanent' }}</td>
                            <td>
                                @if($grant->revoked_at)
                                    <span class="badge bg-danger">Révoqué</span>
                                @elseif($grant->expires_at && $grant->expires_at->isPast())
                                    <span class="badge bg-secondary">Expiré</span>
                                @else
                                    <span class="badge bg-success">Actif</span>
                                @endif
                            </td>
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
