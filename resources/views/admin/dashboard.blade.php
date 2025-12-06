@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Dashboard Admin</h2>

        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button class="btn btn-outline-danger">Se déconnecter</button>
        </form>
    </div>

    {{-- Section BOC --}}
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body">
            <h4 class="mb-3">Derniers BOC analysés</h4>

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
                                        <span class="badge bg-success">Payé</span>
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

    {{-- Section États financiers --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-3">Derniers états financiers analysés</h4>

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
                                        <span class="badge bg-success">Payé</span>
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
