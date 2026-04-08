@php $a = $announcement; @endphp

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

<div class="row g-3 mb-4">
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
        <div class="text-muted small">Si vide : visible dès maintenant (si "Publié = Oui").</div>
    </div>
</div>

<hr class="my-4">

{{-- ── Pièces jointes existantes ── --}}
@if(!empty($a) && $a->attachments?->count())
<div class="mb-4">
    <label class="form-label fw-semibold">Fichiers actuels</label>
    <div class="row g-2">
        @foreach($a->attachments as $att)
        <div class="col-sm-6 col-md-4">
            <div class="border rounded p-2 bg-light d-flex flex-column gap-1">
                @if($att->is_image)
                    <img src="{{ $att->url }}" class="img-fluid rounded" style="max-height:140px;object-fit:cover;">
                @else
                    <div class="d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-file-earmark-{{ $att->type === 'pdf' ? 'pdf text-danger' : 'word text-primary' }} fs-3"></i>
                        <span class="small text-truncate">{{ $att->original_name }}</span>
                    </div>
                @endif
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <a href="{{ $att->url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye"></i>
                    </a>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox"
                               name="remove_ids[]" value="{{ $att->id }}"
                               id="remove_{{ $att->id }}">
                        <label class="form-check-label text-danger small" for="remove_{{ $att->id }}">
                            Supprimer
                        </label>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Nouveaux fichiers ── --}}
<div class="mb-4">
    <label class="form-label fw-semibold">
        {{ !empty($a) && $a->attachments?->count() ? 'Ajouter des fichiers' : 'Fichiers (images / PDF / docs)' }}
    </label>
    <input type="file" name="files[]" class="form-control"
           accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx"
           multiple>
    <div class="text-muted small mt-1">Plusieurs fichiers acceptés · jpg, png, webp, gif, pdf, doc, docx · 20 Mo max/fichier</div>
    @error('files')   <div class="text-danger small">{{ $message }}</div> @enderror
    @error('files.*') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
