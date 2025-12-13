@extends('layouts.admin')

@section('title', 'Dashboard Administrateur – Coach BRVM')

@section('content')
<div class="container py-5" style="max-width: 1200px;">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard Administrateur</h2>
            <p class="text-muted mb-0">
                Aperçu rapide des analyses effectuées par les utilisateurs.
            </p>
        </div>

        {{-- 👉 Bouton Analytics --}}
        <a href="{{ route('admin.analytics.index') }}"
           class="btn btn-outline-primary fw-semibold">
            📊 Analytics visiteurs
        </a>
    </div>

    {{-- 🔥 CARDS RAPIDES --}}
    <div class="row g-3 mb-4">

        {{-- Gestion BOC journalières --}}
        <div class="col-md-4">
            <a href="{{ route('admin.bocs.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 bg-primary text-white">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="fw-bold mb-1">📊 Gestion BOC journalières</h5>
                        <p class="small mb-0">
                            Suivi journalier des BOC : uploads, retards, fichiers manquants.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Performances --}}
        <div class="col-md-4">
            <a href="{{ route('admin.performances.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 bg-dark text-white position-relative overflow-hidden">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="fw-bold mb-0">📈 Performances</h5>
                            <span class="badge bg-warning text-dark fw-semibold">7 jours</span>
                        </div>
                        <p class="small mb-0">
                            Courbes des variations (%) par société sur les 7 derniers jours de BOC.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Total BOC --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-muted">BOC analysés</h6>
                    <h3 class="fw-bold">
                        {{ number_format($bocs->count(), 0, ',', ' ') }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    {{-- SECTION BOC --}}
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body">
            <h4 class="fw-semibold mb-3">Derniers BOC analysés</h4>

            @if($bocs->isEmpty())
                <p class="text-muted">Aucun BOC analysé pour l’instant.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Intitulé</th>
                                <th>Status</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($bocs as $boc)
                                <tr>
                                    <td>{{ $boc->title }}</td>
                                    <td>
                                        @switch($boc->status)
                                            @case('paid')
                                                <span class="badge bg-success">Payé</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">En attente</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">Échec</span>
                                                @break
                                            @case('abandoned')
                                                <span class="badge bg-secondary">Abandonné</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">Inconnu</span>
                                        @endswitch
                                    </td>
                                    <td>{{ number_format($boc->amount, 0, ',', ' ') }} FCFA</td>
                                    <td>{{ $boc->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('client-bocs.show', $boc->id) }}"
                                           class="btn btn-sm btn-primary">
                                            Voir l’analyse
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- SECTION ÉTATS FINANCIERS --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="fw-semibold mb-3">Derniers états financiers analysés</h4>

            @if($financials->isEmpty())
                <p class="text-muted">Aucun état financier analysé pour l’instant.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Intitulé</th>
                                <th>Entreprise</th>
                                <th>Période</th>
                                <th>Status</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($financials as $f)
                                <tr>
                                    <td>{{ $f->title }}</td>
                                    <td>{{ $f->company }}</td>
                                    <td>{{ $f->period }}</td>
                                    <td>
                                        @switch($f->status)
                                            @case('paid')
                                                <span class="badge bg-success">Payé</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">En attente</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">Échec</span>
                                                @break
                                            @case('abandoned')
                                                <span class="badge bg-secondary">Abandonné</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">Inconnu</span>
                                        @endswitch
                                    </td>
                                    <td>{{ number_format($f->amount, 0, ',', ' ') }} FCFA</td>
                                    <td>{{ $f->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('client-financials.show', $f->id) }}"
                                           class="btn btn-sm btn-primary">
                                            Voir l’analyse
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
