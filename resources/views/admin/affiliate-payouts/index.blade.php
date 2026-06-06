@extends('layouts.admin')

@section('title', 'Reversements apporteurs – Admin')

@section('content')
<div class="container py-4" style="max-width:1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">💸 Reversements apporteurs</h3>
            <p class="text-muted mb-0">Caisse séparée de la caisse vendeur · Seuil mini 10 000 FCFA · Mobile money</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="min-width:180px;">
                <option value="">Tous les statuts</option>
                <option value="pending"  @selected(request('status')==='pending')>En attente</option>
                <option value="approved" @selected(request('status')==='approved')>Approuvés</option>
                <option value="paid"     @selected(request('status')==='paid')>Payés</option>
                <option value="rejected" @selected(request('status')==='rejected')>Rejetés</option>
            </select>
            <button class="btn btn-sm btn-dark">Filtrer</button>
        </form>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
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
                        <th>Apporteur</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>Demandé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payouts as $p)
                    @php
                        $badge = match($p->status) {
                            'pending'  => 'warning',
                            'approved' => 'primary',
                            'rejected' => 'danger',
                            'paid'     => 'success',
                            default    => 'secondary',
                        };
                        $affiliateName = $p->affiliate?->user?->name ?? ('Apporteur #' . $p->affiliate_id);
                        $affiliateCode = $p->affiliate?->code ?? '—';
                        $amountFmt     = number_format((int) $p->amount, 0, ',', ' ');
                    @endphp
                    <tr>
                        <td class="text-muted small">#{{ $p->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $affiliateName }}</div>
                            <div class="text-muted small">Code : {{ $affiliateCode }}</div>
                        </td>
                        <td class="fw-bold">{{ number_format((int)$p->amount, 0, ',', ' ') }} FCFA</td>
                        <td class="text-uppercase small">{{ $p->payout_method }}</td>
                        <td class="text-muted small">{{ $p->payout_account }}</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $p->status }}</span></td>
                        <td class="text-muted small">
                            {{ optional($p->requested_at ?? $p->created_at)->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">

                                {{-- Approuver --}}
                                @if($p->status === 'pending')
                                    <form method="POST" action="{{ route('admin.affiliate-payouts.approve', $p) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Approuver ce reversement de {{ $amountFmt }} FCFA ?')">
                                            ✅ Approuver
                                        </button>
                                    </form>
                                @endif

                                {{-- Rejeter --}}
                                @if(in_array($p->status, ['pending','approved']))
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal{{ $p->id }}">
                                        ❌ Rejeter
                                    </button>

                                    {{-- Modal rejet --}}
                                    <div class="modal fade" id="rejectModal{{ $p->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('admin.affiliate-payouts.reject', $p) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Rejeter le reversement #{{ $p->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label class="form-label fw-semibold">Motif du rejet <span class="text-danger">*</span></label>
                                                        <textarea name="admin_note" rows="3" class="form-control" required maxlength="500"
                                                                  placeholder="Explique la raison du rejet à l'apporteur…"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-danger">Rejeter</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Marquer payé --}}
                                @if($p->status === 'approved')
                                    <button class="btn btn-sm btn-dark"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paidModal{{ $p->id }}">
                                        💸 Marquer payé
                                    </button>

                                    {{-- Modal marquer payé (avec upload reçu) --}}
                                    <div class="modal fade" id="paidModal{{ $p->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST"
                                                      action="{{ route('admin.affiliate-payouts.paid', $p) }}"
                                                      enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmer le paiement #{{ $p->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Référence transaction</label>
                                                            <input type="text" name="reference" class="form-control"
                                                                   maxlength="120"
                                                                   placeholder="Ex : REF-WAVE-20260605-001">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Reçu de paiement <span class="text-muted small">(optionnel, JPEG/PNG/PDF, max 5 Mo)</span></label>
                                                            <input type="file" name="receipt" class="form-control"
                                                                   accept=".jpg,.jpeg,.png,.pdf">
                                                            <div class="form-text">Preuve du virement mobile money.</div>
                                                        </div>
                                                        <div class="alert alert-info small mb-0">
                                                            💡 Montant : <strong>{{ number_format((int)$p->amount, 0, ',', ' ') }} FCFA</strong>
                                                            via <strong class="text-uppercase">{{ $p->payout_method }}</strong>
                                                            → <strong>{{ $p->payout_account }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-success">Confirmer le paiement</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($p->admin_note)
                                    <span class="text-muted small" title="{{ $p->admin_note }}">📝</span>
                                @endif

                                @if($p->status === 'paid' && data_get($p->meta,'receipt_path'))
                                    <a href="{{ route('admin.affiliate-payouts.receipt', $p) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       target="_blank">
                                        🧾 Reçu
                                    </a>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucune demande de reversement.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $payouts->links() }}
        </div>
    </div>

</div>
@endsection
