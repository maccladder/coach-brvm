@extends('layouts.app')

@push('meta')
<meta name="description" content="Analyse du PND 2026-2030 × BRVM — Le Plan National de Développement ivoirien identifie 7 secteurs porteurs. Boursiv croise ces secteurs avec les sociétés cotées à la BRVM.">
<meta property="og:title" content="PND 2026-2030 × BRVM — Boursiv">
<meta property="og:description" content="216 pages publiées par l'État ivoirien. 6 piliers. 7 secteurs. Quelles sociétés BRVM sont concernées ?">
<meta property="og:type" content="article">
@endpush

@push('styles')
<style>
/* ═══════════════════════════════════════════════
   SCROLL ANIMATIONS
════════════════════════════════════════════════ */
.cbr  { opacity:0; transform:translateY(32px); transition:opacity .7s cubic-bezier(.4,0,.2,1),transform .7s cubic-bezier(.4,0,.2,1); }
.cbr2 { opacity:0; transform:translateY(32px); transition:opacity .7s .15s cubic-bezier(.4,0,.2,1),transform .7s .15s cubic-bezier(.4,0,.2,1); }
.cbr3 { opacity:0; transform:translateY(32px); transition:opacity .7s .3s  cubic-bezier(.4,0,.2,1),transform .7s .3s  cubic-bezier(.4,0,.2,1); }
.cbr.on,.cbr2.on,.cbr3.on { opacity:1; transform:translateY(0); }

/* ═══════════════════════════════════════════════
   HERO
════════════════════════════════════════════════ */
.pnd-hero {
    background: var(--cb-dark);
    padding: 100px 0 80px;
    position: relative;
    overflow: hidden;
}
.pnd-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 60% at 60% 40%, rgba(201,168,76,.07) 0%, transparent 70%),
        radial-gradient(ellipse 40% 40% at 20% 80%, rgba(15,207,164,.04) 0%, transparent 60%);
    pointer-events: none;
}
.pnd-hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
    background-size: 48px 48px;
    pointer-events: none;
}
.pnd-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cb-gold);
    background: rgba(201,168,76,.08);
    border: 1px solid rgba(201,168,76,.2);
    padding: 6px 14px;
    border-radius: 100px;
    margin-bottom: 28px;
}
.pnd-hero-badge i { font-size: 13px; }
.pnd-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5vw, 3.4rem);
    font-weight: 900;
    color: var(--cb-text);
    line-height: 1.15;
    margin-bottom: 24px;
    max-width: 780px;
}
.pnd-hero h1 .gold { color: var(--cb-gold); }
.pnd-hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 17px;
    color: var(--cb-muted);
    line-height: 1.7;
    max-width: 620px;
    margin-bottom: 40px;
}
.pnd-hero-sub strong { color: var(--cb-text); font-weight: 500; }
.pnd-btn-gold {
    display: inline-flex; align-items: center; gap: 8px;
    font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 800;
    letter-spacing: .07em; text-transform: uppercase;
    color: #050810 !important; background: linear-gradient(135deg, var(--cb-gold), #9B6B15);
    border: none; padding: 13px 28px; border-radius: 4px; text-decoration: none;
    transition: all .25s; box-shadow: 0 4px 18px rgba(201,168,76,.25);
}
.pnd-btn-gold:hover { box-shadow: 0 8px 28px rgba(201,168,76,.4); transform: translateY(-2px); }
.pnd-btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: var(--cb-forest) !important; background: transparent;
    border: 1px solid rgba(15,92,67,.3); padding: 12px 24px; border-radius: 4px;
    text-decoration: none; transition: all .25s;
}
.pnd-btn-outline:hover { background: rgba(15,92,67,.07); border-color: var(--cb-forest); }

