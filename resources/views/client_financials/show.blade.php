@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 1100px;">

    {{-- Titre haut de page --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">{{ $financial->company }}</h3>
            <div class="text-muted small">
                Exercice {{ $financial->period }}
                @if($financial->financial_date)
                    • États arrêtés au
                    {{ \Illuminate\Support\Carbon::parse($financial->financial_date)->format('d/m/Y') }}
                @endif
            </div>
        </div>

        <span
            class="badge rounded-pill px-3 py-2 text-uppercase
                   bg-{{ $financial->status === 'paid' ? 'success' : 'secondary' }}">
            {{ strtoupper($financial->status) }}
        </span>
    </div>

    <div class="row g-4">
        {{-- Colonne gauche : avatar + audio --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex flex-column">

                    {{-- Avatar vidéo --}}
                    @if($financial->avatar_video_url)
                        <h6 class="mb-3 text-uppercase text-muted small">Ton coach BRVM</h6>
                        <div class="mb-4">
                            <video
                                src="{{ $financial->avatar_video_url }}"
                                controls
                                style="width:100%; border-radius:1rem;">
                            </video>
                        </div>
                    @endif

                    {{-- Audio --}}
                    <h6 class="mb-3 text-uppercase text-muted small">Écouter l’analyse</h6>

                    @if($audioPath)
                        <audio controls style="width:100%;" class="mb-2">
                            {{-- 🔥 IMPORTANT : audio fixé ici --}}
                            <source src="{{ asset('storage/' . $audioPath) }}" type="audio/mpeg">
                            Ton navigateur ne supporte pas l’audio.
                        </audio>

                        <p class="text-muted small mb-0">
                            Lance l’audio pour que le coach te lise le décryptage complet.
                        </p>
                    @else
                        <p class="text-muted small mb-0">
                            La version audio sera disponible dès que le traitement sera terminé.
                        </p>
                    @endif

                </div>
            </div>
        </div>

        {{-- Colonne droite : analyse markdown --}}
        <div class="col-lg-8">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <h4 class="mb-3">{{ $financial->title }}</h4>

                    @if($financial->interpreted_markdown)
                        <div class="markdown-body" style="line-height:1.6;">
                            {!! \Illuminate\Support\Str::markdown($financial->interpreted_markdown) !!}
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            L’analyse est encore en cours de génération.
                            Recharge cette page dans quelques instants si le résultat n’apparaît pas.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Détails en bas sur toute la largeur --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-3 text-uppercase text-muted small">Détails de l’analyse</h6>

                    <div class="row gy-2">
                        <div class="col-md-3 col-sm-6">
                            <div class="text-muted small">Société</div>
                            <div class="fw-semibold">{{ $financial->company }}</div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="text-muted small">Période</div>
                            <div class="fw-semibold">{{ $financial->period }}</div>
                        </div>

                        @if($financial->financial_date)
                            <div class="col-md-3 col-sm-6">
                                <div class="text-muted small">Date des états</div>
                                <div class="fw-semibold">
                                    {{ \Illuminate\Support\Carbon::parse($financial->financial_date)->format('d/m/Y') }}
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3 col-sm-6">
                            <div class="text-muted small">Montant payé</div>
                            <div class="fw-semibold">
                                {{ number_format($financial->amount, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
