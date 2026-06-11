{{-- resources/views/lettre-ci/marketplace-showcase.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    /* ── Page base ── */
    .lci-page { background: var(--cb-paper); min-height: 100vh; }

    /* ── Hero ── */
    .lci-hero {
        min-height: 72vh;
        background:
            radial-gradient(ellipse 90% 60% at 50% 0%, rgba(176,134,46,.11) 0%, transparent 55%),
            radial-gradient(ellipse 50% 40% at 85% 80%, rgba(15,92,67,.06) 0%, transparent 50%),
            var(--cb-paper);
        position: relative; overflow: hidden;
        display: flex; align-items: center;
        padding: 80px 0;
    }
    .lci-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(176,134,46,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(176,134,46,.05) 1px, transparent 1px);
        background-size: 60px 60px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .lci-hero-orb-1 {
        position: absolute; border-radius: 50%; filter: blur(90px); pointer-events: none;
        width: 500px; height: 500px; top: -100px; left: -100px;
        background: rgba(176,134,46,.07);
        animation: orbF 14s ease-in-out infinite;
    }
    .lci-hero-orb-2 {
        position: absolute; border-radius: 50%; filter: blur(90px); pointer-events: none;
        width: 400px; height: 400px; bottom: -60px; right: -60px;
        background: rgba(15,92,67,.05);
        animation: orbF 18s ease-in-out infinite reverse;
    }
    @keyframes orbF { 0%,100%{transform:translate(0,0)} 50%{transform:translate(20px,-30px)} }

    .lci-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(176,134,46,.09); border: 1px solid rgba(176,134,46,.22);
        border-radius: 100px; padding: 6px 16px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .12em; text-transform: uppercase; color: var(--cb-gold);
        margin-bottom: 28px;
    }
    .lci-badge-dot {
        width: 6px; height: 6px; background: var(--cb-forest); border-radius: 50%;
        animation: bdot 2s ease-in-out infinite;
    }
    @keyframes bdot { 0%,100%{opacity:1;box-shadow:0 0 6px var(--cb-forest)} 50%{opacity:.2;box-shadow:none} }

    .lci-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(34px, 5.5vw, 64px);
        font-weight: 900; line-height: 1.06; letter-spacing: -.02em;
        color: var(--cb-ink); margin-bottom: 12px;
    }
    .lci-hero-title em { font-style: italic; color: var(--cb-gold); }

    .lci-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .24em; text-transform: uppercase; color: var(--cb-muted); margin-bottom: 22px;
    }
    .lci-hero-tag span { color: var(--cb-forest); }

    .lci-hero-desc {
        font-size: clamp(14px, 1.8vw, 16px); color: var(--cb-muted); font-weight: 300;
        line-height: 1.75; max-width: 540px; margin-bottom: 36px;
    }
    .lci-hero-desc strong { color: var(--cb-ink); font-weight: 500; }

    .cb-cta-primary {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, var(--cb-gold), #9B6B15);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 13px; letter-spacing: .06em; text-transform: uppercase;
        padding: 14px 28px; border-radius: 3px; text-decoration: none; transition: all .3s; border: none;
    }
    .cb-cta-primary:hover { box-shadow: 0 10px 32px rgba(176,134,46,.3); transform: translateY(-2px); color: #050810 !important; }

    .cb-cta-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: transparent; color: var(--cb-ink) !important;
        font-family: 'Syne', sans-serif; font-weight: 600; font-size: 13px;
        letter-spacing: .06em; text-transform: uppercase;
        padding: 13px 22px; border-radius: 3px; text-decoration: none;
        border: 1px solid var(--cb-border); transition: all .3s;
    }
    .cb-cta-outline:hover { border-color: var(--cb-gold); color: var(--cb-gold) !important; background: rgba(176,134,46,.05); }

    .lci-hero-indicators {
        display: flex; flex-wrap: wrap; gap: 20px; margin-top: 40px;
    }
    .lci-indicator {
        display: flex; align-items: center; gap: 8px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .08em; text-transform: uppercase; color: var(--cb-muted);
    }
    .lci-indicator .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--cb-forest); }

    .lci-hero-card {
        background: var(--cb-card); border: 1px solid rgba(176,134,46,.12);
        border-radius: 6px; overflow: hidden; backdrop-filter: blur(12px);
    }
    .lci-hero-card-header {
        background: var(--cb-paper); border-bottom: 1px solid rgba(176,134,46,.08);
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
    }
    .lci-card-dot { width: 8px; height: 8px; border-radius: 50%; }
    .lci-card-label {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase; color: var(--cb-muted);
        flex: 1; text-align: center;
    }
    .lci-step-item {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 20px; border-bottom: 1px solid var(--cb-border);
        transition: background .2s;
    }
    .lci-step-item:last-child { border-bottom: none; }
    .lci-step-item:hover { background: rgba(176,134,46,.04); }
    .lci-step-n {
        font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 900;
        color: rgba(176,134,46,.25); line-height: 1; min-width: 24px;
    }
    .lci-step-name { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 600; color: var(--cb-ink); }
    .lci-step-desc { font-size: 11px; color: var(--cb-muted); margin-top: 1px; }
    .lci-step-tag {
        font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        padding: 2px 8px; border-radius: 100px; margin-left: auto; flex-shrink: 0;
    }
    .lci-step-tag.ai  { background: rgba(43,127,196,.08); color: #2B7FC4; border: 1px solid rgba(43,127,196,.2); }
    .lci-step-tag.new { background: rgba(176,134,46,.08); color: var(--cb-gold); border: 1px solid rgba(176,134,46,.2); }
    .lci-step-tag.ok  { background: rgba(15,92,67,.08); color: var(--cb-forest); border: 1px solid rgba(15,92,67,.18); }

    /* ── Sections communes ── */
    .cb-sec { padding: clamp(56px, 8vw, 100px) 0; }
    .cb-sec-alt { background: var(--cb-card); }

    .cb-sec-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; letter-spacing: .2em;
        text-transform: uppercase; color: var(--cb-forest); margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .cb-sec-tag::before { content:''; width:28px; height:1px; background:var(--cb-forest); }

    .cb-sec-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(26px, 4vw, 44px); font-weight: 700; line-height: 1.1;
        color: var(--cb-ink); margin-bottom: 12px;
    }
    .cb-sec-title em { font-style: italic; color: var(--cb-gold); }
    .cb-divider { width: 52px; height: 1px; background: linear-gradient(90deg, var(--cb-gold), transparent); margin: 14px 0 0; }

    /* Comment ça marche */
    .cb-how-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1px; background: rgba(176,134,46,.07); border: 1px solid rgba(176,134,46,.07);
        margin-top: 44px;
    }
    .cb-how-step { background: var(--cb-card); padding: 30px 26px; }
    .cb-how-n {
        font-family: 'Playfair Display', serif; font-size: 40px; font-weight: 900;
        color: rgba(176,134,46,.15); line-height: 1; margin-bottom: 14px;
    }
    .cb-how-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: var(--cb-ink); margin-bottom: 8px; }
    .cb-how-desc { font-size: 13px; color: var(--cb-muted); line-height: 1.65; }
    .cb-how-icon { font-size: 22px; margin-bottom: 12px; display: block; }

    /* Types de lettres */
    .lci-cat-label {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .14em; text-transform: uppercase;
        padding: 4px 12px; border-radius: 3px; display: inline-block; margin-bottom: 16px;
    }
    .lci-cat-pro   { background: rgba(15,92,67,.08); color: var(--cb-forest); border: 1px solid rgba(15,92,67,.18); }
    .lci-cat-admin { background: rgba(176,134,46,.1); color: var(--cb-gold); border: 1px solid rgba(176,134,46,.2); }
    .lci-cat-perso { background: rgba(107,117,144,.1); color: var(--cb-muted); border: 1px solid rgba(107,117,144,.2); }

    .lci-tpl-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(15,92,67,.04); border: 1px solid var(--cb-border);
        border-radius: 3px; padding: 7px 11px; font-size: 12.5px; color: var(--cb-muted);
        margin: 3px; transition: all .2s; text-decoration: none;
    }
    .lci-tpl-chip:hover { background: rgba(176,134,46,.06); border-color: rgba(176,134,46,.22); color: var(--cb-gold); text-decoration: none; }

    /* Value prop */
    .cb-feature-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1px; background: rgba(176,134,46,.08); border: 1px solid rgba(176,134,46,.08);
        margin-top: 48px;
    }
    .cb-feature-card {
        background: var(--cb-card); padding: 30px 26px; position: relative; overflow: hidden;
        transition: background .35s;
    }
    .cb-feature-card::before {
        content: ''; position: absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, var(--cb-gold), transparent);
        opacity: 0; transition: opacity .35s;
    }
    .cb-feature-card:hover { background: rgba(176,134,46,.04); }
    .cb-feature-card:hover::before { opacity: 1; }
    .cb-feature-num {
        position: absolute; top:16px; right:18px;
        font-family: 'Playfair Display', serif; font-size: 40px; font-weight: 900;
        color: rgba(176,134,46,.06); line-height: 1;
    }
    .cb-feature-emoji { font-size: 26px; margin-bottom: 16px; display: block; }
    .cb-feature-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: var(--cb-ink); margin-bottom: 8px; }
    .cb-feature-desc { font-size: 13px; color: var(--cb-muted); line-height: 1.65; }

    /* Pricing CTA */
    .lci-price-box {
        background: var(--cb-card); border: 1px solid rgba(176,134,46,.18);
        border-radius: 6px; padding: 40px 48px; position: relative; overflow: hidden;
        max-width: 560px; margin: 0 auto;
    }
    .lci-price-box::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, var(--cb-gold), var(--cb-forest), transparent);
    }
    .lci-price-num {
        font-family: 'Playfair Display', serif; font-size: 52px; font-weight: 900;
        color: var(--cb-gold); line-height: 1;
    }
    .lci-price-num span { font-size: 18px; color: var(--cb-muted); font-family: 'DM Sans', sans-serif; font-weight: 300; }
    .lci-perks { list-style: none; padding: 0; margin: 20px 0 28px; }
    .lci-perks li {
        font-size: 13px; color: var(--cb-muted); padding: 8px 0;
        border-bottom: 1px solid var(--cb-border);
        display: flex; align-items: center; gap: 10px;
    }
    .lci-perks li:last-child { border-bottom: none; }
    .lci-perks li::before { content: '✓'; color: var(--cb-forest); font-weight: 700; flex-shrink: 0; }
    .lci-perks li strong { color: var(--cb-ink); font-weight: 500; }

    /* CTA finale */
    .lci-cta-finale {
        padding: clamp(80px, 12vw, 140px) 0;
        background: radial-gradient(circle at 50% 50%, rgba(176,134,46,.07) 0%, transparent 60%), var(--cb-paper);
        text-align: center;
    }
    .lci-cta-finale-title {
        font-family: 'Playfair Display', serif; font-size: clamp(30px, 6vw, 60px);
        font-weight: 900; line-height: 1.05; color: var(--cb-ink); margin-bottom: 12px;
    }
    .lci-cta-finale-title em { font-style: italic; color: var(--cb-gold); }

    .cbr { opacity:0; transform:translateY(24px); transition:all .8s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')
@php $lciPrice = number_format(config('services.lettreci.price', 5000), 0, ',', ' '); @endphp
<div class="lci-page">

{{-- ════════════════════════════════════════
     HERO
════════════════════════════════════════ --}}
<section class="lci-hero">
    <div class="lci-hero-grid"></div>
    <div class="lci-hero-orb-1"></div>
    <div class="lci-hero-orb-2"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="cbr">
                    <div class="lci-badge">
                        <span class="lci-badge-dot"></span>
                        Nouveau — Outil IA
                    </div>
                    <h1 class="lci-hero-title">
                        Vos lettres officielles<br>rédigées par une IA,<br><em>en 30 secondes</em>
                    </h1>
                    <p class="lci-hero-tag">
                        Outil <span>·</span> Côte d'Ivoire <span>·</span> 15 modèles
                    </p>
                    <p class="lci-hero-desc">
                        Démission, bail, mise en demeure, lettre de motivation&nbsp;—
                        <strong>15 types de lettres professionnelles</strong> générées instantanément,
                        prêtes à imprimer ou à envoyer en PDF.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        @auth
                            @if(\App\Models\LettreCIAccess::hasActiveAccess(auth()->user()))
                                <a href="{{ route('lettreci.dashboard') }}" class="cb-cta-primary">
                                    Accéder à mon espace →
                                </a>
                            @else
                                <a href="{{ route('lettreci.landing') }}" class="cb-cta-primary">
                                    Débloquer — {{ $lciPrice }} FCFA
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}?redirect={{ urlencode(route('lettreci.landing')) }}" class="cb-cta-primary">
                                Débloquer — {{ $lciPrice }} FCFA
                            </a>
                            <a href="{{ route('register') }}" class="cb-cta-outline">
                                Créer un compte gratuit
                            </a>
                        @endauth
                    </div>
                    <div class="lci-hero-indicators">
                        <span class="lci-indicator"><span class="dot"></span>15 types de lettres</span>
                        <span class="lci-indicator"><span class="dot"></span>Générations illimitées</span>
                        <span class="lci-indicator"><span class="dot"></span>Export PDF professionnel</span>
                        <span class="lci-indicator"><span class="dot"></span>Accès à vie</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block cbr cbr2">
                <div class="lci-hero-card">
                    <div class="lci-hero-card-header">
                        <span class="lci-card-dot" style="background:#FF5F57;"></span>
                        <span class="lci-card-dot" style="background:#FEBC2E;"></span>
                        <span class="lci-card-dot" style="background:#28C840;"></span>
                        <span class="lci-card-label">LettreCI — Modèles disponibles</span>
                    </div>
                    @php $allTemplates = config('lettreci_templates.types', []); $preview = array_slice($allTemplates, 0, 6, true); @endphp
                    @foreach($preview as $slug => $tpl)
                    <div class="lci-step-item">
                        <span style="font-size:18px;">{{ $tpl['icon'] ?? '📄' }}</span>
                        <div class="flex-grow-1">
                            <div class="lci-step-name">{{ $tpl['name'] }}</div>
                            <div class="lci-step-desc">{{ $tpl['description'] ?? '' }}</div>
                        </div>
                        <span class="lci-step-tag ai">IA</span>
                    </div>
                    @endforeach
                    <div class="lci-step-item" style="opacity:.55;font-family:'Syne',sans-serif;font-size:11px;color:var(--cb-muted);justify-content:center;letter-spacing:.1em;text-transform:uppercase;">
                        + {{ count($allTemplates) - 6 }} autres modèles…
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════
     COMMENT ÇA MARCHE
