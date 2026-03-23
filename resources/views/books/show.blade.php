{{-- ════════ books/show.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .book-show-page { background:#060910;min-height:100vh; }
    .book-show-header { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:24px 0; }
    .book-show-nav { display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px; }
    .book-show-info h3 { font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,28px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .book-show-info-sub { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }
    .book-show-btns { display:flex;gap:8px; }
    .bk-btn { display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:3px;font-size:16px;cursor:pointer;transition:all .25s;border:none; }
    .bk-btn-nav { background:rgba(255,255,255,.06);color:#E8EAF0;border:1px solid rgba(255,255,255,.12); }
    .bk-btn-nav:hover:not(:disabled) { background:rgba(201,168,76,.1);border-color:rgba(201,168,76,.2);color:#C9A84C; }
    .bk-btn-nav:disabled { opacity:.3;cursor:default; }

    /* Livre */
    .book-reader { margin:28px auto;max-width:980px; }
    .book-wrapper { display:flex;justify-content:center;perspective:1800px; }
    .book { position:relative;display:grid;grid-template-columns:1fr 1fr;width:100%;max-width:980px;min-height:480px;border-radius:8px;background:#1a1f2e;box-shadow:0 24px 60px rgba(0,0,0,.5);overflow:hidden; }
    .book::before { content:"";position:absolute;left:50%;top:0;width:14px;height:100%;transform:translateX(-50%);background:linear-gradient(to right,rgba(0,0,0,.3),rgba(0,0,0,.1),rgba(0,0,0,.3));z-index:5;pointer-events:none; }
    .book-left { background:linear-gradient(to right,#0C1120,#0E1520);border-right:1px solid rgba(255,255,255,.05);position:relative; }
    .book-left::after { content:"";position:absolute;top:0;bottom:0;right:0;width:24px;background:linear-gradient(to right,transparent,rgba(0,0,0,.1));pointer-events:none; }
    .book-left-inner { padding:28px;color:#6B7590; }
    .book-left-brand { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#C9A84C;margin-bottom:12px; }
    .book-left-chapter { font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#9AA3B8;line-height:1.4; }
    .book-left-hint { font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:rgba(107,117,144,.5);margin-top:16px; }
    .book-right { background:#0C1120;position:relative;transform-origin:left center;will-change:transform,opacity; }
    .book-right::before { content:"";position:absolute;inset:0;background:radial-gradient(circle at 40% 30%,rgba(0,0,0,.04),transparent 55%),linear-gradient(to left,rgba(0,0,0,.06),transparent 30%);pointer-events:none; }
    .page-content { padding:30px 32px 36px;font-size:1rem;line-height:1.8;color:#9AA3B8; }
    .page-content h1,.page-content h2,.page-content h3 { font-family:'Playfair Display',serif;color:#E8EAF0;margin-bottom:12px; }
    .page-content strong { color:#E8EAF0; }
    .page-content ul { margin-bottom:12px; }
    .book-footer-bar { background:#0C1120;border-top:1px solid rgba(255,255,255,.05);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;max-width:980px;margin:0 auto; }
    .book-footer-hint { font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#6B7590; }
    .bk-btn-continue { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:10px 22px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .bk-btn-continue:hover:not(:disabled) { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .bk-btn-continue:disabled { opacity:.3;cursor:default;transform:none; }

    @keyframes flipNext { 0%{transform:rotateY(0deg);opacity:1} 100%{transform:rotateY(-38deg);opacity:0;box-shadow:inset -60px 0 60px rgba(0,0,0,.25)} }
    @keyframes flipPrev { 0%{transform:rotateY(0deg);opacity:1} 100%{transform:rotateY(38deg);opacity:0;box-shadow:inset 60px 0 60px rgba(0,0,0,.25)} }
    .turn-next { animation:flipNext .45s ease forwards; }
    .turn-prev { animation:flipPrev .45s ease forwards; }
</style>
@endpush

@section('content')
<div class="book-show-page">
    <div class="book-show-header">
        <div class="container" style="max-width:1000px;">
            <div class="book-show-nav">
                <div class="book-show-info">
                    <a href="{{ route('books.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:10px;">← Livres</a>
                    <h3>{{ $book->title }}</h3>
                    <div class="book-show-info-sub">Page {{ $currentPage }} / {{ $pagesCount }}</div>
                </div>
                <div class="book-show-btns">
                    <button class="bk-btn bk-btn-nav" id="btnPrev" data-next="{{ max(1,$currentPage-1) }}" @disabled($currentPage<=1)>←</button>
                    <button class="bk-btn bk-btn-nav" id="btnNext" data-next="{{ min($pagesCount,$currentPage+1) }}" @disabled($currentPage>=$pagesCount)>→</button>
                </div>
            </div>
        </div>
    </div>

    <div class="book-reader">
        <div class="book-wrapper">
            <div class="book">
                <div class="book-left">
                    <div class="book-left-inner">
                        <div class="book-left-brand">Coach BRVM · Livre</div>
                        <div class="book-left-chapter">{{ $currentTitle }}</div>
                        <div class="book-left-hint">Flèches ← → pour feuilleter</div>
                    </div>
                </div>
                <div class="book-right" id="page">
                    <div class="page-content">{!! $currentContent !!}</div>
                </div>
            </div>
        </div>
        <div class="book-footer-bar">
            <div class="book-footer-hint">Clavier ← → aussi disponible</div>
            <button class="bk-btn-continue" id="btnContinue" data-next="{{ min($pagesCount,$currentPage+1) }}" @disabled($currentPage>=$pagesCount)>Continuer →</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const page=document.getElementById('page');
    function goTo(next,dir){
        if(!next)return;
        page.classList.remove('turn-next','turn-prev');
        page.classList.add(dir==='prev'?'turn-prev':'turn-next');
        setTimeout(()=>{const u=new URL(window.location.href);u.searchParams.set('page',next);window.location.href=u.toString();},420);
    }
    const btnPrev=document.getElementById('btnPrev');
    const btnNext=document.getElementById('btnNext');
    const btnCont=document.getElementById('btnContinue');
    if(btnPrev)btnPrev.addEventListener('click',()=>goTo(btnPrev.dataset.next,'prev'));
    if(btnNext)btnNext.addEventListener('click',()=>goTo(btnNext.dataset.next,'next'));
    if(btnCont)btnCont.addEventListener('click',()=>goTo(btnCont.dataset.next,'next'));
    document.addEventListener('keydown',e=>{
        if(e.key==='ArrowLeft'&&btnPrev&&!btnPrev.disabled)goTo(btnPrev.dataset.next,'prev');
        if(e.key==='ArrowRight'&&btnNext&&!btnNext.disabled)goTo(btnNext.dataset.next,'next');
    });
})();
</script>
@endpush
