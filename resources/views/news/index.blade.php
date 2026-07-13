{{-- resources/views/news/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .news-page { background: #060910; min-height: 100vh; }

    /* Hero */
    .news-hero {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px;
        position: relative; overflow: hidden;
    }
    .news-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .news-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #0FCFA4;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .news-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .news-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 5vw, 48px); font-weight: 900;
        color: #E8EAF0; line-height: 1.08; margin-bottom: 10px;
    }
    .news-hero-title em { font-style: italic; color: #C9A84C; }
    .news-hero-desc { font-size: 14px; color: #6B7590; font-weight: 300; line-height: 1.7; }

    /* Cards */
    .news-card {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 4px;
        padding: 22px 24px;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all .32s;
        position: relative;
        overflow: hidden;
    }
    .news-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, #C9A84C, transparent);
        opacity: 0; transition: opacity .32s;
    }
    .news-card:hover {
        border-color: rgba(201,168,76,.2);
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(0,0,0,.35);
        color: inherit;
    }
    .news-card:hover::before { opacity: 1; }

    .news-card-top {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .news-card-date {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .12em; text-transform: uppercase; color: #6B7590;
        display: flex; align-items: center; gap: 8px;
    }
    .news-card-date::before {
        content: '';
        width: 6px; height: 6px; border-radius: 50%;
        background: #C9A84C; flex-shrink: 0;
    }
    .news-card-badge {
        font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        background: rgba(15,207,164,.08); color: #0FCFA4;
        border: 1px solid rgba(15,207,164,.18);
        padding: 2px 9px; border-radius: 100px;
    }
    .news-card-title {
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
        color: #E8EAF0; line-height: 1.4; margin-bottom: 8px;
        transition: color .25s;
    }
    .news-card:hover .news-card-title { color: #C9A84C; }
    .news-card-excerpt { font-size: 13px; color: #6B7590; line-height: 1.65; }
    .news-card-source { font-size: 11px; color: #4B5570; margin-top: 8px; }
    .news-card-arrow {
        position: absolute; right: 22px; top: 50%;
        transform: translateY(-50%);
        font-family: 'Syne', sans-serif; font-size: 18px;
        color: rgba(201,168,76,.2);
        transition: all .25s;
    }
    .news-card:hover .news-card-arrow { color: #C9A84C; transform: translateY(-50%) translateX(4px); }

    /* Empty */
    .news-empty {
        text-align: center; padding: 80px 20px;
        background: rgba(12,17,32,.6);
        border: 1px solid rgba(201,168,76,.08);
        border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 13px;
        letter-spacing: .08em; text-transform: uppercase; color: #6B7590;
    }

    /* Pagination */
    .pagination .page-link { background: #0C1120 !important; border-color: rgba(255,255,255,.08) !important; color: #6B7590 !important; font-family: 'Syne', sans-serif; font-size: 12px; }
    .pagination .page-link:hover { background: rgba(201,168,76,.1) !important; color: #C9A84C !important; border-color: rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background: linear-gradient(135deg,#C9A84C,#9B6B15) !important; border-color: transparent !important; color: #050810 !important; }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="news-page">

    <div class="news-hero">
        <div class="news-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="news-hero-tag">Marché BRVM</p>
            <h1 class="news-hero-title">Actualités <em>BRVM</em></h1>
            <p class="news-hero-desc">
                L'essentiel de l'actualité financière africaine, sélectionnée et analysée chaque jour.
            </p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        @forelse($newsList as $n)
            <a href="{{ route('news.show', $n->slug) }}" class="news-card mb-3 cbr">
                <div class="news-card-top">
                    <div class="news-card-date">{{ $n->public_date }}</div>
                    @if($n->categorie)
                        <span class="news-card-badge">{{ $n->categorie }}</span>
                    @endif
                    @if($n->impact)
                        <span class="news-card-badge">Impact {{ $n->impact }}</span>
                    @endif
                </div>
                <div class="news-card-title">{{ $n->title }}</div>
                <div class="news-card-excerpt">{{ \Illuminate\Support\Str::limit($n->resume, 160) }}</div>
                @if($n->source_name)
                    <div class="news-card-source">Source : {{ $n->source_name }}</div>
                @endif
                <span class="news-card-arrow">→</span>
            </a>
        @empty
            <div class="news-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📰</div>
                Aucune actualité pour le moment
            </div>
        @endforelse

        @if($newsList->hasPages())
            <div class="mt-4 d-flex justify-content-center cbr">
                {{ $newsList->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: 0.06 });
    cbEls.forEach(el => cbObs.observe(el));
</script>
@endpush