/* ═══════════════════════════════════════════════
   CITATION-CHOC
════════════════════════════════════════════════ */
.pnd-cite-section {
    background: var(--cb-paper);
    padding: 80px 0;
    border-top: 1px solid var(--cb-border);
}
.pnd-cite-block {
    max-width: 820px;
    margin: 0 auto;
    border-left: 5px solid var(--cb-gold);
    padding: 32px 40px;
    background: rgba(201,168,76,.03);
    border-radius: 0 8px 8px 0;
}
.pnd-cite-block blockquote {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.15rem, 2.5vw, 1.5rem);
    font-style: italic;
    font-weight: 700;
    color: var(--cb-text);
    line-height: 1.65;
    margin: 0 0 20px;
    border: none; padding: 0;
}
.pnd-cite-block blockquote::before { content: '\00AB\00A0'; color: var(--cb-gold); }
.pnd-cite-block blockquote::after  { content: '\00A0\00BB'; color: var(--cb-gold); }
.pnd-cite-source {
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .06em;
    color: rgba(201,168,76,.7);
    margin-bottom: 16px;
}
.pnd-cite-link {
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: var(--cb-muted);
    text-decoration: none;
    transition: color .2s;
}
.pnd-cite-link:hover { color: var(--cb-gold); }

/* ═══════════════════════════════════════════════
   STATS
════════════════════════════════════════════════ */
.pnd-stats-section {
    background: var(--cb-dark2);
    padding: 72px 0;
    border-top: 1px solid var(--cb-border);
    border-bottom: 1px solid var(--cb-border);
}
.pnd-stat-card {
    background: var(--cb-dark3);
    border: 1px solid var(--cb-border);
    border-radius: 8px;
    padding: 36px 32px;
    text-align: center;
    transition: border-color .25s, transform .25s;
}
.pnd-stat-card:hover { border-color: rgba(201,168,76,.3); transform: translateY(-3px); }
.pnd-stat-number {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 900;
    color: var(--cb-gold);
    line-height: 1;
    margin-bottom: 10px;
}
.pnd-stat-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--cb-text);
    line-height: 1.4;
    margin-bottom: 10px;
}
.pnd-stat-ref {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .06em;
    color: rgba(201,168,76,.5);
}

/* ═══════════════════════════════════════════════
   PILIERS
════════════════════════════════════════════════ */
.pnd-piliers-section {
    background: var(--cb-dark);
    padding: 80px 0;
}
.pnd-section-overline {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--cb-gold);
    margin-bottom: 10px;
}
.pnd-section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3.5vw, 2.3rem);
    font-weight: 900;
    color: var(--cb-text);
    line-height: 1.2;
    margin-bottom: 12px;
}
.pnd-section-intro {
    font-family: 'DM Sans', sans-serif;
    font-size: 16px;
    color: var(--cb-muted);
    line-height: 1.7;
    max-width: 600px;
    margin-bottom: 48px;
}
.pnd-pilier-card {
    background: var(--cb-dark2);
    border: 1px solid var(--cb-border);
    border-radius: 8px;
    padding: 28px 24px;
    height: 100%;
    transition: border-color .25s, transform .25s;
}
.pnd-pilier-card:hover { border-color: rgba(201,168,76,.25); transform: translateY(-2px); }
.pnd-pilier-num {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    font-weight: 900;
    color: rgba(201,168,76,.15);
    line-height: 1;
    margin-bottom: 8px;
}
.pnd-pilier-title {
    font-family: 'Syne', sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--cb-text);
    line-height: 1.4;
}

/* ═══════════════════════════════════════════════
   PIVOT NARRATIF
════════════════════════════════════════════════ */
.pnd-pivot-section {
    background: var(--cb-dark2);
    padding: 80px 0;
    text-align: center;
    border-top: 1px solid var(--cb-border);
    border-bottom: 1px solid var(--cb-border);
}
.pnd-pivot-text {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.3rem, 3vw, 2rem);
    font-weight: 700;
    color: var(--cb-text);
    line-height: 1.5;
    max-width: 780px;
    margin: 0 auto;
}
.pnd-pivot-text .gold { color: var(--cb-gold); }

