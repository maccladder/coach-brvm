{{-- ════════ notre-histoire.blade.php ════════ --}}
@extends('layouts.app')
@section('title','Notre histoire – Boursiv')

@push('styles')
<style>
    .histoire-page { background:#060910;min-height:100vh; }
    .histoire-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .histoire-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .histoire-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .histoire-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .histoire-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .histoire-hero-title em { font-style:italic;color:#C9A84C; }
    .histoire-article { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:40px 44px;margin-top:32px; }
    .histoire-h { font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#C9A84C;margin:28px 0 12px; }
    .histoire-h:first-child { margin-top:0; }
    .histoire-p { font-size:14px;color:#9AA3B8;line-height:1.85;margin-bottom:16px; }
    .histoire-em { font-style:italic;color:#E8EAF0; }
    .histoire-ul { list-style:none;padding:0;margin-bottom:20px; }
    .histoire-ul li { font-size:13.5px;color:#9AA3B8;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);display:flex;align-items:flex-start;gap:10px; }
    .histoire-ul li::before { content:'→';color:#C9A84C;flex-shrink:0;margin-top:1px; }
    .histoire-mission-box { background:rgba(15,207,164,.04);border:1px solid rgba(15,207,164,.1);border-radius:3px;padding:20px 24px;margin-top:24px; }
    .histoire-mission-text { font-size:14px;color:#9AA3B8;line-height:1.75; }
    .histoire-mission-text strong { color:#E8EAF0; }
    @media(max-width:600px){ .histoire-article{padding:24px 20px;} }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="histoire-page">
    <div class="histoire-hero">
        <div class="histoire-hero-grid"></div>
        <div class="container" style="max-width:900px;position:relative;z-index:1;">
            <p class="histoire-hero-tag">À propos</p>
            <h1 class="histoire-hero-title">Notre <em>histoire</em></h1>
            <p style="font-size:14px;color:#6B7590;font-weight:300;">Boursiv est né d'une expérience réelle, d'un parcours d'investisseurs passionnés mais souvent livrés à eux-mêmes.</p>
        </div>
    </div>
    <div class="container py-5" style="max-width:900px;">
        <div class="histoire-article cbr">
            <h2 class="histoire-h">Au début, comme beaucoup d'investisseurs…</h2>
            <p class="histoire-p">Lorsque nous avons commencé à investir à la BRVM, nous étions motivés mais peu outillés. Pas d'expérience solide, pas de guide clair, et encore moins d'outils modernes capables d'interpréter rapidement un Bulletin Officiel de Cote (BOC) ou un état financier complexe.</p>
            <p class="histoire-p">Nous passions des heures à essayer de comprendre des documents techniques, à analyser manuellement des données, et à chercher des signaux fiables. Beaucoup d'erreurs, beaucoup d'essais… et énormément d'apprentissage.</p>

            <h2 class="histoire-h">Avec le temps, l'expérience est venue.</h2>
            <p class="histoire-p">À force de persévérance, de lectures, d'échanges avec d'autres investisseurs, et d'analyse de centaines de BOC et d'états financiers, nous avons développé une meilleure compréhension du marché, des mécanismes et des opportunités de la BRVM.</p>
            <p class="histoire-p">Et surtout : nous avons réalisé que ce qui nous manquait à nos débuts, manque encore à des milliers d'investisseurs aujourd'hui.</p>

            <h2 class="histoire-h">Alors est née une idée simple : aider ceux qui commencent.</h2>
            <p class="histoire-p"><em class="histoire-em">"Et si les nouveaux investisseurs avaient accès aux outils que nous n'avons jamais eus ? Et si l'analyse financière devenait enfin accessible à tous ?"</em></p>
            <p class="histoire-p">C'est ainsi qu'est né <strong style="color:#E8EAF0;">Boursiv</strong> : une plateforme pensée pour les débutants, mais utile aussi aux investisseurs confirmés.</p>

            <h2 class="histoire-h">Notre mission</h2>
            <ul class="histoire-ul">
                <li>Rendre l'analyse des BOC simple, rapide et compréhensible</li>
                <li>Interpréter automatiquement les états financiers</li>
                <li>Proposer des formations adaptées au niveau de chacun</li>
                <li>Donner aux investisseurs les outils que nous aurions aimé avoir à nos débuts</li>
            </ul>

            <div class="histoire-mission-box">
                <div class="histoire-mission-text">
                    <strong>Boursiv</strong> n'est pas seulement un outil. C'est une mission : contribuer à créer une nouvelle génération d'investisseurs africains mieux préparés, mieux accompagnés, et mieux informés.<br><br>
                    Merci de faire partie de cette aventure. Ensemble, faisons grandir la culture financière dans toute la région.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
