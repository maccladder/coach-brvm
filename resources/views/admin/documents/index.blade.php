@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Documents (Études & Business plans)</h3>
        <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">
            + Ajouter
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $doc->title }}</div>
                                <small class="text-muted">{{ $doc->slug }}</small>
                            </td>
                            <td>{{ $doc->type }}</td>
                            <td>{{ number_format($doc->price, 0, ',', ' ') }} FCFA</td>
                            <td>
                                @if($doc->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.documents.edit', $doc) }}" class="btn btn-sm btn-outline-primary">
                                    Modifier
                                </a>

                                <form action="{{ route('admin.documents.toggle', $doc) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-warning">
                                        {{ $doc->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>

                                @if(session('is_admin'))
                                <form action="{{ route('admin.documents.destroy', $doc) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Supprimer ce document ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        Supprimer
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Aucun document</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
