@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="books-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-dark-subtle text-dark fw-semibold">Mini-cours</span>
                    <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">Gratuit & Premium</span>
                </div>
                <h3 class="fw-bold mb-1">📚 Livres instructifs</h3>
                <div class="text-muted">Mini-cours à lire comme un livre, page par page.</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('landing') }}" class="btn btn-outline-secondary">
                    ← Accueil
                </a>
                <a href="{{ route('formations.brvm') }}" class="btn btn-outline-success">
                    🎓 Formations
                </a>
            </div>
        </div>

        {{-- Search --}}
        <div class="mt-3">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0">🔎</span>
                <input id="bookSearch" type="text" class="form-control border-start-0"
                       placeholder="Rechercher un livre (ex : dividendes, action, obligation...)">
            </div>
            <div class="small text-muted mt-1">Astuce : tape un mot-clé, la liste se filtre instantanément.</div>
        </div>
    </div>

    {{-- Grid --}}
    <div class="row g-4" id="booksGrid">
        @foreach($books as $book)
            @php
                $palettes = [
                    ['#0ea5e9', '#0369a1'], // bleu
                    ['#22c55e', '#15803d'], // vert
                    ['#f59e0b', '#b45309'], // orange
                    ['#a855f7', '#6d28d9'], // violet
                    ['#ef4444', '#991b1b'], // rouge
                    ['#14b8a6', '#0f766e'], // teal
                ];
                $p = $palettes[$book->id % count($palettes)];
                $c1 = $p[0];
                $c2 = $p[1];
            @endphp

            <div class="col-12 col-sm-6 col-lg-4 book-item"
                 data-title="{{ strtolower($book->title) }}"
                 data-desc="{{ strtolower($book->description ?? '') }}">
                <a href="{{ route('books.show', $book->slug) }}" class="text-decoration-none">
                    <div class="book-card h-100">
                        <div class="book-mock" style="--cover: {{ $c1 }}; --spine: {{ $c2 }};">
                            <div class="book-spine"></div>

                            <div class="book-cover">
                                <div class="book-top">
                                    <div class="mini-pill">📖 Mini-cours</div>

                                    <div class="book-badge">
                                        @if($book->is_free)
                                            <span class="badge bg-success">Gratuit</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Premium</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="book-title">
                                    {{ $book->title }}
                                </div>

                                @if(!empty($book->description))
                                    <div class="book-desc">
                                        {{ $book->description }}
                                    </div>
                                @else
                                    <div class="book-desc">
                                        Mini-cours BRVM : notions, exemples, erreurs à éviter.
                                    </div>
                                @endif

                                <div class="book-meta">
                                    <span class="text-white-50 small">⏱️ {{ $book->estimated_minutes }} min</span>
                                    <span class="text-white-50 small">→ Ouvrir</span>
                                </div>
                            </div>

                            <div class="book-shine"></div>
                        </div>

                        <div class="book-footer">
                            <div class="text-muted small">Clique pour ouvrir →</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Empty state (search) --}}
    <div id="emptyState" class="d-none">
        <div class="alert alert-light border shadow-sm mt-4">
            <div class="fw-semibold mb-1">Aucun livre trouvé</div>
            <div class="text-muted small">Essaie un autre mot-clé (ex : “dividendes”, “obligation”, “action”).</div>
        </div>
    </div>
</div>

<style>
/* ===== Header / Hero ===== */
.books-hero{
    background: linear-gradient(135deg, rgba(13,110,253,.08), rgba(34,197,94,.08));
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.06);
}
.mini-pill{
    font-size: .78rem;
    font-weight: 700;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.20);
    color: rgba(255,255,255,.95);
    border: 1px solid rgba(255,255,255,.25);
}

/* ====== CARTE LIVRE (INDEX) ====== */
.book-card{
    border-radius: 16px;
}

.book-mock{
    position: relative;
    height: 220px;
    border-radius: 16px;
    transform: translateZ(0);
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    box-shadow: 0 14px 28px rgba(0,0,0,.10);
    overflow: hidden;
    display: grid;
    grid-template-columns: 34px 1fr;
    perspective: 1000px;
}

.book-mock:hover{
    transform: translateY(-3px) rotateX(1deg);
    box-shadow: 0 18px 38px rgba(0,0,0,.14);
    filter: saturate(1.05);
}

/* Tranche (spine) */
.book-spine{
    background: linear-gradient(to bottom, var(--spine), #0b1220);
    position: relative;
}

/* petits traits pour faire “pages” sur la tranche */
.book-spine::after{
    content:"";
    position:absolute;
    left:6px; right:6px; top:12px; bottom:12px;
    background: repeating-linear-gradient(
        to bottom,
        rgba(255,255,255,.18),
        rgba(255,255,255,.18) 2px,
        transparent 2px,
        transparent 8px
    );
    border-radius: 10px;
    opacity: .35;
}

/* Couverture */
.book-cover{
    background: linear-gradient(135deg, var(--cover), #111827);
    padding: 16px 16px 14px;
    position: relative;
    display:flex;
    flex-direction:column;
    gap:10px;
}

/* légère texture */
.book-cover::before{
    content:"";
    position:absolute;
    inset:0;
    background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,.18), transparent 55%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,.10), transparent 55%),
        linear-gradient(to right, rgba(255,255,255,.10), transparent 25%);
    pointer-events:none;
}

.book-top{
    position: relative;
    z-index: 2;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
}

.book-badge{
    position: relative;
    z-index: 2;
    display:flex;
    justify-content:flex-end;
}

.book-title{
    position: relative;
    z-index: 2;
    color:#fff;
    font-weight: 900;
    font-size: 1.10rem;
    line-height: 1.2;
    max-height: 2.4em;
    overflow: hidden;
    text-shadow: 0 8px 22px rgba(0,0,0,.25);
}

.book-desc{
    position: relative;
    z-index: 2;
    color: rgba(255,255,255,.82);
    font-size: .93rem;
    line-height: 1.35;
    max-height: 3.9em;
    overflow: hidden;
}

.book-meta{
    position: relative;
    z-index: 2;
    margin-top:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* Brillance sur la couverture */
.book-shine{
    position:absolute;
    inset:0;
    background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,.22) 45%, transparent 60%);
    transform: translateX(-120%);
    transition: transform .5s ease;
    pointer-events:none;
}
.book-mock:hover .book-shine{
    transform: translateX(120%);
}

/* Petit footer sous le livre */
.book-footer{
    padding: 10px 2px 0;
}

/* Search input polish */
.input-group-lg .form-control,
.input-group-lg .input-group-text{
    border-radius: 14px;
}
.input-group-lg .input-group-text{
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.input-group-lg .form-control{
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
</style>

<script>
(function () {
    const input = document.getElementById('bookSearch');
    const items = document.querySelectorAll('.book-item');
    const emptyState = document.getElementById('emptyState');

    function filter() {
        const q = (input.value || '').trim().toLowerCase();
        let visible = 0;

        items.forEach(el => {
            const t = el.dataset.title || '';
            const d = el.dataset.desc || '';
            const ok = !q || t.includes(q) || d.includes(q);
            el.classList.toggle('d-none', !ok);
            if (ok) visible++;
        });

        emptyState.classList.toggle('d-none', visible !== 0);
    }

    if (input) {
        input.addEventListener('input', filter);
    }
})();
</script>
@endsection
