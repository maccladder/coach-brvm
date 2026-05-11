{{-- ════════ forum/index.blade.php ════════ --}}
@extends('layouts.app')
@section('title', 'Forum – Coach BRVM')

@push('styles')
<style>
    .forum-page { background: #060910; min-height: 100vh; }

    .forum-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.08) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 48px 0 40px;
    }
    .forum-label {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #C9A84C;
        display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
    }
    .forum-label::before { content: ''; width: 28px; height: 1px; background: #C9A84C; }
    .forum-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(26px, 4vw, 42px); font-weight: 900;
        color: #E8EAF0; line-height: 1.15; margin-bottom: 10px;
    }
    .forum-title em { font-style: italic; color: #C9A84C; }
    .forum-subtitle { font-size: 15px; color: #6B7590; line-height: 1.7; max-width: 540px; }

    /* Category cards */
    .forum-cat-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 24px; height: 100%;
        text-decoration: none !important; display: block;
        transition: border-color .3s, transform .3s;
    }
    .forum-cat-card:hover { border-color: rgba(201,168,76,.3); transform: translateY(-3px); }
    .forum-cat-icon { font-size: 30px; margin-bottom: 12px; }
    .forum-cat-name {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: #E8EAF0; margin-bottom: 6px;
    }
    .forum-cat-desc { font-size: 13px; color: #6B7590; line-height: 1.65; margin: 0 0 14px; }
    .forum-cat-stats {
        display: flex; gap: 16px;
        font-family: 'Syne', sans-serif; font-size: 11px; letter-spacing: .06em;
        text-transform: uppercase; color: #6B7590;
    }
    .forum-cat-stats span { color: #C9A84C; font-weight: 700; margin-right: 3px; }

    /* Recent topics */
    .forum-topic-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .forum-topic-row:last-child { border-bottom: none; }
    .forum-topic-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: rgba(201,168,76,.15); border: 1px solid rgba(201,168,76,.2);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: #C9A84C;
    }
    .forum-topic-title {
        font-family: 'Syne', sans-serif; font-size: 13.5px; font-weight: 600; color: #E8EAF0;
        text-decoration: none;
    }
    .forum-topic-title:hover { color: #C9A84C; }
    .forum-topic-meta { font-size: 12px; color: #6B7590; margin-top: 2px; }
    .forum-topic-badge {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        background: rgba(201,168,76,.1); color: #C9A84C;
        border: 1px solid rgba(201,168,76,.2); border-radius: 3px;
        padding: 2px 7px; white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="forum-page">

    {{-- HERO --}}
    <section class="forum-hero">
        <div class="container" style="max-width:1100px;">
            <div class="forum-label">Forum</div>
            <h1 class="forum-title">La communauté des <em>investisseurs BRVM</em></h1>
            <p class="forum-subtitle">
                Échangez, posez vos questions, partagez vos analyses.
                La lecture est libre pour tous — publiez en vous connectant.
            </p>
        </div>
    </section>

    {{-- CATÉGORIES --}}
    <section style="background:#060910; padding:56px 0 40px;">
        <div class="container" style="max-width:1100px;">
            <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#6B7590;margin-bottom:20px;">Catégories</p>
            <div class="row g-3">
                @foreach($categories as $cat)
                <div class="col-md-6">
                    <a href="{{ route('forum.category', $cat->slug) }}" class="forum-cat-card">
                        <div class="forum-cat-icon">{{ $cat->icon }}</div>
                        <div class="forum-cat-name">{{ $cat->name }}</div>
                        <p class="forum-cat-desc">{{ $cat->description }}</p>
                        <div class="forum-cat-stats">
                            <div><span>{{ $cat->topics_count }}</span> sujet{{ $cat->topics_count !== 1 ? 's' : '' }}</div>
                            <div><span>{{ $cat->posts_count }}</span> réponse{{ $cat->posts_count !== 1 ? 's' : '' }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- DERNIERS SUJETS --}}
    @if($recentTopics->isNotEmpty())
    <section style="background:#0C1120; border-top:1px solid rgba(255,255,255,.05); padding:48px 0;">
        <div class="container" style="max-width:1100px;">
            <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#6B7590;margin-bottom:20px;">Dernières discussions</p>
            <div style="background:#060910; border:1px solid rgba(255,255,255,.06); border-radius:6px; padding:0 20px;">
                @foreach($recentTopics as $topic)
                <div class="forum-topic-row">
                    <div class="forum-topic-avatar">
                        {{ strtoupper(substr($topic->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <a href="{{ route('forum.topic', [$topic->category->slug, $topic->slug]) }}" class="forum-topic-title">
                            {{ $topic->title }}
                        </a>
                        <div class="forum-topic-meta">
                            par {{ $topic->user->name ?? 'Anonyme' }}
                            &nbsp;·&nbsp; {{ $topic->created_at->diffForHumans() }}
                            &nbsp;·&nbsp; {{ $topic->posts_count }} réponse{{ $topic->posts_count !== 1 ? 's' : '' }}
                        </div>
                    </div>
                    <div class="forum-topic-badge flex-shrink-0">{{ $topic->category->name }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CTA connexion --}}
    @guest
    <section style="background:#060910; padding:48px 0 64px;">
        <div class="container" style="max-width:680px; text-align:center;">
            <div style="background:rgba(201,168,76,.05); border:1px solid rgba(201,168,76,.15); border-radius:8px; padding:36px;">
                <p style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#E8EAF0;margin-bottom:10px;">
                    Participez à la discussion
                </p>
                <p style="font-size:14px;color:#6B7590;line-height:1.75;margin-bottom:24px;">
                    Créez un compte gratuit ou connectez-vous pour publier un sujet, répondre ou liker des contributions.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:#060910 !important;text-decoration:none !important;padding:12px 28px;border-radius:4px;font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.06em;">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:#C9A84C !important;text-decoration:none !important;padding:12px 28px;border-radius:4px;border:1px solid rgba(201,168,76,.4);font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.06em;">
                        Créer un compte
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endguest

</div>
@endsection