/* ═══════════════════════════════════════════════
   SECTEURS
════════════════════════════════════════════════ */
.pnd-secteurs-section {
    background: var(--cb-dark);
    padding: 80px 0;
}
.pnd-secteur-card {
    background: var(--cb-dark2);
    border: 1px solid var(--cb-border);
    border-radius: 10px;
    padding: 40px;
    margin-bottom: 24px;
    transition: border-color .25s;
}
.pnd-secteur-card:hover { border-color: rgba(201,168,76,.25); }
.pnd-secteur-header {
    display: flex;
    align-items: baseline;
    gap: 16px;
    margin-bottom: 24px;
}
.pnd-secteur-num {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 900;
    color: rgba(201,168,76,.18);
    line-height: 1;
    flex-shrink: 0;
}
.pnd-secteur-name {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.2rem, 2.5vw, 1.6rem);
    font-weight: 900;
    color: var(--cb-text);
    line-height: 1.2;
}
.pnd-secteur-cite {
    border-left: 3px solid var(--cb-gold);
    padding: 16px 20px;
    background: rgba(201,168,76,.04);
    border-radius: 0 6px 6px 0;
    margin-bottom: 20px;
}
.pnd-secteur-cite p {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 15px;
    color: var(--cb-text);
    line-height: 1.65;
    margin-bottom: 8px;
}
.pnd-secteur-cite p::before { content: '\00AB\00A0'; color: var(--cb-gold); }
.pnd-secteur-cite p::after  { content: '\00A0\00BB'; color: var(--cb-gold); }
.pnd-secteur-ref {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .06em;
    color: rgba(201,168,76,.6);
}
.pnd-secteur-body-label {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cb-gold);
    margin-bottom: 8px;
}
.pnd-secteur-body p {
    font-family: 'DM Sans', sans-serif;
    font-size: 14.5px;
    color: var(--cb-muted);
    line-height: 1.7;
    margin-bottom: 0;
}
.pnd-secteur-brvm {
    background: var(--cb-dark3);
    border: 1px solid rgba(201,168,76,.1);
    border-radius: 6px;
    padding: 16px 20px;
    margin-top: 20px;
}
.pnd-secteur-brvm-label {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cb-forest);
    margin-bottom: 8px;
}
.pnd-secteur-brvm p {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    color: var(--cb-muted);
    line-height: 1.6;
    margin-bottom: 0;
    font-style: italic;
}
.pnd-secteur-footer {
    margin-top: 20px;
}
.pnd-secteur-link {
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .06em;
    color: var(--cb-muted);
    text-decoration: none;
    transition: color .2s;
}
.pnd-secteur-link:hover { color: var(--cb-gold); }

/* ═══════════════════════════════════════════════
   MÉTHODOLOGIE
════════════════════════════════════════════════ */
.pnd-methodo-section {
    background: var(--cb-dark2);
    padding: 64px 0;
    border-top: 1px solid var(--cb-border);
    border-bottom: 1px solid var(--cb-border);
}
.pnd-methodo-block {
    max-width: 720px;
    margin: 0 auto;
    background: var(--cb-dark3);
    border: 1px solid var(--cb-border);
    border-radius: 8px;
    padding: 36px 40px;
}
.pnd-methodo-block h3 {
    font-family: 'Syne', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--cb-gold);
    margin-bottom: 20px;
}
.pnd-methodo-block p {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--cb-muted);
    line-height: 1.75;
    margin-bottom: 12px;
}
.pnd-methodo-block p:last-child { margin-bottom: 0; }

/* ═══════════════════════════════════════════════
   PDF DOWNLOAD
════════════════════════════════════════════════ */
.pnd-download-section {
    background: var(--cb-dark);
    padding: 80px 0;
}
.pnd-download-card {
    background: linear-gradient(135deg, rgba(201,168,76,.06) 0%, rgba(201,168,76,.02) 100%);
    border: 1px solid rgba(201,168,76,.2);
    border-radius: 12px;
    padding: 48px 40px;
}
.pnd-download-cover {
    background: var(--cb-dark3);
    border: 1px solid var(--cb-border);
    border-radius: 6px;
    aspect-ratio: 3/4;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 12px;
    color: var(--cb-muted);
    text-align: center;
    padding: 24px;
}
.pnd-download-cover i { font-size: 2.5rem; color: rgba(201,168,76,.4); }
.pnd-download-cover span {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(201,168,76,.5);
}
.pnd-download-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.4rem, 3vw, 2rem);
    font-weight: 900;
    color: var(--cb-text);
    line-height: 1.2;
    margin-bottom: 16px;
}
.pnd-download-body {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--cb-muted);
    line-height: 1.7;
    margin-bottom: 28px;
}
.pnd-download-source {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    color: rgba(107,117,144,.6);
    margin-top: 14px;
    font-style: italic;
}

