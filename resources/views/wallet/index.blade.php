{{-- resources/views/wallet/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .wallet-page { background: var(--cb-paper); min-height: 100vh; }

    /* Hero */
    .wallet-hero { background:var(--cb-card);border-bottom:1px solid rgba(176,134,46,.08);padding:36px 0 28px; }
    .wallet-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .wallet-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-gold); }
    .wallet-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:var(--cb-ink);margin-bottom:4px; }
    .wallet-hero-sub { font-size:13px;color:var(--cb-muted); }

    /* Flash messages */
    .wallet-alert-ok { background:rgba(15,92,67,.06);border:1px solid rgba(15,92,67,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:var(--cb-forest);font-family:'Syne',sans-serif;margin-bottom:20px; }
    .wallet-alert-err { background:rgba(192,57,43,.06);border:1px solid rgba(192,57,43,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:var(--cb-down);margin-bottom:20px; }
    .wallet-alert-err ul { margin:6px 0 0 16px;padding:0; }

    /* KPI cards */
    .kpi-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px; }
    @media(max-width:640px){ .kpi-grid{grid-template-columns:1fr;} }
    .kpi-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:20px 22px;position:relative;overflow:hidden;transition:border-color .25s; }
    .kpi-card:hover { border-color:rgba(176,134,46,.25); }
    .kpi-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--kpi-color),transparent); }
    .kpi-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:8px; }
    .kpi-value { font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,28px);font-weight:900;color:var(--kpi-color);line-height:1;margin-bottom:4px; }
    .kpi-hint { font-size:11px;color:var(--cb-muted); }

    /* Panneaux */
    .wallet-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;overflow:hidden;margin-bottom:20px; }
    .wallet-card-header { background:var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:14px 20px; }
    .wallet-card-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:8px; }
    .wallet-card-body { padding:20px; }

    /* Inputs */
    .w-input { background:var(--cb-paper) !important;border:1px solid var(--cb-border) !important;color:var(--cb-ink) !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:9px 12px !important;outline:none;transition:border-color .25s; }
    .w-input:focus { border-color:rgba(176,134,46,.4) !important;box-shadow:0 0 0 3px rgba(176,134,46,.07) !important; }
    .w-input option { background:var(--cb-card); }
    .w-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);display:block;margin-bottom:6px; }
    .w-hint { font-size:12px;color:var(--cb-muted);margin-top:6px; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--cb-gold),#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s;white-space:nowrap; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:7px;background:rgba(15,92,67,.1);color:var(--cb-forest) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid rgba(15,92,67,.2);border-radius:3px;cursor:pointer;transition:all .3s;white-space:nowrap; }
    .cb-btn-green:hover { background:rgba(15,92,67,.16);border-color:rgba(15,92,67,.35); }
    .cb-btn-red { display:inline-flex;align-items:center;gap:7px;background:rgba(192,57,43,.08);color:var(--cb-down) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:7px 12px;border:1px solid rgba(192,57,43,.2);border-radius:3px;cursor:pointer;transition:all .3s;white-space:nowrap; }
    .cb-btn-red:hover { background:rgba(192,57,43,.14);border-color:rgba(192,57,43,.4); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .3s;cursor:pointer; }
    .cb-btn-outline:hover { border-color:var(--cb-gold);color:var(--cb-gold) !important;background:rgba(176,134,46,.05); }
    .cb-btn-modal { display:inline-flex;align-items:center;gap:7px;background:rgba(15,92,67,.04);color:var(--cb-muted) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:8px 16px;border:1px solid var(--cb-border);border-radius:3px;cursor:pointer;transition:all .25s; }
    .cb-btn-modal:hover { border-color:rgba(176,134,46,.25);color:var(--cb-gold) !important; }

    /* Tables */
    .w-table { width:100%;border-collapse:collapse; }
    .w-table th { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);padding:10px 12px;border-bottom:1px solid var(--cb-border);text-align:right; }
    .w-table th:first-child,.w-table th:nth-child(2) { text-align:left; }
    .w-table td { padding:12px 12px;border-bottom:1px solid rgba(15,92,67,.05);font-size:13px;color:var(--cb-muted);text-align:right; }
    .w-table td:first-child { text-align:left;font-family:'Syne',sans-serif;font-weight:700;color:var(--cb-ink); }
    .w-table td:nth-child(2) { text-align:left;color:var(--cb-muted); }
    .w-table tr:last-child td { border-bottom:none; }
    .w-table tr:hover td { background:rgba(176,134,46,.03); }

    /* Badges tx */
    .tx-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border-radius:100px; }
    .tx-topup { background:rgba(15,92,67,.08);color:var(--cb-forest);border:1px solid rgba(15,92,67,.2); }
    .tx-buy   { background:rgba(176,134,46,.1);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }
    .tx-sell  { background:rgba(192,57,43,.08);color:var(--cb-down);border:1px solid rgba(192,57,43,.2); }
    .tx-other { background:rgba(15,92,67,.04);color:var(--cb-muted);border:1px solid var(--cb-border); }

    /* P/L colors */
    .pl-up { color:var(--cb-up);font-weight:700; }
    .pl-dn { color:var(--cb-down);font-weight:700; }
    .amt-up { color:var(--cb-forest);font-weight:700; }
    .amt-dn { color:var(--cb-down);font-weight:700; }

    /* Empty rows */
    .w-empty { text-align:center;padding:32px;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-muted); }

    /* Note */
    .wallet-note { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(108,114,105,.5);margin-top:14px; }

    /* Modal */
    .modal-content { background:var(--cb-card);border:1px solid rgba(176,134,46,.15);border-radius:6px; }
    .modal-header { background:var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:16px 22px; }
    .modal-title { font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-ink); }
    .modal-body { color:var(--cb-muted);font-size:13.5px;line-height:1.75; }
    .modal-body h6 { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-gold);margin-bottom:10px;margin-top:0; }
    .modal-body ul { padding-left:18px;margin-bottom:0; }
    .modal-body li { margin-bottom:5px; }
    .modal-body b { color:var(--cb-ink); }
    .modal-body .alert-light { background:rgba(15,92,67,.04);border:1px solid var(--cb-border);color:var(--cb-muted); }
    .modal-body .alert-warning { background:rgba(176,134,46,.06);border:1px solid rgba(176,134,46,.15);color:var(--cb-gold); }
    .modal-footer { border-top:1px solid var(--cb-border);padding:14px 20px; }
    .btn-close { filter:none;opacity:.5; }
    .btn-secondary { background:var(--cb-paper);border:1px solid var(--cb-border);color:var(--cb-ink);font-family:'Syne',sans-serif;font-size:12px;font-weight:600;padding:8px 18px;border-radius:3px; }
    .btn-secondary:hover { background:rgba(15,92,67,.04);color:var(--cb-ink); }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; }
    .cbr3 { transition-delay:.16s; }
