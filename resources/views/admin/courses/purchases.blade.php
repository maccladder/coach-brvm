@extends('layouts.admin')

@section('title', 'Achats formations')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">💳 Achats de formations</h2>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Utilisateur</th>
                    <th>Cours</th>
                    <th>Montant</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->user->name }}<br>
                            <small class="text-muted">{{ $purchase->user->email }}</small>
                        </td>
                        <td>{{ $purchase->course->title }}</td>
                        <td class="fw-semibold">
                            {{ number_format($purchase->amount_fcfa) }} FCFA
                        </td>
                        <td>{{ $purchase->paid_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $purchases->links() }}

</div>
@endsection
