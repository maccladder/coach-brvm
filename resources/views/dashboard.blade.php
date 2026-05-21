{{-- ════════ dashboard.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .dash-page { background:#060910;min-height:100vh; }
    .dash-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:36px 0 28px; }
    .dash-welcome { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#C9A84C;margin-bottom:8px; }
    .dash-name { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0; }

    .dash-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:24px;height:100%;transition:all .32s;position:relative;overflow:hidden; }
    .dash-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#C9A84C,transparent);opacity:0;transition:opacity .32s; }
    .dash-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.3); }
    .dash-card:hover::before { opacity:1; }
    .dash-card-icon { font-size:24px;margin-bottom:14px;display:block; }
    .dash-card-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:6px; }
    .dash-card-desc { font-size:12.5px;color:#6B7590;line-height:1.6;margin-bottom:18px; }
    .dash-card-btn { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 18px;border:none;border-radius:3px;text-decoration:none;transition:all .3s; }
    .dash-card-btn:hover { box-shadow:0 5px 16px rgba(201,168,76,.3);transform:translateY(-1px); }
    .dash-card-btn.disabled { background:rgba(255,255,255,.06);color:#6B7590 !important;cursor:default;pointer-events:none; }

    .dash-cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 16px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .dash-cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.05s; }
    .cbr3 { transition-delay:.1s; }
    .cbr4 { transition-delay:.15s; }
    .cbr5 { transition-delay:.2s; }

    /* ── OTP Banner ─── */
    .otp-banner-wrap{background:#0C1120;border-bottom:1px solid rgba(201,168,76,.15);}
    .otp-banner{display:flex;align-items:center;gap:24px;padding:18px 0;flex-wrap:wrap;}
    .otp-banner-text{flex:1;min-width:220px;}
    .otp-badge-cadeau{display:inline-block;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#060910;background:#0FCFA4;border-radius:2px;padding:2px 9px;margin-bottom:8px;}
    .otp-badge-deadline{display:inline-block;font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#C9A84C;border:1px solid rgba(201,168,76,.35);border-radius:2px;padding:2px 9px;margin-bottom:8px;margin-left:8px;}
    .otp-banner-title{font-family:'Playfair Display',serif;font-size:clamp(15px,2.2vw,20px);font-weight:700;color:#E8EAF0;margin-bottom:4px;line-height:1.3;}
    .otp-banner-sub{font-size:13px;color:#6B7590;line-height:1.5;}
    .otp-submit{background:#C9A84C;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;cursor:pointer;transition:opacity .2s;display:inline-flex;align-items:center;gap:8px;text-decoration:none;}
    .otp-submit:hover{opacity:.85;}
    .otp-submit:disabled{opacity:.5;cursor:not-allowed;}

    /* ── OTP Modal ─── */
    #otpModal .modal-content{background:#0C1120;border:1px solid rgba(201,168,76,.18);border-radius:6px;}
    #otpModal .modal-header{border-bottom:1px solid rgba(255,255,255,.06);padding:20px 24px 16px;}
    #otpModal .modal-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0;}
    #otpModal .modal-body{padding:24px;}
    #otpModal .btn-close{filter:invert(1);opacity:.4;}
    .otp-step{display:none;}
    .otp-step.active{display:block;}
    .otp-field{margin-bottom:16px;}
    .otp-field label{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#C9A84C;margin-bottom:6px;display:block;}
    .otp-field select,.otp-field input[type=text],.otp-field input[type=tel]{background:#060910;border:1px solid rgba(255,255,255,.12);border-radius:3px;color:#E8EAF0;font-size:14px;padding:10px 14px;width:100%;transition:border-color .2s;}
    .otp-field select:focus,.otp-field input:focus{border-color:#C9A84C;outline:none;box-shadow:none;}
    .otp-field .is-invalid{border-color:#d97272 !important;}
    .otp-field .invalid-feedback{display:block;font-size:12px;color:#d97272;margin-top:4px;}
    .otp-hint{font-size:12.5px;color:#6B7590;line-height:1.6;margin-bottom:20px;}
    .otp-step-lbl{font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:14px;}
    .otp-step-lbl b{color:#C9A84C;}
    .otp-phone-preview{font-family:'Syne',sans-serif;font-size:12px;color:#C9A84C;margin-top:5px;min-height:18px;}

    /* ── Bonus Banner Teal (Promo 2 — bonus wallet reçu) ─── */
    .bonus-granted-wrap{background:rgba(15,207,164,.04);border-bottom:1px solid rgba(15,207,164,.18);}
    .bonus-badge-granted{display:inline-block;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#060910;background:#0FCFA4;border-radius:2px;padding:2px 9px;margin-bottom:8px;}
    .bonus-submit-teal{display:inline-flex;align-items:center;gap:8px;background:#0FCFA4;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;text-decoration:none;transition:opacity .2s;}
    .bonus-submit-teal:hover{opacity:.85;}
</style>
@endpush

@section('content')
<div class="dash-page">
    <div class="dash-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="dash-welcome">Mon espace</div>
                    <h1 class="dash-name">Bienvenue, {{ auth()->user()->name }} 👋</h1>
                </div>
                <a href="{{ route('landing') }}" class="dash-cb-btn-outline">← Accueil</a>
            </div>
        </div>
    </div>

    @php
        $showOtpBanner = !auth()->user()->phone_reward_claimed
            && \Carbon\Carbon::parse(config('otp.reward_deadline'))->endOfDay()->isFuture();
        $otpDeadlineLabel = \Carbon\Carbon::parse(config('otp.reward_deadline'))
            ->locale('fr')->isoFormat('D MMMM');
    @endphp

    @php
        $_du = auth()->user();

        // Bannière A — bonus déjà reçu (teal) : flash immédiat ou dans les 7 derniers jours
        $showBonusGranted = !is_null($_du->wallet_bonus_claimed_at)
            && (session('wallet_bonus_just_granted')
                || $_du->wallet_bonus_claimed_at->gte(now()->subDays(7)));

        // Bannière B — CTA Segment B : éligible mais pas encore vérifié, dans la fenêtre promo
        $showBonusCta = is_null($_du->wallet_bonus_claimed_at)
            && is_null($_du->phone_verified_at)
            && $_du->created_at < \Carbon\Carbon::parse(config('otp.eligibility_cutoff'))
            && now()->gte(\Carbon\Carbon::parse(config('otp.wallet_bonus_start')))
            && now()->lte(\Carbon\Carbon::parse(config('otp.wallet_bonus_end')));

        $bonusAmount   = number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ');
        $bonusEndLabel = \Carbon\Carbon::parse(config('otp.wallet_bonus_end'))
            ->locale('fr')->isoFormat('D MMMM');
    @endphp
    @if($showOtpBanner)
    <div class="otp-banner-wrap">
        <div class="container" style="max-width:1100px;">
            <div class="otp-banner">
                <div class="otp-banner-text">
                    <div>
                        <span class="otp-badge-cadeau">Cadeau membre</span>
                        <span class="otp-badge-deadline">Jusqu'au {{ $otpDeadlineLabel }}</span>
                    </div>
                    <div class="otp-banner-title">Cours dans les rues d'Abidjan avec AYA et GBÉ 🎮</div>
                    <div class="otp-banner-sub">Reçois Abidjan Run gratuitement. Ajoute ton WhatsApp pour activer ton cadeau.</div>
                </div>
                <div class="flex-shrink-0">
                    <button class="otp-submit" data-bs-toggle="modal" data-bs-target="#otpModal">Activer mon cadeau →</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal vérification téléphone --}}
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">Activer mon cadeau 🎁</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    {{-- Étape 1 : numéro de téléphone --}}
                    <div class="otp-step" id="otp-step-1">
                        <div class="otp-step-lbl">Étape <b>1 / 2</b> — Ton numéro</div>
                        <p class="otp-hint">Saisis ton numéro WhatsApp. On t'envoie un code SMS pour confirmer que c'est bien toi.</p>
                        <form method="POST" action="{{ route('phone.verify.send') }}">
                            @csrf
                            <div class="d-flex gap-2 align-items-start">
                                <div class="otp-field" style="flex:0 0 168px;">
                                    <label for="otp_country_code">Pays</label>
                                    <select name="country_code" id="otp_country_code" class="{{ $errors->hasAny(['phone','country_code']) ? 'is-invalid' : '' }}">
                                        @foreach(config('otp.countries') as $code => $country)
                                            <option value="{{ $code }}"
                                                data-digits="{{ $country['digits'] }}"
                                                data-prefix="{{ $country['prefix'] }}"
                                                {{ old('country_code', 'CI') === $code ? 'selected' : '' }}>
                                                {{ $country['prefix'] }} {{ $country['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="otp-field flex-grow-1">
                                    <label for="otp_phone_local">Numéro local</label>
                                    <input type="tel" name="phone_local" id="otp_phone_local"
                                        value="{{ old('phone_local') }}"
                                        placeholder="0102345678"
                                        class="{{ $errors->has('phone_local') || $errors->has('phone') ? 'is-invalid' : '' }}"
                                        inputmode="numeric" maxlength="12">
                                    @error('phone_local')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="otp-phone-preview" id="otp-phone-preview"></div>
                            <button type="submit" class="otp-submit w-100 justify-content-center mt-3">
                                Envoyer le code SMS →
                            </button>
                        </form>
                    </div>

                    {{-- Étape 2 : code OTP --}}
                    <div class="otp-step" id="otp-step-2">
                        <div class="otp-step-lbl">Étape <b>2 / 2</b> — Code reçu par SMS</div>
                        <p class="otp-hint">Saisis le code à 6 chiffres reçu par SMS. Il expire après 10&nbsp;minutes.</p>
                        <form method="POST" action="{{ route('phone.verify.confirm') }}">
                            @csrf
                            <div class="otp-field">
                                <label for="otp_code">Code SMS</label>
                                <input type="text" name="otp_code" id="otp_code"
                                    value="{{ old('otp_code') }}"
                                    placeholder="123456"
                                    maxlength="6"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    class="{{ $errors->has('otp_code') ? 'is-invalid' : '' }}">
                                @error('otp_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="otp-submit w-100 justify-content-center mt-2">
                                Vérifier et activer →
                            </button>
                        </form>
                        <button type="button" class="btn btn-link p-0 mt-3 d-block"
                            style="font-size:12px;color:#6B7590;text-decoration:none;"
                            onclick="otpGoStep(1)">
                            ← Changer de numéro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Bannière A : bonus wallet reçu (Promo 2) ─────────────────── --}}
    @if($showBonusGranted)
    <div class="bonus-granted-wrap">
        <div class="container" style="max-width:1100px;">
            <div class="otp-banner">
                <div class="otp-banner-text">
                    <div>
                        <span class="bonus-badge-granted">🎉 Bonus activé</span>
                    </div>
                    <div class="otp-banner-title">{{ $bonusAmount }} FCFA virtuels ajoutés à ton portefeuille !</div>
                    <div class="otp-banner-sub">Ton bonus de bienvenue est disponible. Commence à simuler des investissements sur la BRVM — sans risquer un seul franc réel.</div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('wallet.index') }}" class="bonus-submit-teal">Découvrir le simulateur →</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Bannière B : CTA Segment B (non vérifié, éligible au bonus) ─ --}}
    @if($showBonusCta)
    <div class="otp-banner-wrap">
        <div class="container" style="max-width:1100px;">
            <div class="otp-banner">
                <div class="otp-banner-text">
                    <div>
                        <span class="otp-badge-cadeau">Cadeau membre</span>
                        <span class="otp-badge-deadline">Jusqu'au {{ $bonusEndLabel }}</span>
                    </div>
                    <div class="otp-banner-title">{{ $bonusAmount }} FCFA virtuels offerts sur le simulateur BRVM</div>
                    <div class="otp-banner-sub">Vérifie ton numéro de téléphone pour activer ton bonus. Découvre comment investir en bourse sans risquer ton argent.</div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('phone.verify.form') }}" class="otp-submit">Activer mon bonus →</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="container py-5" style="max-width:1100px;">
        <div class="row g-3">
            <div class="col-md-4 cbr">
                <div class="dash-card">
                    <span class="dash-card-icon">🎓</span>
                    <div class="dash-card-title">Mes cours</div>
                    <div class="dash-card-desc">Accès à tes formations payées. Continue là où tu t'es arrêté.</div>
                    <a href="{{ route('courses.my') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr2">
                <div class="dash-card">
                    <span class="dash-card-icon">🧾</span>
                    <div class="dash-card-title">Mes produits</div>
                    <div class="dash-card-desc">Livres PDF, logiciels et contenus achetés sur la Marketplace.</div>
                    <a href="{{ route('my.products') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr3">
                <div class="dash-card">
                    <span class="dash-card-icon">🎮</span>
                    <div class="dash-card-title">Mes jeux</div>
                    <div class="dash-card-desc">Jeux achetés sur la Marketplace. Joue directement dans ton navigateur.</div>
                    <a href="{{ route('my.products') }}?type=game" class="dash-card-btn">Jouer →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr3">
                <div class="dash-card">
                    <span class="dash-card-icon">💼</span>
                    <div class="dash-card-title">Mon portefeuille</div>
                    <div class="dash-card-desc">Solde virtuel, positions et historique de tes transactions.</div>
                    <a href="{{ route('wallet.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr2">
                <div class="dash-card">
                    <span class="dash-card-icon">📚</span>
                    <div class="dash-card-title">Mes documents</div>
                    <div class="dash-card-desc">Études de marché & business plans achetés.</div>
                    <a href="{{ route('documents.mine') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr3">
                <div class="dash-card">
                    <span class="dash-card-icon">📄</span>
                    <div class="dash-card-title">
                        Mes analyses
                        @if(!empty($myAnalysesCount) && $myAnalysesCount > 0)
                            <span style="font-size:.75rem;background:var(--cb-gold);color:#060910;border-radius:999px;padding:1px 8px;margin-left:6px;font-weight:700;">{{ $myAnalysesCount }}</span>
                        @endif
                    </div>
                    <div class="dash-card-desc">BOC & états financiers commandés et analysés par l'IA.</div>
                    <a href="{{ route('client-financials.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr4">
                <div class="dash-card">
                    <span class="dash-card-icon">📡</span>
                    <div class="dash-card-title">Radar Marché</div>
                    <div class="dash-card-desc">Performance 7 jours des sociétés cotées à la BRVM.</div>
                    <a href="{{ route('radar.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            @if(\App\Models\LettreCIAccess::hasActiveAccess(auth()->user()))
            <div class="col-md-4 cbr cbr2">
                <div class="dash-card" style="border-color:rgba(201,168,76,.2);">
                    <span class="dash-card-icon">✉️</span>
                    <div class="dash-card-title" style="color:var(--cb-gold);">Mes lettres</div>
                    <div class="dash-card-desc">Lettres administratives générées par IA — démission, bail, motivation et plus.</div>
                    <a href="{{ route('lettreci.dashboard') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});

@if($showOtpBanner ?? false)
(function(){
    const modal = document.getElementById('otpModal');
    if(!modal) return;
    const bsModal = bootstrap.Modal.getOrCreateInstance(modal);

    window.otpGoStep = function(n){
        document.querySelectorAll('.otp-step').forEach(s=>s.classList.remove('active'));
        const t=document.getElementById('otp-step-'+n);
        if(t) t.classList.add('active');
    };

    const countrySelect = document.getElementById('otp_country_code');
    const phoneInput    = document.getElementById('otp_phone_local');
    const preview       = document.getElementById('otp-phone-preview');

    function syncPhone(){
        if(!countrySelect||!phoneInput) return;
        const opt=countrySelect.options[countrySelect.selectedIndex];
        phoneInput.maxLength=parseInt(opt.dataset.digits,10);
        phoneInput.placeholder='0'.repeat(opt.dataset.digits);
        preview.textContent=phoneInput.value.trim()?(opt.dataset.prefix+phoneInput.value.trim()):'';
    }
    if(countrySelect){ countrySelect.addEventListener('change',syncPhone); syncPhone(); }
    if(phoneInput){ phoneInput.addEventListener('input',function(){ this.value=this.value.replace(/\D/g,''); syncPhone(); }); }

    const hasOtpSent   = {{ session('otp_sent')                                    ? 'true':'false' }};
    const hasOtpError  = {{ $errors->has('otp_code')                               ? 'true':'false' }};
    const hasPhoneErr  = {{ $errors->hasAny(['phone','phone_local','country_code']) ? 'true':'false' }};

    otpGoStep((hasOtpSent||hasOtpError) ? 2 : 1);

    if(hasOtpSent||hasOtpError||hasPhoneErr){ bsModal.show(); }
})();
@endif
</script>
@endpush
