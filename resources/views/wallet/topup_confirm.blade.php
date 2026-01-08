@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 800px;">

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-2">✅ Confirmation du rechargement</h1>

            <div class="text-muted mb-3">
                Tu es sur le point de payer <strong>{{ number_format($payment->amount_paid, 0, ',', ' ') }} FCFA</strong>
                pour recevoir <strong>{{ number_format($payment->amount_virtual, 0, ',', ' ') }} FCFA</strong>
                dans ton portefeuille virtuel.
            </div>

            <div class="alert alert-info">
                Transaction : <strong>{{ $payment->transaction_id }}</strong>
            </div>

            <form method="POST" action="{{ route('wallet.topup.pay') }}">
                @csrf
                <input type="hidden" name="payment_id" value="{{ $payment->id }}">

                <div class="d-flex gap-2">
                    <a href="{{ route('wallet.index') }}" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                    <button class="btn btn-success fw-semibold">
                        Continuer vers paiement (CinetPay)
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
