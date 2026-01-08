{{-- resources/views/wallet/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-3">
            <div class="fw-semibold mb-1">Erreurs :</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">💼 Mon portefeuille virtuel</h1>
            <div class="text-muted small">Achats / ventes au cours BRVM (simulation).</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                ⬅️ Dashboard
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Solde cash</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format($wallet->balance ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-muted small">Argent disponible pour acheter</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Valeur positions</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format($totalValue ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-muted small">Somme des titres (au dernier cours)</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Valeur nette</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format($netWorth ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-muted small">Cash + positions</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recharge + Buy --}}
    <div class="row g-3 mb-4">
        {{-- Recharge --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-2">➕ Recharger</div>
                    <div class="text-muted small mb-3">
                        Ajoute du cash virtuel pour tester des achats.
                    </div>

                    <form method="POST" action="{{ route('wallet.topup.confirm') }}" class="d-flex gap-2">
                        @csrf
                        <input
                            type="number"
                            name="amount_paid"
                            class="form-control"
                            min="1000"
                            step="500"
                            value="1000"
                            required
                        >
                        <button class="btn btn-success fw-semibold">
                            Recharger
                        </button>
                    </form>

                    <div class="small text-muted mt-2">
                        Exemple : 100 000 FCFA
                    </div>
                </div>
            </div>
        </div>

        {{-- Acheter --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="fw-semibold">🛒 Acheter</div>
                        <div class="text-muted small">
                            Tu verras un <strong>récap + frais SGI</strong> avant de confirmer.
                        </div>
                    </div>

                    {{-- RECAP --}}
                    <form method="POST" action="{{ route('wallet.buy.recap') }}" class="row g-2 align-items-end">
                        @csrf

                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">Action (ticker)</label>
                            <select name="ticker" class="form-select" required>
                                <option value="" selected disabled>Choisir…</option>
                                @foreach(($market ?? []) as $s)
                                    @php
                                        $t = $s['ticker'] ?? '';
                                        $name = $s['name'] ?? '';
                                        $p = $s['buy_price'] ?? ($s['close'] ?? null);
                                    @endphp
                                    <option value="{{ $t }}">
                                        {{ $t }} — {{ $name }}
                                        @if(!is_null($p))
                                            ({{ number_format((float)$p, 0, ',', ' ') }} FCFA)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Quantité</label>
                            <input type="number" name="qty" min="1" step="1" value="1" class="form-control" required>
                        </div>

                        <div class="col-md-3 d-grid">
                            <button class="btn btn-primary fw-semibold">
                                Continuer
                            </button>
                        </div>
                    </form>

                    <div class="small text-muted mt-2">
                        Astuce : commence avec 1 ou 2 titres pour tester.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Positions --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="fw-semibold">📌 Mes positions</div>
                <div class="text-muted small">
                    Plus-value = (prix actuel − prix moyen) × quantité
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Ticker</th>
                            <th>Nom</th>
                            <th class="text-end">Qté</th>
                            <th class="text-end">Prix moyen</th>
                            <th class="text-end">Cours</th>
                            <th class="text-end">Valeur</th>
                            <th class="text-end">P/L</th>
                            <th class="text-end">Vendre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($positions ?? []) as $p)
                            @php
                                // ✅ IMPORTANT: le controller renvoie avg_price (pas avg)
                                $qty   = (int)($p['qty'] ?? 0);
                                $avg   = (float)($p['avg_price'] ?? 0);
                                $price = (float)($p['price'] ?? 0);

                                $value = $price > 0 ? $price * $qty : 0;
                                $pl    = $price > 0 ? ($price - $avg) * $qty : 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $p['ticker'] }}</td>
                                <td class="text-muted">{{ $p['name'] ?? '' }}</td>
                                <td class="text-end">{{ number_format($qty, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($avg, 0, ',', ' ') }}</td>
                                <td class="text-end">
                                    @if($price > 0)
                                        {{ number_format($price, 0, ',', ' ') }}
                                    @else
                                        <span class="text-muted">n/a</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($value, 0, ',', ' ') }}</td>
                                <td class="text-end">
                                    @if($price > 0)
                                        <span class="{{ $pl >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                            {{ $pl >= 0 ? '+' : '' }}{{ number_format($pl, 0, ',', ' ') }}
                                        </span>
                                    @else
                                        <span class="text-muted">n/a</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('wallet.sell') }}" class="d-flex justify-content-end gap-2">
                                        @csrf
                                        <input type="hidden" name="ticker" value="{{ $p['ticker'] }}">
                                        <input
                                            type="number"
                                            name="qty"
                                            min="1"
                                            max="{{ $qty }}"
                                            step="1"
                                            value="1"
                                            class="form-control form-control-sm"
                                            style="width: 90px;"
                                            required
                                        >
                                        <button class="btn btn-sm btn-outline-danger">
                                            Vendre
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Aucune position pour l’instant. Fais un premier achat 👇
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- Historique --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="fw-semibold mb-2">🧾 Historique</div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Date</th>
                            <th>Type</th>
                            <th>Ticker</th>
                            <th class="text-end">Qté</th>
                            <th class="text-end">Prix</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($history ?? []) as $tx)
                            @php
                                $type = strtolower((string)($tx->type ?? ''));

                                $badge = match($type) {
                                    'topup' => 'success',
                                    'buy'   => 'primary',
                                    'sell'  => 'danger',
                                    default => 'secondary',
                                };

                                // ✅ compat: certains enregistrements utilisent qty au lieu de quantity
                                $q = $tx->quantity ?? $tx->qty ?? null;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ optional($tx->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $badge }}">
                                        {{ strtoupper($type ?: '—') }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $tx->ticker ?? '—' }}</td>
                                <td class="text-end">
                                    {{ $q ? number_format((int)$q, 0, ',', ' ') : '—' }}
                                </td>
                                <td class="text-end">
                                    {{ $tx->price ? number_format((float)$tx->price, 0, ',', ' ') : '—' }}
                                </td>
                                <td class="text-end">
                                    @php $amt = (float)($tx->amount ?? 0); @endphp
                                    <span class="{{ $amt >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $amt >= 0 ? '+' : '' }}{{ number_format($amt, 0, ',', ' ') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Aucun mouvement pour l’instant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="small text-muted">
                Note : c’est un portefeuille pédagogique (virtuel).
            </div>
        </div>
    </div>

</div>
@endsection
