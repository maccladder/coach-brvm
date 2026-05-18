@extends('layouts.admin')

@section('title', 'Portefeuilles – Admin')

@section('content')
<div class="container py-4" style="max-width:1200px">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0">Portefeuilles virtuels</h4>
            <div class="text-muted small">{{ $users->total() }} utilisateur(s) avec un portefeuille</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Recherche --}}
    <form method="GET" action="{{ route('admin.wallets.index') }}" class="mb-3">
        <div class="input-group" style="max-width:400px">
            <input type="text" name="search" class="form-control" placeholder="Rechercher nom ou email…" value="{{ $search }}">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if($search)
                <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-danger">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Utilisateur</th>
                        <th class="text-end">Solde cash</th>
                        <th class="text-center">Positions</th>
                        <th>Dernière recharge</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($user->wallet_balance, 0, ',', ' ') }}
                                <span class="text-muted fw-normal small">FCFA</span>
                            </td>
                            <td class="text-center">
                                @if($user->virtual_positions_count > 0)
                                    <span class="badge bg-primary rounded-pill">{{ $user->virtual_positions_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                @if(isset($lastTopups[$user->id]))
                                    {{ \Carbon\Carbon::parse($lastTopups[$user->id])->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">Aucune</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.wallets.show', $user) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-wallet2 me-1"></i>Gérer
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucun portefeuille trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>

</div>
@endsection