</style>
@endpush

@section('content')
<div class="wallet-page">

    {{-- Hero --}}
    <div class="wallet-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <p class="wallet-hero-tag">Mon espace</p>
                    <h1 class="wallet-hero-title">💼 Mon portefeuille virtuel</h1>
                    <p class="wallet-hero-sub">Achats / ventes au cours BRVM réel — simulation pédagogique.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="cb-btn-modal" data-bs-toggle="modal" data-bs-target="#walletHelpModal">
                        ❓ Aide
                    </button>
                    <a href="{{ route('dashboard') }}" class="cb-btn-outline" style="font-size:11px;padding:8px 14px;">
                        ← Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width:1100px;">

        {{-- Flash --}}
        @if(session('success'))
            <div class="wallet-alert-ok cbr">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="wallet-alert-err cbr">⚠️ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="wallet-alert-err cbr">
                <strong>Erreurs :</strong>
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- KPIs --}}
        <div class="kpi-grid cbr">
            <div class="kpi-card" style="--kpi-color:var(--cb-gold);">
                <div class="kpi-label">Solde cash</div>
                <div class="kpi-value">{{ number_format($wallet->balance ?? 0, 0, ',', ' ') }}</div>
                <div class="kpi-hint">FCFA disponibles</div>
            </div>
            <div class="kpi-card" style="--kpi-color:#2B7FC4;">
                <div class="kpi-label">Valeur positions</div>
                <div class="kpi-value">{{ number_format($totalValue ?? 0, 0, ',', ' ') }}</div>
                <div class="kpi-hint">Titres au cours du jour</div>
            </div>
            <div class="kpi-card" style="--kpi-color:var(--cb-forest);">
                <div class="kpi-label">Valeur nette</div>
                <div class="kpi-value">{{ number_format($netWorth ?? 0, 0, ',', ' ') }}</div>
                <div class="kpi-hint">Cash + positions</div>
            </div>
        </div>

        {{-- Recharge + Acheter --}}
        <div class="row g-3 mb-4 cbr cbr2">
            {{-- Recharge --}}
            <div class="col-lg-4">
                <div class="wallet-card h-100">
                    <div class="wallet-card-header">
                        <div class="wallet-card-title">➕ Recharger</div>
                    </div>
                    <div class="wallet-card-body">
                        <p style="font-size:13px;color:var(--cb-muted);margin-bottom:10px;">
                            Ajoute du cash virtuel pour tester des achats.
                        </p>
                        <div style="background:rgba(201,168,76,.07);border:1px solid rgba(201,168,76,.2);border-radius:3px;padding:8px 12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <span style="font-size:16px;">💡</span>
                            <span style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:var(--cb-gold);letter-spacing:.04em;">
                                1 000 F réels = 100 000 F virtuels
                            </span>
                        </div>
                        <form method="POST" action="{{ route('wallet.topup.confirm') }}" class="d-flex gap-2">
                            @csrf
                            <input type="number" name="amount_paid"
                                   class="w-input form-control flex-1"
                                   min="1000" step="500" value="1000" required>
                            <button type="submit" class="cb-btn-green">Recharger</button>
                        </form>
                        <div class="w-hint">Ex : 1 000 F → vous recevez 100 000 F virtuels</div>
                    </div>
                </div>
            </div>

            {{-- Acheter --}}
            <div class="col-lg-8">
                <div class="wallet-card h-100">
                    <div class="wallet-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="wallet-card-title">🛒 Acheter</div>
                            <span style="font-size:11px;color:var(--cb-muted);font-family:'Syne',sans-serif;">Récap + frais SGI avant confirmation</span>
                        </div>
                    </div>
                    <div class="wallet-card-body">
                        <form method="POST" action="{{ route('wallet.buy.recap') }}"
                              class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-6">
                                <label class="w-label">Action (ticker)</label>
                                <select name="ticker" class="w-input form-select" required>
                                    <option value="" disabled @selected(empty($preSelectedTicker ?? ''))>Choisir…</option>
                                    @foreach(($market ?? []) as $s)
                                        @php
                                            $t = $s['ticker'] ?? '';
                                            $n = $s['name'] ?? '';
                                            $p = $s['buy_price'] ?? ($s['close'] ?? null);
                                        @endphp
                                        <option value="{{ $t }}" @selected($t === ($preSelectedTicker ?? ''))>
                                            {{ $t }} — {{ $n }}
                                            @if(!is_null($p))
                                                ({{ number_format((float)$p,0,',',' ') }} F)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="w-label">Quantité</label>
                                <input type="number" name="qty" min="1" step="1" value="1"
                                       class="w-input form-control" required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="cb-btn-gold">Continuer →</button>
                            </div>
                        </form>
                        <div class="w-hint">💡 Commence avec 1 ou 2 titres pour tester.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Positions --}}
        <div class="wallet-card cbr cbr2">
            <div class="wallet-card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="wallet-card-title">📌 Mes positions</div>
                    <span style="font-size:11px;color:var(--cb-muted);font-family:'Syne',sans-serif;">P/L = (cours − PRU) × quantité</span>
                </div>
            </div>
            <div class="wallet-card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="w-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Ticker</th>
                                <th style="text-align:left;">Nom</th>
                                <th>Qté</th>
                                <th>PRU</th>
                                <th>Cours</th>
                                <th>Valeur</th>
                                <th>P/L</th>
                                <th>Vendre</th>
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
                                    <td>{{ $p['ticker'] }}</td>
                                    <td>{{ $p['name'] ?? '' }}</td>
                                    <td>{{ number_format($qty,0,',',' ') }}</td>
                                    <td>{{ number_format($avg,0,',',' ') }}</td>
                                    <td>
                                        @if($price > 0)
                                            {{ number_format($price,0,',',' ') }}
                                        @else
                                            <span style="color:var(--cb-muted);">n/a</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($value,0,',',' ') }}</td>
                                    <td>
                                        @if($price > 0)
                                            <span class="{{ $pl >= 0 ? 'pl-up' : 'pl-dn' }}">
                                                {{ $pl >= 0 ? '+' : '' }}{{ number_format($pl,0,',',' ') }}
                                            </span>
                                        @else
                                            <span style="color:var(--cb-muted);">n/a</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('wallet.sell.recap') }}"
                                              class="d-flex justify-content-end gap-2 align-items-center">
                                            @csrf
                                            <input type="hidden" name="ticker" value="{{ $p['ticker'] }}">
                                            <input type="number" name="qty"
                                                   min="1" max="{{ $qty }}" step="1" value="1"
                                                   class="w-input form-control form-control-sm"
                                                   style="width:80px;" required>
                                            <button type="submit" class="cb-btn-red">Vendre</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="w-empty">
                                        Aucune position — fais un premier achat 👆
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Historique --}}
        <div class="wallet-card cbr cbr3">
            <div class="wallet-card-header">
                <div class="wallet-card-title">🧾 Historique des mouvements</div>
            </div>
            <div class="wallet-card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="w-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Date</th>
                                <th style="text-align:left;">Type</th>
                                <th style="text-align:left;">Ticker</th>
                                <th>Qté</th>
                                <th>Prix</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($history ?? []) as $tx)
                                @php
                                    $type = strtolower((string)($tx->type ?? ''));
                                    $badgeClass = match($type) {
                                        'topup' => 'tx-topup',
                                        'buy'   => 'tx-buy',
                                        'sell'  => 'tx-sell',
                                        default => 'tx-other',
                                    };
                                    $q   = $tx->quantity ?? $tx->qty ?? null;
                                    $amt = (float)($tx->amount ?? 0);
                                @endphp
                                <tr>
                                    <td style="text-align:left;color:var(--cb-muted);font-size:12px;">
                                        {{ optional($tx->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="text-align:left;">
                                        <span class="tx-badge {{ $badgeClass }}">
                                            {{ strtoupper($type ?: '—') }}
                                        </span>
                                    </td>
                                    <td style="text-align:left;">{{ $tx->ticker ?? '—' }}</td>
                                    <td>{{ $q ? number_format((int)$q,0,',',' ') : '—' }}</td>
                                    <td>{{ $tx->price ? number_format((float)$tx->price,0,',',' ') : '—' }}</td>
                                    <td>
                                        <span class="{{ $amt >= 0 ? 'amt-up' : 'amt-dn' }}">
                                            {{ $amt >= 0 ? '+' : '' }}{{ number_format($amt,0,',',' ') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="w-empty">
                                        Aucun mouvement pour l'instant.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="wallet-note" style="padding:12px 20px;">
                    Note : portefeuille pédagogique virtuel — aucun vrai argent impliqué.
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Aide --}}
<div class="modal fade" id="walletHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">❓ Aide — Portefeuille Virtuel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <div class="mb-4">
                    <h6>📊 Comprendre les indicateurs</h6>
                    <ul>
                        <li><b>Solde cash</b> : argent disponible pour acheter.</li>
                        <li><b>Valeur positions</b> : Σ cours × quantité pour chaque titre.</li>
                        <li><b>Valeur nette</b> : Cash + Valeur positions.</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>🧾 Table "Positions"</h6>
                    <ul>
                        <li><b>PRU</b> : Prix de Revient Unitaire (coût moyen pondéré).</li>
                        <li><b>Cours</b> : prix actuel du marché.</li>
                        <li><b>Valeur</b> : Qté × Cours.</li>
                        <li><b>P/L</b> : (Cours − PRU) × Qté (plus/moins-value latente).</li>
                    </ul>
                    <div class="alert-light p-3 rounded mt-2" style="font-size:13px;">
                        <b>Formules :</b><br>
                        <span style="color:var(--cb-muted);">Valeur = Quantité × Cours<br>P&L = (Cours − PRU) × Quantité</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h6>🟢 Achat & Renforcement</h6>
                    <ul>
                        <li>Un <b>achat</b> débite : (prix × quantité) + frais SGI.</li>
                        <li><b>PRU recalculé</b> = (Ancien Qté × Ancien PRU + Nouvelle Qté × Prix) ÷ Total Qté.</li>
                        <li>Renforcer à prix bas → PRU baisse ("moyenner à la baisse").</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>🔴 Vente</h6>
                    <ul>
                        <li>Cash crédité = (prix × quantité) − frais SGI.</li>
                        <li>Plus-value réalisée = (Prix vente − PRU) × Quantité vendue.</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>💸 Frais SGI</h6>
                    <ul>
                        <li>Appliqués à chaque achat ET vente.</li>
                        <li>Formule : <b>frais = max(montant × taux, minimum)</b>.</li>
                        <li>Un récapitulatif est affiché avant chaque confirmation.</li>
                    </ul>
                    <div class="alert-warning p-3 rounded mt-2" style="font-size:13px;">
                        💡 Astuce : si ton cash "bouge trop", pense aux frais (surtout sur petits montants).
                    </div>
                </div>

                <div>
                    <h6>🧾 Historique</h6>
                    <ul>
                        <li>Chaque mouvement est enregistré (topup, buy, sell).</li>
                        <li>Utilise-le pour analyser tes décisions et affiner ta stratégie.</li>
                    </ul>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, { threshold: .06 }).observe(el);
    });
</script>
@endpush
