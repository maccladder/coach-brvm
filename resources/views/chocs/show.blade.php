{{-- resources/views/chocs/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .chocs-show-page { background: #060910; min-height: 100vh; }

    .chocs-breadcrumb { padding:24px 0 0; display:flex; align-items:center; gap:10px; font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; }
    .chocs-breadcrumb a { color:#6B7590; text-decoration:none; transition:color .25s; }
    .chocs-breadcrumb a:hover { color:#C9A84C; }
    .chocs-breadcrumb span { color:rgba(107,117,144,.4); }

    .chocs-show-hero {
        background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(201,168,76,.08) 0%, transparent 60%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 36px 0 28px; margin-bottom: 32px;
    }
    .chocs-show-title { font-family:'Playfair Display',serif; font-size:clamp(26px,4vw,44px); font-weight:900; color:#E8EAF0; line-height:1.1; margin-bottom:8px; }
    .chocs-show-sub { font-size:14px; color:#6B7590; font-weight:300; }

    /* Cards up/down */
    .chocs-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.05);
        border-radius: 4px; padding: 26px 24px; height: 100%;
        position: relative; overflow: hidden;
    }
    .chocs-card-up { border-top: 3px solid #1fbf4a; }
    .chocs-card-dn { border-top: 3px solid #e53935; }

    .chocs-card-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
    }
    .chocs-card-title.up { color: #1fbf4a; }
    .chocs-card-title.dn { color: #e53935; }

    .chocs-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
    .chocs-list li {
        font-size: 13.5px; color: #9AA3B8; line-height: 1.6;
        padding: 10px 14px; border-radius: 3px;
        display: flex; align-items: flex-start; gap: 10px;
    }
    .chocs-list li.up-item { background: rgba(31,191,74,.04); border-left: 2px solid rgba(31,191,74,.3); }
    .chocs-list li.dn-item { background: rgba(229,57,53,.04); border-left: 2px solid rgba(229,57,53,.3); }
    .chocs-list-icon { flex-shrink: 0; margin-top: 1px; }

    /* Exemples */
    .chocs-examples-card {
        background: #0C1120; border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; padding: 26px 24px;
        border-top: 3px solid #C9A84C;
    }
    .chocs-example-item {
        background: #121A2C; border: 1px solid rgba(255,255,255,.05);
        border-radius: 3px; padding: 18px 20px;
        transition: border-color .25s;
    }
    .chocs-example-item:hover { border-color: rgba(201,168,76,.2); }
    .chocs-example-label { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; margin-bottom:6px; }
    .chocs-example-note { font-size:13px; color:#6B7590; line-height:1.6; }

    /* Warning */
    .chocs-warning {
        background: rgba(201,168,76,.04); border: 1px solid rgba(201,168,76,.1);
        border-radius: 3px; padding: 14px 18px; margin-top: 24px;
        font-size: 13px; color: #6B7590; line-height: 1.65;
        font-style: italic;
    }
    .chocs-warning strong { color: #C9A84C; font-style: normal; }

    /* Boutons */
    .cb-btn-outline { display:inline-flex; align-items:center; gap:8px; background:transparent; color:#E8EAF0 !important; font-family:'Syne',sans-serif; font-weight:600; font-size:12px; letter-spacing:.06em; text-transform:uppercase; padding:9px 18px; border:1px solid rgba(255,255,255,.12); border-radius:3px; text-decoration:none; transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')
<div class="chocs-show-page">

    <div class="chocs-show-hero">
        <div class="container" style="max-width:1100px;">
            <div class="chocs-breadcrumb cbr">
                <a href="{{ route('chocs.index') }}">← Chocs de marché</a>
                <span>/</span>
                <span style="color:#E8EAF0;">{{ $data['title'] }}</span>
            </div>
            <div style="margin-top:20px;">
                <h1 class="chocs-show-title">⚡ {{ $data['title'] }}</h1>
                <p class="chocs-show-sub">Causes typiques de hausse / baisse + exemples concrets</p>
            </div>
        </div>
    </div>

    <div class="container pb-5" style="max-width:1100px;">

        {{-- Hausse / Baisse --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-6 cbr">
                <div class="chocs-card chocs-card-up">
                    <div class="chocs-card-title up">
                        <span>📈</span> Ce qui fait monter
                    </div>
                    <ul class="chocs-list">
                        @foreach($data['up'] as $item)
                            <li class="up-item">
                                <span class="chocs-list-icon">▲</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-lg-6 cbr cbr2">
                <div class="chocs-card chocs-card-dn">
                    <div class="chocs-card-title dn">
                        <span>📉</span> Ce qui fait chuter
                    </div>
                    <ul class="chocs-list">
                        @foreach($data['down'] as $item)
                            <li class="dn-item">
                                <span class="chocs-list-icon">▼</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Exemples --}}
        <div class="chocs-examples-card cbr cbr3">
            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#C9A84C;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span>🧾</span> Exemples réels / typiques
            </div>

            <div class="row g-3">
                @foreach($data['examples'] as $ex)
                    <div class="col-md-6">
                        <div class="chocs-example-item">
                            <div class="chocs-example-label">{{ $ex['label'] }}</div>
                            <div class="chocs-example-note">{{ $ex['note'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="chocs-warning">
                ⚠️ <strong>Pédagogique uniquement</strong> — Ce module explique les réactions typiques du marché.
                Il ne constitue pas un conseil d'investissement personnalisé.
            </div>
        </div>

        {{-- Navigation --}}
        <div class="mt-4 d-flex justify-content-between align-items-center cbr">
            <a href="{{ route('chocs.index') }}" class="cb-btn-outline">
                ← Tous les secteurs
            </a>
            <a href="{{ route('radar.index') }}" class="cb-btn-outline">
                📡 Radar Marché →
            </a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, {threshold:.06}).observe(el);
    });
</script>
@endpush