/* ═══════════════════════════════════════════════
   CTA FINAL
════════════════════════════════════════════════ */
.pnd-cta-section {
    background: var(--cb-dark2);
    padding: 96px 0;
    text-align: center;
}
.pnd-cta-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 4vw, 2.8rem);
    font-weight: 900;
    color: var(--cb-text);
    line-height: 1.2;
    margin-bottom: 24px;
}
.pnd-cta-title .gold { color: var(--cb-gold); }
.pnd-cta-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 17px;
    color: var(--cb-muted);
    line-height: 1.7;
    max-width: 480px;
    margin: 0 auto 40px;
}

@media (max-width: 767px) {
    .pnd-hero { padding: 64px 0 56px; }
    .pnd-secteur-card { padding: 28px 20px; }
    .pnd-secteur-header { flex-direction: column; gap: 4px; }
    .pnd-secteur-num { font-size: 2rem; }
    .pnd-cite-block { padding: 24px 20px; }
    .pnd-download-card { padding: 32px 20px; }
    .pnd-methodo-block { padding: 28px 24px; }
}
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════
     SECTION 1 — HERO
════════════════════════════════════════════════════ --}}
<section class="pnd-hero" id="pnd-hero">
    <div class="pnd-hero-grid"></div>
    <div class="container" style="max-width:1100px; position:relative; z-index:1;">
        <div class="cbr">
            <div class="pnd-hero-badge">
                <i class="bi bi-building"></i>
                Document officiel — Ministère du Plan et du Développement
            </div>
            <h1>
                Le Plan National de Développement vous dit où investir.<br>
                <span class="gold">Boursiv vous dit par où.</span>
            </h1>
            <p class="pnd-hero-sub">
                <strong>216 pages</strong> publiées par l'État ivoirien. <strong>6 piliers stratégiques.</strong><br>
                <strong>7 secteurs</strong> identifiés comme moteurs de croissance jusqu'en 2030.<br>
                Et une phrase, au paragraphe&nbsp;173, qui change tout.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="#pnd-citation" class="pnd-btn-gold">
                    <i class="bi bi-arrow-down"></i> Lire l'analyse
                </a>
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-btn-outline" target="_blank" rel="noopener" download="PND-2026-2030.pdf">
                    <i class="bi bi-download"></i> Télécharger le PND officiel (PDF, 18 Mo)
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 2 — CITATION-CHOC
════════════════════════════════════════════════════ --}}
<section class="pnd-cite-section" id="pnd-citation">
    <div class="container" style="max-width:1100px;">
        <div class="pnd-cite-block cbr">
            <blockquote>
                La BRVM se présente comme une alternative aux mécanismes classiques d'accès au financement pour les PME/PMI. Toutefois, l'activité boursière régionale demeure timide.
            </blockquote>
            <div class="pnd-cite-source">— PND 2026-2030, §173, page 35</div>
            <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-cite-link" target="_blank" rel="noopener">
                Vérifier dans le document officiel →
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 3 — LES 3 CHIFFRES QUI POSENT LE DÉCOR
════════════════════════════════════════════════════ --}}
<section class="pnd-stats-section">
    <div class="container" style="max-width:1100px;">
        <div class="row g-4">
            <div class="col-md-4 cbr">
                <div class="pnd-stat-card">
                    <div class="pnd-stat-number">41&nbsp;735 Mds</div>
                    <div class="pnd-stat-label">Investissements mobilisés sur le PND 2021-2025, en FCFA</div>
                    <div class="pnd-stat-ref">§12, page 2</div>
                </div>
            </div>
            <div class="col-md-4 cbr2">
                <div class="pnd-stat-card">
                    <div class="pnd-stat-number">6&nbsp;100 Mds</div>
                    <div class="pnd-stat-label">Capitalisation de la BRVM en 2023, en FCFA</div>
                    <div class="pnd-stat-ref">§160, page 33</div>
                </div>
            </div>
            <div class="col-md-4 cbr3">
                <div class="pnd-stat-card">
                    <div class="pnd-stat-number">6,5 %</div>
                    <div class="pnd-stat-label">Croissance annuelle moyenne 2021-2024</div>
                    <div class="pnd-stat-ref">§9, page 1</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 4 — LES 6 PILIERS STRATÉGIQUES
