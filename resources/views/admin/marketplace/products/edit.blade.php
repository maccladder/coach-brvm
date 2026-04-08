{{-- resources/views/admin/marketplace/products/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Marketplace – Modifier produit')

@section('content')
<div class="container py-5" style="max-width:950px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">✏️ Modifier produit</h2>
            <p class="text-muted mb-0">Modifie les infos, la cover et le fichier / vidéo du produit.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary">
                ← Retour
            </a>

            @if(session('is_admin'))
            {{-- ✅ DELETE form séparé (PAS imbriqué) --}}
            <form id="deleteForm"
                  action="{{ route('admin.marketplace.destroy', $product) }}"
                  method="POST"
                  onsubmit="return confirm('Supprimer ce produit ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    Supprimer
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

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

    @php
        $fileAsset = $product->assets->firstWhere('kind', 'file');
        $streamAsset = $product->assets->firstWhere('kind', 'stream');
        $streamId = $streamAsset?->url;
    @endphp

    <form class="card border-0 shadow-sm"
          method="POST"
          action="{{ route('admin.marketplace.update', $product) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card-body p-4">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Titre *</label>
                    <input type="text"
                           name="title"
                           value="{{ old('title', $product->title) }}"
                           class="form-control">
                    <div class="form-text">
                        Slug: <span class="text-muted">{{ $product->slug }}</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-select" id="typeSelect">
                        <option value="book" @selected(old('type', $product->type)==='book')>📘 Livre (PDF)</option>
                        <option value="video" @selected(old('type', $product->type)==='video')>🎬 Vidéo</option>
                        <option value="software" @selected(old('type', $product->type)==='software')>🧩 Logiciel</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Catégorie</label>
                    <select name="category_id" class="form-select">
                        <option value="">— Aucune —</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)old('category_id', $product->category_id)===(string)$c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Gérer catégories :
                        <a href="{{ route('admin.marketplace-categories.index') }}">Marketplace → Catégories</a>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prix (FCFA) *</label>
                    <input type="number"
                           name="price"
                           value="{{ old('price', $product->price) }}"
                           min="0"
                           class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Statut *</label>
                    <select name="status" class="form-select">
                        <option value="draft" @selected(old('status', $product->status)==='draft')>Brouillon</option>
                        <option value="published" @selected(old('status', $product->status)==='published')>Publié</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                </div>

                {{-- ✅ NEW: WhatsApp développeur --}}
                <div class="col-md-6">
                    <label class="form-label">WhatsApp du développeur (optionnel)</label>
                    <input type="text"
                           name="support_whatsapp"
                           value="{{ old('support_whatsapp', $product->support_whatsapp) }}"
                           class="form-control"
                           placeholder="Ex: +2250788035432">
                    <div class="form-text">
                        Pour les <b>logiciels</b>, affiche un bouton “Contacter le développeur” si renseigné.
                    </div>
                </div>

                {{-- COVER --}}
                <div class="col-md-6">
                    <label class="form-label">Image de couverture</label>

                    @if($product->cover_image_path)
                        <div class="mb-2">
                            <img src="{{ asset('storage/'.$product->cover_image_path) }}"
                                 alt="cover"
                                 style="max-height:120px; border-radius:10px;"
                                 class="border">
                        </div>
                        <div class="form-text mb-2">
                            Cover actuelle : <span class="text-muted">{{ $product->cover_image_path }}</span>
                        </div>
                    @else
                        <div class="form-text mb-2">Aucune cover actuellement.</div>
                    @endif

                    <input type="file" name="cover" class="form-control" accept="image/*">
                    <div class="form-text">PNG/JPG, max 4MB. (Si tu upload, ça remplace l’ancienne)</div>
                </div>

                <div class="col-12 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_featured"
                               value="1"
                               id="featured"
                               @checked(old('is_featured', $product->is_featured))>
                        <label class="form-check-label" for="featured">
                            Mettre en avant (featured)
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            {{-- ✅ FICHIER PRODUIT --}}
            <div class="row g-3" id="fileBlock">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0" id="fileLabel">Fichier produit</label>
                    </div>

                    @if($fileAsset && $fileAsset->path)
                        <div class="alert alert-light border d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Fichier actuel : {{ $fileAsset->label ?? 'Fichier' }}</div>
                                <div class="text-muted small">{{ $fileAsset->path }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-outline-primary btn-sm"
                                   href="{{ asset('storage/'.$fileAsset->path) }}"
                                   target="_blank">
                                    Télécharger
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="form-text mb-2">
                            Aucun fichier uploadé pour ce produit.
                        </div>
                    @endif

                    <input type="file" name="file" class="form-control" id="fileInput">
                    <div class="form-text" id="fileHelp">
                        Livre → PDF, Logiciel → ZIP/RAR.
                    </div>
                </div>
            </div>

            {{-- ✅ VIDEO (Cloudflare Stream) --}}
            <div class="row g-3 mt-0" id="videoBlock" style="display:none;">
                <div class="col-12">
                    <label class="form-label">Cloudflare Video ID *</label>

                    @if($streamId)
                        <div class="alert alert-light border d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Vidéo actuelle (Cloudflare)</div>
                                <div class="text-muted small">{{ $streamId }}</div>
                            </div>
                            <div class="small text-muted">
                                (remplace en saisissant un nouvel ID)
                            </div>
                        </div>
                    @else
                        <div class="form-text mb-2">Aucun Cloudflare Video ID enregistré.</div>
                    @endif

                    <input type="text"
                           name="cloudflare_video_id"
                           value="{{ old('cloudflare_video_id', $streamId) }}"
                           class="form-control"
                           placeholder="Ex: 1ccbd5cea14c894b8c50c6d9d2aca6e">
                    <div class="form-text">
                        Colle le <b>Video ID</b> depuis Cloudflare Stream.
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer ✅</button>
            </div>

        </div>
    </form>

</div>

<script>
(function(){
    const typeSelect = document.getElementById('typeSelect');

    const fileBlock  = document.getElementById('fileBlock');
    const fileHelp   = document.getElementById('fileHelp');
    const fileLabel  = document.getElementById('fileLabel');
    const fileInput  = document.getElementById('fileInput');

    const videoBlock = document.getElementById('videoBlock');

    function refresh(){
        const t = typeSelect.value;

        if (t === 'video') {
            fileBlock.style.display  = 'none';
            videoBlock.style.display = 'block';
            return;
        }

        videoBlock.style.display = 'none';
        fileBlock.style.display  = 'block';

        if (t === 'book') {
            fileLabel.textContent = 'PDF du livre';
            fileHelp.textContent  = 'Si tu upload ici, ça remplace le PDF. (max 50MB)';
            fileInput.setAttribute('accept', 'application/pdf,.pdf');
        } else if (t === 'software') {
            fileLabel.textContent = 'Fichier logiciel (ZIP/RAR)';
            fileHelp.textContent  = 'Si tu upload ici, ça remplace l’archive. (max 400MB)';
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
