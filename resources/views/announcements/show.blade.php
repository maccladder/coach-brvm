{{-- resources/views/announcements/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .ann-show-page { background: #060910; min-height: 100vh; }

    /* Breadcrumb */
    .ann-breadcrumb {
        padding: 24px 0 0;
        display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
    }
    .ann-breadcrumb a { color: #6B7590; text-decoration: none; transition: color .25s; }
    .ann-breadcrumb a:hover { color: #C9A84C; }
    .ann-breadcrumb span { color: rgba(107,117,144,.4); }
    .ann-breadcrumb .current { color: #E8EAF0; }

    /* Article card */
    .ann-article {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 24px;
        margin-bottom: 48px;
    }

    /* Top bar dorée */
    .ann-article::before {
        content: '';
        display: block; height: 3px;
        background: linear-gradient(90deg, #C9A84C, rgba(15,207,164,.6), transparent);
    }

    /* Header article */
    .ann-article-header {
        padding: 32px 36px 24px;
        border-bottom: 1px solid rgba(255,255,255,.05);
    }

    .ann-article-meta {
        display: flex; align-items: center; gap: 10px;
        flex-wrap: wrap; margin-bottom: 16px;
    }
    .ann-meta-date {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .14em; text-transform: uppercase;
        background: rgba(201,168,76,.08); color: #C9A84C;
        border: 1px solid rgba(201,168,76,.18);
        padding: 4px 12px; border-radius: 100px;
    }
    .ann-meta-badge {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        background: rgba(15,207,164,.08); color: #0FCFA4;
        border: 1px solid rgba(15,207,164,.18);
        padding: 4px 12px; border-radius: 100px;
    }

    .ann-article-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px, 4vw, 38px); font-weight: 700;
        color: #E8EAF0; line-height: 1.15; margin-bottom: 0;
    }

    /* Excerpt */
    .ann-article-excerpt {
        padding: 20px 36px 0;
        font-size: 15px; color: #6B7590; font-weight: 300;
        line-height: 1.75; font-style: italic;
        border-bottom: 1px solid rgba(255,255,255,.04);
        padding-bottom: 20px;
    }

    /* Pièce jointe */
    .ann-attachment {
        margin: 24px 36px;
        border-radius: 4px; overflow: hidden;
        border: 1px solid rgba(255,255,255,.06);
    }
    .ann-attachment-img {
        width: 100%; max-height: 520px;
        object-fit: contain; background: #121A2C;
        display: block;
    }
    .ann-attachment-footer {
        background: #121A2C;
        border-top: 1px solid rgba(255,255,255,.05);
        padding: 12px 16px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .ann-attachment-label {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
        color: #6B7590;
    }

    .ann-pdf-box {
        margin: 24px 36px;
        background: rgba(201,168,76,.04);
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; padding: 16px 20px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        flex-wrap: wrap;
    }
    .ann-pdf-label {
        font-family: 'Syne', sans-serif; font-size: 12px;
        font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
        color: #C9A84C; display: flex; align-items: center; gap: 8px;
    }

    /* Contenu */
    .ann-article-body {
        padding: 28px 36px 32px;
        font-size: 15px; color: #9AA3B8;
        line-height: 1.85;
    }
    .ann-article-body p { margin-bottom: 16px; }

    /* Signature */
    .ann-signature {
        margin: 0 36px 32px;
        padding: 20px 24px;
        background: rgba(15,207,164,.03);
        border: 1px solid rgba(15,207,164,.08);
        border-radius: 4px;
        display: flex; align-items: center; gap: 16px;
        flex-wrap: wrap;
    }
    .ann-signature-avatar {
        width: 48px; height: 48px; border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(201,168,76,.2);
        flex-shrink: 0;
    }
    .ann-signature-name {
        font-family: 'Syne', sans-serif; font-size: 13px;
        font-weight: 700; color: #E8EAF0; margin-bottom: 2px;
    }
    .ann-signature-sub { font-size: 12px; color: #6B7590; }
    .ann-signature-logo { margin-left: auto; height: 32px; width: auto; }

    /* Boutons */
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

    /* Mobile */
    @media (max-width: 600px) {
        .ann-article-header,
        .ann-article-excerpt,
        .ann-article-body,
        .ann-signature { padding-left: 20px; padding-right: 20px; }
        .ann-attachment,
        .ann-pdf-box { margin-left: 20px; margin-right: 20px; }
        .ann-signature-logo { display: none; }
    }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="ann-show-page">
<div class="container" style="max-width:860px;">

    {{-- Breadcrumb --}}
    <div class="ann-breadcrumb cbr">
        <a href="{{ route('announcements.index') }}">← Annonces</a>
        <span>/</span>
        <span class="current">{{ \Illuminate\Support\Str::limit($announcement->title, 48) }}</span>
    </div>

    {{-- Article --}}
    <div class="ann-article cbr">

        {{-- Header --}}
        <div class="ann-article-header">
            <div class="ann-article-meta">
                <span class="ann-meta-date">{{ $announcement->public_date }}</span>
                <span class="ann-meta-badge">📢 Annonce BRVM</span>
            </div>
            <h1 class="ann-article-title">{{ $announcement->title }}</h1>
        </div>

        {{-- Excerpt --}}
        @if($announcement->excerpt)
            <div class="ann-article-excerpt">{{ $announcement->excerpt }}</div>
        @endif

        {{-- Pièce jointe --}}
        @if($announcement->attachment_url)
            @if($announcement->attachment_type === 'image')
                <div class="ann-attachment">
                    <img src="{{ $announcement->attachment_url }}"
                         alt="Pièce jointe"
                         class="ann-attachment-img">
                    <div class="ann-attachment-footer">
                        <span class="ann-attachment-label">🖼️ Pièce jointe</span>
                        <a href="{{ $announcement->attachment_url }}"
                           target="_blank"
                           class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                            Ouvrir l'image →
                        </a>
                    </div>
                </div>

            @elseif($announcement->attachment_type === 'pdf')
                <div class="ann-pdf-box">
                    <span class="ann-pdf-label">
                        <i class="bi bi-file-earmark-pdf"></i>
                        Pièce jointe PDF disponible
                    </span>
                    <a href="{{ $announcement->attachment_url }}"
                       target="_blank"
                       class="cb-btn-gold" style="font-size:11px;padding:9px 18px;">
                        <i class="bi bi-download"></i> Ouvrir le PDF
                    </a>
                </div>
            @endif
        @endif

        {{-- Contenu --}}
        <div class="ann-article-body">
            {!! nl2br(e($announcement->content)) !!}
        </div>

        {{-- Signature --}}
        <div class="ann-signature">
            <img src="{{ asset('avatars/coach.png') }}"
                 alt="Coach BRVM"
                 class="ann-signature-avatar"
                 onerror="this.style.display='none'">
            <div>
                <div class="ann-signature-name">Coach BRVM</div>
                <div class="ann-signature-sub">Service indépendant — analyses & explications pédagogiques.</div>
            </div>
            <img src="{{ asset('img/coach-brvm-logo.png') }}"
                 alt="Coach BRVM"
                 class="ann-signature-logo"
                 onerror="this.style.display='none'">
        </div>

    </div>

    {{-- Navigation bas --}}
    <div class="d-flex justify-content-between align-items-center pb-5 cbr">
        <a href="{{ route('announcements.index') }}" class="cb-btn-outline">
            ← Toutes les annonces
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
