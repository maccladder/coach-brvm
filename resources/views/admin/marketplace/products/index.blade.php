@extends('layouts.admin')

@section('title', 'Marketplace – Produits')

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">🛒 Marketplace – Produits</h2>
            <p class="text-muted mb-0">Gère les livres, vidéos et logiciels vendus sur la plateforme.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketplace-categories.index') }}" class="btn btn-outline-secondary">
                📁 Catégories
            </a>
            <a href="{{ route('admin.marketplace.create') }}" class="btn btn-primary">
                ➕ Nouveau produit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Total</div>
                    <div class="h3 fw-bold mb-0">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Publiés</div>
                    <div class="h3 fw-bold text-success mb-0">{{ $stats['published'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">En attente</div>
                    <div class="h3 fw-bold text-warning mb-0">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Rejetés</div>
                    <div class="h3 fw-bold text-danger mb-0">{{ $stats['rejected'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTRES --}}
    <form class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted">Recherche</label>
                    <input type="text" name="s" value="{{ request('s') }}" class="form-control" placeholder="Titre produit...">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-muted">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Tous</option>
                        @foreach(['video'=>'Vidéo','book'=>'Livre','software'=>'Logiciel'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-muted">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        @foreach(['published'=>'Publié','draft'=>'Brouillon','pending'=>'En attente','rejected'=>'Rejeté'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th>Type</th>
                            <th>Catégorie</th>
                            <th class="text-end">Prix</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $p)
                        @php
                            $badge = match($p->status) {
                                'published' => 'success',
                                'draft'     => 'secondary',
                                'pending'   => 'warning',
                                'rejected'  => 'danger',
                                default     => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $p->title }}</div>
                                <div class="text-muted small">slug: {{ $p->slug }}</div>
                                @if($p->admin_note)
                                    <div class="small text-danger">Motif: {{ $p->admin_note }}</div>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ ['video'=>'Vidéo','book'=>'Livre','software'=>'Logiciel'][$p->type] ?? $p->type }}
                            </td>
                            <td class="text-muted">
                                {{ $p->category->name ?? '—' }}
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($p->price ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                            <td>
                                <span class="badge bg-{{ $badge }}">{{ $p->status }}</span>
                                @if($p->is_featured)
                                    <span class="badge bg-warning text-dark ms-1">featured</span>
                                @endif
                                @if($p->affiliate_eligible && is_null($p->user_id))
                                    <span class="badge bg-success ms-1" title="Éligible affiliation">🤝</span>
                                @elseif($p->affiliate_eligible && !is_null($p->user_id))
                                    <span class="badge bg-secondary ms-1" title="Flag actif mais vendeur tiers — commission bloquée par garde-fou ownership">🤝⚠️</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{-- ✅ Inspecter --}}
                                <a href="{{ route('admin.marketplace.show', $p) }}" class="btn btn-sm btn-outline-dark">
                                    Inspecter
                                </a>

                                {{-- Approve/Reject rapides --}}
                                @if($p->status === 'pending')
                                    <form action="{{ route('admin.marketplace.approve', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success">✅</button>
                                    </form>

                                    <a href="{{ route('admin.marketplace.show', $p) }}" class="btn btn-sm btn-outline-danger">
                                        ⛔
                                    </a>
                                @endif

                                <a href="{{ route('admin.marketplace.edit', $p) }}" class="btn btn-sm btn-outline-primary">
                                    Modifier
                                </a>

                                {{-- Toggle éligibilité affiliation --}}
                                <form action="{{ route('admin.affiliates.toggle-product', $p) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $p->affiliate_eligible ? 'btn-success' : 'btn-outline-secondary' }}"
                                            title="{{ $p->affiliate_eligible ? 'Éligible affiliation — cliquer pour désactiver' : 'Non éligible — cliquer pour activer' }}">
                                        🤝
                                    </button>
                                </form>

                                @if(session('is_admin'))
                                <form action="{{ route('admin.marketplace.destroy', $p) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Supprimer ce produit ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun produit.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
