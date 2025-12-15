@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1000px;">

    <a href="{{ route('societes.index') }}" class="small text-decoration-none">
        ← Retour aux sociétés
    </a>

    <h1 class="fw-bold mt-3 mb-1">🏆 Classement des dividendes {{ $year }}</h1>
    <p class="text-muted mb-4">
        {{ $total }} sociétés ont versé un dividende
        <span class="d-block small">
            Classement par montant net (FCFA)
        </span>
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            @forelse($dividendes as $d)
                <div class="mb-4">

                    {{-- Ligne titre --}}
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <div class="fw-semibold">
                            <span class="badge bg-light text-dark border me-1">
                                #{{ $d->rank }}
                            </span>

                            {{ $d->societe }}
                            <span class="text-muted small">({{ $d->ticker }})</span>
                        </div>

                        <div class="fw-bold">
                            {{ number_format($d->dividende_net, 2, ',', ' ') }} FCFA
                        </div>
                    </div>

                    {{-- Barre de progression --}}
                    <div class="progress" style="height:10px;">
                        <div class="progress-bar bg-success"
                             role="progressbar"
                             style="width: {{ $d->bar_percent }}%"
                             aria-valuenow="{{ $d->bar_percent }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>

                </div>
            @empty
                <div class="alert alert-light border mb-0">
                    ℹ️ Aucun dividende enregistré pour l’année {{ $year }}.
                </div>
            @endforelse

        </div>
    </div>

    <div class="text-muted small mt-3">
        Source : Bulletins Officiels de Cote (BOC) – année {{ $year }}
    </div>

</div>
@endsection
