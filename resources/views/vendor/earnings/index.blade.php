@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">💰 Revenus vendeur</h3>
            <div class="text-muted">
                Commission Coach BRVM : <strong>15%</strong> —
                Délai sécurité : <strong>{{ $totals['delay_hours'] ?? 72 }}h</strong> —
                Arrondi (round)
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
                        {{ number_format((int)($totals['gross_total'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">🏦 Commission Coach BRVM (15%)</div>
                    <div class="fs-4 fw-bold text-danger">
                        {{ number_format((int)($totals['fee_total'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">✅ Montant net (théorique)</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ number_format((int)($totals['net_total'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-muted small">(visible immédiatement)</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        🟢 Retirable (après {{ $totals['delay_hours'] ?? 72 }}h)
                    </div>
                    <div class="fs-4 fw-bold">
                        {{ number_format((int)($totals['available'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>

                    <div class="text-muted small mt-1">
                        En attente de déblocage :
                        {{ number_format((int)($totals['locked_net_72h'] ?? 0), 0, ',', ' ') }} FCFA

                        @if(!empty($totals['next_unlock_at']))
                            <div>
                                Prochain déblocage :
                                <strong>
                                    {{ \Carbon\Carbon::parse($totals['next_unlock_at'])->format('d/m/Y H:i') }}
                                </strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ✅ Reversement --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="fw-semibold">📤 Demander un reversement</div>
            <span class="badge text-bg-light border">
                Minimum :
                {{ number_format((int)($totals['min_payout'] ?? 10000), 0, ',', ' ') }} FCFA
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
                $available   = (int)($totals['available'] ?? 0);
                $minPayout   = (int)($totals['min_payout'] ?? 10000);
                $canRequest  = $available >= $minPayout;
                $missingToMin = max(0, $minPayout - $available);
                $lockedNet   = (int)($totals['locked_net_72h'] ?? 0);
            @endphp

            <form method="POST"
                  action="{{ route('vendor.payouts.request') }}"
                  class="row g-3">
                @csrf

                {{-- Montant --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Montant à demander</label>
                    <input type="number"
                           name="amount"
                           min="{{ $minPayout }}"
                           max="{{ max($available, $minPayout) }}"
                           value="{{ old('amount', $minPayout) }}"
                           class="form-control"
                           {{ $canRequest ? '' : 'disabled' }}
                           required>

                    <div class="form-text">
                        Retirable actuellement :
                        {{ number_format($available, 0, ',', ' ') }} FCFA
                    </div>
                </div>

                {{-- Méthode (Wave uniquement) --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Méthode</label>
                    <select name="payout_method"
                            class="form-select"
                            {{ $canRequest ? '' : 'disabled' }}
                            required>
                        <option value="wave" selected>Wave</option>
                    </select>
                </div>

                {{-- Numéro Wave --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Numéro Wave</label>
                    <input type="tel"
                           name="payout_phone"
                           value="{{ old('payout_phone') }}"
                           class="form-control"
                           placeholder="Ex: 07xxxxxxxx ou +22507xxxxxxxx"
                           {{ $canRequest ? '' : 'disabled' }}
                           required>
                </div>

                {{-- Footer form --}}
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div class="text-muted small">
                        @if($canRequest)
                            ✅ Tu peux demander un reversement maintenant.
                        @else
                            🔒 Reversement indisponible pour le moment.
                            @if($available < $minPayout)
                                Il te manque
                                <strong>
                                    {{ number_format($missingToMin, 0, ',', ' ') }} FCFA
                                </strong>
                                pour atteindre le minimum.
                            @endif

                            @if($lockedNet > 0)
                                <span class="ms-1">
                                    En attente de déblocage
                                    ({{ $totals['delay_hours'] ?? 72 }}h) :
                                    <strong>
                                        {{ number_format($lockedNet, 0, ',', ' ') }} FCFA
                                    </strong>.
                                </span>
                            @endif
                        @endif
                    </div>

                    <button class="btn btn-dark"
                            {{ $canRequest ? '' : 'disabled' }}>
                        <i class="bi bi-send"></i> Envoyer la demande
                    </button>

                </div>
            </form>

        </div>
    </div>

    {{-- ✅ Historique --}}
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
                    <th>Téléphone</th>
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
                        <td class="text-muted">
                            {{ strtoupper((string)$p->payout_method) }}
                        </td>
                        <td class="text-muted">
                            {{ $p->payout_account }}
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $badge }}">
                                {{ $p->status }}
                            </span>
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