════════════════════════════════════════════════════ --}}
<section class="pnd-piliers-section">
    <div class="container" style="max-width:1100px;">
        <div class="cbr">
            <div class="pnd-section-overline">Architecture du PND</div>
            <h2 class="pnd-section-title">Les 6 piliers stratégiques</h2>
            <p class="pnd-section-intro">
                Le PND 2026-2030 repose sur six piliers. Ils définissent l'architecture des politiques publiques ivoiriennes pour les cinq prochaines années.
                <span style="display:block; margin-top:6px; font-size:12px; color:rgba(107,117,144,.6); font-family:'Syne',sans-serif; font-weight:600; letter-spacing:.06em;">§6, page 1</span>
            </p>
        </div>
        <div class="row g-4">
            @php
            $piliers = [
                ['01', 'Paix, sécurité et stabilité durables'],
                ['02', 'Transformation structurelle de l\'économie par l\'industrialisation et le développement de grappes'],
                ['03', 'Capital humain et promotion de l\'emploi'],
                ['04', 'Inclusion, solidarité nationale et action sociale'],
                ['05', 'Développement régional équilibré, préservation de l\'environnement et lutte contre le changement climatique'],
                ['06', 'Gouvernance, modernisation de l\'État et transformation culturelle'],
            ];
            @endphp
            @foreach($piliers as $i => $p)
            <div class="col-md-6 col-lg-4 {{ $i < 3 ? 'cbr' : 'cbr2' }}">
                <div class="pnd-pilier-card">
                    <div class="pnd-pilier-num">{{ $p[0] }}</div>
                    <div class="pnd-pilier-title">{{ $p[1] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 5 — LE PIVOT NARRATIF
════════════════════════════════════════════════════ --}}
<section class="pnd-pivot-section">
    <div class="container" style="max-width:1100px;">
        <p class="pnd-pivot-text cbr">
            Sur ces 6 piliers, un seul concentre les 7 secteurs où l'argent va couler :
            <span class="gold">Pilier 2 — Transformation structurelle de l'économie.</span>
        </p>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 6 — LES 7 SECTEURS PORTEURS
