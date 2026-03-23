{{-- ════════ books/index.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .books-page { background:#060910;min-height:100vh; }
    .books-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .books-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .books-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .books-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .books-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .books-hero-title em { font-style:italic;color:#C9A84C; }
    .books-search { background:#0C1120;border:1px solid rgba(201,168,76,.08);border-radius:4px;padding:18px 22px;margin-bottom:32px; }
    .books-search input { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:14px !important;padding:12px 16px !important;width:100%;outline:none;transition:border-color .25s; }
    .books-search input:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .books-search input::placeholder { color:#6B7590 !important; }

    /* Livre card */
    .book-item-card { text-decoration:none;color:inherit;display:block; }
    .book-mock {
        position:relative;height:220px;border-radius:6px;overflow:hidden;
        display:grid;grid-template-columns:32px 1fr;
        box-shadow:0 14px 32px rgba(0,0,0,.3);
        transition:transform .2s,box-shadow .2s;
    }
    .book-item-card:hover .book-mock { transform:translateY(-4px) rotateX(1deg);box-shadow:0 20px 40px rgba(0,0,0,.4); }
    .book-spine { background:linear-gradient(to bottom,var(--spine),#0b1220);position:relative; }
    .book-spine::after { content:"";position:absolute;left:6px;right:6px;top:12px;bottom:12px;background:repeating-linear-gradient(to bottom,rgba(255,255,255,.15),rgba(255,255,255,.15) 2px,transparent 2px,transparent 8px);border-radius:10px;opacity:.3; }
    .book-cover { background:linear-gradient(135deg,var(--cover),#111827);padding:16px;position:relative;display:flex;flex-direction:column;gap:8px; }
    .book-cover::before { content:"";position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.15),transparent 55%);pointer-events:none; }
    .book-top { position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start; }
    .book-mini-pill { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:4px 8px;border-radius:100px;background:rgba(255,255,255,.18);color:rgba(255,255,255,.9);border:1px solid rgba(255,255,255,.22); }
    .book-badge-free { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:3px 8px;border-radius:100px;background:rgba(15,207,164,.2);color:#0FCFA4;border:1px solid rgba(15,207,164,.3); }
    .book-badge-prem { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:3px 8px;border-radius:100px;background:rgba(201,168,76,.2);color:#C9A84C;border:1px solid rgba(201,168,76,.3); }
    .book-title-text { position:relative;z-index:2;color:#fff;font-weight:900;font-size:1rem;line-height:1.2;max-height:2.4em;overflow:hidden;text-shadow:0 4px 12px rgba(0,0,0,.3); }
    .book-desc-text { position:relative;z-index:2;color:rgba(255,255,255,.8);font-size:.88rem;line-height:1.35;max-height:3.9em;overflow:hidden; }
    .book-meta { position:relative;z-index:2;margin-top:auto;display:flex;align-items:center;justify-content:space-between; }
    .book-meta span { font-size:.78rem;color:rgba(255,255,255,.5); }
    .book-shine { position:absolute;inset:0;background:linear-gradient(120deg,transparent 0%,rgba(255,255,255,.2) 45%,transparent 60%);transform:translateX(-120%);transition:transform .5s;pointer-events:none; }
    .book-item-card:hover .book-shine { transform:translateX(120%); }
    .book-footer-text { padding:8px 2px 0;font-size:12px;color:#6B7590;font-family:'Syne',sans-serif; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="books-page">
    <div class="books-hero">
        <div class="books-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="books-hero-tag">Mini-cours</p>
                    <h1 class="books-hero-title">📚 Livres <em>instructifs</em></h1>
                    <p style="font-size:14px;color:#6B7590;font-weight:300;">Mini-cours à lire comme un livre, page par page.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('landing') }}" style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:9px 16px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s;">← Accueil</a>
                    <a href="{{ route('formations.brvm') }}" style="display:inline-flex;align-items:center;gap:8px;background:rgba(15,207,164,.1);color:#0FCFA4 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:9px 16px;border:1px solid rgba(15,207,164,.2);border-radius:3px;text-decoration:none;transition:all .3s;">🎓 Formations</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">
        <div class="books-search cbr">
            <input id="bookSearch" type="text" placeholder="🔎 Rechercher un livre (ex: dividendes, action, obligation…)">
            <div style="font-size:12px;color:#6B7590;margin-top:6px;">Astuce : tape un mot-clé, la liste se filtre instantanément.</div>
        </div>

        <div class="row g-4" id="booksGrid">
            @php $palettes=[['#0ea5e9','#0369a1'],['#22c55e','#15803d'],['#f59e0b','#b45309'],['#a855f7','#6d28d9'],['#ef4444','#991b1b'],['#14b8a6','#0f766e']]; @endphp
            @foreach($books as $book)
                @php $p=$palettes[$book->id % count($palettes)]; @endphp
                <div class="col-12 col-sm-6 col-lg-4 book-item cbr" data-title="{{ strtolower($book->title) }}" data-desc="{{ strtolower($book->description??'') }}">
                    <a href="{{ route('books.show',$book->slug) }}" class="book-item-card">
                        <div class="book-mock" style="--cover:{{ $p[0] }};--spine:{{ $p[1] }};">
                            <div class="book-spine"></div>
                            <div class="book-cover">
                                <div class="book-top">
                                    <span class="book-mini-pill">📖 Mini-cours</span>
                                    @if($book->is_free)<span class="book-badge-free">Gratuit</span>@else<span class="book-badge-prem">Premium</span>@endif
                                </div>
                                <div class="book-title-text">{{ $book->title }}</div>
                                <div class="book-desc-text">{{ $book->description ?? 'Mini-cours BRVM : notions, exemples, erreurs à éviter.' }}</div>
                                <div class="book-meta">
                                    <span>⏱️ {{ $book->estimated_minutes }} min</span>
                                    <span>→ Ouvrir</span>
                                </div>
                            </div>
                            <div class="book-shine"></div>
                        </div>
                        <div class="book-footer-text">Clique pour ouvrir →</div>
                    </a>
                </div>
            @endforeach
        </div>

        <div id="emptyState" class="d-none" style="text-align:center;padding:80px 20px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;">
            <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📚</div>
            Aucun livre trouvé
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const input=document.getElementById('bookSearch');
    const items=document.querySelectorAll('.book-item');
    const empty=document.getElementById('emptyState');
    if(!input)return;
    input.addEventListener('input',function(){
        const q=(this.value||'').trim().toLowerCase();
        let v=0;
        items.forEach(el=>{const ok=!q||el.dataset.title.includes(q)||el.dataset.desc.includes(q);el.classList.toggle('d-none',!ok);if(ok)v++;});
        empty.classList.toggle('d-none',v!==0);
    });
})();
document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});
</script>
@endpush
