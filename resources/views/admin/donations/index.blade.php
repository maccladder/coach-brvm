@extends('layouts.admin')

@section('title', 'Dons – Admin Boursiv')

@push('styles')
<style>
    .stat-card { border: 1px solid rgba(0,0,0,.08); border-radius: .6rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    .badge-ACCEPTED { background: #198754; color: #fff; }
    .badge-PENDING  { background: #ffc107; color: #000; }
    .badge-FAILED   { background: #dc3545; color: #fff; }
</style>
@endpush

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">🤝 Dons — Soutenir Boursiv</h2>
            <p class="text-muted mb-0">Suivi de tous les dons reçus via Paystack.</p>
        </div>
        <a href="{{ route('donation.show') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            Voir la page publique →
        </a>
    </div>

    {{-- CARTES STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Dons (total)</h6>
                    <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Confirmés</h6>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['accepted'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Donateurs uniques</h6>
                    <h3 class="fw-bold mb-0">{{ $stats['donors_uniq'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100 bg-dark text-white">
                <div class="card-body">
                    <h6 class="opacity-75 mb-1">Montant collecté</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($stats['amount_total'], 0, ',', ' ') }} F</h3>
                    <small class="opacity-50">dons confirmés uniquement</small>
                </div>
            </div>
        </div>
    </div>

    {{-- LIGNE PENDING / FAILED --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">En attente</h6>
                    <h3 class="fw-bold mb-0 text-warning">{{ $stats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Échoués / Annulés</h6>
                    <h3 class="fw-bold mb-0 text-danger">{{ $stats['failed'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    @php
                        $pct = $stats['total'] > 0
                            ? round($stats['accepted'] / $stats['total'] * 100)
                            : 0;
                    @endphp
                    <div class="flex-fill">
                        <h6 class="text-muted mb-1">Taux de conversion</h6>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    <span class="fw-bold fs-5">{{ $pct }} %</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTRES --}}
    <form class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Nom, email ou référence"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="ACCEPTED" {{ request('status') === 'ACCEPTED' ? 'selected' : '' }}>Confirmé</option>
                        <option value="PENDING"  {{ request('status') === 'PENDING'  ? 'selected' : '' }}>En attente</option>
                        <option value="FAILED"   {{ request('status') === 'FAILED'   ? 'selected' : '' }}>Échoué</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Du</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Au</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm flex-fill">Filtrer</button>
                    <a href="{{ route('admin.donations.index') }}" class="btn btn-outline-secondary btn-sm">✕</a>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLEAU --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;padding:.8rem 1rem;">Date</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Donateur</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Référence</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Montant</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Statut</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Confirmé le</th>
                            <th style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;">Canal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($donations as $don)
                        <tr>
                            <td class="text-muted small ps-3" style="white-space:nowrap;">
                                {{ $don->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size:.92rem;">{{ $don->donor_name }}</div>
                                <div class="text-muted small">{{ $don->donor_email }}</div>
                            </td>
                            <td>
                                <code class="small">{{ $don->reference }}</code>
                            </td>
                            <td class="fw-bold">
                                {{ number_format($don->amount, 0, ',', ' ') }} F
                            </td>
                            <td>
                                <span class="badge badge-{{ $don->status }}" style="font-size:.78rem;padding:.35em .65em;">
                                    {{ $don->status }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $don->confirmed_at ? $don->confirmed_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="text-muted small">
                                {{ data_get($don->meta, 'paystack.channel', '—') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Aucun don trouvé avec ces critères.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($donations->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3 px-4">
            <div class="text-muted small">
                {{ $donations->firstItem() }}–{{ $donations->lastItem() }} sur {{ $donations->total() }} dons
            </div>
            {{ $donations->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

</div>
@endsection
