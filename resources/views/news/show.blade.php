{{-- resources/views/news/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .news-show-page { background: #060910; min-height: 100vh; }

    /* Breadcrumb */
    .news-breadcrumb {
        padding: 24px 0 0;
        display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
    }
    .news-breadcrumb a { color: #6B7590; text-decoration: none; transition: color .25s; }
    .news-breadcrumb a:hover { color: #C9A84C; }
    .news-breadcrumb span { color: rgba(107,117,144,.4); }
    .news-breadcrumb .current { color: #E8EAF0; }

    /* Article card */
    .news-article {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 24px;
        margin-bottom: 48px;
    }
    .news-article::before {
        content: '';
        display: block; height: 3px;
        background: linear-gradient(90deg, #C9A84C, rgba(15,207,164,.6), transparent);
    }

    .news-article-header {
        padding: 32px 36px 24px;
        border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .news-article-meta {
        display: flex; align-items: center; gap: 10px;
        flex-wrap: wrap; margin-bottom: 16px;
    }
    .news-meta-date {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .14em; text-transform: uppercase;
        background: rgba(201,168,76,.08); color: #C9A84C;
        border: 1px solid rgba(201,168,76,.18);
        padding: 4px 12px; border-radius: 100px;
    }
    .news-meta-badge {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        background: rgba(15,207,164,.08); color: #0FCFA4;
        border: 1px solid rgba(15,207,164,.18);
        padding: 4px 12px; border-radius: 100px;
    }

    .news-article-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px, 4vw, 38px); font-weight: 700;
        color: #E8EAF0; line-height: 1.15; margin-bottom: 0;
    }

    .news-article-body {
        padding: 28px 36px 8px;
        font-size: 15px; color: #9AA3B8;
        line-height: 1.85;
    }
    .news-article-body p { margin-bottom: 16px; }

    .news-source-box {
        margin: 8px 36px 24px;
        background: rgba(201,168,76,.04);
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; padding: 14px 20px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        flex-wrap: wrap;
    }
    .news-source-label {
        font-family: 'Syne', sans-serif; font-size: 12px;
        font-weight: 600; letter-spacing: .04em;
        color: #6B7590;
    }
    .news-source-label strong { color: #C9A84C; }

    .news-tags {
        margin: 0 36px 28px;
        display: flex; flex-wrap: wrap; gap: 8px;
    }
    .news-tag {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 600;
        letter-spacing: .04em;
        background: rgba(255,255,255,.04); color: #8A93A8;
        border: 1px solid rgba(255,255,255,.06);
        padding: 4px 10px; border-radius: 100px;
    }

    .cb-btn-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: transparent; color: #E8EAF0 !important;
        font-family: 'Syne', sans-serif; font-weight: 600;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(255,255,255,.12);
        border-radius: 3px; text-decoration: none; transition: all .3s;
    }
    .cb-btn-outline:hover { border-color: #C9A84C; color: #C9A84C !important; background: rgba(201,168,76,.05); }

    .cb-btn-gold {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, #C9A84C, #9B6B15);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 12px; letter-spacing: .07em; text-transform: uppercase;
        padding: 10px 20px; border: none; border-radius: 3px;
        cursor: pointer; text-decoration: none; transition: all .3s;
    }
    .cb-btn-gold:hover { box-shadow: 0 6px 20px rgba(201,168,76,.3); transform: translateY(-1px); }

    .cb-btn-green {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(15,207,164,.1); color: #0FCFA4 !important;
        font-family: 'Syne', sans-serif; font-weight: 700;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(15,207,164,.2);
        border-radius: 3px; text-decoration: none; transition: all .3s;
    }
    .cb-btn-green:hover { background: rgba(15,207,164,.16); border-color: rgba(15,207,164,.4); }

    @media (max-width: 600px) {
        .news-article-header,
        .news-article-body { padding-left: 20px; padding-right: 20px; }
        .news-source-box,
        .news-tags { margin-left: 20px; margin-right: 20px; }
    }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="news-show-page">
<div class="container" style="max-width:860px;">

    <div class="news-breadcrumb cbr">
        <a href="{{ route('news.index') }}">← Actualités</a>
        <span>/</span>
        <span class="current">{{ \Illuminate\Support\Str::limit($news->title, 48) }}</span>
    </div>

    <div class="news-article cbr">

        <div class="news-article-header">
            <div class="news-article-meta">
                <span class="news-meta-date">{{ $news->public_date }}</span>
                @if($news->categorie)
                    <span class="news-meta-badge">{{ $news->categorie }}</span>
                @endif
                @if($news->impact)
                    <span class="news-meta-badge">Impact {{ $news->impact }}</span>
                @endif
            </div>
            <h1 class="news-article-title">{{ $news->title }}</h1>
        </div>

        <div class="news-article-body">
            {!! nl2br(e($news->resume)) !!}
        </div>

        @if($news->source_name || $news->source_url)
        <div class="news-source-box">
            <span class="news-source-label">
                Source : <strong>{{ $news->source_name ?? 'article original' }}</strong>
            </span>
            @if($news->source_url)
                <a href="{{ $news->source_url }}" target="_blank" rel="noopener nofollow" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                    Lire l'article original →
                </a>
            @endif
        </div>
        @endif

        @if(!empty($news->societes) || !empty($news->mots_cles))
        <div class="news-tags">
            @foreach(($news->societes ?? []) as $s)
                <span class="news-tag">🏢 {{ $s }}</span>
            @endforeach
            @foreach(($news->mots_cles ?? []) as $m)
                <span class="news-tag">#{{ $m }}</span>
            @endforeach
        </div>
        @endif

    </div>

    <div class="d-flex justify-content-between align-items-center pb-5 cbr">
        <a href="{{ route('news.index') }}" class="cb-btn-outline">
            ← Toutes les actualités
        </a>
        <a href="{{ route('landing') }}" class="cb-btn-green">
            Accueil →
        </a>
    </div>

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
