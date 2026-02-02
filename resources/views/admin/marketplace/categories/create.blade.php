@extends('layouts.admin')

@section('title', 'Créer une catégorie')

@section('content')
<div class="container py-5" style="max-width:900px;">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">➕ Nouvelle catégorie</h2>
        <p class="text-muted mb-0">Ex : Livres PDF, Vidéos, Logiciels, etc.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.marketplace-categories.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label text-muted">Nom</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="Ex: Livres PDF">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Créer</button>
                    <a href="{{ route('admin.marketplace-categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
