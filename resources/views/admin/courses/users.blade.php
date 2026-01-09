@extends('layouts.admin')

@section('title', 'Utilisateurs acheteurs – Admin')

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <h2 class="fw-bold mb-4">👥 Utilisateurs acheteurs</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Formations achetées</th>
                            <th>Total payé</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="fw-semibold">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $u->courses_count }}
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    {{ number_format($u->total_spent, 0, ',', ' ') }} FCFA
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    Aucun utilisateur acheteur.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@endsection
