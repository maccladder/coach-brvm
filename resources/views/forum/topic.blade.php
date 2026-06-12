{{-- ════════ forum/topic.blade.php ════════ --}}
@extends('layouts.app')
@section('title', $topic->title . ' – Forum Boursiv')

@push('styles')
<style>
    .forum-page { background: var(--cb-paper); min-height: 100vh; }

    .forum-topic-hero {
        background: var(--cb-paper); border-bottom: 1px solid var(--cb-border);
        padding: 36px 0 28px;
    }
    .forum-breadcrumb {
        font-family: 'Syne', sans-serif; font-size: 12px; color: var(--cb-muted);
        margin-bottom: 12px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    }
    .forum-breadcrumb a { color: var(--cb-muted); text-decoration: none; }
    .forum-breadcrumb a:hover { color: var(--cb-gold); }
    .forum-breadcrumb .sep { opacity: .4; }
    .forum-topic-title-h {
        font-family: 'Playfair Display', serif;
        font-size: clamp(20px, 3vw, 32px); font-weight: 800;
        color: var(--cb-ink); line-height: 1.25; margin-bottom: 8px;
    }
    .forum-topic-meta-h { font-size: 13px; color: var(--cb-muted); }

    /* Post cards */
    .forum-post {
        background: var(--cb-card); border: 1px solid var(--cb-border);
        border-radius: 6px; padding: 24px; margin-bottom: 12px;
    }
    .forum-post-header {
        display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
    }
    .forum-post-avatar {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        background: rgba(176,134,46,.1); border: 1px solid rgba(176,134,46,.2);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; color: var(--cb-gold);
    }
    .forum-post-author { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: var(--cb-ink); }
    .forum-post-date { font-size: 12px; color: var(--cb-muted); margin-top: 2px; }
    .forum-post-body { font-size: 14px; color: var(--cb-ink); line-height: 1.8; white-space: pre-wrap; word-break: break-word; }

    /* Like button */
    .forum-like-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: transparent; border: 1px solid var(--cb-border);
        color: var(--cb-muted); padding: 6px 14px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all .2s;
    }
    .forum-like-btn:hover { border-color: rgba(176,134,46,.4); color: var(--cb-gold); }
    .forum-like-btn.liked { border-color: rgba(176,134,46,.5); color: var(--cb-gold); background: rgba(176,134,46,.08); }

    /* Original post */
    .forum-op { border-left: 3px solid var(--cb-gold); }

    /* Reply form */
    .forum-reply-box {
        background: var(--cb-card); border: 1px solid var(--cb-border);
        border-radius: 6px; padding: 28px;
    }
    .forum-reply-textarea {
        width: 100%; background: var(--cb-paper); border: 1px solid var(--cb-border);
        border-radius: 4px; color: var(--cb-ink); font-size: 14px; line-height: 1.7;
        padding: 14px 16px; resize: vertical; min-height: 130px; font-family: inherit;
        outline: none; transition: border-color .2s;
    }
    .forum-reply-textarea:focus { border-color: rgba(176,134,46,.4); box-shadow: 0 0 0 3px rgba(176,134,46,.06); }
    .forum-reply-textarea::placeholder { color: var(--cb-muted); }
    .forum-reply-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--cb-gold); color: #060910 !important; text-decoration: none !important;
        padding: 12px 28px; border-radius: 4px; border: none;
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: .06em;
        cursor: pointer; transition: background .2s, box-shadow .2s;
    }
    .forum-reply-btn:hover { background: #9A7020; box-shadow: 0 4px 14px rgba(176,134,46,.3); }
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
                <span style="color:var(--cb-gold);">{{ Str::limit($topic->title, 50) }}</span>
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

    <section style="background:var(--cb-paper); padding:36px 0 64px;">
        <div class="container" style="max-width:860px;">

            @if(session('success'))
            <div style="background:rgba(15,92,67,.06);border:1px solid rgba(15,92,67,.15);border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:13.5px;color:var(--cb-forest);">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div style="background:rgba(220,53,69,.06);border:1px solid rgba(220,53,69,.2);border-radius:4px;padding:12px 16px;margin-bottom:20px;font-size:13.5px;color:#dc3545;">
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
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:16px;">
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
                <p style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:16px;">Votre réponse</p>
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
            <div style="background:var(--cb-paper);border:1px solid var(--cb-border);border-radius:6px;padding:20px;text-align:center;margin-top:32px;">
                <p style="font-size:14px;color:var(--cb-muted);margin:0;">🔒 Ce sujet est verrouillé — les nouvelles réponses sont désactivées.</p>
            </div>
            @endif
            @else
            <div style="background:rgba(176,134,46,.04);border:1px solid rgba(176,134,46,.15);border-radius:6px;padding:24px;text-align:center;margin-top:32px;">
                <p style="font-size:14px;color:var(--cb-muted);margin-bottom:16px;">
                    <a href="{{ route('login') }}" style="color:var(--cb-gold);text-decoration:none;font-weight:600;">Connectez-vous</a>
                    ou
                    <a href="{{ route('register') }}" style="color:var(--cb-gold);text-decoration:none;font-weight:600;">créez un compte</a>
                    pour participer à la discussion.
                </p>
            </div>
            @endauth

        </div>
    </section>

</div>
@endsection
