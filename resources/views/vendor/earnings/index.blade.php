{{-- ════════════════════════════════════════════════
     resources/views/vendor/earnings.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .vd-page { background:#060910;min-height:100vh; }
    .vd-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:36px 0 28px; }
    .vd-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .vd-hero-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .vd-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0; }
    .vd-hero-sub { font-size:13px;color:#6B7590;margin-top:4px; }

    .vd-kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px; }
    @media(max-width:768px){ .vd-kpi-grid{grid-template-columns:1fr 1fr;} }
    .vd-kpi { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:18px 20px;position:relative;overflow:hidden;transition:border-color .25s; }
    .vd-kpi::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--kc),transparent); }
    .vd-kpi:hover { border-color:rgba(201,168,76,.15); }
    .vd-kpi-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;margin-bottom:8px; }
    .vd-kpi-value { font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,28px);font-weight:900;color:var(--kc);line-height:1;margin-bottom:4px; }
    .vd-kpi-sub { font-size:11px;color:#6B7590;line-height:1.6; }

    .vd-section { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;margin-bottom:20px; }
    .vd-section-header { background:#121A2C;border-bottom:1px solid rgba(255,255,255,.05);padding:14px 20px;display:flex;justify-content:space-between;align-items:center; }
    .vd-section-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:8px; }
    .vd-section-body { padding:24px; }

    /* Reversement form */
    .vd-input,.vd-select { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:10px 14px !important;width:100%;outline:none;transition:border-color .25s; }
    .vd-input:focus,.vd-select:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .vd-input::placeholder { color:#6B7590 !important; }
    .vd-input:disabled,.vd-select:disabled { opacity:.4;cursor:default; }
    .vd-select option { background:#0C1120; }
    .vd-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;display:block;margin-bottom:6px; }
    .vd-help { font-size:12px;color:#6B7590;margin-top:5px; }

    /* min badge */
    .vd-min-badge { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 10px;border-radius:100px;background:rgba(255,255,255,.04);color:#9AA3B8;border:1px solid rgba(255,255,255,.1); }

    /* Locks box */
    .vd-locked-box { background:rgba(255,200,30,.04);border:1px solid rgba(255,200,30,.12);border-radius:3px;padding:12px 16px;font-size:13px;color:#9AA3B8;line-height:1.7; }
    .vd-locked-box strong { color:#FFC850; }

    /* Status note */
    .vd-status-ok  { font-size:13px;color:#0FCFA4; }
    .vd-status-no  { font-size:13px;color:#FF6B6B; }

    /* Table */
    .vd-table { width:100%;border-collapse:collapse; }
    .vd-table th { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap; }
    .vd-table td { padding:13px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;color:#9AA3B8;vertical-align:middle; }
    .vd-table tr:last-child td { border-bottom:none; }
    .vd-table tr:hover td { background:rgba(201,168,76,.02); }
    .vd-table td:first-child { color:#E8EAF0; }
    .vd-table-footer { padding:14px 20px;border-top:1px solid rgba(255,255,255,.05); }

    /* payout badges */
    .po-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:100px; }
    .po-pending  { background:rgba(255,200,30,.08);color:#FFC850;border:1px solid rgba(255,200,30,.2); }
    .po-approved { background:rgba(99,179,237,.08);color:#63B3ED;border:1px solid rgba(99,179,237,.2); }
    .po-paid     { background:rgba(15,207,164,.08);color:#0FCFA4;border:1px solid rgba(15,207,164,.2); }
    .po-rejected { background:rgba(255,107,107,.08);color:#FF6B6B;border:1px solid rgba(255,107,107,.2); }

    .vd-price { font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#C9A84C; }
    .vd-empty { text-align:center;padding:36px;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    /* Flash */
    .vd-alert-ok  { background:rgba(15,207,164,.07);border:1px solid rgba(15,207,164,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:#0FCFA4;margin-bottom:16px; }
    .vd-alert-err { background:rgba(255,107,107,.07);border:1px solid rgba(255,107,107,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:#FF6B6B;margin-bottom:16px; }
    .vd-alert-err ul { margin:6px 0 0 16px;padding:0; }
    .vd-alert-warn { background:rgba(255,200,30,.06);border:1px solid rgba(255,200,30,.15);border-radius:3px;padding:12px 16px;font-size:13px;color:#FFC850;margin-bottom:16px; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-gold:disabled { opacity:.35;transform:none;box-shadow:none;cursor:default; }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    /* Pagination */
    .pagination .page-link { background:#0C1120 !important;border-color:rgba(255,255,255,.08) !important;color:#6B7590 !important;font-family:'Syne',sans-serif;font-size:12px; }
    .pagination .page-link:hover { background:rgba(201,168,76,.1) !important;color:#C9A84C !important;border-color:rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,#C9A84C,#9B6B15) !important;border-color:transparent !important;color:#050810 !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; } .cbr3 { transition-delay:.16s; }
</style>
@endpush

@section('content')
@php
    $available    = (int)($totals['available'] ?? 0);
    $minPayout    = (int)($totals['min_payout'] ?? 10000);
    $canRequest   = $available >= $minPayout;
    $missingToMin = max(0, $minPayout - $available);
    $lockedNet    = (int)($totals['locked_net_72h'] ?? 0);
    $delayH       = $totals['delay_hours'] ?? 72;
@endphp
<div class="vd-page">

    <div class="vd-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="vd-hero-tag">Vendeur</p>
                    <h1 class="vd-hero-title">💰 Mes revenus</h1>
                    <p class="vd-hero-sub">Commission Boursiv : <strong style="color:#FF6B6B;">15%</strong> · Délai sécurité : <strong style="color:#FFC850;">{{ $delayH }}h</strong></p>
                </div>
                <a href="{{ route('vendor.dashboard') }}" class="cb-btn-outline">← Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        {{-- KPIs --}}
        <div class="vd-kpi-grid cbr">
            <div class="vd-kpi" style="--kc:#C9A84C;">
                <div class="vd-kpi-label">💵 Brut encaissé</div>
                <div class="vd-kpi-value">{{ number_format((int)($totals['gross_total']??0),0,',',' ') }}</div>
                <div class="vd-kpi-sub">FCFA total ventes</div>
            </div>
            <div class="vd-kpi" style="--kc:#FF6B6B;">
                <div class="vd-kpi-label">🏦 Commission 15%</div>
                <div class="vd-kpi-value">{{ number_format((int)($totals['fee_total']??0),0,',',' ') }}</div>
                <div class="vd-kpi-sub">FCFA prélevés</div>
            </div>
            <div class="vd-kpi" style="--kc:#0FCFA4;">
                <div class="vd-kpi-label">✅ Net théorique</div>
                <div class="vd-kpi-value">{{ number_format((int)($totals['net_total']??0),0,',',' ') }}</div>
                <div class="vd-kpi-sub">FCFA (visible immédiatement)</div>
            </div>
            <div class="vd-kpi" style="--kc:#63B3ED;">
                <div class="vd-kpi-label">🟢 Retirable</div>
                <div class="vd-kpi-value">{{ number_format($available,0,',',' ') }}</div>
                <div class="vd-kpi-sub">
                    En attente ({{ $delayH }}h) :
                    <strong style="color:#FFC850;">{{ number_format($lockedNet,0,',',' ') }} F</strong>
                    @if(!empty($totals['next_unlock_at']))
                        <div>Déblocage : {{ \Carbon\Carbon::parse($totals['next_unlock_at'])->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Reversement --}}
        <div class="vd-section cbr cbr2">
            <div class="vd-section-header">
                <div class="vd-section-title">📤 Demander un reversement</div>
                <span class="vd-min-badge">Min : {{ number_format($minPayout,0,',',' ') }} F</span>
            </div>
            <div class="vd-section-body">

                @if(session('success')) <div class="vd-alert-ok">✅ {{ session('success') }}</div> @endif
                @if(session('error'))   <div class="vd-alert-err">⚠️ {{ session('error') }}</div>   @endif
                @if(session('warning')) <div class="vd-alert-warn">⚠️ {{ session('warning') }}</div> @endif
                @if($errors->any())
                    <div class="vd-alert-err">
                        <strong>Erreurs :</strong>
                        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('vendor.payouts.request') }}"
                      class="row g-3 align-items-end">
                    @csrf

                    <div class="col-md-4">
                        <label class="vd-label">Montant à demander</label>
                        <input type="number" name="amount"
                               min="{{ $minPayout }}" max="{{ max($available,$minPayout) }}"
                               value="{{ old('amount',$minPayout) }}"
                               class="vd-input form-control"
                               {{ $canRequest ? '' : 'disabled' }} required>
                        <div class="vd-help">Retirable : <strong style="color:#E8EAF0;">{{ number_format($available,0,',',' ') }} FCFA</strong></div>
                    </div>

                    <div class="col-md-4">
                        <label class="vd-label">Méthode</label>
                        <select name="payout_method" class="vd-select form-select"
                                {{ $canRequest ? '' : 'disabled' }} required>
                            <option value="wave" selected>Wave</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="vd-label">Numéro Wave</label>
                        <input type="tel" name="payout_phone"
                               value="{{ old('payout_phone') }}"
                               class="vd-input form-control"
                               placeholder="Ex: 07xxxxxxxx"
                               {{ $canRequest ? '' : 'disabled' }} required>
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            @if($canRequest)
                                <span class="vd-status-ok">✅ Tu peux demander un reversement maintenant.</span>
                            @else
                                <span class="vd-status-no">
                                    🔒 Reversement indisponible.
                                    @if($available < $minPayout)
                                        Il te manque <strong>{{ number_format($missingToMin,0,',',' ') }} FCFA</strong> pour atteindre le minimum.
                                    @endif
                                    @if($lockedNet > 0)
                                        En attente ({{ $delayH }}h) : <strong>{{ number_format($lockedNet,0,',',' ') }} FCFA</strong>.
                                    @endif
                                </span>
                            @endif
                        </div>
                        <button type="submit" class="cb-btn-gold" {{ $canRequest ? '' : 'disabled' }}>
                            <i class="bi bi-send"></i> Envoyer la demande
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- Historique reversements --}}
        <div class="vd-section cbr cbr3">
            <div class="vd-section-header">
                <div class="vd-section-title">🧾 Historique des demandes</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="vd-table">
                    <thead>
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
                                $pocls = match($p->status) {
                                    'pending'  => 'po-pending',
                                    'approved' => 'po-approved',
                                    'paid'     => 'po-paid',
                                    'rejected' => 'po-rejected',
                                    default    => 'po-pending',
                                };
                                $poico = match($p->status) {
                                    'pending'=>'⏳','approved'=>'🔵','paid'=>'✅','rejected'=>'❌',default=>'—'
                                };
                            @endphp
                            <tr>
                                <td style="font-size:12px;color:#6B7590;">{{ optional($p->requested_at ?? $p->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="vd-price">{{ number_format((int)$p->amount,0,',',' ') }}</span> <span style="font-size:11px;color:#6B7590;">F</span></td>
                                <td style="font-size:12px;color:#9AA3B8;">{{ strtoupper((string)$p->payout_method) }}</td>
                                <td style="font-size:12px;color:#9AA3B8;">{{ $p->payout_account }}</td>
                                <td><span class="po-badge {{ $pocls }}">{{ $poico }} {{ $p->status }}</span></td>
                                <td style="font-size:12px;color:#6B7590;max-width:240px;">{{ $p->admin_note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="vd-empty">Aucune demande pour l'instant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payouts->hasPages())
                <div class="vd-table-footer d-flex justify-content-center">
                    {{ $payouts->links() }}
                </div>
            @endif
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
