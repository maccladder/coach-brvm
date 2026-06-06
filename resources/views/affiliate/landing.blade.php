@extends('layouts.app')

@push('styles')
<style>
    .af-page { background:#060910; min-height:100vh; }
    .af-hero { background:#0C1120; border-bottom:1px solid rgba(201,168,76,.08); padding:48px 0 36px; }
    .af-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:#C9A84C; display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .af-hero-tag::before { content:''; width:28px; height:1px; background:#C9A84C; }
    .af-hero-title { font-family:'Playfair Display',serif; font-size:clamp(26px,4vw,40px); font-weight:900; color:#E8EAF0; line-height:1.2; }
    .af-hero-sub { font-size:14px; color:#6B7590; margin-top:8px; max-width:520px; }

    .af-benefits { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:32px; }
    @media(max-width:768px){ .af-benefits{grid-template-columns:1fr;} }
    .af-benefit { background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:4px; padding:22px; }
    .af-benefit-icon { font-size:28px; margin-bottom:10px; }
    .af-benefit-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#C9A84C; margin-bottom:6px; }
    .af-benefit-desc { font-size:13px; color:#6B7590; line-height:1.6; }

    .af-form-card { background:#0C1120; border:1px solid rgba(201,168,76,.12); border-radius:4px; overflow:hidden; }
    .af-form-header { background:#121A2C; border-bottom:1px solid rgba(255,255,255,.05); padding:16px 24px; }
    .af-form-title { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#C9A84C; }
    .af-form-body { padding:28px 24px; }

    .af-label { font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#6B7590; display:block; margin-bottom:6px; }
    .af-input { background:rgba(6,9,16,.9) !important; border:1px solid rgba(255,255,255,.1) !important; color:#E8EAF0 !important; border-radius:3px !important; font-family:'DM Sans',sans-serif !important; font-size:13px !important; padding:10px 14px !important; width:100%; outline:none; transition:border-color .25s; }
    .af-input:focus { border-color:rgba(201,168,76,.4) !important; box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .af-input::placeholder { color:#6B7590 !important; }
    .af-help { font-size:12px; color:#6B7590; margin-top:5px; }

    .af-toggle-label { display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; color:#9AA3B8; }
    .af-toggle-label input[type=checkbox] { accent-color:#C9A84C; width:16px; height:16px; cursor:pointer; }

    .cb-btn-gold { display:inline-flex; align-items:center; gap:7px; background:linear-gradient(135deg,#C9A84C,#9B6B15); color:#050810 !important; font-family:'Syne',sans-serif; font-weight:800; font-size:12px; letter-spacing:.07em; text-transform:uppercase; padding:13px 28px; border:none; border-radius:3px; cursor:pointer; text-decoration:none; transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3); transform:translateY(-1px); }

    .af-alert-ok   { background:rgba(15,207,164,.07); border:1px solid rgba(15,207,164,.2); border-radius:3px; padding:12px 16px; font-size:13px; color:#0FCFA4; margin-bottom:16px; }
    .af-alert-warn { background:rgba(255,200,30,.06); border:1px solid rgba(255,200,30,.15); border-radius:3px; padding:12px 16px; font-size:13px; color:#FFC850; margin-bottom:16px; }
    .af-alert-err  { background:rgba(255,107,107,.07); border:1px solid rgba(255,107,107,.2); border-radius:3px; padding:12px 16px; font-size:13px; color:#FF6B6B; margin-bottom:16px; }
    .af-alert-err ul { margin:6px 0 0 16px; padding:0; }

    .af-rate-pill { display:inline-flex; align-items:center; gap:5px; background:rgba(201,168,76,.08); border:1px solid rgba(201,168,76,.18); border-radius:100px; padding:5px 14px; font-family:'Syne',sans-serif; font-size:11px; font-weight:700; color:#C9A84C; }

    /* Bloc WhatsApp — même style que page Pack BRVM */
    .btn-whatsapp { display:inline-flex; align-items:center; gap:9px; background:rgba(37,211,102,.1); border:1px solid rgba(37,211,102,.35); color:#25D366 !important; text-decoration:none; font-family:'Syne',sans-serif; font-size:12.5px; font-weight:700; letter-spacing:.04em; padding:11px 26px; border-radius:4px; transition:all .28s; }
    .btn-whatsapp:hover { background:rgba(37,211,102,.18); border-color:rgba(37,211,102,.6); box-shadow:0 6px 22px rgba(37,211,102,.18); transform:translateY(-1px); }
    .btn-whatsapp svg { flex-shrink:0; }
</style>
@endpush

@section('content')
<div class="af-page">

    <div class="af-hero">
        <div class="container" style="max-width:900px;">
            <p class="af-hero-tag">Apporteur d'affaires</p>
            <h1 class="af-hero-title">Gagne une commission sur chaque vente que tu génères</h1>
            <p class="af-hero-sub">Partage ton lien unique. Quand quelqu'un achète via ton lien, tu touches <strong style="color:#C9A84C;">10%</strong> de commission — et il bénéficie d'une remise de <strong style="color:#0FCFA4;">10%</strong>.</p>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <span class="af-rate-pill">💰 +10% pour toi</span>
                <span class="af-rate-pill" style="color:#0FCFA4;border-color:rgba(15,207,164,.25);background:rgba(15,207,164,.06);">🎁 −10% pour l'acheteur</span>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:900px;">

        {{-- Alertes --}}
        @if(session('success')) <div class="af-alert-ok">{{ session('success') }}</div> @endif
        @if(session('warning')) <div class="af-alert-warn">{{ session('warning') }}</div> @endif
        @if($errors->any())
            <div class="af-alert-err">
                <strong>Erreurs :</strong>
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Avantages --}}
        <div class="af-benefits mb-4">
            <div class="af-benefit">
                <div class="af-benefit-icon">🔗</div>
                <div class="af-benefit-title">Ton lien unique</div>
                <div class="af-benefit-desc">Un code personnel (ex. DAVY), un lien <code style="color:#C9A84C;">/r/DAVY</code>, un QR code à partager.</div>
            </div>
            <div class="af-benefit">
                <div class="af-benefit-icon">💳</div>
                <div class="af-benefit-title">Commission automatique</div>
                <div class="af-benefit-desc">Chaque achat éligible via ton lien crédite 10% sur ton solde apporteur.</div>
            </div>
            <div class="af-benefit">
                <div class="af-benefit-icon">📤</div>
                <div class="af-benefit-title">Reversement mobile money</div>
                <div class="af-benefit-desc">Demande ton reversement dès 10 000 FCFA via Wave, Orange Money, MTN ou Moov.</div>
            </div>
        </div>

        {{-- Formulaire --}}
        @auth
            @if(auth()->user()->affiliate && auth()->user()->affiliate->status === 'pending')
                <div class="af-alert-warn">
                    ⏳ Ta demande est en cours de validation. Tu recevras une notification dès que ton compte sera activé.
                </div>
            @else
                <div class="af-form-card">
                    <div class="af-form-header">
                        <div class="af-form-title">🤝 Devenir apporteur d'affaires</div>
                    </div>
                    <div class="af-form-body">
                        <form method="POST" action="{{ route('affiliate.store') }}" id="af-form">
                            @csrf

                            {{-- Toggle code personnalisé --}}
                            <div class="mb-4">
                                <label class="af-toggle-label">
                                    <input type="checkbox" id="use_custom_toggle" name="use_custom_code" value="1"
                                           {{ old('use_custom_code') ? 'checked' : '' }}>
                                    Je veux choisir mon propre code (ex. DAVY, INVEST225…)
                                </label>
                            </div>

                            {{-- Champ code personnalisé (affiché si toggle coché) --}}
                            <div id="custom_code_field" class="mb-4" style="{{ old('use_custom_code') ? '' : 'display:none;' }}">
                                <label class="af-label" for="custom_code">Mon code personnalisé</label>
                                <input type="text" id="custom_code" name="custom_code"
                                       value="{{ old('custom_code') }}"
                                       class="af-input"
                                       placeholder="Ex: DAVY225"
                                       maxlength="20"
                                       style="max-width:280px;">
                                <p class="af-help">3 à 20 caractères · Lettres et chiffres uniquement · Sera converti en MAJUSCULES</p>
                                @error('custom_code')
                                    <div class="mt-1" style="font-size:12px;color:#FF6B6B;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="af-help mb-4" style="color:#9AA3B8;">
                                ℹ️ Si tu ne choisis pas de code, un code de 7 caractères sera généré automatiquement.
                                Ton compte sera activé après validation par l'équipe.
                            </div>

                            <button type="submit" class="cb-btn-gold">
                                🤝 Soumettre ma demande
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <div class="af-alert-warn">
                🔒 <a href="{{ route('login') }}" style="color:#FFC850;">Connecte-toi</a> pour devenir apporteur d'affaires.
            </div>
        @endauth

        {{-- WhatsApp contact — même style que page Pack BRVM --}}
        @php $waNumber = config('services.whatsapp.support_number'); @endphp
        @if($waNumber)
        <div style="margin-top:28px; padding-top:24px; border-top:1px solid rgba(255,255,255,.06);">
            <p style="font-size:12.5px; color:#6B7590; margin-bottom:12px;">Des questions sur le processus d'apporteur d'affaires ? Écrivez-nous sur WhatsApp.</p>
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Bonjour, j\'ai une question sur le programme apporteur d\'affaires Coach BRVM.') }}"
               target="_blank" rel="noopener" class="btn-whatsapp">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                </svg>
                Écrire sur WhatsApp
            </a>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    const toggle = document.getElementById('use_custom_toggle');
    const field  = document.getElementById('custom_code_field');
    if (toggle && field) {
        toggle.addEventListener('change', () => {
            field.style.display = toggle.checked ? '' : 'none';
        });
    }
</script>
@endpush
