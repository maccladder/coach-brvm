@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    <h1 class="fw-bold mb-3">🏢 Sociétés cotées à la BRVM</h1>

    <form method="GET" class="mb-4 d-flex gap-2">
        <input type="text" name="q" value="{{ $q }}"
               class="form-control"
               placeholder="Rechercher une société (ex: AIR, SIVC, SONATEL)">
        <button class="btn btn-primary">Rechercher</button>
    </form>

    <div class="row g-3">
        @foreach($items as $s)
            @php
                $d = $dividendes[$s['ticker']] ?? null;
            @endphp

            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        {{-- Logo + Nom --}}
                        <div class="d-flex align-items-center gap-3 mb-2">
                            @if(!empty($s['logo']) && file_exists(public_path($s['logo'])))
                                <img src="{{ asset($s['logo']) }}"
                                     alt="{{ $s['name'] }}"
                                     class="border rounded p-1 bg-white"
                                     style="width:48px;height:48px;object-fit:contain;">
                            @else
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center"
                                     style="width:48px;height:48px;font-weight:700;">
                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                </div>
                            @endif

                            <div>
                                <div class="fw-semibold">{{ $s['name'] }}</div>
                                <div class="text-muted small">
                                    Ticker : {{ $s['ticker'] }}
                                </div>
                            </div>
                        </div>

                        {{-- Badges financiers --}}
                        <div class="mb-2 d-flex flex-wrap gap-1">
                            @if($d)
                                @if($d->rendement_net !== null)
                                    <span class="badge text-bg-success">
                                        💰 Rdt {{ number_format($d->rendement_net, 2, ',', ' ') }}%
                                    </span>
                                @endif

                                @if($d->per !== null)
                                    <span class="badge text-bg-secondary">
                                        PER {{ number_format($d->per, 2, ',', ' ') }}
                                    </span>
                                @endif
                            @else
                                <span class="badge text-bg-light border">
                                    Dividendes —
                                </span>
                            @endif
                        </div>

                        {{-- Description --}}
                        <p class="text-muted small mb-3">
                            {{ \Illuminate\Support\Str::limit($s['description'], 100) }}
                        </p>

                        {{-- Action --}}
                        <a href="{{ route('societes.show', $s['slug']) }}"
                           class="btn btn-sm btn-outline-primary">
                            Voir la présentation →
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
