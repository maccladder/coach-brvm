@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:700px;">
    <h3 class="mb-3">Test Paystack (Redirect)</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('paystack.start') }}" class="card p-3 shadow-sm">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email','test@email.com') }}" required>
            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Montant (FCFA)</label>
            <input name="amount" type="number" class="form-control" value="{{ old('amount',500) }}" min="100" required>
            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <button class="btn btn-success w-100">
            Payer avec Paystack (test)
        </button>
    </form>
</div>
@endsection
