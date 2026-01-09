@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">📉 Récapitulatif de vente</h1>
            <div class="text-muted small">Vérifie les montants avant de confirmer.</div>
        </div>
        <a href="{{ route('wallet.index') }}" class="btn btn-outline-secondary">⬅️ Retour</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Ticker</div>
                    <div class="fs-5 fw-semibold">{{ $ticker }}</div>
                    <div class="text-muted small">{{ $name }}</div>
                </div>

                <div class="col-md-6 text-md-end">
                    <div class="text-muted small">Quantité détenue</div>
                    <div class="fs-6 fw-semibold">{{ number_format($ownedQty, 0, ',', ' ') }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Quantité à vendre</div>
                    <div class="fs-5 fw-bold">{{ number_format($qty, 0, ',', ' ') }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Cours (close)</div>
                    <div class="fs-5 fw-bold">{{ number_format($price, 0, ',', ' ') }} FCFA</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Montant brut</div>
                    <div class="fs-5 fw-bold">{{ number_format($grossAmount, 0, ',', ' ') }} FCFA</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Frais SGI</div>
                    <div class="fw-semibold">
                        {{ number_format($fee, 0, ',', ' ') }} FCFA
                        <span class="text-muted small">(taux {{ $rate * 100 }}% / min {{ number_format($min, 0, ',', ' ') }})</span>
                    </div>
                </div>

                <div class="col-md-6 text-md-end">
                    <div class="text-muted small">Montant net crédité</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ number_format($net, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <form method="POST" action="{{ route('wallet.sell') }}" class="d-flex justify-content-end gap-2">
                @csrf
                <input type="hidden" name="ticker" value="{{ $ticker }}">
                <input type="hidden" name="qty" value="{{ $qty }}">
                <button class="btn btn-danger fw-semibold">
                    Confirmer la vente
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
