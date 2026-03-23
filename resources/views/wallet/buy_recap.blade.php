{{-- ════════════════════════════════════════════════
     resources/views/wallet/buy_recap.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .recap-page { background:#060910;min-height:100vh; }
    .recap-header { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:28px 0; }
    .recap-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,32px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .recap-sub { font-size:13px;color:#6B7590; }

    .recap-card { background:#0C1120;border:1px solid rgba(201,168,76,.12);border-radius:4px;overflow:hidden;margin-top:28px;margin-bottom:48px; }
    .recap-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,rgba(15,207,164,.5),transparent); }
    .recap-card-body { padding:28px 32px; }

    .recap-row { display:flex;justify-content:space-between;align-items:baseline;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04); }
    .recap-row:last-of-type { border-bottom:none; }
    .recap-row-label { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }
    .recap-row-value { font-size:15px;color:#E8EAF0;font-weight:500; }
    .recap-row-value.gold { font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:#C9A84C; }
    .recap-row-value.muted { color:#6B7590;font-size:13px; }

    .recap-divider { height:1px;background:rgba(255,255,255,.06);margin:16px 0; }

    .recap-hint { font-size:12px;color:#6B7590;margin-top:4px; }

    .recap-actions { display:flex;flex-direction:column;gap:10px;margin-top:24px; }

    .cb-btn-confirm { display:flex;align-items:center;justify-content:center;gap:10px;width:100%;background:linear-gradient(135deg,#1fbf4a,#0f8a35);color:#fff !important;font-family:'Syne',sans-serif;font-weight:800;font-size:13px;letter-spacing:.08em;text-transform:uppercase;padding:15px 24px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-confirm:hover { box-shadow:0 8px 24px rgba(31,191,74,.3);transform:translateY(-1px); }

    .cb-btn-back { display:flex;align-items:center;justify-content:center;gap:8px;width:100%;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:12px 20px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-back:hover { border-color:rgba(255,255,255,.2);color:#E8EAF0 !important; }

    .recap-alert-err { background:rgba(255,107,107,.07);border:1px solid rgba(255,107,107,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:#FF6B6B;margin-bottom:20px; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="recap-page">
    <div class="recap-header">
        <div class="container" style="max-width:700px;">
            <a href="{{ route('wallet.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">← Portefeuille</a>
            <h1 class="recap-title">🛒 Récapitulatif d'achat</h1>
            <p class="recap-sub">Vérifie les montants avant de confirmer.</p>
        </div>
    </div>

    <div class="container" style="max-width:700px;">

        @if(session('error'))
            <div class="recap-alert-err cbr">⚠️ {{ session('error') }}</div>
        @endif

        <div class="recap-card cbr">
            <div class="recap-card-body">

                <div class="recap-row">
                    <span class="recap-row-label">Ticker</span>
                    <span class="recap-row-value" style="font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:#C9A84C;">{{ $ticker }}</span>
                </div>
                <div class="recap-row">
                    <span class="recap-row-label">Quantité</span>
                    <span class="recap-row-value">{{ number_format($qty,0,',',' ') }} titre(s)</span>
                </div>
                <div class="recap-row">
                    <span class="recap-row-label">Prix du jour</span>
                    <span class="recap-row-value">{{ number_format($price,0,',',' ') }} FCFA</span>
                </div>

                <div class="recap-divider"></div>

                <div class="recap-row">
                    <span class="recap-row-label">Montant brut</span>
                    <span class="recap-row-value">{{ number_format($grossAmount,0,',',' ') }} FCFA</span>
                </div>
                <div class="recap-row">
                    <div>
                        <span class="recap-row-label">Frais SGI</span>
                        <div class="recap-hint">Taux {{ $rate * 100 }}% · Min {{ number_format($min,0,',',' ') }} F</div>
                    </div>
                    <span class="recap-row-value muted">{{ number_format($fee,0,',',' ') }} FCFA</span>
                </div>

                <div class="recap-divider"></div>

                <div class="recap-row">
                    <span class="recap-row-label" style="color:#E8EAF0;font-size:13px;">Total à débiter</span>
                    <span class="recap-row-value gold">{{ number_format($total,0,',',' ') }} F</span>
                </div>

                <div class="recap-actions">
                    <form method="POST" action="{{ route('wallet.buy') }}">
                        @csrf
                        <input type="hidden" name="ticker" value="{{ $ticker }}">
                        <input type="hidden" name="qty" value="{{ $qty }}">
                        <button type="submit" class="cb-btn-confirm">
                            ✅ Confirmer l'achat
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
