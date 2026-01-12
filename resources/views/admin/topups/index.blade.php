@extends('layouts.admin')

@section('title', 'Topups Wallet – Admin')

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">💳 Topups Wallet</h2>
        <p class="text-muted mb-0">
            Suivi des rechargements (topups) des utilisateurs.
        </p>
    </div>

    {{-- CARTES STATS --}}
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Topups (total)</h6>
                    <h3 class="fw-bold">{{ $stats['count_total'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Topups validés</h6>
                    <h3 class="fw-bold text-success">{{ $stats['count_accepted'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Utilisateurs (validés)</h6>
                    <h3 class="fw-bold">{{ $stats['unique_buyers'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-dark text-white">
                <div class="card-body">
                    <h6 class="opacity-75">Montant encaissé</h6>
                    <h3 class="fw-bold">
                        {{ number_format($stats['amount_accepted'], 0, ',', ' ') }} FCFA
                    </h3>
                </div>
            </div>
        </div>

    </div>

    {{-- FILTRES --}}
    <form class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted">Utilisateur (nom/email)</label>
                    <input type="text" name="user" value="{{ request('user') }}" class="form-control" placeholder="ex: mac / gmail">
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        @foreach(['ACCEPTED','PENDING','INITIATED','REFUSED','CANCELLED','FAILED'] as $s)
                            <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted">Du</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted">Au</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('admin.topups.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">📋 Liste des topups</h5>

                <div class="text-muted small">
                    En attente: <b>{{ $stats['count_pending'] }}</b> •
                    Échoués: <b>{{ $stats['count_failed'] }}</b>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Transaction</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($topups as $t)
                        <tr>
                            <td class="text-muted">
                                {{ optional($t->created_at)->format('d/m/Y H:i') }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $t->user->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $t->user->email ?? '' }}</div>
                            </td>

                            <td class="text-muted">
                                <div class="fw-semibold">{{ $t->transaction_id ?? '—' }}</div>
                                <div class="small">{{ $t->payment_method ?? '' }}</div>
                            </td>

                            <td class="text-end fw-bold">
                                {{ number_format($t->amount_paid ?? 0, 0, ',', ' ') }} FCFA
                            </td>

                            <td>
                                @php
                                    $status = $t->status ?? '—';
                                    $badge = match($status) {
                                        'ACCEPTED' => 'success',
                                        'PENDING', 'INITIATED' => 'warning',
                                        'REFUSED', 'CANCELLED', 'FAILED' => 'danger',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ $status }}</span>

                                @if(!empty($t->credited_at))
                                    <div class="text-muted small">Crédité: {{ \Carbon\Carbon::parse($t->credited_at)->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucun topup trouvé.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $topups->links() }}
            </div>

        </div>
    </div>

</div>
@endsection
