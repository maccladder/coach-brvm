@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">🛍️ Tableau de bord vendeur</h3>
            <div class="text-muted">Crée des produits, soumets-les, puis l’admin valide.</div>
        </div>
        <a class="btn btn-dark" href="{{ route('vendor.products.create') }}">
            <i class="bi bi-plus-circle"></i> Nouveau produit
        </a>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label'=>'Brouillons','val'=>(int)($stats->drafts ?? 0)],
                ['label'=>'En attente','val'=>(int)($stats->pendings ?? 0)],
                ['label'=>'Publiés','val'=>(int)($stats->published ?? 0)],
                ['label'=>'Rejetés','val'=>(int)($stats->rejected ?? 0)],
            ];
        @endphp
        @foreach($cards as $c)
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">{{ $c['label'] }}</div>
                    <div class="fs-4 fw-bold">{{ $c['val'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="fw-semibold">Derniers produits</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($latest as $p)
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
                        <td>{{ number_format((float)$p->price, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ $p->status }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('vendor.products.edit', $p) }}">
                                Gérer
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Aucun produit pour l’instant.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body border-top bg-white">
            <a href="{{ route('vendor.products.index') }}">Voir tous mes produits →</a>
        </div>
    </div>
</div>
@endsection
