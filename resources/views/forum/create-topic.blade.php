{{-- ════════ forum/create-topic.blade.php ════════ --}}
@extends('layouts.app')
@section('title', 'Nouveau sujet – ' . $category->name . ' – Forum Boursiv')

@push('styles')
<style>
    .forum-page { background: var(--cb-paper); min-height: 100vh; }

    .forum-create-hero {
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

    .forum-create-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(20px, 3vw, 30px); font-weight: 800; color: var(--cb-ink);
    }

    .forum-form-box {
        background: var(--cb-card); border: 1px solid var(--cb-border);
        border-radius: 6px; padding: 32px;
    }
    .forum-input-label {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .14em; text-transform: uppercase; color: var(--cb-muted);
        display: block; margin-bottom: 8px;
    }
    .forum-input {
        width: 100%; background: var(--cb-paper); border: 1px solid var(--cb-border);
        border-radius: 4px; color: var(--cb-ink); font-size: 14px;
        padding: 12px 16px; outline: none; transition: border-color .2s; font-family: inherit;
    }
    .forum-input:focus { border-color: rgba(176,134,46,.4); box-shadow: 0 0 0 3px rgba(176,134,46,.06); }
    .forum-input::placeholder { color: var(--cb-muted); }
    .forum-textarea {
        width: 100%; background: var(--cb-paper); border: 1px solid var(--cb-border);
        border-radius: 4px; color: var(--cb-ink); font-size: 14px; line-height: 1.75;
        padding: 14px 16px; resize: vertical; min-height: 200px; font-family: inherit;
        outline: none; transition: border-color .2s;
    }
    .forum-textarea:focus { border-color: rgba(176,134,46,.4); box-shadow: 0 0 0 3px rgba(176,134,46,.06); }
    .forum-textarea::placeholder { color: var(--cb-muted); }
    .forum-submit-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--cb-gold); color: #060910 !important; text-decoration: none !important;
        padding: 13px 32px; border-radius: 4px; border: none;
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: .06em;
        cursor: pointer; transition: background .2s, box-shadow .2s;
    }
    .forum-submit-btn:hover { background: #9A7020; box-shadow: 0 4px 14px rgba(176,134,46,.3); }
    .forum-cancel-link {
        font-family: 'Syne', sans-serif; font-size: 13px; color: var(--cb-muted);
        text-decoration: none; margin-left: 16px;
    }
    .forum-cancel-link:hover { color: var(--cb-ink); }
</style>
@endpush

@section('content')
<div class="forum-page">

    {{-- HEADER --}}
    <section class="forum-create-hero">
        <div class="container" style="max-width:760px;">
            <div class="forum-breadcrumb">
                <a href="{{ route('forum.index') }}">Forum</a>
                <span class="sep">›</span>
                <a href="{{ route('forum.category', $category->slug) }}">{{ $category->name }}</a>
                <span class="sep">›</span>
                <span style="color:var(--cb-gold);">Nouveau sujet</span>
            </div>
            <h1 class="forum-create-title">
                {{ $category->icon }} Nouveau sujet dans <em style="color:var(--cb-gold);font-style:italic;">{{ $category->name }}</em>
            </h1>
        </div>
    </section>

    <section style="background:var(--cb-paper); padding:36px 0 64px;">
        <div class="container" style="max-width:760px;">
            <div class="forum-form-box">
                <form method="POST" action="{{ route('forum.topic.store', $category->slug) }}">
                    @csrf

                    <div style="margin-bottom:22px;">
                        <label class="forum-input-label" for="title">Titre du sujet</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="forum-input"
                            placeholder="Rédigez un titre clair et concis…"
                            value="{{ old('title') }}"
                            maxlength="200"
                            required
                            autofocus
                        >
                        @error('title')
                        <p style="font-size:12px;color:#dc3545;margin-top:6px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:28px;">
                        <label class="forum-input-label" for="body">Contenu de votre message</label>
                        <textarea
                            id="body"
                            name="body"
                            class="forum-textarea"
                            placeholder="Développez votre sujet en détail…"
                            maxlength="10000"
                            required
                        >{{ old('body') }}</textarea>
                        @error('body')
                        <p style="font-size:12px;color:#dc3545;margin-top:6px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center">
                        <button type="submit" class="forum-submit-btn">Publier le sujet</button>
                        <a href="{{ route('forum.category', $category->slug) }}" class="forum-cancel-link">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

</div>
@endsection
