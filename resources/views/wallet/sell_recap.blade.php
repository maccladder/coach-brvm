{{-- ════════════════════════════════════════════════
     resources/views/wallet/sell_recap.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .recap-page { background:#060910;min-height:100vh; }
    .recap-header { background:#0C1120;border-bottom:1px solid rgba(255,107,107,.1);padding:28px 0; }
    .recap-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,32px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .recap-sub { font-size:13px;color:#6B7590; }

    .recap-card { background:#0C1120;border:1px solid rgba(255,107,107,.12);border-radius:4px;overflow:hidden;margin-top:28px;margin-bottom:48px; }
    .recap-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#FF6B6B,rgba(255,200,30,.5),transparent); }
    .recap-card-body { padding:28px 32px; }

    .recap-grid { display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px; }
    @media(max-width:600px){ .recap-grid{grid-template-columns:1fr 1fr;} }
    .recap-cell { background:#121A2C;border:1px solid rgba(255,255,255,.05);border-radius:3px;padding:14px 16px; }
    .recap-cell-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:6px; }
    .recap-cell-value { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0; }

    .recap-row { display:flex;justify-content:space-between;align-items:baseline;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04); }
    .recap-row:last-of-type { border-bottom:none; }
    .recap-row-label { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }
    .recap-row-value { font-size:14px;color:#E8EAF0; }
    .recap-hint { font-size:12px;color:#6B7590;margin-top:3px; }

    .recap-net-box { background:rgba(15,207,164,.05);border:1px solid rgba(15,207,164,.15);border-radius:3px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px; }
    .recap-net-label { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#0FCFA4; }
    .recap-net-value { font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#0FCFA4; }

    .recap-divider { height:1px;background:rgba(255,255,255,.06);margin:16px 0; }
    .recap-actions { display:flex;gap:10px;margin-top:24px;flex-wrap:wrap; }

    .cb-btn-sell { display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#FF6B6B,#cc3333);color:#fff !important;font-family:'Syne',sans-serif;font-weight:800;font-size:13px;letter-spacing:.08em;text-transform:uppercase;padding:14px 32px;border:none;border-radius:3px;cursor:pointer;transition:all .3s;flex:1; }
    .cb-btn-sell:hover { box-shadow:0 8px 24px rgba(255,107,107,.3);transform:translateY(-1px); }
    .cb-btn-back { display:inline-flex;align-items:center;justify-content:center;gap:8px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:13px 20px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-back:hover { border-color:rgba(255,255,255,.2);color:#E8EAF0 !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="recap-page">
    <div class="recap-header">
        <div class="container" style="max-width:700px;">
            <a href="{{ route('wallet.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">← Portefeuille</a>
            <h1 class="recap-title">📉 Récapitulatif de vente</h1>
            <p class="recap-sub">Vérifie les montants avant de confirmer.</p>
        </div>
    </div>

    <div class="container" style="max-width:700px;">
        <div class="recap-card cbr">
            <div class="recap-card-body">

                {{-- Infos ticker --}}
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div style="font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#FF6B6B;line-height:1;">{{ $ticker }}</div>
                        <div style="font-size:13px;color:#6B7590;margin-top:2px;">{{ $name }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:4px;">Quantité détenue</div>
                        <div style="font-size:16px;color:#E8EAF0;font-weight:600;">{{ number_format($ownedQty,0,',',' ') }} titre(s)</div>
                    </div>
                </div>

                {{-- Grid 3 colonnes --}}
                <div class="recap-grid">
                    <div class="recap-cell">
                        <div class="recap-cell-label">À vendre</div>
                        <div class="recap-cell-value">{{ number_format($qty,0,',',' ') }}</div>
                    </div>
                    <div class="recap-cell">
                        <div class="recap-cell-label">Cours (close)</div>
                        <div class="recap-cell-value" style="font-size:17px;">{{ number_format($price,0,',',' ') }} F</div>
                    </div>
                    <div class="recap-cell">
                        <div class="recap-cell-label">Montant brut</div>
                        <div class="recap-cell-value" style="font-size:17px;">{{ number_format($grossAmount,0,',',' ') }} F</div>
                    </div>
                </div>

                {{-- Frais --}}
                <div class="recap-row">
                    <div>
                        <span class="recap-row-label">Frais SGI</span>
                        <div class="recap-hint">Taux {{ $rate * 100 }}% · Min {{ number_format($min,0,',',' ') }} F</div>
                    </div>
                    <span class="recap-row-value" style="color:#FF6B6B;">− {{ number_format($fee,0,',',' ') }} FCFA</span>
                </div>

                {{-- Net --}}
                <div class="recap-net-box">
                    <span class="recap-net-label">Montant net crédité</span>
                    <span class="recap-net-value">{{ number_format($net,0,',',' ') }} F</span>
                </div>

                {{-- Actions --}}
                <div class="recap-actions">
                    <form method="POST" action="{{ route('wallet.sell') }}" style="flex:1;">
                        @csrf
                        <input type="hidden" name="ticker" value="{{ $ticker }}">
                        <input type="hidden" name="qty" value="{{ $qty }}">
                        <button type="submit" class="cb-btn-sell" style="width:100%;">
                            Confirmer la vente
                        </button>
                    </form>
                    <a href="{{ route('wallet.index') }}" class="cb-btn-back">← Annuler</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
