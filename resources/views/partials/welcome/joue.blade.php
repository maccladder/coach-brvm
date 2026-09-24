{{-- resources/views/partials/welcome/joue.blade.php --}}
{{-- Bloc « Joue & entraîne-toi » : $jeux (jeux publiés), $bonusSimulateur (?int, bonus en cours) --}}
<section class="cb-joue">
    <div class="container" style="max-width:1100px;">
        <p class="cb-sec-tag" style="margin-bottom:6px;">Pratique</p>
        <h2 class="cb-une-titre" style="margin-bottom:16px;">Joue &amp; <em>entraîne-toi</em></h2>

        <div class="row g-3">
            {{-- Simulateur --}}
            <div class="col-md-6">
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

            {{-- Jeux (marketplace) --}}
            <div class="col-md-6">
                <div class="cb-joue-carte cb-joue-jeux">
                    <div class="cb-joue-icone">🎮</div>
                    <div class="cb-joue-titre">Jeux Boursiv</div>
                    @if($jeux->isNotEmpty())
                        <ul class="cb-joue-liste">
                            @foreach($jeux as $jeu)
                                <li>
                                    <a href="{{ route('marketplace.show', $jeu->slug) }}">{{ $jeu->title }}</a>
                                    <span>{{ (float) $jeu->price > 0 ? number_format((float) $jeu->price, 0, ',', ' ') . ' F' : 'Gratuit' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="cb-joue-texte">Des jeux pour apprendre la bourse en s'amusant.</p>
                    @endif
                    <a href="{{ route('jeux') }}" class="cb-ann-link" style="text-decoration:none;">Tous les jeux →</a>
                </div>
            </div>
        </div>
    </div>
</section>
