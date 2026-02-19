@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">
    <a href="{{ route('vendor.documents.index') }}" class="text-decoration-none small">← Retour</a>

    <div class="card border-0 shadow-sm mt-2">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-semibold">✏️ Gérer le document</div>
                <div class="text-muted small">
                    Statut:
                    <span class="badge text-bg-{{ $document->status==='published'?'success':($document->status==='pending'?'warning':'danger') }}">
                        {{ $document->status }}
                    </span>
                </div>
                @if($document->status==='rejected' && $document->rejected_note)
                    <div class="small text-danger mt-1">Motif : {{ $document->rejected_note }}</div>
                @endif
            </div>

            <form method="POST" action="{{ route('vendor.documents.destroy', $document) }}"
                  onsubmit="return confirm('Supprimer ce document ?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('vendor.documents.update', $document) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Titre</label>
                    <input class="form-control" name="title" value="{{ old('title', $document->title) }}" required>
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="etude_marche" @selected(old('type', $document->type)==='etude_marche')>Étude de marché</option>
                            <option value="business_plan" @selected(old('type', $document->type)==='business_plan')>Business plan</option>
                            <option value="autre" @selected(old('type', $document->type)==='autre')>Autre</option>
                        </select>
                        @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pays</label>
                        <input class="form-control" name="country" value="{{ old('country', $document->country) }}">
                        @error('country') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Prix (FCFA)</label>
                    <input type="number" class="form-control" name="price" value="{{ old('price', (int)$document->price) }}" min="0" required>
                    @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="5" name="description">{{ old('description', $document->description) }}</textarea>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label class="form-label">Remplacer le fichier (optionnel)</label>
                    <input type="file" class="form-control" name="file">
                    @error('file') <div class="text-danger small">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">
                        Fichier actuel : <code>{{ $document->file_path }}</code>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-dark">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>

                    {{-- Optionnel --}}
                    <form class="d-inline" method="POST" action="{{ route('vendor.documents.submit', $document) }}">
                        @csrf
                        <button class="btn btn-outline-warning" type="submit">
                            <i class="bi bi-send"></i> Soumettre
                        </button>
                    </form>
                </div>

                <div class="text-muted small mt-2">
                    ⚠️ Si le document était rejeté, une modification le remet automatiquement en “pending”.
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
