{{-- ════════════════════════════════════════════════
     resources/views/aide/strategies.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .strat-page { background: #060910; min-height: 100vh; }

    /* Hero */
    .strat-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .strat-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .strat-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .strat-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .strat-hero-title { font-family:'Playfair Display',serif;font-size:clamp(26px,4.5vw,46px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .strat-hero-title em { font-style:italic;color:#C9A84C; }
    .strat-hero-desc { font-size:14px;color:#6B7590;font-weight:300;line-height:1.75;max-width:680px; }

    /* Warning */
    .strat-warning {
        background: rgba(255,200,30,.04); border: 1px solid rgba(255,200,30,.15);
        border-radius: 4px; padding: 16px 20px; margin-bottom: 32px;
        font-size: 13px; color: #9AA3B8; line-height: 1.7;
    }
    .strat-warning strong { color: #FFC850; }

    /* Accordion */
    .strat-item {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px; overflow: hidden; margin-bottom: 16px;
        transition: border-color .3s;
    }
    .strat-item.open { border-color: rgba(201,168,76,.18); }

    .strat-btn {
        width: 100%; text-align: left; background: transparent; border: none;
        cursor: pointer; padding: 20px 24px;
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: #E8EAF0; transition: all .25s;
    }
    .strat-btn:hover { background: rgba(201,168,76,.04); color: #C9A84C; }
    .strat-btn.active { color: #C9A84C; background: rgba(201,168,76,.04); }
    .strat-btn-arrow { font-size: 12px; color: #6B7590; transition: transform .3s; flex-shrink: 0; }
    .strat-btn.active .strat-btn-arrow { transform: rotate(180deg); color: #C9A84C; }

    .strat-body { display: none; padding: 0 24px 24px; }
    .strat-body.open { display: block; }

    /* Principe */
    .strat-principe {
        font-size: 14px; color: #9AA3B8; line-height: 1.75;
        margin-bottom: 20px; padding-bottom: 18px;
        border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .strat-principe strong { color: #E8EAF0; }

    /* Avantages / Dangers */
    .strat-pro-con {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        margin-bottom: 16px;
    }
    @media(max-width: 600px) { .strat-pro-con { grid-template-columns: 1fr; } }

    .strat-box { background: #121A2C; border: 1px solid rgba(255,255,255,.05); border-radius: 3px; padding: 16px 18px; }
    .strat-box-title { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px; }
    .strat-box-title.up { color: #1fbf4a; }
    .strat-box-title.dn { color: #FF6B6B; }
    .strat-box ul { list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:7px; }
    .strat-box li { font-size:13px;color:#9AA3B8;line-height:1.5;display:flex;align-items:flex-start;gap:8px; }
    .strat-box li::before { font-size:10px;flex-shrink:0;margin-top:2px; }
    .strat-box.up li::before { content:'✓';color:#1fbf4a; }
    .strat-box.dn li::before { content:'⚠';color:#FF6B6B; }

    /* Bon réflexe */
    .strat-reflex {
        background: rgba(15,207,164,.04); border: 1px solid rgba(15,207,164,.1);
        border-radius: 3px; padding: 14px 18px; margin-bottom: 20px;
        font-size: 13px; color: #9AA3B8; line-height: 1.65;
    }
    .strat-reflex strong { color: #0FCFA4; }
    .strat-reflex-title { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#0FCFA4;margin-bottom:6px; }

    /* Outils Coach BRVM */
    .strat-tools { background: #121A2C; border: 1px solid rgba(201,168,76,.1); border-radius: 4px; padding: 20px; }
    .strat-tools-title { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#C9A84C;margin-bottom:14px; }
    .strat-tool-btn {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 11px 16px; margin-bottom: 8px;
        border-radius: 3px; text-decoration: none;
        font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
        letter-spacing: .06em; text-transform: uppercase;
        transition: all .25s; border: 1px solid;
    }
    .strat-tool-btn:last-child { margin-bottom: 0; }
    .strat-tool-btn.dark { background:rgba(255,255,255,.04);color:#E8EAF0;border-color:rgba(255,255,255,.1); }
    .strat-tool-btn.dark:hover { background:rgba(201,168,76,.08);border-color:rgba(201,168,76,.2);color:#C9A84C; }
    .strat-tool-btn.green { background:rgba(31,191,74,.08);color:#1fbf4a;border-color:rgba(31,191,74,.18); }
    .strat-tool-btn.green:hover { background:rgba(31,191,74,.14);border-color:rgba(31,191,74,.3); }
    .strat-tool-btn.blue { background:rgba(15,207,164,.08);color:#0FCFA4;border-color:rgba(15,207,164,.18); }
    .strat-tool-btn.blue:hover { background:rgba(15,207,164,.14);border-color:rgba(15,207,164,.3); }
    .strat-tool-hint { font-size:12px;color:#6B7590;margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.05);line-height:1.6; }

    /* Boutons généraux */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    /* CTA finale */
    .strat-cta { background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.12);border-radius:4px;padding:26px 30px;margin-top:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px; }
    .strat-cta-title { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0;margin-bottom:4px; }
    .strat-cta-sub { font-size:13px;color:#6B7590; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="strat-page">

    {{-- Hero --}}
    <div class="strat-hero">
        <div class="strat-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="strat-hero-tag">Guide pratique · BRVM</p>
                    <h1 class="strat-hero-title">Stratégies <em>d'investissement</em></h1>
                    <p class="strat-hero-desc">
                        Comprendre, appliquer, éviter les pièges. Pour chaque stratégie : principe, avantages,
                        dangers et comment Coach BRVM peut t'aider avec des liens directs vers les outils.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('radar.index') }}" class="cb-btn-gold" style="font-size:11px;padding:10px 16px;">📡 Radar</a>
                    <a href="{{ route('dividendes.index', ['year' => 2025]) }}" class="cb-btn-outline" style="font-size:11px;padding:9px 16px;">🏆 Dividendes</a>
                    <a href="{{ route('chocs.index') }}" class="cb-btn-outline" style="font-size:11px;padding:9px 16px;">⚡ Chocs</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <div class="strat-warning cbr">
            <strong>⚠️ Important</strong> — Ce contenu est pédagogique. Il ne constitue pas un conseil d'investissement personnalisé.
            À la BRVM, la liquidité peut être faible : toujours entrer progressivement et éviter le "tout d'un coup".
        </div>

        {{-- 1. Mean Reversion --}}
        <div class="strat-item cbr" id="strat-1">
            <button class="strat-btn active" onclick="toggleStrat(this,'strat-1')">
                <span>🧠 Mean Reversion — Retour à la moyenne</span>
                <span class="strat-btn-arrow">▼</span>
            </button>
            <div class="strat-body open">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="strat-principe">
                            Une action chute fortement : le marché a parfois <strong>surréagi</strong>.
                            La stratégie consiste à acheter après une <strong>baisse excessive</strong>,
                            en pariant sur un <strong>rebond</strong> vers un prix plus "normal".
                        </div>
                        <div class="strat-pro-con">
                            <div class="strat-box up">
                                <div class="strat-box-title up">✅ Avantages</div>
                                <ul>
                                    <li>Très efficace sur marchés calmes (souvent la BRVM)</li>
                                    <li>Achat à prix cassé</li>
                                    <li>Bonne stratégie "contrarienne"</li>
                                </ul>
                            </div>
                            <div class="strat-box dn">
                                <div class="strat-box-title dn">⚠️ Dangers</div>
                                <ul>
                                    <li>Une action peut rester rouge longtemps</li>
                                    <li>Si la baisse est fondamentale → piège</li>
                                    <li>Entrer trop tôt sans confirmation</li>
                                </ul>
                            </div>
                        </div>
                        <div class="strat-reflex">
                            <div class="strat-reflex-title">✅ Bon réflexe</div>
                            Entrer en <strong>2–3 fois</strong> (fractionner), et vérifier "pourquoi ça baisse" avant tout.
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="strat-tools">
                            <div class="strat-tools-title">🔎 Coach BRVM peut t'aider</div>
                            <a class="strat-tool-btn dark" href="{{ route('radar.index') }}">📡 Radar : repérer les bulles rouges</a>
                            <a class="strat-tool-btn dark" href="{{ route('chocs.index') }}">⚡ Chocs : comprendre si c'est un "vrai choc"</a>
                            @auth
                                <a class="strat-tool-btn blue" href="{{ route('wallet.index') }}">💼 Portefeuille virtuel : tester sans risque</a>
                            @else
                                <a class="strat-tool-btn blue" href="{{ route('login') }}">💼 Portefeuille virtuel : se connecter</a>
                            @endauth
                            <div class="strat-tool-hint">💡 Si une bulle rouge est énorme, ce n'est pas "achat automatique". Cherche d'abord la cause → puis entrée progressive.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Dividend Investing --}}
        <div class="strat-item cbr" id="strat-2">
            <button class="strat-btn" onclick="toggleStrat(this,'strat-2')">
                <span>💰 Dividend Investing — Stratégie dividendes</span>
                <span class="strat-btn-arrow">▼</span>
            </button>
            <div class="strat-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="strat-principe">
                            Investir dans des entreprises qui versent régulièrement des <strong>dividendes</strong>
                            pour générer un <strong>revenu passif</strong> (souvent annuel à la BRVM).
                        </div>
                        <div class="strat-pro-con">
                            <div class="strat-box up">
                                <div class="strat-box-title up">✅ Avantages</div>
                                <ul>
                                    <li>Revenus réguliers</li>
                                    <li>Moins stressant psychologiquement</li>
                                    <li>Très adapté au long terme</li>
                                </ul>
                            </div>
                            <div class="strat-box dn">
                                <div class="strat-box-title dn">⚠️ Dangers</div>
                                <ul>
                                    <li>Dividende peut baisser ou être supprimé</li>
                                    <li>Rendement "trop beau" parfois trompeur</li>
                                    <li>Le cours peut baisser malgré le dividende</li>
                                </ul>
                            </div>
                        </div>
                        <div class="strat-reflex">
                            <div class="strat-reflex-title">✅ Bon réflexe</div>
                            Regarder l'<strong>historique</strong> (régularité des paiements) plutôt qu'une seule année.
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="strat-tools">
                            <div class="strat-tools-title">🔎 Coach BRVM peut t'aider</div>
                            <a class="strat-tool-btn green" href="{{ route('dividendes.index', ['year' => 2025]) }}">🏆 Classement dividendes (2025)</a>
                            <a class="strat-tool-btn dark" href="{{ route('societes.index') }}">🏢 Annuaire : choisir des entreprises solides</a>
                            @auth
                                <a class="strat-tool-btn blue" href="{{ route('wallet.index') }}">💼 Portefeuille virtuel : simuler revenu dividendes</a>
                            @else
                                <a class="strat-tool-btn blue" href="{{ route('login') }}">💼 Portefeuille virtuel : se connecter</a>
                            @endauth
                            <div class="strat-tool-hint">💡 Un bon investisseur dividendes regarde la stabilité + la capacité à continuer de payer.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Trend Following --}}
        <div class="strat-item cbr" id="strat-3">
            <button class="strat-btn" onclick="toggleStrat(this,'strat-3')">
                <span>📈 Trend Following — Suivre la tendance</span>
                <span class="strat-btn-arrow">▼</span>
            </button>
            <div class="strat-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="strat-principe">
                            Acheter ce qui <strong>monte déjà</strong> (bulles vertes fortes) et éviter ce qui baisse.
                            Objectif : surfer la vague du marché.
                        </div>
                        <div class="strat-pro-con">
                            <div class="strat-box up">
                                <div class="strat-box-title up">✅ Avantages</div>
                                <ul>
                                    <li>Simple à appliquer</li>
                                    <li>Profite des phases de hausse</li>
                                    <li>Psychologiquement confortable</li>
                                </ul>
                            </div>
                            <div class="strat-box dn">
                                <div class="strat-box-title dn">⚠️ Dangers</div>
                                <ul>
                                    <li>Risque d'acheter trop tard</li>
                                    <li>Retour violent si la tendance se casse</li>
                                    <li>Besoin de règles de sortie strictes</li>
                                </ul>
                            </div>
                        </div>
                        <div class="strat-reflex">
                            <div class="strat-reflex-title">✅ Bon réflexe</div>
                            Ne jamais acheter une bulle verte "juste parce qu'elle est verte".
                            Chercher <strong>confirmation</strong> sur plusieurs jours + entrée progressive.
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="strat-tools">
                            <div class="strat-tools-title">🔎 Coach BRVM peut t'aider</div>
                            <a class="strat-tool-btn dark" href="{{ route('radar.index') }}">📡 Radar : repérer les bulles vertes dominantes</a>
                            <a class="strat-tool-btn dark" href="{{ route('chocs.index') }}">⚡ Chocs : éviter les "fausses" hausses</a>
                            @auth
                                <a class="strat-tool-btn blue" href="{{ route('wallet.index') }}">💼 Portefeuille virtuel : tester des règles d'entrée/sortie</a>
                            @else
                                <a class="strat-tool-btn blue" href="{{ route('login') }}">💼 Portefeuille virtuel : se connecter</a>
                            @endauth
                            <div class="strat-tool-hint">💡 Sur BRVM, la liquidité compte. Attention aux hausses "vides" (sans volume réel).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Buy & Hold --}}
        <div class="strat-item cbr" id="strat-4">
            <button class="strat-btn" onclick="toggleStrat(this,'strat-4')">
                <span>🧱 Buy & Hold — Acheter et conserver</span>
                <span class="strat-btn-arrow">▼</span>
            </button>
            <div class="strat-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="strat-principe">
                            Acheter des entreprises solides et les conserver longtemps (années),
                            sans se laisser perturber par les <strong>variations à court terme</strong>.
                        </div>
                        <div class="strat-pro-con">
                            <div class="strat-box up">
                                <div class="strat-box-title up">✅ Avantages</div>
                                <ul>
                                    <li>Simple & efficace sur long terme</li>
                                    <li>Moins de stress quotidien</li>
                                    <li>Moins d'erreurs émotionnelles</li>
                                </ul>
                            </div>
                            <div class="strat-box dn">
                                <div class="strat-box-title dn">⚠️ Dangers</div>
                                <ul>
                                    <li>Si mauvaise entreprise → erreur durable</li>
                                    <li>Capital immobilisé pendant des années</li>
                                    <li>Ignorer les signaux de dégradation</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="strat-tools">
                            <div class="strat-tools-title">🔎 Coach BRVM peut t'aider</div>
                            <a class="strat-tool-btn dark" href="{{ route('societes.index') }}">🏢 Annuaire : choisir des entreprises de qualité</a>
                            <a class="strat-tool-btn dark" href="{{ route('announcements.index') }}">📢 Annonces : suivre les événements importants</a>
                            @auth
                                <a class="strat-tool-btn blue" href="{{ route('wallet.index') }}">💼 Portefeuille virtuel : simuler une stratégie long terme</a>
                            @else
                                <a class="strat-tool-btn blue" href="{{ route('login') }}">💼 Se connecter</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Sector Rotation --}}
        <div class="strat-item cbr" id="strat-5">
            <button class="strat-btn" onclick="toggleStrat(this,'strat-5')">
                <span>🧩 Sector Rotation — Rotation sectorielle</span>
                <span class="strat-btn-arrow">▼</span>
            </button>
            <div class="strat-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="strat-principe">
                            Investir selon les secteurs qui deviennent attractifs
                            (banques, agro, industrie, services…) en fonction du contexte économique.
                        </div>
                        <div class="strat-pro-con">
                            <div class="strat-box up">
                                <div class="strat-box-title up">✅ Avantages</div>
                                <ul>
                                    <li>Diversification "intelligente"</li>
                                    <li>Approche macro / stratégique</li>
                                    <li>Permet d'anticiper des cycles</li>
                                </ul>
                            </div>
                            <div class="strat-box dn">
                                <div class="strat-box-title dn">⚠️ Dangers</div>
                                <ul>
                                    <li>Mauvais timing sectoriel</li>
                                    <li>Changer trop souvent de secteur</li>
                                    <li>Se baser sur du bruit / rumeurs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="strat-tools">
                            <div class="strat-tools-title">🔎 Coach BRVM peut t'aider</div>
                            <a class="strat-tool-btn dark" href="{{ route('radar.index') }}">📡 Radar : voir quels secteurs dominent</a>
                            <a class="strat-tool-btn dark" href="{{ route('chocs.index') }}">⚡ Chocs : comprendre les montées/chutes par secteur</a>
                            <a class="strat-tool-btn dark" href="{{ route('announcements.index') }}">📢 Annonces : suivre les infos sectorielles</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA finale --}}
        <div class="strat-cta cbr">
            <div>
                <div class="strat-cta-title">🚀 Prêt à appliquer ?</div>
                <div class="strat-cta-sub">Commence par observer le marché, puis teste ta stratégie en simulation.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('radar.index') }}" class="cb-btn-gold">📡 Radar</a>
                <a href="{{ route('chocs.index') }}" class="cb-btn-outline">⚡ Chocs</a>
                @auth
                    <a href="{{ route('wallet.index') }}" class="cb-btn-outline">💼 Portefeuille virtuel</a>
                @else
                    <a href="{{ route('login') }}" class="cb-btn-outline">💼 Se connecter</a>
                @endauth
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleStrat(btn, id) {
    const item = document.getElementById(id);
    const body = item.querySelector('.strat-body');
    const isOpen = body.classList.contains('open');

    // Ferme tout
    document.querySelectorAll('.strat-body.open').forEach(b => b.classList.remove('open'));
    document.querySelectorAll('.strat-btn.active').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.strat-item.open').forEach(i => i.classList.remove('open'));

    if (!isOpen) {
        body.classList.add('open');
        btn.classList.add('active');
        item.classList.add('open');
    }
}

document.querySelectorAll('.cbr').forEach(el => {
    new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, { threshold: .06 }).observe(el);
});
</script>
@endpush
