{{-- resources/views/sgis/show.blade.php --}}
@extends('layouts.app')

@section('title', $sgi->name . ' – SGI – Coach BRVM')

@push('styles')
<style>
    .sgi-show-page { background: #060910; min-height: 100vh; }

    .sgi-breadcrumb { padding:24px 0 0;display:flex;align-items:center;gap:10px;font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase; }
    .sgi-breadcrumb a { color:#6B7590;text-decoration:none;transition:color .25s; }
    .sgi-breadcrumb a:hover { color:#C9A84C; }
    .sgi-breadcrumb span { color:rgba(107,117,144,.4); }

    /* Hero */
    .sgi-show-header { padding:28px 0 0;margin-bottom:32px; }
    .sgi-show-name { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,38px);font-weight:900;color:#E8EAF0;line-height:1.1;margin-bottom:6px; }
    .sgi-show-loc { font-size:14px;color:#6B7590; }
    .sgi-badge-agr { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 12px;border-radius:100px;background:rgba(31,191,74,.1);color:#1fbf4a;border:1px solid rgba(31,191,74,.2); }

    /* Cards */
    .sgi-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;height:100%; }
    .sgi-card-header { background:#121A2C;border-bottom:1px solid rgba(255,255,255,.05);padding:16px 22px; }
    .sgi-card-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:8px; }
    .sgi-card-body { padding:22px; }

    /* Info rows */
    .info-row { padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04); }
    .info-row:last-child { border-bottom:none; }
    .info-row-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:4px; }
    .info-row-value { font-size:13.5px;color:#E8EAF0; }
    .info-row-value a { color:#0FCFA4;text-decoration:none;transition:color .25s; }
    .info-row-value a:hover { color:#63B3ED; }

    /* Copy btn */
    .copy-btn { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#6B7590;padding:3px 8px;border-radius:3px;cursor:pointer;margin-left:8px;transition:all .25s; }
    .copy-btn:hover { border-color:rgba(201,168,76,.3);color:#C9A84C; }

    /* Value add cards */
    .sgi-info-box { background:rgba(15,207,164,.04);border:1px solid rgba(15,207,164,.1);border-radius:4px;padding:20px; }
    .sgi-info-box-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#0FCFA4;margin-bottom:10px;display:flex;align-items:center;gap:8px; }
    .sgi-info-box-text { font-size:13px;color:#6B7590;line-height:1.75; }

    .sgi-source-box { background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:18px;margin-top:16px; }
    .sgi-source-title { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:8px; }
    .sgi-disclaimer-text { font-size:12px;color:#6B7590;line-height:1.65;margin-top:10px; }

    /* CTA */
    .sgi-cta-box { background:rgba(201,168,76,.04);border:1px solid rgba(201,168,76,.1);border-radius:4px;padding:22px 26px;margin-top:28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px; }
    .sgi-cta-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:4px; }
    .sgi-cta-sub { font-size:13px;color:#6B7590; }

    /* Boutons */
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:8px;background:rgba(15,207,164,.1);color:#0FCFA4 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 18px;border:1px solid rgba(15,207,164,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,207,164,.16);border-color:rgba(15,207,164,.4); }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')
<div class="sgi-show-page">
<div class="container pb-5" style="max-width:1000px;">

    <div class="sgi-breadcrumb cbr">
        <a href="{{ route('sgis.index', array_filter(['country' => $sgi->country])) }}">← SGI ({{ $sgi->country }})</a>
        <span>/</span>
        <span style="color:#E8EAF0;">{{ \Illuminate\Support\Str::limit($sgi->name, 40) }}</span>
    </div>

    {{-- Header --}}
    <div class="sgi-show-header cbr">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="sgi-show-name">{{ $sgi->name }}</h1>
                <div class="sgi-show-loc">
                    🌍 {{ $sgi->country }}{{ $sgi->city ? ' · 📍 '.$sgi->city : '' }}
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="sgi-badge-agr">✅ Agréée BRVM</span>
                @if($sgi->website)
                    <a href="{{ $sgi->website }}" target="_blank" rel="noopener" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                        🔗 Site web
                    </a>
                @endif
                @if($sgi->email)
                    <a href="mailto:{{ $sgi->email }}" class="cb-btn-green" style="font-size:11px;padding:7px 14px;">
                        ✉️ Écrire
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Infos & Contacts --}}
        <div class="col-lg-7 cbr">
            <div class="sgi-card">
                <div class="sgi-card-header">
                    <div class="sgi-card-title">📌 Informations & Contacts</div>
                </div>
                <div class="sgi-card-body">
                    @if($sgi->address)
                        <div class="info-row">
                            <div class="info-row-label">Adresse</div>
                            <div class="info-row-value">{{ $sgi->address }}</div>
                        </div>
                    @endif
                    @if($sgi->po_box)
                        <div class="info-row">
                            <div class="info-row-label">Boîte postale</div>
                            <div class="info-row-value">{{ $sgi->po_box }}</div>
                        </div>
                    @endif
                    <div class="info-row">
                        <div class="info-row-label">Email</div>
                        @if($sgi->email)
                            <div class="info-row-value">
                                <a href="mailto:{{ $sgi->email }}">{{ $sgi->email }}</a>
                                <button class="copy-btn" onclick="navigator.clipboard.writeText('{{ $sgi->email }}');this.textContent='✓ Copié!'">Copier</button>
                            </div>
                        @else
                            <div class="info-row-value" style="color:#6B7590;">Non renseigné</div>
                        @endif
                    </div>
                    <div class="info-row">
                        <div class="info-row-label">Téléphone</div>
                        @if($sgi->phone)
                            <div class="info-row-value">
                                <a href="tel:{{ preg_replace('/\s+/','',$sgi->phone) }}">{{ $sgi->phone }}</a>
                            </div>
                            @if($sgi->phone2)
                                <div class="info-row-value mt-1">
                                    <a href="tel:{{ preg_replace('/\s+/','',$sgi->phone2) }}">{{ $sgi->phone2 }}</a>
                                </div>
                            @endif
                        @else
                            <div class="info-row-value" style="color:#6B7590;">Non renseigné</div>
                        @endif
                    </div>
                    <div class="info-row">
                        <div class="info-row-label">Site web</div>
                        @if($sgi->website)
                            <div class="info-row-value">
                                <a href="{{ $sgi->website }}" target="_blank" rel="noopener">{{ $sgi->website }}</a>
                            </div>
                        @else
                            <div class="info-row-value" style="color:#6B7590;">Non renseigné</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Infos pédagogiques + Source --}}
        <div class="col-lg-5">
            <div class="sgi-info-box cbr cbr2">
                <div class="sgi-info-box-title">✅ Ce que fait une SGI</div>
                <div class="sgi-info-box-text">
                    Une SGI (Société de Gestion et d'Intermédiation) est un intermédiaire agréé par la BRVM
                    qui permet d'acheter/vendre des titres cotés et d'ouvrir un compte-titres.
                </div>
            </div>

            <div class="sgi-source-box cbr cbr3">
                <div class="sgi-source-title">📚 Source des données</div>
                <div style="font-size:13px;color:#9AA3B8;">{{ $sgi->source_name }}</div>
                @if($sgi->source_url)
                    <div style="margin-top:8px;">
                        <a href="{{ $sgi->source_url }}" target="_blank" rel="noopener"
                           style="font-family:'Syne',sans-serif;font-size:11px;color:#0FCFA4;text-decoration:none;">
                            Voir la source officielle ↗
                        </a>
                    </div>
                @endif
                <div class="sgi-disclaimer-text">
                    ℹ️ Coach BRVM affiche ces données à titre indicatif. Pour les frais, procédures et documents nécessaires, contactez directement la SGI.
                </div>
            </div>
        </div>

    </div>

    {{-- CTA bas --}}
    <div class="sgi-cta-box cbr">
        <div>
            <div class="sgi-cta-title">Tu débutes à la BRVM ?</div>
            <div class="sgi-cta-sub">On t'aide à comprendre le marché : BOC, indices, actions, dividendes…</div>
        </div>
        <a href="{{ route('announcements.index') }}" class="cb-btn-gold">
            Voir les annonces BRVM →
        </a>
    </div>

    <div class="d-flex justify-content-between mt-4 pb-4 cbr">
        <a href="{{ route('sgis.index') }}" class="cb-btn-outline">← Toutes les SGI</a>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); },{threshold:.06}).observe(el);
    });
</script>
@endpush
