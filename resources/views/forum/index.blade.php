{{-- ════════ forum/index.blade.php ════════ --}}
@extends('layouts.app')
@section('title', 'Forum – Boursiv')

@push('styles')
<style>
    .forum-page { background: var(--cb-paper); min-height: 100vh; }

    .forum-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(176,134,46,.08) 0%, transparent 55%), var(--cb-paper);
        border-bottom: 1px solid var(--cb-border);
        padding: 48px 0 40px;
    }
    .forum-label {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: var(--cb-gold);
        display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
    }
    .forum-label::before { content: ''; width: 28px; height: 1px; background: var(--cb-gold); }
    .forum-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(26px, 4vw, 42px); font-weight: 900;
        color: var(--cb-ink); line-height: 1.15; margin-bottom: 10px;
    }
    .forum-title em { font-style: italic; color: var(--cb-gold); }
    .forum-subtitle { font-size: 15px; color: var(--cb-muted); line-height: 1.7; max-width: 540px; }

    /* Category cards */
    .forum-cat-card {
        background: var(--cb-card); border: 1px solid var(--cb-border);
        border-radius: 6px; padding: 24px; height: 100%;
        text-decoration: none !important; display: block;
        transition: border-color .3s, transform .3s, box-shadow .3s;
    }
    .forum-cat-card:hover { border-color: rgba(176,134,46,.3); transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.07); }
    .forum-cat-icon { font-size: 30px; margin-bottom: 12px; }
    .forum-cat-name {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: var(--cb-ink); margin-bottom: 6px;
    }
    .forum-cat-desc { font-size: 13px; color: var(--cb-muted); line-height: 1.65; margin: 0 0 14px; }
    .forum-cat-stats {
        display: flex; gap: 16px;
        font-family: 'Syne', sans-serif; font-size: 11px; letter-spacing: .06em;
        text-transform: uppercase; color: var(--cb-muted);
    }
    .forum-cat-stats span { color: var(--cb-gold); font-weight: 700; margin-right: 3px; }

    /* Recent topics */
    .forum-topic-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 0; border-bottom: 1px solid var(--cb-border);
    }
    .forum-topic-row:last-child { border-bottom: none; }
    .forum-topic-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: rgba(176,134,46,.12); border: 1px solid rgba(176,134,46,.2);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: var(--cb-gold);
    }
    .forum-topic-title {
        font-family: 'Syne', sans-serif; font-size: 13.5px; font-weight: 600; color: var(--cb-ink);
        text-decoration: none;
    }
    .forum-topic-title:hover { color: var(--cb-gold); }
    .forum-topic-meta { font-size: 12px; color: var(--cb-muted); margin-top: 2px; }
    .forum-topic-badge {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        background: rgba(176,134,46,.1); color: var(--cb-gold);
        border: 1px solid rgba(176,134,46,.2); border-radius: 3px;
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
    <section style="background:var(--cb-paper); padding:56px 0 40px;">
        <div class="container" style="max-width:1100px;">
            <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:20px;">Catégories</p>
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
    <section style="background:var(--cb-card); border-top:1px solid var(--cb-border); padding:48px 0;">
        <div class="container" style="max-width:1100px;">
            <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:20px;">Dernières discussions</p>
            <div style="background:var(--cb-paper); border:1px solid var(--cb-border); border-radius:6px; padding:0 20px;">
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
    <section style="background:var(--cb-paper); padding:48px 0 64px;">
        <div class="container" style="max-width:680px; text-align:center;">
            <div style="background:rgba(176,134,46,.05); border:1px solid rgba(176,134,46,.15); border-radius:8px; padding:36px;">
                <p style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--cb-ink);margin-bottom:10px;">
                    Participez à la discussion
                </p>
                <p style="font-size:14px;color:var(--cb-muted);line-height:1.75;margin-bottom:24px;">
                    Créez un compte gratuit ou connectez-vous pour publier un sujet, répondre ou liker des contributions.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:8px;background:var(--cb-gold);color:#060910 !important;text-decoration:none !important;padding:12px 28px;border-radius:4px;font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.06em;">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:var(--cb-gold) !important;text-decoration:none !important;padding:12px 28px;border-radius:4px;border:1px solid rgba(176,134,46,.4);font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.06em;">
                        Créer un compte
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endguest

</div>
@endsection
