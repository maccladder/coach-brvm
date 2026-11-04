{{-- resources/views/partials/welcome/a-suivre.blade.php --}}
{{-- Cartes « À suivre » : $societesASuivre (App\Services\SocietesASuivre::cartes(), config/vitrine.php) --}}
@if(($societesASuivre ?? collect())->isNotEmpty())
<section class="cb-suivre-sec" aria-labelledby="cb-suivre-titre">
    <div class="container" style="max-width:1100px;">
        <p class="cb-sec-tag" id="cb-suivre-titre">À suivre à la BRVM</p>

        <div class="cb-suivre-grid">
            @foreach($societesASuivre as $s)
                @php
                    $fmtPrix = fn ($p) => number_format((float) $p, 0, ',', ' ') . ' F CFA';
                    $var     = $s['variation'];
                    $sens    = $var === null ? '' : ($var > 0 ? 'up' : ($var < 0 ? 'dn' : 'eq'));
                @endphp
                <a href="{{ route('societes.show', $s['fiche']) }}" class="cb-suivre">
                    <div class="cb-suivre-head">
                        @if(!empty($s['logo']) && file_exists(public_path($s['logo'])))
                            <span class="cb-suivre-logo"><img src="{{ asset($s['logo']) }}" alt="{{ $s['nom'] }}" loading="lazy"></span>
                        @endif
                        <div>
                            <div class="cb-suivre-nom">{{ $s['nom'] }} <span>({{ $s['ticker'] }})</span></div>
                            <div class="cb-suivre-secteur">{{ $s['secteur'] }}</div>
                        </div>
                    </div>

                    <div class="cb-suivre-cours">
                        @if($s['cours'] !== null)
                            <span class="cb-suivre-prix">{{ $fmtPrix($s['cours']) }}</span>
                            @if($var !== null)
                                <span class="cb-suivre-var {{ $sens }}">
                                    {{ $var > 0 ? '▲ +' : ($var < 0 ? '▼ -' : '') }}{{ number_format(abs($var), 2, ',', ' ') }} %
                                </span>
                            @endif
                            <span class="cb-suivre-date">Clôture au {{ $s['date_cours']->format('d/m') }}{{ $var !== null ? ' · variation sur la veille' : '' }}</span>
                        @else
                            <span class="cb-suivre-bientot">Cours bientôt disponible</span>
                        @endif
                    </div>

                    @if(!empty($s['prix_introduction']))
                        <div class="cb-suivre-intro">Prix d'introduction : {{ $fmtPrix($s['prix_introduction']) }}</div>
                    @endif

                    @if(!empty($s['accroche']))
                        <p class="cb-suivre-accroche">{{ $s['accroche'] }}</p>
                    @endif

                    <span class="cb-suivre-lien">Voir la fiche →</span>
                </a>
            @endforeach
        </div>

        <p class="cb-suivre-mention">Informations à titre éducatif, pas un conseil en investissement.</p>
    </div>
</section>
@endif
