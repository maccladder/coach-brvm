@extends('layouts.admin')

@section('title', 'Actualités')

@section('content')
<div class="container py-5" style="max-width:1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Actualités</h2>
            <div class="text-muted">News BRVM collectées par l'agent IA + saisies manuelles. Brouillon par défaut, à publier après validation.</div>
        </div>
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary">+ Nouvelle actualité</a>
    </div>

    <div class="btn-group mb-3">
        <a href="{{ route('admin.news.index') }}" class="btn btn-sm btn-outline-secondary {{ !$status ? 'active' : '' }}">Toutes</a>
        <a href="{{ route('admin.news.index', ['status' => 'draft']) }}" class="btn btn-sm btn-outline-secondary {{ $status === 'draft' ? 'active' : '' }}">Brouillons</a>
        <a href="{{ route('admin.news.index', ['status' => 'published']) }}" class="btn btn-sm btn-outline-secondary {{ $status === 'published' ? 'active' : '' }}">Publiées</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Impact</th>
                        <th>Statut</th>
                        <th>Publication</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($newsList as $n)
                        <tr>
                            <td class="fw-semibold">{{ $n->title }}</td>
                            <td class="text-muted">{{ $n->categorie ?? '—' }}</td>
                            <td class="text-muted">{{ $n->impact ?? '—' }}</td>
                            <td>
                                @if($n->is_published)
                                    <span class="badge bg-success">Publié</span>
                                @else
                                    <span class="badge bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ $n->published_at ? $n->published_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.news.toggle', $n->slug) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success">
                                        {{ $n->is_published ? 'Repasser en brouillon' : 'Publier' }}
                                    </button>
                                </form>
                                <a href="{{ route('admin.news.edit', $n->slug) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                @if(session('is_admin'))
                                <form action="{{ route('admin.news.destroy', $n->slug) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette actualité ?')">Supprimer</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted p-4">Aucune actualité.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $newsList->links() }}
    </div>
</div>
@endsection
