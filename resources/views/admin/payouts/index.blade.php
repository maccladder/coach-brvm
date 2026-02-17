@extends('layouts.admin')

@section('title', 'Reversements vendeurs – Admin')

@section('content')
<div class="container py-4" style="max-width:1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">💸 Reversements vendeurs</h3>
            <div class="text-muted">Approuver / rejeter / marquer payé (délai sécurité 72h).</div>
        </div>

        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="min-width:180px;">
                <option value="">Tous</option>
                <option value="pending"  @selected(request('status')==='pending')>En attente</option>
                <option value="approved" @selected(request('status')==='approved')>Approuvés</option>
                <option value="rejected" @selected(request('status')==='rejected')>Rejetés</option>
                <option value="paid"     @selected(request('status')==='paid')>Payés</option>
            </select>
            <button class="btn btn-sm btn-dark">Filtrer</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Vendeur</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Compte</th>
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
                    @endphp

                    <tr>
                        <td class="text-muted">#{{ $p->id }}</td>
                        <td class="fw-semibold">{{ $p->vendor?->name ?? ('Vendeur #' . $p->vendor_id) }}</td>
                        <td class="fw-bold">{{ number_format((int)$p->amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $p->payout_method }}</td>
                        <td class="text-muted">{{ $p->payout_account }}</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $p->status }}</span></td>
                        <td class="text-muted small">
                            {{ optional($p->requested_at)->diffForHumans() ?? optional($p->created_at)->diffForHumans() }}
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">

                                @if($p->status === 'pending')
                                    <form method="POST" action="{{ route('admin.payouts.approve', $p) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Approuver cette demande ?')">
                                            ✅ Approuver
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.payouts.reject', $p) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Rejeter cette demande ?')">
                                            ❌ Rejeter
                                        </button>
                                    </form>
                                @endif

                                @if($p->status === 'approved')
                                    <form method="POST" action="{{ route('admin.payouts.paid', $p) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-dark"
                                                onclick="return confirm('Marquer comme payé ?')">
                                            💰 Marquer payé
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($p->status, ['rejected','paid']))
                                    <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucune demande de reversement.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body border-top bg-white">
            {{ $payouts->links() }}
        </div>
    </div>

</div>
@endsection
