@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    <a href="{{ route('societes.index') }}" class="small text-decoration-none">
        ← Retour aux sociétés
    </a>

    {{-- Header société --}}
    <div class="d-flex align-items-center gap-3 mt-3 mb-4">
        @if(!empty($societe['logo']) && file_exists(public_path($societe['logo'])))
            <img src="{{ asset($societe['logo']) }}"
                 alt="{{ $societe['name'] }}"
                 class="border rounded p-2 bg-white shadow-sm"
                 style="width:72px;height:72px;object-fit:contain;">
        @else
            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center shadow-sm"
                 style="width:72px;height:72px;font-size:1.5rem;font-weight:700;">
                {{ strtoupper(substr($societe['name'], 0, 1)) }}
            </div>
        @endif

        <div>
            <h1 class="fw-bold mb-1">{{ $societe['name'] }}</h1>
            <div class="text-muted">
                Ticker : <strong>{{ $societe['ticker'] }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Présentation --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-2">📌 Présentation</h5>
                    <p class="text-muted mb-0">
                        {{ $societe['description'] }}
                    </p>
                </div>
            </div>

            {{-- ✅ Bloc Dividendes --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">💰 Dividendes & Valorisation</h5>

                    @if($dividende)
                        <div class="row small">
                            <div class="col-6 mb-3">
                                <div class="text-muted">Montant du dernier dividende (net)</div>
                                <div class="fw-bold">
                                    {{ $dividende->dividende_net !== null
                                        ? number_format((float)$dividende->dividende_net, 2, ',', ' ') . ' FCFA'
                                        : '—' }}
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">Date du dernier paiement</div>
                                <div class="fw-bold">
                                    {{ $dividende->date_paiement
                                        ? \Carbon\Carbon::parse($dividende->date_paiement)->format('d/m/Y')
                                        : '—' }}
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">Rendement net</div>
                                <div class="fw-bold text-success">
                                    {{ $dividende->rendement_net !== null
                                        ? number_format((float)$dividende->rendement_net, 2, ',', ' ') . ' %'
                                        : '—' }}
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">PER</div>
                                <div class="fw-bold">
                                    {{ $dividende->per !== null
                                        ? number_format((float)$dividende->per, 2, ',', ' ')
                                        : '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="text-muted small mt-2">
                            Source : {{ $dividende->boc_date_reference
                                ? 'BOC du ' . \Carbon\Carbon::parse($dividende->boc_date_reference)->format('d/m/Y')
                                : 'BOC' }}
                        </div>
                    @else
                        <div class="alert alert-light border small mb-0">
                            ℹ️ Infos dividendes non disponibles pour le moment pour ce titre.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Informations --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">ℹ️ Informations</h6>

                    <div class="small">
                        <div class="text-muted">Téléphone</div>
                        <div class="fw-semibold mb-3">
                            {{ $societe['telephone'] ?? '—' }}
                        </div>

                        <div class="text-muted">Adresse</div>
                        <div class="fw-semibold mb-3">
                            {{ $societe['adresse'] ?? '—' }}
                        </div>

                        <div class="text-muted mb-1">Dirigeants</div>
                        @if(!empty($societe['dirigeants']))
                            <ul class="mb-0 ps-3">
                                @foreach($societe['dirigeants'] as $role => $name)
                                    <li>
                                        <strong>{{ $role }}</strong> : {{ $name }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="fw-semibold">—</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
