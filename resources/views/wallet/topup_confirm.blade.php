{{-- ════════════════════════════════════════════════
     resources/views/wallet/topup_confirm.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .topup-page { background:#060910;min-height:100vh; }
    .topup-header { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:28px 0; }
    .topup-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,32px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .topup-sub { font-size:13px;color:#6B7590; }

    .topup-card { background:#0C1120;border:1px solid rgba(201,168,76,.12);border-radius:4px;overflow:hidden;margin-top:28px;margin-bottom:48px; }
    .topup-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,#0FCFA4,transparent); }
    .topup-card-body { padding:28px 32px; }

    /* Résumé */
    .topup-summary { background:#121A2C;border:1px solid rgba(201,168,76,.1);border-radius:3px;padding:20px 24px;margin-bottom:24px; }
    .topup-summary-text { font-size:15px;color:#9AA3B8;line-height:1.75; }
    .topup-summary-text strong { color:#E8EAF0; }
    .topup-amount-paid { font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#C9A84C; }
    .topup-amount-virtual { font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#0FCFA4; }

    /* TX id */
    .topup-tx-box { background:rgba(15,207,164,.04);border:1px solid rgba(15,207,164,.1);border-radius:3px;padding:12px 16px;margin-bottom:24px;display:flex;align-items:center;gap:10px; }
    .topup-tx-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;flex-shrink:0; }
    .topup-tx-value { font-family:'DM Sans',sans-serif;font-size:13px;color:#0FCFA4;word-break:break-all; }

    /* Boutons paiement */
    .pay-btn { display:inline-flex;align-items:center;justify-content:center;gap:10px;font-family:'Syne',sans-serif;font-weight:800;font-size:13px;letter-spacing:.08em;text-transform:uppercase;padding:14px 28px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .pay-btn-cinetpay { background:linear-gradient(135deg,#0FCFA4,#0a9e7d);color:#050810 !important; }
    .pay-btn-cinetpay:hover { box-shadow:0 8px 24px rgba(15,207,164,.3);transform:translateY(-1px); }
    .pay-btn-paystack { background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important; }
    .pay-btn-paystack:hover { box-shadow:0 8px 24px rgba(201,168,76,.3);transform:translateY(-1px); }
    .pay-btn-cancel { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:13px 20px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .pay-btn-cancel:hover { border-color:rgba(255,255,255,.2);color:#E8EAF0 !important; }

    .pay-actions { display:flex;flex-wrap:wrap;gap:12px;align-items:center; }

    .topup-note { font-size:12px;color:#6B7590;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.05);font-family:'Syne',sans-serif;letter-spacing:.06em; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="topup-page">
    <div class="topup-header">
        <div class="container" style="max-width:700px;">
            <a href="{{ route('wallet.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">← Portefeuille</a>
            <h1 class="topup-title">✅ Confirmation du rechargement</h1>
            <p class="topup-sub">Choisis ton moyen de paiement pour continuer.</p>
        </div>
    </div>

    <div class="container" style="max-width:700px;">
        <div class="topup-card cbr">
            <div class="topup-card-body">

                {{-- Résumé montants --}}
                <div class="topup-summary">
                    <div class="topup-summary-text">
                        Tu es sur le point de payer
                    </div>
                    <div class="topup-amount-paid">{{ number_format($payment->amount_paid,0,',',' ') }} FCFA</div>
                    <div class="topup-summary-text" style="margin:8px 0;">
                        pour recevoir dans ton portefeuille virtuel
                    </div>
                    <div class="topup-amount-virtual">{{ number_format($payment->amount_virtual,0,',',' ') }} FCFA</div>
                </div>

                {{-- Transaction ID --}}
                <div class="topup-tx-box">
                    <span class="topup-tx-label">Transaction</span>
                    <span class="topup-tx-value">{{ $payment->transaction_id }}</span>
                </div>

                {{-- Boutons paiement --}}
                <div class="pay-actions">

                    <form method="POST" action="{{ route('wallet.topup.pay') }}">
                        @csrf
                        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                        <button type="submit" class="pay-btn pay-btn-cinetpay">
                            💳 CinetPay
                        </button>
                    </form>

                    <form method="POST" action="{{ route('wallet.topup.paystack') }}">
                        @csrf
                        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                        <button type="submit" class="pay-btn pay-btn-paystack">
                            🔐 Paystack
                        </button>
                    </form>

                    <a href="{{ route('wallet.index') }}" class="pay-btn-cancel">Annuler</a>
                </div>

                <div class="topup-note">
                    🔒 Paiement sécurisé · Aucun vrai argent ne sera investi — portefeuille pédagogique virtuel uniquement.
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
