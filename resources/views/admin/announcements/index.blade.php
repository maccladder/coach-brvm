@extends('layouts.admin')

@section('title', 'Annonces')

@section('content')
<div class="container py-5" style="max-width:1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Annonces</h2>
            <div class="text-muted">Gérer les annonces BRVM affichées au public.</div>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">+ Nouvelle annonce</a>
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
                        <th>Statut</th>
                        <th>Publication</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $a)
                        <tr>
                            <td class="fw-semibold">{{ $a->title }}</td>
                            <td>
                                @if($a->is_published)
                                    <span class="badge bg-success">Publié</span>
                                @else
                                    <span class="badge bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ $a->published_at ? $a->published_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.announcements.edit', $a->id) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <form action="{{ route('admin.announcements.destroy', $a->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette annonce ?')">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-4">Aucune annonce.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $announcements->links() }}
    </div>
</div>
@endsection
