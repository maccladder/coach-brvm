@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">
    <a href="{{ route('vendor.documents.index') }}" class="text-decoration-none small">← Retour</a>

    <div class="card border-0 shadow-sm mt-2">
        <div class="card-header bg-white">
            <div class="fw-semibold">➕ Ajouter un document</div>
            <div class="text-muted small">Il sera en attente de validation admin.</div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('vendor.documents.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Titre</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" required>
                    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="">—</option>
                            <option value="etude_marche" @selected(old('type')==='etude_marche')>Étude de marché</option>
                            <option value="business_plan" @selected(old('type')==='business_plan')>Business plan</option>
                            <option value="autre" @selected(old('type')==='autre')>Autre</option>
                        </select>
                        @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pays (optionnel)</label>
                        <input class="form-control" name="country" value="{{ old('country','Côte d’Ivoire') }}">
                        @error('country') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Prix (FCFA)</label>
                    <input type="number" class="form-control" name="price" value="{{ old('price', 2500) }}" min="0" required>
                    @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label class="form-label">Description (optionnel)</label>
                    <textarea class="form-control" rows="5" name="description">{{ old('description') }}</textarea>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label class="form-label">Fichier</label>
                    <input type="file" class="form-control" name="file" required>
                    @error('file') <div class="text-danger small">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">PDF recommandé.</div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-dark">
                        <i class="bi bi-cloud-upload"></i> Envoyer
                    </button>
                    <a class="btn btn-light" href="{{ route('vendor.documents.index') }}">Annuler</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
