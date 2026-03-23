@extends('layouts.app')

@push('styles')
<style>
    .watch-page { background: #060910; min-height: 100vh; }

    .watch-header {
        background: #0C1120;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 24px 0;
    }
    .watch-header-inner {
        display: flex; justify-content: space-between;
        align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .watch-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(20px, 3.5vw, 30px); font-weight: 900;
        color: #E8EAF0; margin-bottom: 4px;
    }
    .watch-sub {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .1em; text-transform: uppercase;
        color: #6B7590;
    }

    /* Player */
    .watch-player-card {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; overflow: hidden;
        margin-top: 28px;
    }
    .watch-player-card::before {
        content:''; display:block; height:2px;
        background: linear-gradient(90deg, #C9A84C, rgba(252,129,74,.6), transparent);
    }
    .watch-player-wrap {
        background: #000;
        aspect-ratio: 16/9;
    }
    .watch-player-wrap iframe {
        width: 100%; height: 100%;
        border: none; display: block;
    }
    .watch-player-footer {
        padding: 14px 20px;
        background: #121A2C;
        border-top: 1px solid rgba(255,255,255,.05);
        display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
        color: #6B7590;
    }
    .watch-player-footer i { color: #FC814A; }

    /* Config error */
    .watch-error {
        padding: 48px 32px; text-align: center;
    }
    .watch-error-icon { font-size: 36px; margin-bottom: 14px; opacity: .5; }
    .watch-error-title {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase; color: #FF6B6B;
        margin-bottom: 8px;
    }
    .watch-error-msg { font-size: 13px; color: #6B7590; }
    .watch-error-msg code {
        background: rgba(255,107,107,.08); color: #FF6B6B;
        padding: 2px 6px; border-radius: 3px; font-size: 12px;
    }

    /* Boutons */
    .cb-btn-outline {
        display:inline-flex; align-items:center; gap:8px;
        background:transparent; color:#E8EAF0 !important;
        font-family:'Syne',sans-serif; font-weight:600;
        font-size:12px; letter-spacing:.06em; text-transform:uppercase;
        padding:9px 18px; border:1px solid rgba(255,255,255,.12);
        border-radius:3px; text-decoration:none; transition:all .3s;
    }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
@php
    $sub      = config('services.cloudflare_stream.customer_subdomain');
    $videoId  = $cloudflareVideoId;
    $iframeSrc = $sub ? "https://{$sub}/{$videoId}/iframe" : null;
@endphp
<div class="watch-page">

    <div class="watch-header">
        <div class="container" style="max-width:1100px;">
            <div class="watch-header-inner">
                <div>
                    <a href="{{ route('my.products') }}"
                       style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;
                              letter-spacing:.08em;text-transform:uppercase;color:#6B7590;
                              text-decoration:none;display:inline-flex;align-items:center;
                              gap:6px;margin-bottom:10px;">
                        ← Mes produits
                    </a>
                    <h1 class="watch-title">{{ $product->title }}</h1>
                    <div class="watch-sub">🎬 Vidéo · Lecture en ligne</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('marketplace.show', $product->slug) }}"
                       class="cb-btn-outline" style="font-size:11px;padding:8px 14px;">
                        Voir la fiche
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5" style="max-width:1100px;">
        <div class="watch-player-card cbr">

            @if(!$iframeSrc)
                <div class="watch-error">
                    <div class="watch-error-icon">⚠️</div>
                    <div class="watch-error-title">Configuration manquante</div>
                    <div class="watch-error-msg">
                        Clé manquante : <code>services.cloudflare_stream.customer_subdomain</code><br>
                        <span style="margin-top:6px;display:block;">
                            Variable env : <code>CLOUDFLARE_STREAM_CUSTOMER_SUBDOMAIN</code>
                        </span>
                    </div>
                </div>
            @else
                <div class="watch-player-wrap">
                    <iframe
                        src="{{ $iframeSrc }}"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="watch-player-footer">
                    <i class="bi bi-shield-lock-fill"></i>
                    Vidéo protégée · Accessible uniquement depuis votre compte · Sans téléchargement
                </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => {
            if(e.isIntersecting) el.classList.add('on');
        }, { threshold: .06 }).observe(el);
    });
</script>
@endpush
