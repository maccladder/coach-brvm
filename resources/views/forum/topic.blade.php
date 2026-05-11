{{-- ════════ forum/topic.blade.php ════════ --}}
@extends('layouts.app')
@section('title', $topic->title . ' – Forum Coach BRVM')

@push('styles')
<style>
    .forum-page { background: #060910; min-height: 100vh; }

    .forum-topic-hero {
        background: #060910; border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 36px 0 28px;
    }
    .forum-breadcrumb {
        font-family: 'Syne', sans-serif; font-size: 12px; color: #6B7590;
        margin-bottom: 12px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    }
    .forum-breadcrumb a { color: #6B7590; text-decoration: none; }
    .forum-breadcrumb a:hover { color: #C9A84C; }
    .forum-breadcrumb .sep { opacity: .4; }
    .forum-topic-title-h {
        font-family: 'Playfair Display', serif;
        font-size: clamp(20px, 3vw, 32px); font-weight: 800;
        color: #E8EAF0; line-height: 1.25; margin-bottom: 8px;
    }
    .forum-topic-meta-h { font-size: 13px; color: #6B7590; }

    /* Post cards */
    .forum-post {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 24px; margin-bottom: 12px;
    }
    .forum-post-header {
        display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
    }
    .forum-post-avatar {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        background: rgba(201,168,76,.12); border: 1px solid rgba(201,168,76,.2);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; color: #C9A84C;
    }
    .forum-post-author { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: #E8EAF0; }
    .forum-post-date { font-size: 12px; color: #6B7590; margin-top: 2px; }
    .forum-post-body { font-size: 14px; color: #9BA3B8; line-height: 1.8; white-space: pre-wrap; word-break: break-word; }

    /* Like button */
    .forum-like-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: transparent; border: 1px solid rgba(255,255,255,.1);
        color: #6B7590; padding: 6px 14px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all .2s;
    }
    .forum-like-btn:hover { border-color: rgba(201,168,76,.4); color: #C9A84C; }
    .forum-like-btn.liked { border-color: rgba(201,168,76,.5); color: #C9A84C; background: rgba(201,168,76,.08); }

    /* Original post */
    .forum-op { border-left: 3px solid #C9A84C; }

    /* Reply form */
    .forum-reply-box {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 28px;
    }
    .forum-reply-textarea {
        width: 100%; background: #060910; border: 1px solid rgba(255,255,255,.1);
        border-radius: 4px; color: #E8EAF0; font-size: 14px; line-height: 1.7;
        padding: 14px 16px; resize: vertical; min-height: 130px; font-family: inherit;
        outline: none; transition: border-color .2s;
    }
    .forum-reply-textarea:focus { border-color: rgba(201,168,76,.4); }
    .forum-reply-textarea::placeholder { color: #6B7590; }
    .forum-reply-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: #C9A84C; color: #060910 !important; text-decoration: none !important;
        padding: 12px 28px; border-radius: 4px; border: none;
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: .06em;
        cursor: pointer; transition: background .2s;
    }
    .forum-reply-btn:hover { background: #b8973e; }
</style>
@endpush

@section('content')
<div class="forum-page">

    {{-- HEADER --}}
    <section class="forum-topic-hero">
        <div class="container" style="max-width:860px;">
            <div class="forum-breadcrumb">
                <a href="{{ route('forum.index') }}">Forum</a>
                <span class="sep">›</span>
                <a href="{{ route('forum.category', $category->slug) }}">{{ $category->name }}</a>
                <span class="sep">›</span>
                <span style="color:#C9A84C;">{{ Str::limit($topic->title, 50) }}</span>
            </div>
            <h1 class="forum-topic-title-h">
                {{ $topic->title }}
                @if($topic->is_pinned) <span style="font-size:18px;">📌</span> @endif
                @if($topic->is_locked) <span style="font-size:18px;">🔒</span> @endif
            </h1>
            <div class="forum-topic-meta-h">
                par {{ $topic->user->name ?? 'Anonyme' }}
                &nbsp;·&nbsp; {{ $topic->created_at->format('d/m/Y à H:i') }}
                &nbsp;·&nbsp; {{ $topic->views }} vue{{ $topic->views !== 1 ? 's' : '' }}
            </div>
        </div>
    </section>

    <section style="background:#060910; padding:36px 0 64px;">
        <div class="container" style="max-width:860px;">

            @if(session('success'))
            <div style="background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2);border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:13.5px;color:#25D366;">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:13.5px;color:#dc3545;">
                {{ session('error') }}
            </div>
            @endif

            {{-- POST ORIGINAL --}}
            <div class="forum-post forum-op">
                <div class="forum-post-header">
                    <div class="forum-post-avatar">
                        {{ strtoupper(substr($topic->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <div class="forum-post-author">{{ $topic->user->name ?? 'Anonyme' }}</div>
                        <div class="forum-post-date">{{ $topic->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
                <div class="forum-post-body">{{ $topic->body }}</div>
            </div>

            {{-- RÉPONSES --}}
            @if($posts->isNotEmpty())
            <div style="margin-top:24px; margin-bottom:24px;">
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">
                    {{ $posts->total() }} réponse{{ $posts->total() !== 1 ? 's' : '' }}
                </p>

                @foreach($posts as $post)
                <div class="forum-post" id="post-{{ $post->id }}">
                    <div class="forum-post-header">
                        <div class="forum-post-avatar">
                            {{ strtoupper(substr($post->user->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="forum-post-author">{{ $post->user->name ?? 'Anonyme' }}</div>
                            <div class="forum-post-date">{{ $post->created_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    </div>
                    <div class="forum-post-body" style="margin-bottom:16px;">{{ $post->body }}</div>

                    {{-- Like --}}
                    @auth
                    <form method="POST" action="{{ route('forum.post.like', $post->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="forum-like-btn {{ $userLikedPostIds->contains($post->id) ? 'liked' : '' }}">
                            👍 {{ $post->likes_count }}
                        </button>
                    </form>
                    @else
                    <span class="forum-like-btn">👍 {{ $post->likes_count }}</span>
                    @endauth
                </div>
                @endforeach

                <div class="mt-3">{{ $posts->links() }}</div>
            </div>
            @endif

            {{-- FORMULAIRE DE RÉPONSE --}}
            @auth
            @if(!$topic->is_locked)
            <div class="forum-reply-box" style="margin-top:32px;">
                <p style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">Votre réponse</p>
                <form method="POST" action="{{ route('forum.post.store', [$category->slug, $topic->slug]) }}">
                    @csrf
                    <textarea name="body" class="forum-reply-textarea" placeholder="Rédigez votre réponse…" required>{{ old('body') }}</textarea>
                    @error('body')
                    <p style="font-size:12px;color:#dc3545;margin-top:6px;">{{ $message }}</p>
                    @enderror
                    <div style="margin-top:14px;">
                        <button type="submit" class="forum-reply-btn">Publier la réponse</button>
                    </div>
                </form>
            </div>
            @else
            <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:20px;text-align:center;margin-top:32px;">
                <p style="font-size:14px;color:#6B7590;margin:0;">🔒 Ce sujet est verrouillé — les nouvelles réponses sont désactivées.</p>
            </div>
            @endif
            @else
            <div style="background:rgba(201,168,76,.04);border:1px solid rgba(201,168,76,.15);border-radius:6px;padding:24px;text-align:center;margin-top:32px;">
                <p style="font-size:14px;color:#9BA3B8;margin-bottom:16px;">
                    <a href="{{ route('login') }}" style="color:#C9A84C;text-decoration:none;font-weight:600;">Connectez-vous</a>
                    ou
                    <a href="{{ route('register') }}" style="color:#C9A84C;text-decoration:none;font-weight:600;">créez un compte</a>
                    pour participer à la discussion.
                </p>
            </div>
            @endauth

        </div>
    </section>

</div>
@endsection
