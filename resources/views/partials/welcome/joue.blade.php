{{-- resources/views/partials/welcome/joue.blade.php --}}
{{-- Bloc « Joue & entraîne-toi » : $jeux (jeux publiés, max 4), $nbJeux (total publiés), $bonusSimulateur (?int, bonus en cours) --}}
@php
    // « Abidjan Run 🏃🏿 - Le jeu RUN à l'ivoirienne » → « Abidjan Run 🏃🏿 »
    $nomCourt = function (string $titre): string {
        $nom = trim(preg_split('/\s+[-—–]\s+/u', $titre)[0]);
        return mb_strtoupper(mb_substr($nom, 0, 1)) . mb_substr($nom, 1);
    };
@endphp
<section class="cb-joue">
    <div class="container" style="max-width:1100px;">
        <p class="cb-sec-tag" style="margin-bottom:6px;">Pratique</p>
        <h2 class="cb-une-titre" style="margin-bottom:16px;">Joue &amp; <em>entraîne-toi</em></h2>

        <div class="row g-3">
            {{-- Simulateur --}}
            <div class="col-lg-4">
                <a href="{{ route('wallet.index') }}" class="cb-joue-carte">
                    <div class="cb-joue-icone">💼</div>
                    <div class="cb-joue-titre">Simulateur de portefeuille</div>
                    <p class="cb-joue-texte">
                        Achète et vends des actions de la BRVM au dernier cours, avec des FCFA virtuels.
                        Zéro risque, vrais réflexes.
                    </p>
                    @if($bonusSimulateur)
                        <span class="cb-news-pill cb-news-nouveau">🎁 {{ number_format($bonusSimulateur, 0, ',', ' ') }} FCFA virtuels offerts</span>
                    @endif
                    <span class="cb-ann-link">Ouvrir le simulateur →</span>
                </a>
            </div>

            {{-- Jeux ivoiriens (miniatures légères, jamais les couvertures d'origine) --}}
            <div class="col-lg-8">
                <div class="cb-joue-carte cb-jeux">
                    <div class="cb-jeux-accroche">
                        {{-- Drapeau en CSS : l'emoji 🇨🇮 s'affiche « CI » sous Windows --}}
                        <span class="cb-drapeau-ci" role="img" aria-label="Côte d'Ivoire"></span>
                        Sur Boursiv, on apprend aussi en jouant
                    </div>
                    <p class="cb-joue-texte">
                        @if($jeux->isNotEmpty())
                            Des jeux 100&nbsp;% ivoiriens :
                            {{ $jeux->map(fn ($j) => $nomCourt($j->title))->implode(', ') }}{{ $nbJeux > $jeux->count() ? '…' : '.' }}
                        @else
                            Des jeux 100&nbsp;% ivoiriens pour apprendre la bourse et se détendre.
                        @endif
                    </p>

                    @if($jeux->isNotEmpty())
                        <div class="cb-jeux-bande">
                            @foreach($jeux as $jeu)
                                <a href="{{ route('marketplace.show', $jeu->slug) }}" class="cb-jeu">
                                    <span class="cb-jeu-img">
                                        @if($jeu->vignette_url)
                                            <img src="{{ $jeu->vignette_url }}" alt="{{ $nomCourt($jeu->title) }}"
                                                 width="400" height="300" loading="lazy" decoding="async">
                                        @else
                                            <span class="cb-jeu-sans-img" aria-hidden="true">🎮</span>
                                        @endif
                                    </span>
                                    <span class="cb-jeu-nom">{{ $nomCourt($jeu->title) }}</span>
                                    <span class="cb-jeu-prix">{{ (float) $jeu->price > 0 ? number_format((float) $jeu->price, 0, ',', ' ') . ' F' : 'Gratuit' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ route('jeux') }}" class="cb-cta-primary cb-jeux-cta">🎮 Découvrir les jeux</a>
                </div>
            </div>
        </div>
    </div>
</section>
