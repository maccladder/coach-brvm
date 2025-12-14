@php
  $a = $announcement;
@endphp

<div class="mb-3">
    <label class="form-label fw-semibold">Titre</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $a->title ?? '') }}" required>
    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Extrait (optionnel)</label>
    <input type="text" name="excerpt" class="form-control" maxlength="500" value="{{ old('excerpt', $a->excerpt ?? '') }}">
    @error('excerpt') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Contenu</label>
    <textarea name="content" rows="8" class="form-control">{{ old('content', $a->content ?? '') }}</textarea>
    @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Publié ?</label>
        <select name="is_published" class="form-select">
            <option value="1" {{ old('is_published', $a->is_published ?? true) ? 'selected' : '' }}>Oui</option>
            <option value="0" {{ !old('is_published', $a->is_published ?? true) ? 'selected' : '' }}>Non</option>
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label fw-semibold">Date/heure de publication (optionnel)</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', isset($a->published_at) ? $a->published_at->format('Y-m-d\TH:i') : '') }}">
        <div class="text-muted small">Si vide: l’annonce est visible dès maintenant (si “Publié = Oui”).</div>
    </div>
</div>

<hr class="my-4">

<div class="mb-3">
    <label class="form-label fw-semibold">Pièce jointe (image/pdf) (optionnel)</label>
    <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
    @error('attachment') <div class="text-danger small">{{ $message }}</div> @enderror

    @if(!empty($a?->attachment_url))
        <div class="mt-2">
            <div class="text-muted small">Actuelle :</div>
            @if($a->attachment_type === 'image')
                <img src="{{ $a->attachment_url }}" class="img-fluid rounded border mt-1" style="max-height:220px;">
            @else
                <a class="btn btn-sm btn-outline-primary mt-1" target="_blank" href="{{ $a->attachment_url }}">📄 Voir PDF</a>
            @endif

            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="remove_attachment">
                <label class="form-check-label" for="remove_attachment">Supprimer la pièce jointe</label>
            </div>
        </div>
    @endif
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
