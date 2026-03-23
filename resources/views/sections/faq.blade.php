{{-- resources/views/sections/faq.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .faq-page { background: #060910; min-height: 100vh; }

    .faq-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .faq-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .faq-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .faq-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .faq-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .faq-hero-title em { font-style:italic;color:#C9A84C; }

    /* Search */
    .faq-search { background:#0C1120;border:1px solid rgba(201,168,76,.08);border-radius:4px;padding:20px 24px;margin-bottom:32px; }
    .faq-search input { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:14px !important;padding:12px 16px !important;width:100%;outline:none;transition:border-color .25s; }
    .faq-search input:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .faq-search input::placeholder { color:#6B7590 !important; }
    .faq-search-hint { font-size:12px;color:#6B7590;margin-top:8px; }

    /* Sections */
    .faq-section-header { display:flex;align-items:center;gap:10px;margin-bottom:16px;margin-top:8px; }
    .faq-section-icon { font-size:20px; }
    .faq-section-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#C9A84C; }

    /* Accordion */
    .faq-accordion { background:#0C1120;border:1px solid rgba(255,255,255,.05);border-radius:4px;overflow:hidden;margin-bottom:28px; }
    .faq-item { border-bottom:1px solid rgba(255,255,255,.04); }
    .faq-item:last-child { border-bottom:none; }

    .faq-btn {
        width:100%;text-align:left;background:transparent;border:none;cursor:pointer;
        padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:12px;
        font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#E8EAF0;
        transition:all .25s;
    }
    .faq-btn:hover { background:rgba(201,168,76,.04);color:#C9A84C; }
    .faq-btn.active { color:#C9A84C;background:rgba(201,168,76,.05); }
    .faq-btn-arrow { font-size:14px;color:#6B7590;transition:transform .3s;flex-shrink:0; }
    .faq-btn.active .faq-btn-arrow { transform:rotate(180deg);color:#C9A84C; }

    .faq-body { display:none;padding:0 22px 18px; }
    .faq-body.open { display:block; }
    .faq-body p { font-size:13.5px;color:#9AA3B8;line-height:1.75;margin-bottom:12px; }

    .faq-links { display:flex;flex-wrap:wrap;gap:8px;margin-top:4px; }
    .faq-link { display:inline-flex;align-items:center;gap:6px;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:6px 12px;border-radius:3px;background:rgba(201,168,76,.08);color:#C9A84C;border:1px solid rgba(201,168,76,.18);text-decoration:none;transition:all .25s; }
    .faq-link:hover { background:rgba(201,168,76,.16);color:#C9A84C; }

    /* CTA bottom */
    .faq-cta { background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.12);border-radius:4px;padding:28px 32px;margin-top:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px; }
    .faq-cta-title { font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#E8EAF0;margin-bottom:4px; }
    .faq-cta-sub { font-size:13px;color:#6B7590; }

    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
@php
    $site = 'https://coach-brvm.com';
    $cta_debutant = 'https://www.udemy.com/course/investir-a-la-brvm-le-guide-du-debutant/';
    $cta_intermediaire = 'https://www.udemy.com/course/brvm-strategies-investissement/';
    $sections = [
        ['title'=>'Comprendre la BRVM','icon'=>'📌','items'=>[
            ['q'=>"Qu'est-ce que la BRVM ?",'a'=>"La BRVM (Bourse Régionale des Valeurs Mobilières) est la bourse commune à 8 pays de l'UEMOA. On y investit dans des actions et obligations d'entreprises cotées.",'links'=>[['label'=>"Découvrir la BRVM",'url'=>$site.'/decouvrir-la-brvm']]],
            ['q'=>"Qui peut investir à la BRVM ?",'a'=>"Tout le monde peut investir (salariés, entrepreneurs, étudiants, diaspora). L'essentiel est de comprendre les bases et d'adopter une stratégie adaptée.",'links'=>[['label'=>"Se former (débutant)",'url'=>$cta_debutant]]],
            ['q'=>"Avec quel budget peut-on commencer ?",'a'=>"On peut démarrer avec un petit budget (souvent dès 10 000 FCFA), selon le prix de l'action, la quantité achetée et les frais du SGI.",'links'=>[['label'=>"Comprendre les SGI",'url'=>route('sgis.index')]]],
            ['q'=>"Investir à la BRVM, est-ce risqué ?",'a'=>"Oui, comme toute bourse. Mais le risque diminue fortement quand on comprend les fondamentaux, les BOC, et les états financiers. La meilleure protection, c'est la formation + la discipline.",'links'=>[['label'=>"Formation débutant",'url'=>$cta_debutant]]],
        ]],
        ['title'=>'Coach-BRVM','icon'=>'🚀','items'=>[
            ['q'=>"C'est quoi Coach-BRVM ?",'a'=>"Coach-BRVM est une plateforme qui aide à comprendre la BRVM, analyser les BOC, suivre l'actualité du marché, et apprendre à investir intelligemment.",'links'=>[['label'=>"Voir les annonces BRVM",'url'=>route('announcements.index')]]],
            ['q'=>"Coach-BRVM remplace-t-il un SGI ?",'a'=>"Non. Un SGI exécute tes ordres de bourse. Coach-BRVM t'aide à comprendre et analyser pour prendre de meilleures décisions.",'links'=>[['label'=>"Liste des SGI",'url'=>route('sgis.index')]]],
            ['q'=>"Coach-BRVM donne-t-il des signaux d'achat/vente ?",'a'=>"Non. Nous fournissons des analyses et de l'éducation financière. La décision finale appartient toujours à l'investisseur.",'links'=>[['label'=>"Apprendre une stratégie",'url'=>$cta_intermediaire]]],
        ]],
        ['title'=>'BOC – Bulletins Officiels de Cote','icon'=>'🧾','items'=>[
            ['q'=>"C'est quoi un BOC ?",'a'=>"Le Bulletin Officiel de Cote (BOC) est le document officiel publié chaque jour de cotation, avec les cours, variations, volumes, indices et informations du marché.",'links'=>[['label'=>"Voir les analyses BOC",'url'=>route('announcements.index')]]],
            ['q'=>"Pourquoi le BOC est difficile à comprendre ?",'a'=>"Parce qu'il est technique et conçu pour les professionnels. Coach-BRVM le transforme en résumé clair et compréhensible.",'links'=>[['label'=>"Voir un exemple",'url'=>route('announcements.index')]]],
            ['q'=>"Comment Coach-BRVM analyse les BOC ?",'a'=>"On extrait les informations clés (indices, hausses/baisses, volumes, tendances), puis on produit un résumé lisible + des explications utiles pour les investisseurs.",'links'=>[['label'=>"Page annonces",'url'=>route('announcements.index')]]],
        ]],
        ['title'=>'États financiers & analyse fondamentale','icon'=>'📊','items'=>[
            ['q'=>"C'est quoi un état financier ?",'a'=>"C'est un document qui montre la santé d'une entreprise (chiffre d'affaires, bénéfice, dettes, trésorerie, etc.). C'est essentiel pour investir sur le long terme.",'links'=>[]],
            ['q'=>"Faut-il lire les états financiers avant d'acheter une action ?",'a'=>"Oui. Ça aide à éviter les entreprises fragiles et à repérer les sociétés solides. C'est la base de l'investissement sérieux.",'links'=>[['label'=>"Se former (intermédiaire)",'url'=>$cta_intermediaire]]],
        ]],
        ['title'=>'Formations & apprentissage','icon'=>'🎓','items'=>[
            ['q'=>"Peut-on investir sans formation ?",'a'=>"On peut, mais c'est le meilleur moyen de perdre du temps et de l'argent. Une formation te donne des repères, une méthode et de la discipline.",'links'=>[['label'=>"Cours débutant BRVM",'url'=>$cta_debutant]]],
            ['q'=>"Les cours sont-ils adaptés aux débutants ?",'a'=>"Oui. Pas besoin de niveau en finance : c'est expliqué pas à pas, avec des exemples BRVM, et l'accès est à vie.",'links'=>[['label'=>"Formation débutant",'url'=>$cta_debutant],['label'=>"Formation stratégies",'url'=>$cta_intermediaire]]],
        ]],
    ];
@endphp
<div class="faq-page">
    <div class="faq-hero">
        <div class="faq-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="faq-hero-tag">Aide & Support</p>
                    <h1 class="faq-hero-title">FAQ — <em>Questions fréquentes</em></h1>
                    <p style="font-size:14px;color:#6B7590;font-weight:300;">Réponses simples sur la BRVM, les BOC, les états financiers et Coach-BRVM.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('announcements.index') }}" class="cb-btn-outline" style="font-size:11px;padding:9px 16px;">🧾 Analyses BOC</a>
                    <a href="{{ $cta_debutant }}" target="_blank" class="cb-btn-gold" style="font-size:11px;padding:10px 16px;">🎓 Se former</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <div class="faq-search cbr">
            <input id="faqSearch" type="text" placeholder="🔎 Rechercher une question (ex: BOC, SGI, dividendes…)">
            <div class="faq-search-hint">Astuce : tape "BOC", "SGI", "dividendes", "états financiers"…</div>
        </div>

        @php $gi = 0; @endphp
        @foreach($sections as $s)
            <div class="faq-section cbr" data-section="{{ Str::lower($s['title']) }}">
                <div class="faq-section-header">
                    <span class="faq-section-icon">{{ $s['icon'] }}</span>
                    <span class="faq-section-title">{{ $s['title'] }}</span>
                </div>
                <div class="faq-accordion">
                    @foreach($s['items'] as $item)
                        @php $gi++; @endphp
                        <div class="faq-item" data-search="{{ Str::lower($item['q'].' '.$item['a'].' '.$s['title']) }}">
                            <button class="faq-btn" onclick="toggleFaq(this)">
                                <span>{{ $item['q'] }}</span>
                                <span class="faq-btn-arrow">▼</span>
                            </button>
                            <div class="faq-body">
                                <p>{{ $item['a'] }}</p>
                                @if(!empty($item['links']))
                                    <div class="faq-links">
                                        @foreach($item['links'] as $lnk)
                                            <a href="{{ $lnk['url'] }}" class="faq-link"
                                               @if(Str::startsWith($lnk['url'],'http')) target="_blank" @endif>
                                                🔗 {{ $lnk['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="faq-cta cbr">
            <div>
                <div class="faq-cta-title">📈 Passe au niveau supérieur</div>
                <div class="faq-cta-sub">Méthode claire pour investir à la BRVM, comprendre les BOC et analyser les entreprises.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $cta_debutant }}" target="_blank" class="cb-btn-gold">🎓 Cours Débutant</a>
                <a href="{{ $cta_intermediaire }}" target="_blank" class="cb-btn-outline">🎓 Stratégies</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleFaq(btn) {
    const body = btn.nextElementSibling;
    const isOpen = body.classList.contains('open');
    // Ferme tous
    document.querySelectorAll('.faq-body.open').forEach(b => { b.classList.remove('open'); b.previousElementSibling.classList.remove('active'); });
    if (!isOpen) { body.classList.add('open'); btn.classList.add('active'); }
}

// Search
const faqInput = document.getElementById('faqSearch');
if (faqInput) {
    faqInput.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"");
        document.querySelectorAll('.faq-item').forEach(el => {
            const hay = (el.dataset.search||'').normalize("NFD").replace(/[\u0300-\u036f]/g,"");
            el.style.display = (!q || hay.includes(q)) ? '' : 'none';
        });
    });
}

document.querySelectorAll('.cbr').forEach(el => {
    new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); },{threshold:.06}).observe(el);
});
</script>
@endpush
