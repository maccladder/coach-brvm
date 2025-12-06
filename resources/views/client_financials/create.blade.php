@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="mb-3">Analyser des états financiers</h2>
            <p class="text-muted">
                Uploade les états financiers de ton entreprise. Après le paiement,
                l’IA ressortira les points clés : chiffre d’affaires, bénéfice net,
                capacité d’autofinancement, dettes et trésorerie.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('client-financials.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Titre (optionnel)</label>
                    <input type="text" name="title" class="form-control"
                           placeholder="États financiers 2024 – SONATEL"
                           value="{{ old('title') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Entreprise *</label>
                    <input type="text" name="company" class="form-control"
                           placeholder="Nom de la société"
                           value="{{ old('company') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Période / Exercice *</label>
                    <input type="text" name="period" class="form-control"
                           placeholder="Ex : Exercice 2024"
                           value="{{ old('period') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date des états financiers (optionnel)</label>
                    <input type="date" name="financial_date" class="form-control"
                           value="{{ old('financial_date') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label">Fichier des états financiers (PDF, Excel, etc.) *</label>
                    <input type="file" name="file" class="form-control" required>
                    <div class="form-text">
                        Taille max 10 Mo.
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Montant du test : {{ env('CINETPAY_TEST_AMOUNT', 1000) }} FCFA
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Payer et lancer l’analyse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
