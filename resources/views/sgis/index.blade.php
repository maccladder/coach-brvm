{{-- resources/views/sgis/index.blade.php --}}
@extends('layouts.app')

@section('title', 'SGI (Courtiers) – Boursiv')

@push('styles')
<style>
    .sgi-page { background: #060910; min-height: 100vh; }

    .sgi-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .sgi-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .sgi-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .sgi-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .sgi-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .sgi-hero-title em { font-style:italic;color:#C9A84C; }
    .sgi-count { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-top:8px; }

    /* Filtres */
    .sgi-filters { background:#0C1120;border:1px solid rgba(201,168,76,.08);border-radius:4px;padding:20px 24px;margin-bottom:24px; }
    .sgi-filters input, .sgi-filters select { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:9px 12px !important;width:100%;outline:none;transition:border-color .25s; }
    .sgi-filters input:focus, .sgi-filters select:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .sgi-filters input::placeholder { color:#6B7590 !important; }
    .sgi-filters select option { background:#0C1120; }
    .sgi-filter-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;display:block;margin-bottom:6px; }

    /* Pays tabs */
    .sgi-tabs { display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px; }
    .sgi-tab { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:6px 14px;border-radius:100px;text-decoration:none;transition:all .25s; }
    .sgi-tab-inactive { background:rgba(255,255,255,.04);color:#6B7590;border:1px solid rgba(255,255,255,.08); }
    .sgi-tab-inactive:hover { border-color:rgba(201,168,76,.2);color:#C9A84C; }
    .sgi-tab-active { background:rgba(201,168,76,.1);color:#C9A84C;border:1px solid rgba(201,168,76,.2); }

    /* Cards SGI */
    .sgi-card { background:#0C1120;border:1px solid rgba(255,255,255,.05);border-radius:4px;padding:22px;height:100%;display:flex;flex-direction:column;transition:all .32s;position:relative;overflow:hidden; }
    .sgi-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#0FCFA4,transparent);opacity:0;transition:opacity .32s; }
    .sgi-card:hover { border-color:rgba(15,207,164,.2);transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.35); }
    .sgi-card:hover::before { opacity:1; }

    .sgi-card-name { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:4px; }
    .sgi-card-location { font-size:12px;color:#6B7590;margin-bottom:12px; }
    .sgi-card-address { font-size:12px;color:#6B7590;margin-bottom:6px; }

    .sgi-divider { height:1px;background:rgba(255,255,255,.05);margin:12px 0; }

    .sgi-contact-item { font-size:12px;color:#9AA3B8;margin-bottom:6px;display:flex;align-items:flex-start;gap:8px; }
    .sgi-contact-item a { color:#0FCFA4;text-decoration:none;transition:color .25s; }
    .sgi-contact-item a:hover { color:#63B3ED; }

    .sgi-card-actions { display:flex;gap:8px;margin-top:auto;padding-top:14px; }

    .sgi-badge-agr { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border-radius:100px;background:rgba(31,191,74,.1);color:#1fbf4a;border:1px solid rgba(31,191,74,.2); }

    /* Boutons */
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cb-btn-green { display:inline-flex;align-items:center;justify-content:center;background:rgba(15,207,164,.1);color:#0FCFA4 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid rgba(15,207,164,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,207,164,.16);border-color:rgba(15,207,164,.4); }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 20px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }

    /* Empty */
    .sgi-empty { text-align:center;padding:80px 20px;background:rgba(12,17,32,.6);border:1px solid rgba(201,168,76,.08);border-radius:4px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    /* Pagination */
    .pagination .page-link { background:#0C1120 !important;border-color:rgba(255,255,255,.08) !important;color:#6B7590 !important;font-family:'Syne',sans-serif;font-size:12px; }
    .pagination .page-link:hover { background:rgba(201,168,76,.1) !important;color:#C9A84C !important;border-color:rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,#C9A84C,#9B6B15) !important;border-color:transparent !important;color:#050810 !important; }

    /* Disclaimer */
    .sgi-disclaimer { font-size:12px;color:#6B7590;padding:16px 20px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:3px;margin-top:24px; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; }
</style>
@endpush

@section('content')
<div class="sgi-page">

    <div class="sgi-hero">
        <div class="sgi-hero-grid"></div>
        <div class="container" style="max-width:1200px;position:relative;z-index:1;">
            <p class="sgi-hero-tag">Courtiers agréés</p>
            <h1 class="sgi-hero-title">📌 SGI — <em>Sociétés de Gestion</em><br>et d'Intermédiation</h1>
            <p style="font-size:14px;color:#6B7590;font-weight:300;">Trouvez une SGI par pays, puis contactez-la directement. Source officielle : BRVM.</p>
            <div class="sgi-count">{{ $sgis->total() }} SGI référencées</div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1200px;">

        {{-- Filtres --}}
        <form method="GET" action="{{ route('sgis.index') }}" class="sgi-filters cbr">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="sgi-filter-label">Pays</label>
                    <select name="country">
                        <option value="">Toutes les SGI</option>
                        @foreach($countries as $c)
                            <option value="{{ $c }}" @selected(request('country') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="sgi-filter-label">Recherche</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, ville, email…">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="cb-btn-gold" style="flex:1;justify-content:center;"><i class="bi bi-search"></i> Rechercher</button>
                    <a href="{{ route('sgis.index') }}" class="cb-btn-outline" style="white-space:nowrap;">✕</a>
                </div>
            </div>
        </form>

        {{-- Tabs pays --}}
        @if($countries->count())
            <div class="sgi-tabs cbr">
                <a href="{{ route('sgis.index') }}" class="sgi-tab {{ !request('country') ? 'sgi-tab-active' : 'sgi-tab-inactive' }}">
                    Tous
                </a>
                @foreach($countries as $c)
                    <a href="{{ route('sgis.index', array_filter(['country' => $c, 'q' => request('q')])) }}"
                       class="sgi-tab {{ request('country') === $c ? 'sgi-tab-active' : 'sgi-tab-inactive' }}">
                        {{ $c }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Liste --}}
        @if($sgis->count() === 0)
            <div class="sgi-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">🏦</div>
                Aucune SGI trouvée avec ces critères
            </div>
        @else
            <div class="row g-3 cbr cbr2">
                @foreach($sgis as $sgi)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="sgi-card">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="sgi-card-name">{{ $sgi->name }}</div>
                                    <div class="sgi-card-location">🌍 {{ $sgi->country }}{{ $sgi->city ? ' · 📍 '.$sgi->city : '' }}</div>
                                </div>
                                <span class="sgi-badge-agr">Agréée</span>
                            </div>

                            @if($sgi->address)
                                <div class="sgi-card-address">📌 {{ $sgi->address }}</div>
                            @endif
                            @if($sgi->po_box)
                                <div class="sgi-card-address">📮 {{ $sgi->po_box }}</div>
                            @endif

                            <div class="sgi-divider"></div>

                            @if($sgi->email)
                                <div class="sgi-contact-item">✉️ <a href="mailto:{{ $sgi->email }}">{{ $sgi->email }}</a></div>
                            @endif
                            @if($sgi->phone)
                                <div class="sgi-contact-item">📞 <a href="tel:{{ preg_replace('/\s+/','',$sgi->phone) }}">{{ $sgi->phone }}</a></div>
                            @endif
                            @if($sgi->phone2)
                                <div class="sgi-contact-item">☎️ <a href="tel:{{ preg_replace('/\s+/','',$sgi->phone2) }}">{{ $sgi->phone2 }}</a></div>
                            @endif
                            @if($sgi->website)
                                <div class="sgi-contact-item">🔗 <a href="{{ $sgi->website }}" target="_blank" rel="noopener">{{ Str::of($sgi->website)->replace(['https://','http://'],'')->limit(28) }}</a></div>
                            @endif

                            <div class="sgi-card-actions">
                                <a href="{{ route('sgis.show', $sgi->slug) }}" class="cb-btn-outline" style="flex:1;justify-content:center;">
                                    Voir la fiche →
                                </a>
                                @if($sgi->email)
                                    <a href="mailto:{{ $sgi->email }}" class="cb-btn-green">✉️</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 d-flex justify-content-center cbr">
                {{ $sgis->links() }}
            </div>
        @endif

        <div class="sgi-disclaimer cbr">
            ℹ️ Les informations proviennent de la BRVM et sont présentées à titre informatif.
            Contactez la SGI directement pour confirmer les conditions d'ouverture de compte et les frais.
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
