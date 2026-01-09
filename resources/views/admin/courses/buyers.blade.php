@extends('layouts.admin')

@section('title', 'Utilisateurs acheteurs')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">👥 Utilisateurs acheteurs</h2>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Formations achetées</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-success">
                                {{ $user->purchases_count }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

</div>
@endsection