════════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container cbr">
        <div class="cb-sec-tag">Comment ça marche</div>
        <h2 class="cb-sec-title">3 étapes, <em>c'est tout</em></h2>
        <div class="cb-divider"></div>
    </div>
    <div class="container cbr cbr2">
        <div class="cb-how-grid">
            <div class="cb-how-step">
                <span class="cb-how-icon">📂</span>
                <div class="cb-how-n">01</div>
                <div class="cb-how-title">Choisis ton type de lettre</div>
                <p class="cb-how-desc">Parcourez nos 15 modèles répartis en 3 catégories : Professionnel, Administration, Personnel.</p>
            </div>
            <div class="cb-how-step">
                <span class="cb-how-icon">✏️</span>
                <div class="cb-how-n">02</div>
                <div class="cb-how-title">Remplis le formulaire guidé</div>
                <p class="cb-how-desc">Entrez vos informations dans un formulaire simple (nom, dates, motif…). L'IA se charge du reste.</p>
            </div>
            <div class="cb-how-step">
                <span class="cb-how-icon">📥</span>
                <div class="cb-how-n">03</div>
                <div class="cb-how-title">Télécharge ton PDF professionnel</div>
                <p class="cb-how-desc">La lettre est générée en moins de 30 secondes. Modifiez-la si besoin, puis exportez en PDF.</p>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════
     TYPES DE LETTRES
