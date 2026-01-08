@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">Récapitulatif achat</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card p-4">
        <div class="row g-2">
            <div class="col-md-6"><strong>Ticker</strong></div>
            <div class="col-md-6">{{ $ticker }}</div>

            <div class="col-md-6"><strong>Quantité</strong></div>
            <div class="col-md-6">{{ $qty }}</div>

            <div class="col-md-6"><strong>Prix du jour</strong></div>
            <div class="col-md-6">{{ number_format($price, 0, ',', ' ') }} FCFA</div>

            <hr class="my-3">

            <div class="col-md-6"><strong>Montant brut</strong></div>
            <div class="col-md-6">{{ number_format($grossAmount, 0, ',', ' ') }} FCFA</div>

            <div class="col-md-6"><strong>Frais SGI</strong></div>
            <div class="col-md-6">
                {{ number_format($fee, 0, ',', ' ') }} FCFA
                <span class="text-muted small">(taux {{ $rate * 100 }}%, min {{ number_format($min, 0, ',', ' ') }})</span>
            </div>

            <div class="col-md-6"><strong>Total à débiter</strong></div>
            <div class="col-md-6 fw-bold">{{ number_format($total, 0, ',', ' ') }} FCFA</div>
        </div>

        <form method="POST" action="{{ route('wallet.buy') }}" class="mt-4">
            @csrf
            <input type="hidden" name="ticker" value="{{ $ticker }}">
            <input type="hidden" name="qty" value="{{ $qty }}">

            <button type="submit" class="btn btn-success w-100">
                ✅ Confirmer l’achat
            </button>
        </form>

        <a href="{{ route('wallet.index') }}" class="btn btn-outline-secondary w-100 mt-2">
            ↩️ Retour
        </a>
    </div>
</div>
@endsection
