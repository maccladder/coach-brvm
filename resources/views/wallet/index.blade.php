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
            {{-- ✅ AIDE (Option A) --}}
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#walletHelpModal">
                ❓ Aide
            </button>

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
                                    <form method="POST" action="{{ route('wallet.sell.recap') }}" class="d-flex justify-content-end gap-2">
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

{{-- ✅ MODAL AIDE (Option A) --}}
<div class="modal fade" id="walletHelpModal" tabindex="-1" aria-labelledby="walletHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="walletHelpModalLabel">❓ Aide — Portefeuille Virtuel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>

      <div class="modal-body">

        <div class="mb-4">
          <h6 class="fw-bold mb-2">📊 Comprendre les indicateurs</h6>
          <ul class="mb-0">
            <li><b>Solde cash</b> : argent disponible pour acheter des actions.</li>
            <li><b>Valeur positions</b> : valeur de tes positions au cours du jour (Σ cours × quantité).</li>
            <li><b>Valeur nette</b> : Cash + Valeur positions.</li>
            <li><b>Performance</b> : reflète ton gain/perte selon ta valeur nette (selon la formule que tu retiendras).</li>
          </ul>
        </div>

        <div class="mb-4">
          <h6 class="fw-bold mb-2">🧾 Table “Positions”</h6>
          <ul class="mb-0">
            <li><b>Qté</b> : nombre de titres détenus.</li>
            <li><b>Prix moyen (PRU)</b> : prix moyen d’achat par action (coût pondéré).</li>
            <li><b>Cours</b> : prix actuel du marché.</li>
            <li><b>Valeur</b> : Qté × Cours.</li>
            <li><b>P/L</b> : (Cours − PRU) × Qté (plus/moins-value latente).</li>
          </ul>

          <div class="alert alert-light border mt-2 mb-0">
            <b>Formules rapides :</b><br>
            <span class="text-muted">
              Valeur = Quantité × Cours<br>
              P&amp;L latent = (Cours − PRU) × Quantité
            </span>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="fw-bold mb-2">🟢 Achat + Renforcer</h6>
          <ul class="mb-0">
            <li>Un <b>achat</b> diminue ton cash de <b>(prix × quantité) + frais SGI</b>.</li>
            <li>Ta position augmente, et ton <b>PRU</b> est recalculé automatiquement.</li>
            <li><b>Renforcer</b> = racheter la même action pour augmenter ta quantité.</li>
            <li>Si tu renforces <b>à un prix plus bas</b> que ton PRU, ton PRU baisse (“moyenner à la baisse”).</li>
            <li>Si tu renforces <b>à un prix plus haut</b>, ton PRU monte.</li>
          </ul>

          <div class="alert alert-light border mt-2 mb-0">
            <b>PRU (coût pondéré)</b> :<br>
            <span class="text-muted">
              Nouveau PRU = (Ancienne Qté × Ancien PRU + Qté achetée × Prix achat) ÷ Nouvelle Qté
            </span>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="fw-bold mb-2">🔴 Vente + Diminuer</h6>
          <ul class="mb-0">
            <li><b>Diminuer</b> = vendre une partie de ta position.</li>
            <li>Quand tu vends, ta quantité baisse (ou disparaît si tu vends tout).</li>
            <li>Ton cash augmente du <b>montant net</b> : (prix × quantité) − frais SGI.</li>
          </ul>

          <div class="alert alert-light border mt-2 mb-0">
            <b>Plus/moins-value réalisée (simple)</b> :<br>
            <span class="text-muted">
              (Prix de vente − PRU) × Quantité vendue
            </span>
            <br>
            <small class="text-muted">
              (Dans ce portefeuille virtuel, on crédite le cash en <b>NET</b> après frais — plus réaliste.)
            </small>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="fw-bold mb-2">💸 Frais SGI (achat &amp; vente)</h6>
          <ul class="mb-0">
            <li>Les frais sont appliqués à chaque opération (achat et vente).</li>
            <li>Formule : <b>frais = max(montant × taux, minimum)</b>.</li>
            <li>Tu vois toujours un <b>récapitulatif</b> avant de confirmer.</li>
          </ul>

          <div class="alert alert-warning mt-2 mb-0">
            ✅ <b>Astuce :</b> si ton cash “bouge trop”, pense aux frais (surtout sur les petits montants).
          </div>
        </div>

        <div class="mb-0">
          <h6 class="fw-bold mb-2">🧾 Historique</h6>
          <ul class="mb-0">
            <li>Chaque mouvement est enregistré (topup, buy, sell).</li>
            <li>Tu peux t’en servir pour analyser tes décisions et ta stratégie.</li>
          </ul>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

@endsection
