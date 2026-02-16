{{-- resources/views/vendor/products/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">
    <a href="{{ route('vendor.products.index') }}" class="text-decoration-none small">← Retour</a>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-3">
        <div>
            <h3 class="mb-0">✏️ Gérer produit</h3>
            <div class="text-muted small">
                Statut :
                <span class="badge text-bg-{{ match($product->status){'draft'=>'secondary','pending'=>'warning','published'=>'success','rejected'=>'danger',default=>'secondary'} }}">
                    {{ $product->status }}
                </span>
                @if($product->status === 'rejected' && $product->admin_note)
                    <span class="ms-2 text-danger">Motif: {{ $product->admin_note }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" target="_blank" href="{{ route('marketplace.show', $product->slug) }}">
                Voir (si publié)
            </a>

            @if(in_array($product->status, ['draft','rejected'], true))
                <form method="POST" action="{{ route('vendor.products.submit', $product) }}">
                    @csrf
                    <button class="btn btn-dark">
                        Soumettre à validation
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0">{{ session('warning') }}</div>
    @endif

    @php
        // on suppose que ton controller fait $product->load('assets')
        $fileAsset = $product->assets->firstWhere('kind', 'file');
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($product->status === 'pending')
                <div class="alert alert-warning border-0">
                    ⏳ Produit en validation admin. Modification désactivée.
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <fieldset @disabled($product->status === 'pending')>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(old('category_id',$product->category_id)==$c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required id="typeSelect">
                                @foreach($types as $k=>$label)
                                    <option value="{{ $k }}" @selected(old('type',$product->type)===$k)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Titre</label>
                            <input name="title" class="form-control" value="{{ old('title',$product->title) }}" required maxlength="160">
                            @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="6">{{ old('description',$product->description) }}</textarea>
                            @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">WhatsApp support</label>
                            <input name="support_whatsapp" class="form-control" value="{{ old('support_whatsapp',$product->support_whatsapp) }}">
                            @error('support_whatsapp') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Prix (FCFA)</label>
                            <input name="price" type="number" min="0" class="form-control" value="{{ old('price',$product->price) }}" required>
                            @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Image de couverture</label>
                            <input name="cover_image" type="file" class="form-control" accept="image/*">
                            @error('cover_image') <div class="text-danger small">{{ $message }}</div> @enderror

                            @if($product->cover_image_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/'.$product->cover_image_path) }}" alt="cover" class="rounded" style="max-height:120px;">
                                </div>
                            @endif

                            <div class="form-text">
                                Obligatoire pour soumettre.
                            </div>
                        </div>

                        {{-- ✅ NEW : fichier produit --}}
                        <div class="col-12">
                            <label class="form-label">Fichier du produit</label>
                            <input name="file" type="file" class="form-control" id="fileInput">
                            @error('file') <div class="text-danger small">{{ $message }}</div> @enderror

                            @if($fileAsset)
                                <div class="alert alert-light border mt-2 mb-0">
                                    <div class="fw-semibold">Fichier actuel :</div>
                                    <div class="text-muted small">
                                        {{ $fileAsset->label }}
                                        @if($fileAsset->path)
                                            — <span>{{ $fileAsset->path }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-1">
                                        (Si tu upload un nouveau fichier ici, il remplacera l’ancien.)
                                    </div>
                                </div>
                            @else
                                <div class="form-text mt-1">
                                    Aucun fichier encore. Ajoute un PDF/ZIP/VIDÉO avant de soumettre.
                                </div>
                            @endif

                            <div class="form-text mt-2" id="fileHelp">
                                PDF → .pdf | ZIP → .zip/.rar | Vidéo → .mp4/.mov/.m4v/.avi
                            </div>
                        </div>

                    </div>
                </fieldset>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-dark" @disabled($product->status === 'pending')>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const typeSelect = document.getElementById('typeSelect');
  const fileHelp = document.getElementById('fileHelp');
  const fileInput = document.getElementById('fileInput');

  function refreshHelp(){
    const t = typeSelect.value;
    if(t === 'pdf'){
      fileInput.accept = 'application/pdf';
      fileHelp.innerHTML = 'Type PDF : fichier <strong>.pdf</strong>';
    } else if(t === 'zip'){
      fileInput.accept = '.zip,.rar,application/zip,application/x-rar-compressed';
      fileHelp.innerHTML = 'Type ZIP : fichier <strong>.zip</strong> / <strong>.rar</strong>';
    } else if(t === 'video'){
      fileInput.accept = 'video/mp4,video/quicktime,video/x-m4v,video/x-msvideo';
      fileHelp.innerHTML = 'Type Vidéo : fichier <strong>.mp4</strong> / <strong>.mov</strong> / <strong>.m4v</strong> / <strong>.avi</strong>';
    } else {
      fileInput.accept = '';
      fileHelp.textContent = 'Ajoute un fichier correspondant au type choisi.';
    }
  }

  typeSelect.addEventListener('change', refreshHelp);
  refreshHelp();
});
</script>
@endsection
