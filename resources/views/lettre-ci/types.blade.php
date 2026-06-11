{{-- resources/views/lettre-ci/types.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page { background: var(--cb-paper); min-height: 100vh; padding-bottom: 80px; }

    .lci-header {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(176,134,46,.1) 0%, transparent 55%), var(--cb-paper);
        border-bottom: 1px solid rgba(176,134,46,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .lci-header-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(176,134,46,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(176,134,46,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .lci-header-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .lci-header-tag::before { content:'';width:28px;height:1px;background:var(--cb-gold); }
    .lci-header-title { font-family:'Playfair Display',serif;font-size:clamp(26px,4vw,42px);font-weight:900;color:var(--cb-ink);margin-bottom:4px; }
    .lci-header-sub { font-size:14px;color:var(--cb-muted); }

    .cb-btn-outline {
        display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--cb-muted) !important;
        font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.08em;text-transform:uppercase;
        padding:8px 14px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .25s;
    }
    .cb-btn-outline:hover { border-color:var(--cb-gold);color:var(--cb-gold) !important;background:rgba(176,134,46,.05); }

    .lci-cat-lbl {
        font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
        padding:5px 12px;border-radius:3px;display:inline-block;margin-bottom:18px;
    }
    .lci-cat-pro   { background:rgba(15,92,67,.08);color:var(--cb-forest);border:1px solid rgba(15,92,67,.18); }
    .lci-cat-admin { background:rgba(176,134,46,.1);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }
    .lci-cat-perso { background:rgba(107,117,144,.1);color:var(--cb-muted);border:1px solid rgba(107,117,144,.2); }

    .lci-tpl-card {
        background: var(--cb-card); border: 1px solid var(--cb-border);
        border-radius: 4px; padding: 22px 20px;
        text-decoration: none; display: block; color: inherit;
        transition: border-color .25s, background .25s, transform .2s;
        height: 100%; position: relative; overflow: hidden;
    }
    .lci-tpl-card::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background:linear-gradient(90deg,var(--cb-gold),transparent);
        opacity:0;transition:opacity .25s;
    }
    .lci-tpl-card:hover { border-color:rgba(176,134,46,.25);background:rgba(176,134,46,.03);transform:translateY(-3px);color:inherit;text-decoration:none; }
    .lci-tpl-card:hover::before { opacity:1; }
    .lci-tpl-icon { font-size:28px;margin-bottom:12px;display:block; }
    .lci-tpl-name { font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:var(--cb-ink);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em; }
    .lci-tpl-desc { font-size:12px;color:var(--cb-muted);line-height:1.6;margin-bottom:14px; }
    .lci-tpl-cta { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:var(--cb-gold);letter-spacing:.08em;text-transform:uppercase; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="lci-header">
        <div class="lci-header-grid"></div>
        <div class="container" style="position:relative;z-index:1;">
            <div class="lci-header-tag">LettreCI · Nouvelle lettre</div>
            <h1 class="lci-header-title">Choisissez un modèle</h1>
            <p class="lci-header-sub">{{ count($templates) }} modèles disponibles — sélectionnez celui qui correspond à votre besoin</p>
        </div>
    </div>

    <div class="container mt-5">
        <a href="{{ route('lettreci.dashboard') }}" class="cb-btn-outline mb-4 d-inline-flex">← Tableau de bord</a>

        @foreach(['professionnel' => ['label' => 'Professionnel', 'class' => 'lci-cat-pro'], 'administration' => ['label' => 'Administration', 'class' => 'lci-cat-admin'], 'personnel' => ['label' => 'Personnel', 'class' => 'lci-cat-perso']] as $cat => $meta)
        @php $catItems = array_filter($templates, fn($t) => ($t['category'] ?? '') === $cat); @endphp
        @if($catItems)
        <div class="mb-5 cbr cbr{{ $loop->iteration }}">
            <span class="lci-cat-lbl {{ $meta['class'] }}">{{ $meta['label'] }}</span>
            <div class="row g-3">
                @foreach($catItems as $slug => $tpl)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <a href="{{ route('lettreci.form', $slug) }}" class="lci-tpl-card">
                        <span class="lci-tpl-icon">{{ $tpl['icon'] ?? '📄' }}</span>
                        <div class="lci-tpl-name">{{ $tpl['name'] }}</div>
                        <p class="lci-tpl-desc">{{ $tpl['description'] ?? '' }}</p>
                        <span class="lci-tpl-cta">Utiliser ce modèle →</span>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) e.target.classList.add('on'); }, {threshold:.08}).observe(el);
    });
</script>
@endpush
