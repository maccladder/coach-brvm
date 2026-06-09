{{-- ════════ forum/category.blade.php ════════ --}}
@extends('layouts.app')
@section('title', $category->name . ' – Forum Boursiv')

@push('styles')
<style>
    .forum-page { background: #060910; min-height: 100vh; }

    .forum-cat-hero {
        background: #060910;
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 40px 0 32px;
    }
    .forum-breadcrumb {
        font-family: 'Syne', sans-serif; font-size: 12px; color: #6B7590;
        margin-bottom: 14px; display: flex; align-items: center; gap: 6px;
    }
    .forum-breadcrumb a { color: #6B7590; text-decoration: none; }
    .forum-breadcrumb a:hover { color: #C9A84C; }
    .forum-breadcrumb .sep { opacity: .4; }

    .forum-cat-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(22px, 3.5vw, 36px); font-weight: 800;
        color: #E8EAF0; margin-bottom: 6px;
    }
    .forum-cat-desc-text { font-size: 14px; color: #6B7590; line-height: 1.7; }

    /* Topic list */
    .forum-topic-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 18px 20px;
        display: flex; align-items: flex-start; gap: 16px;
        text-decoration: none !important; transition: border-color .25s;
        margin-bottom: 8px;
    }
    .forum-topic-card:hover { border-color: rgba(201,168,76,.25); }
    .forum-topic-card-avatar {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        background: rgba(201,168,76,.12); border: 1px solid rgba(201,168,76,.2);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #C9A84C;
    }
    .forum-topic-card-title {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: #E8EAF0; margin-bottom: 4px; line-height: 1.4;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .forum-pinned-badge {
        font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        background: rgba(201,168,76,.1); color: #C9A84C;
        border: 1px solid rgba(201,168,76,.25); border-radius: 3px; padding: 1px 6px;
    }
    .forum-locked-badge {
        font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        background: rgba(255,255,255,.04); color: #6B7590;
        border: 1px solid rgba(255,255,255,.08); border-radius: 3px; padding: 1px 6px;
    }
    .forum-topic-card-meta { font-size: 12.5px; color: #6B7590; line-height: 1.5; }
    .forum-topic-card-stats {
        margin-left: auto; flex-shrink: 0; text-align: right;
        font-family: 'Syne', sans-serif; font-size: 12px; color: #6B7590;
    }
    .forum-topic-card-stats strong { color: #E8EAF0; font-size: 16px; display: block; }
</style>
@endpush

@section('content')
<div class="forum-page">

    {{-- HEADER --}}
    <section class="forum-cat-hero">
        <div class="container" style="max-width:1100px;">
            <div class="forum-breadcrumb">
                <a href="{{ route('forum.index') }}">Forum</a>
                <span class="sep">›</span>
                <span style="color:#C9A84C;">{{ $category->name }}</span>
            </div>
            <h1 class="forum-cat-title">{{ $category->icon }} {{ $category->name }}</h1>
            <p class="forum-cat-desc-text">{{ $category->description }}</p>
        </div>
    </section>

    {{-- TOPICS --}}
    <section style="background:#060910; padding:40px 0 64px;">
        <div class="container" style="max-width:1100px;">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#6B7590;margin:0;">
                    {{ $topics->total() }} sujet{{ $topics->total() !== 1 ? 's' : '' }}
                </p>
                @auth
                <a href="{{ route('forum.topic.create', $category->slug) }}"
                   style="display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:#060910 !important;text-decoration:none !important;padding:10px 22px;border-radius:4px;font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.06em;">
                    + Nouveau sujet
                </a>
                @else
                <a href="{{ route('login') }}"
                   style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:#C9A84C !important;text-decoration:none !important;padding:10px 22px;border-radius:4px;border:1px solid rgba(201,168,76,.35);font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.06em;">
                    Connectez-vous pour publier
                </a>
                @endauth
            </div>

            @if($topics->isEmpty())
            <div style="text-align:center;padding:60px 0;color:#6B7590;">
                <div style="font-size:36px;margin-bottom:12px;">💬</div>
                <p style="font-family:'Syne',sans-serif;font-size:14px;">Aucun sujet pour l'instant. Soyez le premier à lancer la discussion !</p>
            </div>
            @else
            @foreach($topics as $topic)
            <a href="{{ route('forum.topic', [$category->slug, $topic->slug]) }}" class="forum-topic-card">
                <div class="forum-topic-card-avatar">
                    {{ strtoupper(substr($topic->user->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="forum-topic-card-title">
                        {{ $topic->title }}
                        @if($topic->is_pinned)<span class="forum-pinned-badge">📌 Épinglé</span>@endif
                        @if($topic->is_locked)<span class="forum-locked-badge">🔒 Verrouillé</span>@endif
                    </div>
                    <div class="forum-topic-card-meta">
                        par {{ $topic->user->name ?? 'Anonyme' }} · {{ $topic->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="forum-topic-card-stats">
                    <strong>{{ $topic->posts_count }}</strong>
                    réponse{{ $topic->posts_count !== 1 ? 's' : '' }}
                </div>
            </a>
            @endforeach

            <div class="mt-4">
                {{ $topics->links() }}
            </div>
            @endif

        </div>
    </section>

</div>
@endsection
