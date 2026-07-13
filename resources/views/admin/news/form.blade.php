@php
    $n = $news;
    $societesText  = old('societes',  isset($n) ? implode("\n", $n->societes ?? [])  : '');
    $motsClesText  = old('mots_cles', isset($n) ? implode("\n", $n->mots_cles ?? []) : '');
@endphp

@if(isset($n))
<div class="mb-3">
    <label class="form-label fw-semibold">Slug (URL publique)</label>
    <input type="text" class="form-control" value="{{ $n->slug }}" disabled>
    <div class="text-muted small">Généré automatiquement à la création, non modifiable.</div>
</div>
@endif

<div class="mb-3">
    <label class="form-label fw-semibold">Titre</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $n->title ?? '') }}" required>
    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Résumé</label>
    <textarea name="resume" rows="4" class="form-control" required>{{ old('resume', $n->resume ?? '') }}</textarea>
    @error('resume') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Source (nom)</label>
        <input type="text" name="source_name" class="form-control" placeholder="Ex : Sika Finance" value="{{ old('source_name', $n->source_name ?? '') }}">
        @error('source_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">URL de l'article original</label>
        <input type="url" name="source_url" class="form-control" placeholder="https://..." value="{{ old('source_url', $n->source_url ?? '') }}">
        @error('source_url') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Catégorie</label>
        <input type="text" name="categorie" class="form-control" placeholder="Ex : BRVM, Banque, IPO, Mines" value="{{ old('categorie', $n->categorie ?? '') }}">
        @error('categorie') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Impact</label>
        @php $impact = old('impact', $n->impact ?? ''); @endphp
        <select name="impact" class="form-select">
            <option value="" {{ $impact === '' ? 'selected' : '' }}>—</option>
            <option value="Faible" {{ $impact === 'Faible' ? 'selected' : '' }}>Faible</option>
            <option value="Moyen" {{ $impact === 'Moyen' ? 'selected' : '' }}>Moyen</option>
            <option value="Élevé" {{ $impact === 'Élevé' ? 'selected' : '' }}>Élevé</option>
        </select>
        @error('impact') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Sociétés concernées</label>
        <textarea name="societes" rows="3" class="form-control" placeholder="Une société par ligne">{{ $societesText }}</textarea>
        <div class="text-muted small">Une société par ligne.</div>
        @error('societes') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Mots-clés</label>
        <textarea name="mots_cles" rows="3" class="form-control" placeholder="Un mot-clé par ligne">{{ $motsClesText }}</textarea>
        <div class="text-muted small">Un mot-clé par ligne.</div>
        @error('mots_cles') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Publié ?</label>
        <select name="is_published" class="form-select">
            <option value="1" {{ old('is_published', $n->is_published ?? false) ? 'selected' : '' }}>Oui</option>
            <option value="0" {{ !old('is_published', $n->is_published ?? false) ? 'selected' : '' }}>Non</option>
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label fw-semibold">Date/heure de publication (optionnel)</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', isset($n->published_at) ? $n->published_at->format('Y-m-d\TH:i') : '') }}">
        <div class="text-muted small">Si vide et "Publié = Oui" : réglée automatiquement à maintenant.</div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