════════════════════════════════════════ --}}
<section class="cb-sec">
    <div class="container">
        <div class="cbr">
            <div class="cb-sec-tag">Catalogue</div>
            <h2 class="cb-sec-title">{{ count($templates ?? config('lettreci_templates.types', [])) }} modèles <em>disponibles</em></h2>
            <div class="cb-divider"></div>
        </div>
        @php $templates = config('lettreci_templates.types', []); @endphp
        <div class="row g-5 mt-1">
            @foreach(['professionnel' => ['label' => 'Professionnel', 'class' => 'lci-cat-pro'], 'administration' => ['label' => 'Administration', 'class' => 'lci-cat-admin'], 'personnel' => ['label' => 'Personnel', 'class' => 'lci-cat-perso']] as $cat => $meta)
            @php $catItems = array_filter($templates, fn($t) => ($t['category'] ?? '') === $cat); @endphp
            @if($catItems)
            <div class="col-md-4 cbr cbr{{ $loop->iteration }}">
                <span class="lci-cat-label {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                <div>
                    @foreach($catItems as $slug => $tpl)
                    <a href="{{ route('lettreci.new') }}" class="lci-tpl-chip">
                        {{ $tpl['icon'] ?? '📄' }} {{ $tpl['name'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════
     POURQUOI 5 000 FCFA
════════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container">
        <div class="cbr">
            <div class="cb-sec-tag">Valeur</div>
            <h2 class="cb-sec-title">Pourquoi <em>{{ $lciPrice }} FCFA</em>&nbsp;?</h2>
            <div class="cb-divider"></div>
        </div>
        <div class="cb-feature-grid cbr cbr2">
            <div class="cb-feature-card">
                <div class="cb-feature-num">01</div>
                <span class="cb-feature-emoji">♾️</span>
                <div class="cb-feature-title">Lettres illimitées, pour toujours</div>
                <p class="cb-feature-desc">Un paiement unique. Générez autant de lettres que vous voulez, sans jamais repayer.</p>
            </div>
            <div class="cb-feature-card">
                <div class="cb-feature-num">02</div>
                <span class="cb-feature-emoji">🆕</span>
                <div class="cb-feature-title">Nouveaux modèles offerts</div>
                <p class="cb-feature-desc">Chaque nouveau modèle ajouté au catalogue est automatiquement disponible pour tous les membres.</p>
            </div>
            <div class="cb-feature-card">
                <div class="cb-feature-num">03</div>
                <span class="cb-feature-emoji">💸</span>
                <div class="cb-feature-title">Vs. écrivain public : 5 à 15× moins cher</div>
                <p class="cb-feature-desc">Un écrivain public à Abidjan facture 5 000–15 000 FCFA <em>par lettre</em>. Ici, c'est {{ $lciPrice }} FCFA pour la vie.</p>
            </div>
            <div class="cb-feature-card">
                <div class="cb-feature-num">04</div>
                <span class="cb-feature-emoji">⚡</span>
                <div class="cb-feature-title">30 secondes vs. 2 heures</div>
                <p class="cb-feature-desc">Pas de déplacement, pas d'attente. Votre lettre est prête avant que vous ayez fini votre café.</p>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════
     CTA FINALE
════════════════════════════════════════ --}}
<section class="lci-cta-finale">
    <div class="container cbr">
        <div class="cb-sec-tag" style="justify-content:center;">Rejoindre</div>
        <h2 class="lci-cta-finale-title">
            Prêt à gagner du temps<br>sur vos <em>lettres officielles</em>&nbsp;?
        </h2>
        <p style="font-size:15px;color:var(--cb-muted);margin:16px 0 36px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.7;">
            Paiement unique · {{ $lciPrice }} FCFA · Accès à vie · Paiement sécurisé Paystack (carte, Mobile Money)
        </p>

        <div class="lci-price-box cbr cbr2">
            <div class="d-flex align-items-baseline gap-3 mb-4">
                <div class="lci-price-num">{{ $lciPrice }} <span>FCFA</span></div>
                <div style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--cb-forest);">Accès à vie</div>
            </div>
            <ul class="lci-perks">
                <li><strong>15 modèles</strong> de lettres couvrant toutes les situations</li>
                <li>Génération IA en <strong>moins de 30 secondes</strong></li>
                <li>Édition libre avant export <strong>PDF professionnel</strong></li>
                <li>Accès permanent — <strong>aucun abonnement</strong></li>
                <li>Nouveaux modèles <strong>ajoutés gratuitement</strong></li>
            </ul>
            @auth
                @if(\App\Models\LettreCIAccess::hasActiveAccess(auth()->user()))
                    <a href="{{ route('lettreci.dashboard') }}" class="cb-cta-primary w-100 justify-content-center">
                        Accéder à mon espace →
                    </a>
                @else
                    <a href="{{ route('lettreci.landing') }}" class="cb-cta-primary w-100 justify-content-center">
                        Débloquer LettreCI — {{ $lciPrice }} FCFA
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}?redirect={{ urlencode(route('lettreci.landing')) }}" class="cb-cta-primary w-100 justify-content-center">
                    Se connecter &amp; débloquer
                </a>
            @endauth
        </div>
    </div>
</section>

</div>
@endsection

@push('scripts')
<script>
    const els = document.querySelectorAll('.cbr');
    const obs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: .12 });
    els.forEach(el => obs.observe(el));
</script>
@endpush
