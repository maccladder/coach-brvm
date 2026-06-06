@extends('layouts.admin')

@section('title', 'Commissions — ' . $affiliate->code)

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.affiliates.index') }}" class="btn btn-sm btn-outline-secondary">← Retour</a>
        <div>
            <h4 class="mb-0">Commissions — <code>{{ $affiliate->code }}</code></h4>
            <div class="text-muted small">{{ $affiliate->user?->name }} · {{ $affiliate->user?->email }}</div>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Créée le</th>
                        <th>Type</th>
                        <th>Base</th>
                        <th>Commission</th>
                        <th>Statut</th>
                        <th>Validation</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($commissions as $c)
                    @php
                        $badge = match($c->status) {
                            'pending'   => 'warning',
                            'validated' => 'success',
                            'paid'      => 'primary',
                            'cancelled' => 'danger',
                            default     => 'secondary',
                        };
                        $typeLabel = match($c->product_type) {
                            'pack_brvm'   => 'Pack BRVM',
                            'course'      => 'Formation',
                            'marketplace' => 'Marketplace',
                            default       => $c->product_type,
                        };
                    @endphp
                    <tr>
                        <td class="text-muted small">#{{ $c->id }}</td>
                        <td class="text-muted small">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge text-bg-light text-dark">{{ $typeLabel }}</span></td>
                        <td class="fw-semibold">{{ number_format($c->base_amount, 0, ',', ' ') }} F</td>
                        <td class="fw-bold text-success">+{{ number_format($c->commission_amount, 0, ',', ' ') }} F</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $c->status }}</span></td>
                        <td class="text-muted small">
                            {{ $c->validated_at ? $c->validated_at->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-end">
                            @if(in_array($c->status, ['pending','validated']))
                                <button class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cancelModal{{ $c->id }}">
                                    🚫 Annuler
                                </button>

                                <div class="modal fade" id="cancelModal{{ $c->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.affiliate-payouts.cancel-commission', $c) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Annuler la commission #{{ $c->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-warning small">
                                                        ⚠️ Le solde de l'apporteur sera mis à jour immédiatement.<br>
                                                        Si la commission est déjà <strong>validated</strong> et que le montant a déjà été retiré, tu devras gérer le litige manuellement.
                                                    </div>
                                                    <label class="form-label fw-semibold">Motif d'annulation <span class="text-danger">*</span></label>
                                                    <textarea name="cancel_reason" rows="3" class="form-control" required maxlength="500"
                                                              placeholder="Raison (remboursement, litige, erreur…)"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucune commission.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $commissions->links() }}
        </div>
    </div>

</div>
@endsection
