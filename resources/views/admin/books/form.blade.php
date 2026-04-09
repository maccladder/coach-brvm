@extends('layouts.admin')

@section('title', isset($book->id) ? 'Modifier le livre – Admin' : 'Nouveau livre – Admin')

@section('content')
<div class="container py-4" style="max-width:760px;">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.books.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="fw-bold mb-0">
            {{ isset($book->id) ? 'Modifier : ' . $book->title : 'Nouveau livre / étude de marché' }}
        </h5>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius:.65rem;">
        <div class="card-body p-4">
            <form method="POST"
                  action="{{ isset($book->id) ? route('admin.books.update', $book) : route('admin.books.store') }}">
                @csrf
                @if(isset($book->id)) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $book->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" rows="4"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Résumé, objectifs, public cible…">{{ old('description', $book->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Temps de lecture (min)</label>
                        <input type="number" name="estimated_minutes" min="1"
                               class="form-control @error('estimated_minutes') is-invalid @enderror"
                               value="{{ old('estimated_minutes', $book->estimated_minutes ?? 5) }}">
                        @error('estimated_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-1">
                            <input class="form-check-input" type="checkbox" name="is_free"
                                   id="is_free" value="1"
                                   {{ old('is_free', $book->is_free ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_free">Gratuit</label>
                        </div>
                    </div>
                    <div class="col-sm-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-1">
                            <input class="form-check-input" type="checkbox" name="is_published"
                                   id="is_published" value="1"
                                   {{ old('is_published', $book->is_published ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_published">Publié</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>{{ isset($book->id) ? 'Enregistrer' : 'Créer le livre' }}
                    </button>
                    <a href="{{ route('admin.books.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
