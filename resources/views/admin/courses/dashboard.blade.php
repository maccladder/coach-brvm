@extends('layouts.admin')

@section('title', 'Dashboard Formations – Admin')

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">🎓 Dashboard Formations</h2>
        <p class="text-muted mb-0">
            Suivi global des ventes et des utilisateurs.
        </p>
    </div>

    {{-- CARTES STATS --}}
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Cours disponibles</h6>
                    <h3 class="fw-bold">{{ $stats['courses'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Achats validés</h6>
                    <h3 class="fw-bold text-success">{{ $stats['purchases'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Utilisateurs acheteurs</h6>
                    <h3 class="fw-bold">{{ $stats['buyers'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-dark text-white">
                <div class="card-body">
                    <h6 class="opacity-75">Revenus totaux</h6>
                    <h3 class="fw-bold">
                        {{ number_format($stats['revenue'], 0, ',', ' ') }} FCFA
                    </h3>
                </div>
            </div>
        </div>

    </div>

    {{-- RACCOURCIS --}}
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.courses.purchases') }}" class="btn btn-outline-primary">
            🧾 Voir les achats
        </a>
        <a href="{{ route('admin.courses.buyers') }}" class="btn btn-outline-secondary">
            👥 Utilisateurs acheteurs
        </a>
        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-success">
            🎥 Gérer les cours
        </a>
    </div>

</div>
@endsection
