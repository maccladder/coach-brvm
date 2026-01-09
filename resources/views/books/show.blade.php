@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <a href="{{ route('books.index') }}" class="text-muted text-decoration-none">← Retour aux livres</a>
            <h3 class="fw-bold mb-0">{{ $book->title }}</h3>
            <div class="text-muted small">Page {{ $currentPage }} / {{ $pagesCount }}</div>
        </div>

        <div class="d-flex gap-2">
            <button
                class="btn btn-outline-secondary"
                id="btnPrev"
                data-next="{{ max(1, $currentPage - 1) }}"
                @disabled($currentPage <= 1)
            >←</button>

            <button
                class="btn btn-outline-secondary"
                id="btnNext"
                data-next="{{ min($pagesCount, $currentPage + 1) }}"
                @disabled($currentPage >= $pagesCount)
            >→</button>
        </div>
    </div>

    <div class="card shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <div class="fw-bold h5 mb-0">{{ $currentTitle }}</div>
            </div>

            {{-- ZONE LIVRE (double page + reliure + vrai look "livre") --}}
            <div class="p-4">
                <div class="book-wrapper">
                    <div class="book">
                        <div class="book-left">
                            {{-- Optionnel: tu peux mettre ici un petit "sommaire" ou une citation --}}
                            <div class="book-left-inner">
                                <div class="small text-muted mb-2">Coach BRVM • Livre</div>
                                <div class="fw-semibold">Chapitre</div>
                                <div class="text-muted small">{{ $currentTitle }}</div>

                                <hr class="my-3">

                                <div class="text-muted small">
                                    Astuce : utilise les flèches ← → pour feuilleter.
                                </div>
                            </div>
                        </div>

                        <div class="book-right" id="page">
                            <div class="page-content" id="pageContent">
                                {!! $currentContent !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 border-top d-flex align-items-center justify-content-between">
                <div class="small text-muted">Astuce : flèches clavier ← →</div>

                <button
                    class="btn btn-primary"
                    id="btnContinue"
                    data-next="{{ min($pagesCount, $currentPage + 1) }}"
                    @disabled($currentPage >= $pagesCount)
                >Continuer →</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ====== LIVRE (vrai look) ====== */
.book-wrapper{
    display: flex;
    justify-content: center;
    perspective: 1800px;
}

.book{
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    width: 100%;
    max-width: 980px;
    min-height: 480px;
    border-radius: 16px;
    background: #e5e7eb;
    box-shadow: 0 18px 40px rgba(0,0,0,.15);
    overflow: hidden;
}

/* reliure centrale */
.book::before{
    content:"";
    position:absolute;
    left:50%;
    top:0;
    width:14px;
    height:100%;
    transform:translateX(-50%);
    background: linear-gradient(to right,
        rgba(0,0,0,.18),
        rgba(0,0,0,.06),
        rgba(0,0,0,.18)
    );
    z-index: 5;
    pointer-events:none;
}

/* page gauche */
.book-left{
    background: linear-gradient(to right, #f8fafc, #eef2f7);
    border-right: 1px solid rgba(0,0,0,.12);
    position: relative;
}

.book-left::after{
    content:"";
    position:absolute;
    top:0; bottom:0; right:0;
    width:26px;
    background: linear-gradient(to right, transparent, rgba(0,0,0,.06));
    pointer-events:none;
}

.book-left-inner{
    padding: 26px;
}

/* page droite (celle qui “tourne”) */
.book-right{
    background: #fff;
    position: relative;
    transform-origin: left center;
    will-change: transform, opacity, box-shadow;
}

/* léger effet papier */
.book-right::before{
    content:"";
    position:absolute;
    inset:0;
    background:
        radial-gradient(circle at 40% 30%, rgba(0,0,0,.035), transparent 55%),
        linear-gradient(to left, rgba(0,0,0,.05), transparent 28%);
    pointer-events:none;
}

/* contenu */
.page-content{
    padding: 30px 30px 34px;
    font-size: 1.05rem;
    line-height: 1.7;
}
.page-content ul{ margin-bottom: 0; }

/* ====== ANIM FEUILLETAGE ====== */
.turn-next{
    animation: flipNext .45s ease forwards;
}
.turn-prev{
    animation: flipPrev .45s ease forwards;
}

@keyframes flipNext{
    0%   { transform: rotateY(0deg);   opacity: 1; box-shadow: inset 0 0 0 rgba(0,0,0,0); }
    100% { transform: rotateY(-38deg); opacity: .0; box-shadow: inset -60px 0 60px rgba(0,0,0,.22); }
}
@keyframes flipPrev{
    0%   { transform: rotateY(0deg);   opacity: 1; box-shadow: inset 0 0 0 rgba(0,0,0,0); }
    100% { transform: rotateY(38deg);  opacity: .0; box-shadow: inset 60px 0 60px rgba(0,0,0,.22); }
}
</style>

<script>
(function() {
    const page = document.getElementById('page');

    function goTo(next, direction) {
        if (!next) return;

        page.classList.remove('turn-next', 'turn-prev');
        page.classList.add(direction === 'prev' ? 'turn-prev' : 'turn-next');

        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('page', next);
            window.location.href = url.toString();
        }, 420);
    }

    // boutons
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const btnContinue = document.getElementById('btnContinue');

    if (btnPrev) btnPrev.addEventListener('click', () => goTo(btnPrev.dataset.next, 'prev'));
    if (btnNext) btnNext.addEventListener('click', () => goTo(btnNext.dataset.next, 'next'));
    if (btnContinue) btnContinue.addEventListener('click', () => goTo(btnContinue.dataset.next, 'next'));

    // clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' && btnPrev && !btnPrev.disabled) goTo(btnPrev.dataset.next, 'prev');
        if (e.key === 'ArrowRight' && btnNext && !btnNext.disabled) goTo(btnNext.dataset.next, 'next');
    });
})();
</script>
@endsection
