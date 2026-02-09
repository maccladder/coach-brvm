@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:700px;">
    <h3 class="mb-3">Résultat Paystack</h3>

    <div class="card p-3 shadow-sm">
        <div><strong>Reference:</strong> {{ $reference }}</div>
        <div class="mt-2">
            <strong>Statut:</strong>
            @if($result === 'ACCEPTED')
                <span class="badge bg-success">ACCEPTED</span>
            @elseif($result === 'REFUSED')
                <span class="badge bg-danger">REFUSED</span>
            @elseif($result === 'PENDING')
                <span class="badge bg-warning text-dark">PENDING</span>
            @else
                <span class="badge bg-secondary">UNKNOWN</span>
            @endif
        </div>

        <a href="{{ route('paystack.test') }}" class="btn btn-outline-secondary mt-3">Re-tester</a>
    </div>
</div>
@endsection
