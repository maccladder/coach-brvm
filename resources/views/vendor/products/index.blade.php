@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">📦 Mes produits</h3>
            <div class="text-muted">Brouillon → Soumettre → Admin valide → Marketplace.</div>
        </div>
        <a class="btn btn-dark" href="{{ route('vendor.products.create') }}">
            <i class="bi bi-plus-circle"></i> Nouveau produit
        </a>
    </div>

    <form class="d-flex gap-2 mb-3" method="GET">
        <select class="form-select" name="status" style="max-width:240px;">
            <option value="">Tous les statuts</option>
            @foreach(['draft','pending','published','rejected'] as $st)
                <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary">Filtrer</button>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    @php
                        $badge = match($p->status) {
                            'draft' => 'secondary',
                            'pending' => 'warning',
                            'published' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $p->title }}</td>
                        <td class="text-muted">{{ $p->category?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $p->type }}</td>
                        <td>{{ number_format((float)$p->price, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $p->status }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('vendor.products.edit', $p) }}">
                                Gérer
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun produit.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $products->links() }}
    </div>
</div>
@endsection
