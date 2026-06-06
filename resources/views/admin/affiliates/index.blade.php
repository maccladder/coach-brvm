@extends('layouts.admin')

@section('title', 'Apporteurs d\'affaires – Admin')

@section('content')
<div class="container py-4" style="max-width:1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">🤝 Apporteurs d'affaires</h3>
            <p class="text-muted mb-0">Commission 10% · Remise acheteur 10% · Sécurité 10 jours</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.affiliate-payouts.index') }}" class="btn btn-sm btn-outline-secondary">
                💸 Voir les reversements
            </a>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="min-width:160px;">
                    <option value="">Tous</option>
                    <option value="pending"   @selected(request('status')==='pending')>En attente</option>
                    <option value="active"    @selected(request('status')==='active')>Actifs</option>
                    <option value="suspended" @selected(request('status')==='suspended')>Suspendus</option>
                </select>
                <button class="btn btn-sm btn-dark">Filtrer</button>
            </form>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Code</th>
                        <th>Clics</th>
                        <th>Statut</th>
                        <th>Demandé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($affiliates as $aff)
                    @php
                        $badge = match($aff->status) {
                            'pending'   => 'warning',
                            'active'    => 'success',
                            'suspended' => 'danger',
                            default     => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted small">#{{ $aff->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $aff->user?->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $aff->user?->email }}</div>
                        </td>
                        <td>
                            <code class="fw-bold">{{ $aff->code }}</code>
                            @if($aff->custom_code)
                                <span class="badge text-bg-light text-muted">perso</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ number_format($aff->click_count, 0, ',', ' ') }}</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $aff->status }}</span></td>
                        <td class="text-muted small">
                            {{ optional($aff->requested_at ?? $aff->created_at)->format('d/m/Y') }}
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">

                                {{-- Approuver --}}
                                @if($aff->status === 'pending')
                                    <form method="POST" action="{{ route('admin.affiliates.approve', $aff) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Activer l\'apporteur {{ $aff->user?->name }} (code {{ $aff->code }}) ?')">
                                            ✅ Activer
                                        </button>
                                    </form>
                                @endif

                                {{-- Suspendre --}}
                                @if($aff->status === 'active')
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#suspendModal{{ $aff->id }}">
                                        ⛔ Suspendre
                                    </button>

                                    <div class="modal fade" id="suspendModal{{ $aff->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('admin.affiliates.suspend', $aff) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Suspendre {{ $aff->user?->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label class="form-label">Motif (optionnel)</label>
                                                        <textarea name="admin_note" rows="3" class="form-control" maxlength="500"
                                                                  placeholder="Raison de la suspension…"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-danger">Suspendre</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Voir commissions --}}
                                <a href="{{ route('admin.affiliates.commissions', $aff) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    📊 Commissions
                                </a>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Aucun apporteur pour l'instant.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $affiliates->links() }}
        </div>
    </div>

</div>
@endsection
