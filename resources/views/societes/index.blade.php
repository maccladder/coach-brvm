{{-- resources/views/societes/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .soc-page { background: #060910; min-height: 100vh; }

    .soc-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .soc-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .soc-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .soc-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .soc-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .soc-hero-title em { font-style:italic;color:#C9A84C; }

    /* Search */
    .soc-search-wrap { background:#0C1120;border:1px solid rgba(201,168,76,.08);border-radius:4px;padding:20px 24px;margin-bottom:32px; }
    .soc-search-input {
        background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;
        color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;
        font-size:14px !important;padding:11px 16px !important;flex:1;outline:none;transition:border-color .25s;
    }
    .soc-search-input:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .soc-search-input::placeholder { color:#6B7590 !important; }

    /* Cards */
    .soc-card {
        background:#0C1120;border:1px solid rgba(255,255,255,.05);border-radius:4px;
        padding:22px;height:100%;transition:all .32s;position:relative;overflow:hidden;
        display:flex;flex-direction:column;
    }
    .soc-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#C9A84C,transparent);opacity:0;transition:opacity .32s; }
    .soc-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.35); }
    .soc-card:hover::before { opacity:1; }

    .soc-card-logo {
        width:44px;height:44px;border-radius:6px;
        background:#121A2C;border:1px solid rgba(255,255,255,.08);
        display:flex;align-items:center;justify-content:center;
        font-family:'Playfair Display',serif;font-size:16px;font-weight:900;color:#C9A84C;
        flex-shrink:0;overflow:hidden;
    }
    .soc-card-logo img { width:100%;height:100%;object-fit:contain;padding:4px; }

    .soc-card-name { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:2px;line-height:1.35; }
    .soc-card-ticker { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    .soc-badges { display:flex;flex-wrap:wrap;gap:6px;margin:10px 0; }
    .soc-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:3px 9px;border-radius:100px; }
    .soc-badge-div { background:rgba(31,191,74,.08);color:#1fbf4a;border:1px solid rgba(31,191,74,.2); }
    .soc-badge-per { background:rgba(99,179,237,.08);color:#63B3ED;border:1px solid rgba(99,179,237,.2); }
    .soc-badge-none { background:rgba(255,255,255,.04);color:#6B7590;border:1px solid rgba(255,255,255,.08); }

    .soc-card-desc { font-size:12.5px;color:#6B7590;line-height:1.6;flex:1;margin-bottom:16px; }

    .cb-btn-see { display:inline-flex;align-items:center;gap:6px;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:7px 14px;border-radius:3px;background:rgba(201,168,76,.08);color:#C9A84C;border:1px solid rgba(201,168,76,.2);text-decoration:none;transition:all .25s;margin-top:auto; }
    .cb-btn-see:hover { background:rgba(201,168,76,.16);color:#C9A84C; }

    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 20px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; }
</style>
@endpush

@section('content')
<div class="soc-page">
    <div class="soc-hero">
        <div class="soc-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="soc-hero-tag">BRVM</p>
            <h1 class="soc-hero-title">🏢 Sociétés <em>cotées</em></h1>
            <p style="font-size:14px;color:#6B7590;font-weight:300;">Annuaire complet des entreprises cotées sur la Bourse Régionale des Valeurs Mobilières.</p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <form method="GET" class="soc-search-wrap cbr">
            <div class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       class="soc-search-input"
                       placeholder="🔍 Rechercher une société (ex: AIR, SIVC, SONATEL…)">
                <button type="submit" class="cb-btn-gold"><i class="bi bi-search"></i> Rechercher</button>
            </div>
        </form>

        <div class="row g-3 cbr cbr2">
            @forelse($items as $s)
                @php $d = $dividendes[$s['ticker']] ?? null; @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="soc-card">
                        <div class="d-flex align-items-center gap-12 mb-12" style="gap:12px;margin-bottom:12px;">
                            <div class="soc-card-logo">
                                @if(!empty($s['logo']) && file_exists(public_path($s['logo'])))
                                    <img src="{{ asset($s['logo']) }}" alt="{{ $s['name'] }}">
                                @else
                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div class="soc-card-name">{{ $s['name'] }}</div>
                                <div class="soc-card-ticker">{{ $s['ticker'] }}</div>
                            </div>
                        </div>

                        <div class="soc-badges">
                            @if($d)
                                @if($d->rendement_net !== null)
                                    <span class="soc-badge soc-badge-div">💰 Rdt {{ number_format($d->rendement_net,2,',','') }}%</span>
                                @endif
                                @if($d->per !== null)
                                    <span class="soc-badge soc-badge-per">PER {{ number_format($d->per,2,',','') }}</span>
                                @endif
                            @else
                                <span class="soc-badge soc-badge-none">Dividendes —</span>
                            @endif
                        </div>

                        <p class="soc-card-desc">{{ \Illuminate\Support\Str::limit($s['description'], 100) }}</p>

                        <a href="{{ route('societes.show', $s['slug']) }}" class="cb-btn-see">
                            Voir la présentation <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div style="text-align:center;padding:80px 20px;background:rgba(12,17,32,.6);border:1px solid rgba(201,168,76,.08);border-radius:4px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;">
                        <div style="font-size:32px;margin-bottom:12px;opacity:.4;">🏢</div>
                        Aucune société trouvée pour "{{ $q }}"
                    </div>
                </div>
            @endforelse
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
