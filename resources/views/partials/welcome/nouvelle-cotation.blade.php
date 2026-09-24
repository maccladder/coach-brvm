{{-- resources/views/partials/welcome/nouvelle-cotation.blade.php --}}
{{-- Encart « Nouvelle cotation » : $nouvellesCotations (App\Services\NouvellesCotations::actives()) --}}
@if(($nouvellesCotations ?? collect())->isNotEmpty())
<section class="cb-cotation-sec">
    <div class="container" style="max-width:1100px;">
        @foreach($nouvellesCotations as $c)
            @php
                $variation = $c['variation_opv'];
                $sens      = $variation === null ? '' : ($variation >= 0 ? 'up' : 'dn');
                $fmtPrix   = fn ($p) => number_format((float) $p, 0, ',', ' ') . ' F';
            @endphp
            <a href="{{ route('societes.show', $c['fiche']) }}" class="cb-cotation">
                <div class="cb-cotation-id">
                    @if(!empty($c['logo']) && file_exists(public_path($c['logo'])))
                        <span class="cb-cotation-logo"><img src="{{ asset($c['logo']) }}" alt="{{ $c['nom'] }}" loading="lazy"></span>
                    @endif
                    <div>
                        <div class="cb-cotation-tag">Nouvelle cotation · depuis le {{ \Illuminate\Support\Carbon::parse($c['premiere_cotation'])->format('d/m/Y') }}</div>
                        <div class="cb-cotation-nom">{{ $c['nom'] }} <span>({{ $c['ticker'] }})</span></div>
                    </div>
                </div>

                <div class="cb-cotation-cours">
                    @if($c['cours'] !== null)
                        <div class="cb-cotation-prix">{{ $fmtPrix($c['cours']) }}</div>
                        @if($variation !== null)
                            <div class="cb-cotation-var {{ $sens }}">
                                {{ $variation >= 0 ? '+' : '-' }}{{ number_format(abs($variation), 1, ',', ' ') }} % vs OPV ({{ $fmtPrix($c['prix_opv']) }})
                            </div>
                        @endif
                        <div class="cb-cotation-source">{{ $c['source_cours'] === 'boc'
                            ? 'Clôture BOC du ' . $c['date_cours']->format('d/m/Y')
                            : 'Dernier relevé BRVM : ' . $c['date_cours']->format('d/m à H\hi') }}</div>
                    @else
                        <div class="cb-cotation-prix">{{ $fmtPrix($c['prix_opv']) }}</div>
                        <div class="cb-cotation-source">Prix de l'OPV · premier cours à venir</div>
                    @endif
                </div>

                <span class="cb-cotation-lien">Voir la fiche →</span>
            </a>
        @endforeach
    </div>
</section>
@endif
