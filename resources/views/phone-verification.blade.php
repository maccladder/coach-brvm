@extends('layouts.app')

@push('styles')
<style>
    .pv-page{background:#060910;min-height:100vh;padding:48px 0 80px;}
    .pv-card{background:#0C1120;border:1px solid rgba(201,168,76,.15);border-radius:6px;padding:40px 36px;max-width:520px;margin:0 auto;}
    .pv-badge{display:inline-block;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#060910;background:#0FCFA4;border-radius:2px;padding:2px 9px;margin-bottom:16px;}
    .pv-title{font-family:'Playfair Display',serif;font-size:clamp(22px,4vw,30px);font-weight:700;color:#E8EAF0;margin-bottom:8px;line-height:1.25;}
    .pv-sub{font-size:13.5px;color:#6B7590;line-height:1.6;margin-bottom:32px;}
    .pv-label{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#C9A84C;margin-bottom:6px;display:block;}
    .pv-input{background:#060910;border:1px solid rgba(255,255,255,.12);border-radius:3px;color:#E8EAF0;font-size:14px;padding:11px 14px;width:100%;transition:border-color .2s;}
    .pv-input:focus{border-color:#C9A84C;outline:none;box-shadow:none;}
    .pv-input.is-invalid{border-color:#d97272;}
    .pv-error{font-size:12px;color:#d97272;margin-top:4px;}
    .pv-submit{background:#C9A84C;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:12px 24px;border:none;border-radius:3px;cursor:pointer;transition:opacity .2s;display:inline-flex;align-items:center;gap:8px;width:100%;justify-content:center;}
    .pv-submit:hover{opacity:.85;}
    .pv-submit:disabled{opacity:.5;cursor:not-allowed;}
    .pv-hint{font-size:12.5px;color:#6B7590;line-height:1.6;margin-bottom:24px;}
    .pv-divider{border-top:1px solid rgba(255,255,255,.06);margin:28px 0;}
    .pv-step-lbl{font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:14px;}
    .pv-step-lbl b{color:#C9A84C;}
    .pv-phone-preview{font-family:'Syne',sans-serif;font-size:12px;color:#C9A84C;margin-top:5px;min-height:18px;}
    .pv-verified{background:rgba(15,207,164,.08);border:1px solid rgba(15,207,164,.25);border-radius:4px;padding:16px 20px;color:#0FCFA4;font-size:13.5px;margin-bottom:24px;}
</style>
@endpush

@section('content')
<div class="pv-page">
    <div class="container" style="max-width:560px;">

        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;margin-bottom:32px;">
            ← Retour au dashboard
        </a>

        <div class="pv-card">
            <span class="pv-badge">Cadeau membre</span>
            <h1 class="pv-title">Vérifie ton numéro, reçois 50 000 FCFA virtuels 🎁</h1>
            <p class="pv-sub">
                @if($alreadyClaimed)
                    Ton numéro a déjà été vérifié. Tu peux le mettre à jour ci-dessous.
                @else
                    Saisis ton numéro WhatsApp et confirme par SMS. Tu recevras 50 000 FCFA virtuels dans ton portefeuille.
                @endif
            </p>

            @if($isVerified && $alreadyClaimed)
            <div class="pv-verified">
                ✅ Numéro vérifié : <strong>{{ $currentPhone }}</strong>
            </div>
            @endif

            {{-- ── Formulaire envoi OTP ── --}}
            @if(!session('otp_sent'))
            <div class="pv-step-lbl">Étape <b>1 / 2</b> — Ton numéro</div>
            <form method="POST" action="{{ route('phone.verify.send') }}" id="form-pv-send">
                @csrf
                <div class="d-flex gap-2 align-items-start mb-2">
                    <div style="flex:0 0 168px;">
                        <label class="pv-label" for="pv_country_code">Pays</label>
                        <select name="country_code" id="pv_country_code"
                            class="pv-input {{ $errors->hasAny(['phone','country_code']) ? 'is-invalid' : '' }}">
                            @foreach($countries as $code => $country)
                                <option value="{{ $code }}"
                                    data-digits="{{ $country['digits'] }}"
                                    data-prefix="{{ $country['prefix'] }}"
                                    {{ old('country_code', 'CI') === $code ? 'selected' : '' }}>
                                    {{ $country['prefix'] }} {{ $country['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-grow-1">
                        <label class="pv-label" for="pv_phone_local">Numéro local</label>
                        <input type="tel" name="phone_local" id="pv_phone_local"
                            value="{{ old('phone_local') }}"
                            placeholder="0102345678"
                            class="pv-input {{ $errors->has('phone_local') || $errors->has('phone') ? 'is-invalid' : '' }}"
                            inputmode="numeric" maxlength="12">
                        @error('phone_local')
                            <div class="pv-error">{{ $message }}</div>
                        @enderror
                        @error('phone')
                            <div class="pv-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="pv-phone-preview" id="pv-phone-preview"></div>
                <button type="submit" class="pv-submit mt-3">Envoyer le code SMS →</button>
            </form>

            {{-- ── Formulaire vérification OTP (après envoi) ── --}}
            @else
            <div class="pv-step-lbl">Étape <b>2 / 2</b> — Code reçu par SMS</div>
            <p class="pv-hint">Saisis le code à 6 chiffres reçu par SMS. Il expire après 10&nbsp;minutes.</p>
            <form method="POST" action="{{ route('phone.verify.confirm') }}">
                @csrf
                <label class="pv-label" for="pv_otp_code">Code SMS</label>
                <input type="text" name="otp_code" id="pv_otp_code"
                    value="{{ old('otp_code') }}"
                    placeholder="123456"
                    maxlength="6"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="pv-input {{ $errors->has('otp_code') ? 'is-invalid' : '' }}">
                @error('otp_code')
                    <div class="pv-error">{{ $message }}</div>
                @enderror
                <button type="submit" class="pv-submit mt-3">Vérifier et activer →</button>
            </form>
            <div class="pv-divider"></div>
            <form method="POST" action="{{ route('phone.verify.send') }}">
                @csrf
                <input type="hidden" name="country_code" value="{{ old('country_code', 'CI') }}">
                <input type="hidden" name="phone_local" value="{{ old('phone_local') }}">
                <button type="submit" style="background:none;border:none;padding:0;font-size:12px;color:#6B7590;cursor:pointer;">
                    ← Renvoyer le code ou changer de numéro
                </button>
            </form>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const countrySelect = document.getElementById('pv_country_code');
    const phoneInput    = document.getElementById('pv_phone_local');
    const preview       = document.getElementById('pv-phone-preview');
    if(!countrySelect||!phoneInput) return;

    function sync(){
        const opt=countrySelect.options[countrySelect.selectedIndex];
        phoneInput.maxLength=parseInt(opt.dataset.digits,10);
        phoneInput.placeholder='0'.repeat(opt.dataset.digits);
        if(preview) preview.textContent=phoneInput.value.trim()?(opt.dataset.prefix+phoneInput.value.trim()):'';
    }
    countrySelect.addEventListener('change',sync);
    phoneInput.addEventListener('input',function(){ this.value=this.value.replace(/\D/g,''); sync(); });
    sync();
})();
</script>
@endpush
