@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">💰 Revenus vendeur</h3>
            <div class="text-muted">
                Commission Coach BRVM : <strong>15%</strong> — Délai sécurité : <strong>72h</strong> — Arrondi (round)
            </div>
        </div>
        <a href="{{ route('vendor.dashboard') }}" class="btn btn-outline-secondary">
            ← Retour dashboard
        </a>
    </div>

    {{-- ✅ Totaux --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">💵 Argent encaissé (brut)</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format((int)($totals['gross'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">🏦 Commission Coach BRVM (15%)</div>
                    <div class="fs-4 fw-bold text-danger">
                        {{ number_format((int)($totals['commission'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">✅ Montant net (théorique)</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ number_format((int)($totals['net'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">🟢 Disponible (après 72h)</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format((int)($totals['available'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-muted small mt-1">
                        En attente de déblocage : {{ number_format((int)($totals['locked_72h'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Form demande reversement --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="fw-semibold">📤 Demander un reversement</div>
            <span class="badge text-bg-light border">
                Minimum : {{ number_format((int)($totals['min_payout'] ?? 10000), 0, ',', ' ') }} FCFA
            </span>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success mb-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mb-3">{{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <div class="fw-semibold mb-1">Veuillez corriger :</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $available = (int)($totals['available'] ?? 0);
                $minPayout = (int)($totals['min_payout'] ?? 10000);
            @endphp

            @if($available < $minPayout)
                <div class="alert alert-info mb-0">
                    Ton montant disponible est de <strong>{{ number_format($available, 0, ',', ' ') }} FCFA</strong>.
                    Tu pourras demander un reversement à partir de <strong>{{ number_format($minPayout, 0, ',', ' ') }} FCFA</strong>.
                </div>
            @else
                <form method="POST" action="{{ route('vendor.earnings.requestPayout') }}" class="row g-3">
                    @csrf

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Montant à demander</label>
                        <input type="number"
                               name="amount"
                               min="{{ $minPayout }}"
                               max="{{ $available }}"
                               value="{{ old('amount', min($available, $minPayout)) }}"
                               class="form-control"
                               required>
                        <div class="form-text">
                            Max : {{ number_format($available, 0, ',', ' ') }} FCFA
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Méthode</label>
                        <select name="payout_method" class="form-select" required>
                            <option value="" disabled {{ old('payout_method') ? '' : 'selected' }}>Choisir…</option>
                            <option value="wave" {{ old('payout_method')==='wave' ? 'selected' : '' }}>Wave</option>
                            <option value="orange" {{ old('payout_method')==='orange' ? 'selected' : '' }}>Orange Money</option>
                            <option value="mtn" {{ old('payout_method')==='mtn' ? 'selected' : '' }}>MTN Money</option>
                            <option value="moov" {{ old('payout_method')==='moov' ? 'selected' : '' }}>Moov Money</option>
                            <option value="bank" {{ old('payout_method')==='bank' ? 'selected' : '' }}>Virement bancaire</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Compte / Numéro</label>
                        <input type="text"
                               name="payout_account"
                               value="{{ old('payout_account') }}"
                               class="form-control"
                               placeholder="Ex: 07xxxxxxxx / RIB / IBAN…"
                               required>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-dark">
                            <i class="bi bi-send"></i> Envoyer la demande
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- ✅ Historique des reversements --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="fw-semibold">🧾 Historique des demandes</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Compte</th>
                    <th>Statut</th>
                    <th>Note admin</th>
                </tr>
                </thead>
                <tbody>
                @forelse($payouts as $p)
                    @php
                        $badge = match($p->status) {
                            'pending'  => 'warning',
                            'approved' => 'primary',
                            'paid'     => 'success',
                            'rejected' => 'danger',
                            default    => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted">
                            {{ optional($p->requested_at ?? $p->created_at)->format('d/m/Y H:i') }}
                        </td>
                        <td class="fw-semibold">
                            {{ number_format((int)$p->amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="text-muted">{{ strtoupper((string)$p->payout_method) }}</td>
                        <td class="text-muted">{{ $p->payout_account }}</td>
                        <td>
                            <span class="badge text-bg-{{ $badge }}">{{ $p->status }}</span>
                        </td>
                        <td class="text-muted small" style="max-width:320px;">
                            {{ $p->admin_note ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Aucune demande de reversement pour l’instant.
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
