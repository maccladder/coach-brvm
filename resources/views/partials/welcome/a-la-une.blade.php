{{-- resources/views/partials/welcome/a-la-une.blade.php --}}
{{-- Bloc « À la une » de l'accueil : $aLaUne = ['vedette' => ?News, 'suivants' => Collection, 'aujourdhui' => int] --}}
@php
    $vedette    = $aLaUne['vedette'] ?? null;
    $suivants   = $aLaUne['suivants'] ?? collect();
    $aujourdhui = (int) ($aLaUne['aujourdhui'] ?? 0);

    $dateNews = function ($n) {
        $d = $n->datePublication();
        if (!$d) return '';
        if ($d->isToday())     return "Aujourd'hui · " . $d->format('H\hi');
        if ($d->isYesterday()) return 'Hier · ' . $d->format('H\hi');
        return $d->format('d/m/Y');
    };
    $classeImpact = fn ($impact) => match ($impact) {
        'Élevé'  => 'cb-impact-eleve',
        'Moyen'  => 'cb-impact-moyen',
        'Faible' => 'cb-impact-faible',
        default  => '',
    };
@endphp

<section class="cb-une" id="a-la-une">
    <div class="container" style="max-width:1100px;">

        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
            <div>
                <p class="cb-sec-tag" style="margin-bottom:6px;">
                    À la une
                    @if($aujourdhui > 0)
                        <span class="cb-une-compteur">
                            <span class="cb-une-compteur-dot"></span>
                            {{ $aujourdhui }} {{ $aujourdhui > 1 ? 'articles' : 'article' }} aujourd'hui
                        </span>
                    @endif
                </p>
                <h2 class="cb-une-titre">L'actu de la <em>BRVM</em></h2>
            </div>
            <a href="{{ route('news.index') }}" class="cb-une-tout">Toutes les actualités →</a>
        </div>

        @if($vedette)
            <div class="row g-3">
                {{-- Article vedette --}}
                <div class="col-lg-7">
                    <a href="{{ route('news.show', $vedette->slug) }}" class="cb-une-vedette">
                        <div class="cb-une-pills">
                            @if($vedette->estNouveau())
                                <span class="cb-news-pill cb-news-nouveau">Nouveau</span>
                            @endif
                            @if($vedette->impact)
                                <span class="cb-news-pill {{ $classeImpact($vedette->impact) }}">Impact {{ $vedette->impact }}</span>
                            @endif
                            @if($vedette->categorie)
                                <span class="cb-news-pill">{{ $vedette->categorie }}</span>
                            @endif
                        </div>
                        <h3 class="cb-une-vedette-titre">{{ $vedette->title }}</h3>
                        @if($vedette->resume)
                            <p class="cb-une-resume">{{ \Illuminate\Support\Str::limit($vedette->resume, 220) }}</p>
                        @endif
                        <div class="cb-une-meta">
                            @if($vedette->source_name)<span>{{ $vedette->source_name }}</span> · @endif
                            <span>{{ $dateNews($vedette) }}</span>
                        </div>
                        <span class="cb-ann-link">Lire l'article →</span>
                    </a>
                </div>

                {{-- Articles suivants --}}
                @if($suivants->isNotEmpty())
                    <div class="col-lg-5">
                        <div class="cb-une-liste">
                            @foreach($suivants as $n)
                                <a href="{{ route('news.show', $n->slug) }}" class="cb-une-item">
                                    <div class="cb-une-pills">
                                        @if($n->estNouveau())
                                            <span class="cb-news-pill cb-news-nouveau">Nouveau</span>
                                        @endif
                                        @if($n->impact)
                                            <span class="cb-news-pill {{ $classeImpact($n->impact) }}">Impact {{ $n->impact }}</span>
                                        @endif
                                        @if($n->categorie)
                                            <span class="cb-news-pill">{{ $n->categorie }}</span>
                                        @endif
                                    </div>
                                    <div class="cb-une-item-titre">{{ $n->title }}</div>
                                    <div class="cb-une-meta">
                                        @if($n->source_name)<span>{{ $n->source_name }}</span> · @endif
                                        <span>{{ $dateNews($n) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="cb-une-vide">
                Les actualités de la BRVM arrivent chaque matin. En attendant, consultez
                <a href="{{ route('news.index') }}">les actualités précédentes</a>
                ou <a href="{{ route('radar.index') }}">le radar du marché</a>.
            </div>
        @endif

    </div>
</section>
