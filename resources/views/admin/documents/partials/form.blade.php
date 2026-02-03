@php
    $value = fn($key, $default='') => old($key, $document?->$key ?? $default);
@endphp

<div class="mb-3">
    <label class="form-label">Titre</label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ $value('title') }}" required>
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
        <option value="">-- Choisir --</option>
        @foreach($types as $k => $label)
            <option value="{{ $k }}" @selected($value('type') === $k)>{{ $label }}</option>
        @endforeach
    </select>
    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Secteur ID (optionnel)</label>
        <input type="number" name="sector_id" class="form-control @error('sector_id') is-invalid @enderror"
               value="{{ $value('sector_id') }}">
        @error('sector_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Pays (optionnel)</label>
        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
               value="{{ $value('country') }}" placeholder="Côte d'Ivoire">
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Prix (FCFA)</label>
        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
               value="{{ $value('price', 0) }}" min="0" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Description (optionnel)</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ $value('description') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">PDF {{ $document ? '(laisser vide si inchangé)' : '' }}</label>
    <input type="file" name="pdf" class="form-control @error('pdf') is-invalid @enderror" {{ $document ? '' : 'required' }} accept="application/pdf">
    @error('pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror

    @if($document)
        <small class="text-muted d-block mt-1">Fichier actuel : {{ $document->file_path }}</small>
    @endif
</div>

<div class="form-check form-switch mb-4">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
           @checked((bool)($value('is_active', true)))>
    <label class="form-check-label" for="is_active">Actif</label>
</div>
