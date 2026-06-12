@extends('layouts.app')

@push('styles')
<style>
    .pvs-page{background:var(--cb-paper);min-height:100vh;display:flex;align-items:center;padding:48px 0;}
    .pvs-card{background:var(--cb-card);border:1px solid rgba(176,134,46,.18);border-radius:6px;padding:48px 40px;max-width:540px;margin:0 auto;text-align:center;}
    .pvs-icon{font-size:56px;margin-bottom:20px;display:block;}
    .pvs-title{font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,32px);font-weight:700;color:var(--cb-ink);margin-bottom:12px;line-height:1.25;}
    .pvs-sub{font-size:14px;color:var(--cb-muted);line-height:1.7;margin-bottom:32px;}
    .pvs-badge{display:inline-block;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#fff;background:var(--cb-forest);border-radius:2px;padding:2px 9px;margin-bottom:20px;}
    .pvs-cta{background:linear-gradient(135deg,var(--cb-gold),#9B6B15);color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:13px 28px;border:none;border-radius:3px;cursor:pointer;transition:opacity .2s;display:inline-flex;align-items:center;gap:8px;text-decoration:none;margin-bottom:12px;}
    .pvs-cta:hover{opacity:.85;}
    .pvs-link{display:block;font-size:12px;color:var(--cb-muted);text-decoration:none;margin-top:14px;}
    .pvs-link:hover{color:var(--cb-gold);}
    .pvs-product{background:rgba(176,134,46,.06);border:1px solid rgba(176,134,46,.15);border-radius:4px;padding:16px 20px;margin-bottom:28px;text-align:left;}
    .pvs-product-name{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:var(--cb-gold);margin-bottom:4px;}
    .pvs-product-desc{font-size:12px;color:var(--cb-muted);line-height:1.5;}
</style>
@endpush

@section('content')
<div class="pvs-page">
    <div class="container" style="max-width:580px;">
        <div class="pvs-card">
            <span class="pvs-badge">Cadeau activé</span>
            <span class="pvs-icon">🎉</span>
            <h1 class="pvs-title">Ton cadeau est prêt !</h1>
            <p class="pvs-sub">
                🎉 Tu as reçu 50 000 FCFA virtuels dans ton portefeuille ! Découvre le simulateur BRVM et commence à investir sans risque.
            </p>

            <a href="{{ route('wallet.index') }}" class="pvs-cta">
                Découvrir le simulateur →
            </a>

            <a href="{{ route('dashboard') }}" class="pvs-link">← Retour au dashboard</a>
        </div>
    </div>
</div>
@endsection
