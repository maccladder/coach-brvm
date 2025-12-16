@extends('layouts.app')

@section('content')
@php
    // ✅ Astuce: tu peux remplacer par config('app.url') si besoin
    $site = 'https://coach-brvm.com';

    // 🔥 CTA (Udemy / pages internes)
    $cta_debutant = 'https://www.udemy.com/course/investir-a-la-brvm-le-guide-du-debutant/';
    $cta_intermediaire = 'https://www.udemy.com/course/brvm-strategies-investissement/';

    // ✅ Catégories + questions
    $sections = [
        [
            'title' => 'Comprendre la BRVM',
            'icon'  => '📌',
            'items' => [
                [
                    'q' => "Qu’est-ce que la BRVM ?",
                    'a' => "La BRVM (Bourse Régionale des Valeurs Mobilières) est la bourse commune à 8 pays de l’UEMOA. On y investit dans des actions et obligations d’entreprises cotées.",
                    'links' => [
                        ['label' => "Découvrir la BRVM", 'url' => $site.'/decouvrir-la-brvm'],
                    ],
                ],
                [
                    'q' => "Qui peut investir à la BRVM ?",
                    'a' => "Tout le monde peut investir (salariés, entrepreneurs, étudiants, diaspora). L’essentiel est de comprendre les bases et d’adopter une stratégie adaptée.",
                    'links' => [
                        ['label' => "Bien débuter", 'url' => $site.'/debuter'],
                        ['label' => "Se former (cours débutant)", 'url' => $cta_debutant],
                    ],
                ],
                [
                    'q' => "Avec quel budget peut-on commencer ?",
                    'a' => "On peut démarrer avec un petit budget (souvent dès 10 000 FCFA), selon le prix de l’action, la quantité achetée et les frais du SGI.",
                    'links' => [
                        ['label' => "Comprendre les SGI", 'url' => $site.'/sgis'],
                    ],
                ],
                [
                    'q' => "Investir à la BRVM, est-ce risqué ?",
                    'a' => "Oui, comme toute bourse. Mais le risque diminue fortement quand on comprend les fondamentaux, les BOC, et les états financiers. La meilleure protection, c’est la formation + la discipline.",
                    'links' => [
                        ['label' => "Formation BRVM (débutant)", 'url' => $cta_debutant],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Coach-BRVM',
            'icon'  => '🚀',
            'items' => [
                [
                    'q' => "C’est quoi Coach-BRVM ?",
                    'a' => "Coach-BRVM est une plateforme qui aide à comprendre la BRVM, analyser les BOC, suivre l’actualité du marché, et apprendre à investir intelligemment.",
                    'links' => [
                        ['label' => "Voir les annonces BRVM", 'url' => $site.'/annonces'],
                    ],
                ],
                [
                    'q' => "Coach-BRVM remplace-t-il un SGI ?",
                    'a' => "Non. Un SGI exécute tes ordres de bourse. Coach-BRVM t’aide à comprendre et analyser pour prendre de meilleures décisions.",
                    'links' => [
                        ['label' => "Liste des SGI par pays", 'url' => $site.'/sgis'],
                    ],
                ],
                [
                    'q' => "Coach-BRVM donne-t-il des signaux d’achat/vente ?",
                    'a' => "Non. Nous fournissons des analyses et de l’éducation financière. La décision finale appartient toujours à l’investisseur.",
                    'links' => [
                        ['label' => "Apprendre une stratégie", 'url' => $cta_intermediaire],
                    ],
                ],
            ],
        ],
        [
            'title' => 'BOC – Bulletins Officiels de Cote',
            'icon'  => '🧾',
            'items' => [
                [
                    'q' => "C’est quoi un BOC ?",
                    'a' => "Le Bulletin Officiel de Cote (BOC) est le document officiel publié chaque jour de cotation, avec les cours, variations, volumes, indices et informations du marché.",
                    'links' => [
                        ['label' => "Voir les analyses BOC", 'url' => $site.'/annonces'],
                    ],
                ],
                [
                    'q' => "Pourquoi le BOC est difficile à comprendre ?",
                    'a' => "Parce qu’il est technique et conçu pour les professionnels. Coach-BRVM le transforme en résumé clair et compréhensible.",
                    'links' => [
                        ['label' => "Voir un exemple analysé", 'url' => $site.'/annonces'],
                    ],
                ],
                [
                    'q' => "Comment Coach-BRVM analyse les BOC ?",
                    'a' => "On extrait les informations clés (indices, hausses/baisses, volumes, tendances), puis on produit un résumé lisible + des explications utiles pour les investisseurs.",
                    'links' => [
                        ['label' => "Page annonces", 'url' => $site.'/annonces'],
                    ],
                ],
            ],
        ],
        [
            'title' => 'États financiers & analyse fondamentale',
            'icon'  => '📊',
            'items' => [
                [
                    'q' => "C’est quoi un état financier ?",
                    'a' => "C’est un document qui montre la santé d’une entreprise (chiffre d’affaires, bénéfice, dettes, trésorerie, etc.). C’est essentiel pour investir sur le long terme.",
                    'links' => [
                        ['label' => "Comprendre les états financiers", 'url' => $site.'/etats-financiers'],
                    ],
                ],
                [
                    'q' => "Faut-il lire les états financiers avant d’acheter une action ?",
                    'a' => "Oui. Ça aide à éviter les entreprises fragiles et à repérer les sociétés solides. C’est la base de l’investissement sérieux.",
                    'links' => [
                        ['label' => "Se former (niveau intermédiaire)", 'url' => $cta_intermediaire],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Formations (Udemy) & apprentissage',
            'icon'  => '🎓',
            'items' => [
                [
                    'q' => "Peut-on investir sans formation ?",
                    'a' => "On peut, mais c’est le meilleur moyen de perdre du temps et de l’argent. Une formation te donne des repères, une méthode et de la discipline.",
                    'links' => [
                        ['label' => "Cours débutant BRVM", 'url' => $cta_debutant],
                    ],
                ],
                [
                    'q' => "Les cours sont-ils adaptés aux débutants ?",
                    'a' => "Oui. Pas besoin de niveau en finance : c’est expliqué pas à pas, avec des exemples BRVM, et l’accès est à vie.",
                    'links' => [
                        ['label' => "Voir la formation débutant", 'url' => $cta_debutant],
                        ['label' => "Voir la formation stratégies", 'url' => $cta_intermediaire],
                    ],
                ],
            ],
        ],
    ];
@endphp

<div class="container py-5" style="max-width: 1100px;">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">Foire Aux Questions (FAQ)</h1>
            <div class="text-muted">
                Réponses simples sur la BRVM, les BOC, les états financiers et l’utilisation de Coach-BRVM.
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ $site.'/annonces' }}" class="btn btn-outline-primary">
                🧾 Voir les analyses BOC
            </a>
            <a href="{{ $cta_debutant }}" target="_blank" class="btn btn-primary">
                🎓 Se former (Débutant)
            </a>
        </div>
    </div>

    {{-- Search --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <label class="form-label fw-semibold mb-2">🔎 Rechercher une question</label>
            <input id="faqSearch" type="text" class="form-control form-control-lg"
                   placeholder="Ex: c’est quoi un BOC ? / comment investir ? / état financier...">
            <div class="small text-muted mt-2">
                Astuce : tape “BOC”, “SGI”, “dividendes”, “états financiers”...
            </div>
        </div>
    </div>

    {{-- FAQ Sections --}}
    <div class="accordion" id="faqAccordion">
        @php $globalIndex = 0; @endphp

        @foreach($sections as $sIndex => $section)
            <div class="mb-4 faq-section" data-section-title="{{ Str::lower($section['title']) }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="fs-4">{{ $section['icon'] }}</div>
                    <h3 class="fw-bold mb-0">{{ $section['title'] }}</h3>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        @foreach($section['items'] as $iIndex => $item)
                            @php
                                $globalIndex++;
                                $collapseId = "faqCollapse".$globalIndex;
                                $headingId  = "faqHeading".$globalIndex;
                                $searchText = Str::lower($item['q'].' '.$item['a'].' '.$section['title']);
                            @endphp

                            <div class="faq-item" data-search="{{ $searchText }}">
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header" id="{{ $headingId }}">
                                        <button class="accordion-button collapsed fw-semibold" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $collapseId }}">
                                            {{ $item['q'] }}
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="{{ $headingId }}"
                                         data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-2">{{ $item['a'] }}</p>

                                            @if(!empty($item['links']))
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    @foreach($item['links'] as $lnk)
                                                        <a href="{{ $lnk['url'] }}"
                                                           class="btn btn-sm btn-outline-secondary"
                                                           @if(Str::startsWith($lnk['url'], 'http')) target="_blank" @endif>
                                                            🔗 {{ $lnk['label'] }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-3">
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer CTA --}}
    <div class="card border-0 shadow-sm mt-5">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="fw-bold fs-5">📈 Tu veux passer au niveau supérieur ?</div>
                <div class="text-muted">Apprends une méthode claire pour investir à la BRVM, comprendre les BOC et analyser les entreprises.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ $cta_debutant }}" target="_blank" class="btn btn-primary">
                    🎓 Cours Débutant
                </a>
                <a href="{{ $cta_intermediaire }}" target="_blank" class="btn btn-outline-primary">
                    🎓 Stratégies (Intermédiaire)
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ✅ Script recherche (simple et efficace) --}}
@push('scripts')
<script>
(function () {
    const input = document.getElementById('faqSearch');
    if (!input) return;

    const items = Array.from(document.querySelectorAll('.faq-item'));

    function normalize(s) {
        return (s || '').toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    input.addEventListener('input', function () {
        const q = normalize(input.value.trim());

        // reset
        if (!q) {
            items.forEach(el => el.style.display = '');
            return;
        }

        items.forEach(el => {
            const hay = normalize(el.getAttribute('data-search') || '');
            el.style.display = hay.includes(q) ? '' : 'none';
        });
    });
})();
</script>
@endpush

@endsection
