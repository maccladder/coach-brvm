@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">États financiers analysés</h2>
        <a href="{{ route('client-financials.create') }}" class="btn btn-primary">
            + Analyser de nouveaux états financiers
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($financials->isEmpty())
        <div class="alert alert-info">
            Aucun état financier pour le moment. Commence par en analyser un !
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Intitulé</th>
                            <th>Entreprise</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($financials as $f)
                            <tr>
                                <td>{{ $f->title }}</td>
                                <td>{{ $f->company }}</td>
                                <td>{{ $f->period }}</td>
                                <td>
                                    @if($f->status === 'paid')
                                        <span class="badge bg-success">Payé</span>
                                    @elseif($f->status === 'pending')
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($f->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('client-financials.show', $f) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Voir l’analyse
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
