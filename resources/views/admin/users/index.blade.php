@extends('layouts.admin')

@section('title', 'Utilisateurs – Admin Coach BRVM')

@section('content')
<div class="container py-5" style="max-width: 1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">👥 Utilisateurs</h2>
            <p class="text-muted mb-0">Liste des comptes (nom, email, date d’inscription).</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary fw-semibold">
                ← Retour dashboard
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total utilisateurs</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalUsers, 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Nouveaux (7 derniers jours)</div>
                    <div class="fs-3 fw-bold">{{ number_format($newLast7Days, 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <form class="row g-2 align-items-center mb-3" method="GET" action="{{ route('admin.users.index') }}">
                <div class="col-md-6">
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           class="form-control"
                           placeholder="Rechercher par nom ou email...">
                </div>

                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="created_at" @selected($sort==='created_at')>Tri : Date</option>
                        <option value="name" @selected($sort==='name')>Tri : Nom</option>
                        <option value="email" @selected($sort==='email')>Tri : Email</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="dir" class="form-select">
                        <option value="desc" @selected($dir==='desc')>Desc</option>
                        <option value="asc" @selected($dir==='asc')>Asc</option>
                    </select>
                </div>

                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary fw-semibold">OK</button>
                </div>
            </form>

            @if($users->isEmpty())
                <p class="text-muted mb-0">Aucun utilisateur trouvé.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="min-width:220px;">Nom</th>
                                <th style="min-width:260px;">Email</th>
                                <th style="min-width:200px;">Inscrit le</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td class="fw-semibold">{{ $u->name ?? '—' }}</td>
                                    <td>
                                        <span class="font-monospace">{{ $u->email }}</span>
                                    </td>
                                    <td>
                                        {{ optional($u->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
