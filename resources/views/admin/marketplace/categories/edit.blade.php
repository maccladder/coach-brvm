@extends('layouts.admin')

@section('title', 'Modifier une catégorie')

@section('content')
<div class="container py-5" style="max-width:900px;">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">✏️ Modifier catégorie</h2>
        <p class="text-muted mb-0">Slug actuel : <b>{{ $category->slug }}</b> (on ne le change pas).</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.marketplace-categories.update', $category) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label text-muted">Nom</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.marketplace-categories.index') }}" class="btn btn-outline-secondary">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
