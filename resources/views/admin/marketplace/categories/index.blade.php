@extends('layouts.admin')

@section('title', 'Marketplace – Catégories')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">📁 Marketplace – Catégories</h2>
            <p class="text-muted mb-0">Organise tes produits (livres, vidéos, logiciels).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary">🛒 Produits</a>
            <a href="{{ route('admin.marketplace-categories.create') }}" class="btn btn-primary">➕ Nouvelle catégorie</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">Erreurs :</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Slug</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $c)
                            <tr>
                                <td class="fw-semibold">{{ $c->name }}</td>
                                <td class="text-muted">{{ $c->slug }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.marketplace-categories.edit', $c) }}"
                                       class="btn btn-sm btn-outline-primary">Modifier</a>

                                    @if(session('is_admin'))
                                    <form action="{{ route('admin.marketplace-categories.destroy', $c) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer cette catégorie ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Aucune catégorie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
