{{-- resources/views/chocs/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .chocs-page { background: #060910; min-height: 100vh; }

    .chocs-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .chocs-hero-grid { position:absolute; inset:0; background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px); background-size:56px 56px; mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%); pointer-events:none; }
    .chocs-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:#0FCFA4; display:flex; align-items:center; gap:10px; margin-bottom:14px; }
    .chocs-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .chocs-hero-title { font-family:'Playfair Display',serif; font-size:clamp(28px,5vw,48px); font-weight:900; color:#E8EAF0; line-height:1.08; margin-bottom:10px; }
    .chocs-hero-title em { font-style:italic; color:#C9A84C; }
    .chocs-hero-desc { font-size:14px; color:#6B7590; font-weight:300; line-height:1.7; max-width:520px; }

    .chocs-free-badge { display:inline-flex; align-items:center; gap:6px; font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; background:rgba(15,207,164,.08); color:#0FCFA4; border:1px solid rgba(15,207,164,.18); padding:4px 12px; border-radius:100px; }

    /* Grid secteurs */
    .chocs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1px; background:rgba(201,168,76,.07); border:1px solid rgba(201,168,76,.07); margin-top:40px; }

    .chocs-sector-card {
        background: #0C1120; padding: 28px 26px;
        text-decoration: none; color: inherit;
        transition: background .3s; position: relative;
        display: block; overflow: hidden;
    }
    .chocs-sector-card::before { content:''; position:absolute; top:0;left:0;right:0;height:2px; background:linear-gradient(90deg,#C9A84C,transparent); opacity:0; transition:opacity .3s; }
    .chocs-sector-card:hover { background:rgba(16,22,40,1); color:inherit; }
    .chocs-sector-card:hover::before { opacity:1; }

    .chocs-sector-num { position:absolute; top:16px; right:20px; font-family:'Playfair Display',serif; font-size:38px; font-weight:900; color:rgba(201,168,76,.06); line-height:1; }
    .chocs-sector-icon { font-size:22px; margin-bottom:14px; display:block; }
    .chocs-sector-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:#E8EAF0; margin-bottom:8px; transition:color .25s; }
    .chocs-sector-card:hover .chocs-sector-title { color:#C9A84C; }
    .chocs-sector-desc { font-size:13px; color:#6B7590; line-height:1.6; margin-bottom:16px; }

    .chocs-tags { display:flex; flex-wrap:wrap; gap:6px; }
    .chocs-tag { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:3px 9px; border-radius:100px; }
    .chocs-tag-up { background:rgba(31,191,74,.08); color:#1fbf4a; border:1px solid rgba(31,191,74,.2); }
    .chocs-tag-dn { background:rgba(229,57,53,.08); color:#e53935; border:1px solid rgba(229,57,53,.2); }
    .chocs-tag-ex { background:rgba(255,255,255,.05); color:#6B7590; border:1px solid rgba(255,255,255,.08); }

    /* Info box */
    .chocs-info-box { background:rgba(201,168,76,.04); border:1px solid rgba(201,168,76,.1); border-radius:4px; padding:22px 26px; margin-top:32px; }
    .chocs-info-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#C9A84C; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
    .chocs-info-text { font-size:13px; color:#6B7590; line-height:1.75; }
    .chocs-info-text strong { color:#E8EAF0; }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="chocs-page">

    <div class="chocs-hero">
        <div class="chocs-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="chocs-hero-tag">Analyse de marché</p>
                    <h1 class="chocs-hero-title">⚡ Chocs de <em>marché</em></h1>
                    <p class="chocs-hero-desc">
                        Comprends pourquoi une action BRVM peut monter ou chuter subitement,
                        selon le secteur, avec des exemples concrets.
                    </p>
                </div>
                <span class="chocs-free-badge">✅ Gratuit</span>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <div class="cbr">
            <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#6B7590;">
                {{ count($sectors) }} secteurs disponibles — Clique pour explorer
            </p>
        </div>

        <div class="chocs-grid cbr">
            @foreach($sectors as $key => $s)
                <a href="{{ route('chocs.show', $key) }}" class="chocs-sector-card">
                    <span class="chocs-sector-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="chocs-sector-icon">📌</span>
                    <div class="chocs-sector-title">{{ $s['title'] }}</div>
                    <div class="chocs-sector-desc">Causes de hausse/baisse + exemples concrets</div>
                    <div class="chocs-tags">
                        <span class="chocs-tag chocs-tag-up">Hausse</span>
                        <span class="chocs-tag chocs-tag-dn">Baisse</span>
                        <span class="chocs-tag chocs-tag-ex">Exemples</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="chocs-info-box cbr">
            <div class="chocs-info-title">🎯 Comment utiliser ce module</div>
            <div class="chocs-info-text">
                Quand tu vois une action bouger fortement, identifie le <strong>secteur</strong>,
                puis compare l'événement avec les causes typiques listées.
                Ensuite, tu décides calmement : <strong>acheter, garder, vendre ou attendre</strong>.
                <br><br>
                ⚠️ <em>Module pédagogique — ne constitue pas un conseil d'investissement personnalisé.</em>
            </div>
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
