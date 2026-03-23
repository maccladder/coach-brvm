{{-- resources/views/docs/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .docs-page { background: #060910; min-height: 100vh; }

    .docs-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .docs-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .docs-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:#0FCFA4; display:flex; align-items:center; gap:10px; margin-bottom:14px; }
    .docs-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .docs-hero-title { font-family:'Playfair Display',serif; font-size:clamp(28px,5vw,48px); font-weight:900; color:#E8EAF0; line-height:1.08; margin-bottom:10px; }
    .docs-hero-title em { font-style:italic; color:#C9A84C; }
    .docs-hero-desc { font-size:14px; color:#6B7590; font-weight:300; line-height:1.7; }

    /* Filtres */
    .docs-filters {
        background: #0C1120; border: 1px solid rgba(201,168,76,.08);
        border-radius: 4px; padding: 20px 24px; margin-bottom: 32px;
    }
    .docs-filters input, .docs-filters select {
        background: rgba(6,9,16,.9) !important; border: 1px solid rgba(255,255,255,.1) !important;
        color: #E8EAF0 !important; border-radius: 3px !important;
        font-family: 'DM Sans', sans-serif !important; font-size: 13px !important;
        padding: 9px 12px !important; width: 100%; outline: none; transition: border-color .25s;
    }
    .docs-filters input:focus, .docs-filters select:focus {
        border-color: rgba(201,168,76,.4) !important; box-shadow: 0 0 0 3px rgba(201,168,76,.07) !important;
    }
    .docs-filters input::placeholder { color: #6B7590 !important; }
    .docs-filters select option { background: #0C1120; }
    .docs-filter-label { font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#6B7590; display:block; margin-bottom:6px; }

    /* Cards */
    .doc-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.05);
        border-radius: 4px; padding: 24px; height: 100%;
        transition: all .32s; position: relative; overflow: hidden;
        text-decoration: none; color: inherit; display: flex; flex-direction: column;
    }
    .doc-card::before { content:''; position:absolute; top:0;left:0;right:0;height:2px; background:linear-gradient(90deg,#C9A84C,transparent); opacity:0; transition:opacity .32s; }
    .doc-card:hover { border-color:rgba(201,168,76,.2); transform:translateY(-4px); box-shadow:0 14px 36px rgba(0,0,0,.4); color:inherit; }
    .doc-card:hover::before { opacity:1; }

    .doc-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
    .doc-chip { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:3px 9px; border-radius:100px; }
    .doc-chip-type { background:rgba(201,168,76,.08); color:#C9A84C; border:1px solid rgba(201,168,76,.18); }
    .doc-chip-country { background:rgba(15,207,164,.08); color:#0FCFA4; border:1px solid rgba(15,207,164,.18); }

    .doc-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:#E8EAF0; line-height:1.4; margin-bottom:10px; }
    .doc-desc { font-size:13px; color:#6B7590; line-height:1.65; flex:1; margin-bottom:18px; }

    .doc-footer { display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:14px; border-top:1px solid rgba(255,255,255,.04); }
    .doc-price { font-family:'Playfair Display',serif; font-size:20px; font-weight:700; color:#C9A84C; }

    .cb-btn-see { display:inline-flex; align-items:center; gap:6px; font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:7px 14px; border-radius:3px; background:rgba(201,168,76,.08); color:#C9A84C; border:1px solid rgba(201,168,76,.2); text-decoration:none; transition:all .25s; }
    .cb-btn-see:hover { background:rgba(201,168,76,.16); color:#C9A84C; }

    .cb-btn-gold { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#C9A84C,#9B6B15); color:#050810 !important; font-family:'Syne',sans-serif; font-weight:800; font-size:12px; letter-spacing:.07em; text-transform:uppercase; padding:10px 20px; border:none; border-radius:3px; cursor:pointer; transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3); transform:translateY(-1px); }

    /* Empty */
    .docs-empty { text-align:center; padding:80px 20px; background:rgba(12,17,32,.6); border:1px solid rgba(201,168,76,.08); border-radius:4px; }

    /* Pagination */
    .pagination .page-link { background:#0C1120 !important; border-color:rgba(255,255,255,.08) !important; color:#6B7590 !important; font-family:'Syne',sans-serif; font-size:12px; }
    .pagination .page-link:hover { background:rgba(201,168,76,.1) !important; color:#C9A84C !important; border-color:rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,#C9A84C,#9B6B15) !important; border-color:transparent !important; color:#050810 !important; }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="docs-page">
    <div class="docs-hero">
        <div class="docs-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="docs-hero-tag">Documents premium</p>
            <h1 class="docs-hero-title">Études & <em>Business Plans</em></h1>
            <p class="docs-hero-desc">Documents prêts à l'emploi. Paiement sécurisé → accès immédiat dans ton espace.</p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <form class="docs-filters cbr" method="GET" action="{{ route('docs.public.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="docs-filter-label">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher...">
                </div>
                <div class="col-md-3">
                    <label class="docs-filter-label">Type</label>
                    <select name="type">
                        <option value="">Tous les types</option>
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected(request('type')===$k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="docs-filter-label">Prix min</label>
                    <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0">
                </div>
                <div class="col-md-2">
                    <label class="docs-filter-label">Prix max</label>
                    <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="∞">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="cb-btn-gold"><i class="bi bi-funnel"></i> Filtrer</button>
                </div>
            </div>
            @if(request('search') || request('type') || request('min_price') || request('max_price'))
                <div class="mt-3 text-end">
                    <a href="{{ route('docs.public.index') }}" style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;">✕ Réinitialiser</a>
                </div>
            @endif
        </form>

        <div class="row g-3 cbr">
            @forelse($documents as $doc)
                <div class="col-md-6 col-lg-4">
                    <div class="doc-card">
                        <div class="doc-chips">
                            <span class="doc-chip doc-chip-type">{{ $types[$doc->type] ?? $doc->type }}</span>
                            @if($doc->country)
                                <span class="doc-chip doc-chip-country">{{ $doc->country }}</span>
                            @endif
                        </div>
                        <div class="doc-title">{{ $doc->title }}</div>
                        <div class="doc-desc">{{ \Illuminate\Support\Str::limit($doc->description, 110) }}</div>
                        <div class="doc-footer">
                            <span class="doc-price">{{ number_format($doc->price,0,',',' ') }} F</span>
                            <a href="{{ route('docs.public.show', $doc->slug) }}" class="cb-btn-see">
                                Voir <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="docs-empty">
                        <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📄</div>
                        <div style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;">Aucun document trouvé</div>
                    </div>
                </div>
            @endforelse
        </div>

        @if($documents->hasPages())
            <div class="mt-4 d-flex justify-content-center cbr">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, {threshold:.07}).observe(el);
    });
</script>
@endpush
