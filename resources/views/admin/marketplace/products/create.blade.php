@extends('layouts.admin')

@section('title', 'Marketplace – Nouveau produit')

@section('content')
<div class="container py-5" style="max-width:950px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">➕ Nouveau produit</h2>
            <p class="text-muted mb-0">Ajoute un livre, une vidéo ou un logiciel.</p>
        </div>
        <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary">
            ← Retour
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Erreurs :</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="card border-0 shadow-sm"
          method="POST"
          action="{{ route('admin.marketplace.store') }}"
          enctype="multipart/form-data">
        @csrf

        <div class="card-body p-4">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Titre *</label>
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           class="form-control"
                           placeholder="Ex: BRVM Pratique (PDF) – Outils d’analyse">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-select" id="typeSelect">
                        <option value="book" @selected(old('type','book')==='book')>📘 Livre (PDF)</option>
                        <option value="video" @selected(old('type')==='video')>🎬 Vidéo</option>
                        <option value="software" @selected(old('type')==='software')>🧩 Logiciel</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Catégorie</label>
                    <select name="category_id" class="form-select">
                        <option value="">— Aucune —</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)old('category_id')===(string)$c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Tu peux gérer les catégories ici :
                        <a href="{{ route('admin.marketplace-categories.index') }}">Marketplace → Catégories</a>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prix (FCFA) *</label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" min="0" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Statut *</label>
                    <select name="status" class="form-select">
                        <option value="draft" @selected(old('status','draft')==='draft')>Brouillon</option>
                        <option value="published" @selected(old('status')==='published')>Publié</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              class="form-control"
                              rows="4"
                              placeholder="Décris le contenu, ce que l’utilisateur reçoit, etc.">{{ old('description') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Image de couverture</label>
                    <input type="file" name="cover" class="form-control" accept="image/*">
                    <div class="form-text">PNG/JPG, max 4MB.</div>
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featured"
                               @checked(old('is_featured'))>
                        <label class="form-check-label" for="featured">
                            Mettre en avant (featured)
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            {{-- ✅ FICHIER PRODUIT (PDF / ZIP / RAR) --}}
            <div class="row g-3" id="fileBlock">
                <div class="col-12">
                    <label class="form-label" id="fileLabel">Fichier produit</label>
                    <input type="file" name="file" class="form-control" id="fileInput">
                    <div class="form-text" id="fileHelp">
                        Upload obligatoire : PDF pour Livre, ZIP/RAR pour Logiciel. (Vidéo: pas de fichier)
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button class="btn btn-primary">Enregistrer ✅</button>
            </div>

        </div>
    </form>

</div>

{{-- ✅ JS: afficher/masquer + instructions selon type --}}
<script>
(function(){
    const typeSelect = document.getElementById('typeSelect');
    const fileBlock  = document.getElementById('fileBlock');
    const fileHelp   = document.getElementById('fileHelp');
    const fileLabel  = document.getElementById('fileLabel');
    const fileInput  = document.getElementById('fileInput');

    function refresh(){
        const t = typeSelect.value;

        if (t === 'video') {
            fileBlock.style.display = 'none';
            fileInput.value = '';
            return;
        }

        fileBlock.style.display = 'block';

        if (t === 'book') {
            fileLabel.textContent = 'PDF du livre *';
            fileHelp.textContent  = 'Upload obligatoire : PDF (max 50MB).';
            fileInput.setAttribute('accept', 'application/pdf,.pdf');
        } else if (t === 'software') {
            fileLabel.textContent = 'Fichier logiciel (ZIP/RAR) *';
            fileHelp.textContent  = 'Upload obligatoire : ZIP ou RAR (max 100MB).';
            fileInput.setAttribute('accept', '.zip,.rar,application/zip,application/x-zip-compressed,application/x-rar-compressed');
        } else {
            fileLabel.textContent = 'Fichier produit';
            fileHelp.textContent  = 'Upload fichier.';
            fileInput.removeAttribute('accept');
        }
    }

    typeSelect.addEventListener('change', refresh);
    refresh();
})();
</script>
@endsection