════════════════════════════════════════════════════ --}}
<section class="pnd-secteurs-section" id="pnd-secteurs">
    <div class="container" style="max-width:1100px;">
        <div class="cbr" style="margin-bottom:48px;">
            <div class="pnd-section-overline">Cœur de l'analyse</div>
            <h2 class="pnd-section-title">Les 7 secteurs porteurs</h2>
            <p class="pnd-section-intro">
                Ce que dit le PND, paragraphe par paragraphe — et les sociétés cotées à la BRVM dans chaque secteur.
            </p>
        </div>

        {{-- 01 — AGRICULTURE --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">01</div>
                <div class="pnd-secteur-name">Agriculture</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>La Côte d'Ivoire dispose d'un potentiel agricole considérable, faisant de ce secteur un pilier essentiel de son économie. En 2023, l'agriculture a représenté 15,2% du PIB, environ 50% des emplois et près de 60% des exportations.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §514, page 85</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Transformation des filières cacao et anacarde, développement des agropoles et grappes industrielles, substitution aux importations, augmentation de la part des produits finis exportés.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés agro-export et de transformation cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 85 du PND →
                </a>
            </div>
        </div>

        {{-- 02 — RESSOURCES ANIMALES ET HALIEUTIQUES --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">02</div>
                <div class="pnd-secteur-name">Ressources animales et halieutiques</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>Le secteur des ressources animales et halieutiques joue un rôle stratégique pour l'amélioration de la sécurité alimentaire et nutritionnelle des populations. Sa contribution aux PIB national et agricole, demeure faible, environ 4,5% pour le PIB agricole et 2% pour le PIB total.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §599, page 98</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Développement de l'élevage moderne, renforcement de la pêche industrielle et artisanale, amélioration de la sécurité alimentaire, valorisation des filières avicole et aquacole.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés agroalimentaires et d'élevage cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 98 du PND →
                </a>
            </div>
        </div>

        {{-- 03 — INDUSTRIE --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">03</div>
                <div class="pnd-secteur-name">Industrie</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>En Côte d'Ivoire, le secteur industriel constitue un levier important pour accélérer la transformation structurelle de l'économie. Il joue un rôle majeur dans la transformation des matières premières agricoles et minières. Sa contribution au PIB est passée de 20,7% en 2020 à 22,7% en 2024.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §671, page 109</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Développement des zones industrielles et agropoles, montée en valeur ajoutée des matières premières locales, attractivité pour les investissements directs étrangers, développement des chaînes de valeur industrielles.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés industrielles et de transformation cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 109 du PND →
                </a>
            </div>
        </div>

        {{-- 04 — MINES, HYDROCARBURES ET ÉNERGIE --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">04</div>
                <div class="pnd-secteur-name">Mines, hydrocarbures et énergie</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>La Côte d'Ivoire envisage d'insuffler au secteur des ressources extractives et énergétiques une dynamique accrue pour en faire le second poumon de l'économie ivoirienne.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §702, page 112</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Exploitation du gisement pétrolier Baleine (phases 2 et 3), développement du champ Calao, stratégie « contenu local » étendue, développement des chaînes de valeur industrielles hors extractif.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés énergétiques et pétrolières cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 112 du PND →
                </a>
            </div>
        </div>

        {{-- 05 — COMMERCE --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">05</div>
                <div class="pnd-secteur-name">Commerce</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>Le secteur du commerce est l'un des principaux moteurs de création de la richesse nationale. Il est associé à une plus grande participation des jeunes et des femmes et à la création d'emplois décents. Il concourt également, à l'amélioration des conditions de vie des populations. En effet, sa contribution au PIB était de 15% en 2024.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §775, page 122</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Modernisation du commerce intérieur, développement des marchés d'approvisionnement, renforcement de l'intégration commerciale régionale, formalisation du secteur informel.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés de distribution et de commerce cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 122 du PND →
                </a>
            </div>
        </div>

        {{-- 06 — TRANSPORT --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">06</div>
                <div class="pnd-secteur-name">Transport</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>Le secteur des transports en Côte d'Ivoire joue un rôle essentiel dans l'intégration régionale ainsi que dans le développement économique et social du pays, contribuant actuellement entre 7% et 10% du PIB.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §804, page 126</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Développement des corridors régionaux (Abidjan-Lagos), modernisation du port de San-Pédro, extension du réseau routier et ferroviaire, renforcement de la plateforme aéroportuaire d'Abidjan.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés de transport et logistique cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 126 du PND →
                </a>
            </div>
        </div>

        {{-- 07 — ÉCONOMIE NUMÉRIQUE ET POSTE --}}
        <div class="pnd-secteur-card cbr">
            <div class="pnd-secteur-header">
                <div class="pnd-secteur-num">07</div>
                <div class="pnd-secteur-name">Économie numérique et poste</div>
            </div>
            <div class="pnd-secteur-cite">
                <p>Le secteur de l'économie numérique et de la poste représente, depuis une décennie, un moteur clé de la croissance économique nationale, avec une contribution de 5% au PIB en 2024.</p>
                <div class="pnd-secteur-ref">PND 2026-2030 — §840, page 131</div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="pnd-secteur-body">
                        <div class="pnd-secteur-body-label">Ce que dit le PND</div>
                        <p>Couverture numérique nationale étendue, développement du e-gouvernement et des fintechs, formation aux métiers du numérique, développement des industries créatives numériques et de la cybersécurité.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pnd-secteur-brvm">
                        <div class="pnd-secteur-brvm-label">Sur la BRVM, ça vous concerne si :</div>
                        <p>Vous êtes actionnaire de sociétés télécoms et numériques cotées à la BRVM. — <em>Liste à compléter.</em></p>
                    </div>
                </div>
            </div>
            <div class="pnd-secteur-footer">
                <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-secteur-link" target="_blank" rel="noopener">
                    Voir la page 131 du PND →
                </a>
            </div>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 7 — MÉTHODOLOGIE
════════════════════════════════════════════════════ --}}
<section class="pnd-methodo-section">
    <div class="container" style="max-width:1100px;">
        <div class="pnd-methodo-block cbr">
            <h3>Méthodologie</h3>
            <p>
                Cette analyse croise le Plan National de Développement 2026-2030 publié par le Ministère du Plan et du Développement (Tome 1 — Diagnostic stratégique, avril 2026) avec la liste des sociétés cotées à la BRVM.
            </p>
            <p>
                Aucune projection, aucune extrapolation : chaque mention sectorielle renvoie à un paragraphe précis du document officiel, vérifiable ligne par ligne.
            </p>
            <p>
                Boursiv ne fournit pas de conseil en investissement. Cette page est une lecture comparée d'un document public.
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 8 — BLOC TÉLÉCHARGEMENT PDF
════════════════════════════════════════════════════ --}}
<section class="pnd-download-section">
    <div class="container" style="max-width:1100px;">
        <div class="pnd-download-card cbr">
            <div class="row g-4 align-items-center">
                <div class="col-md-3 d-none d-md-block">
                    <div class="pnd-download-cover">
                        <i class="bi bi-file-earmark-pdf"></i>
                        <span>PND 2026-2030<br>Tome 1<br>216 pages</span>
                    </div>
                </div>
                <div class="col-md-9">
                    <h2 class="pnd-download-title">Téléchargez le PND 2026-2030</h2>
                    <p class="pnd-download-body">
                        Tout ce que nous avons écrit ci-dessus, vous pouvez le vérifier ligne par ligne. 216 pages, document officiel, publié par le Ministère du Plan et du Développement.
                    </p>
                    <a href="{{ asset('dossiers/PND.pdf') }}" class="pnd-btn-gold" target="_blank" rel="noopener" download="PND-2026-2030.pdf">
                        <i class="bi bi-download"></i> Télécharger le PDF (18 Mo) →
                    </a>
                    <p class="pnd-download-source">
                        Source : Ministère du Plan et du Développement, République de Côte d'Ivoire, avril 2026.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════
     SECTION 9 — CTA FINAL
════════════════════════════════════════════════════ --}}
<section class="pnd-cta-section">
    <div class="container" style="max-width:1100px;">
        <div class="cbr">
            <h2 class="pnd-cta-title">
                Le PND fixe le cap jusqu'en 2030.<br>
                La BRVM offre les véhicules.<br>
                <span class="gold">Boursiv vous donne la carte.</span>
            </h2>
            <p class="pnd-cta-sub">
                Formation, analyse, accompagnement. Investissez en connaissance de cause.
            </p>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="{{ route('formations.brvm') }}" class="pnd-btn-gold">
                    Découvrir nos formations
                </a>
                <a href="{{ route('pack.show') }}" class="pnd-btn-outline">
                    Voir le Pack Boursiv
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    const io = new IntersectionObserver(
        entries => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('on'); }),
        { threshold: .1 }
    );
    document.querySelectorAll('.cbr, .cbr2, .cbr3').forEach(el => io.observe(el));
})();
</script>
@endpush
