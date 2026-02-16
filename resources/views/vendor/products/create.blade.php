{{-- resources/views/vendor/products/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">
    <a href="{{ route('vendor.products.index') }}" class="text-decoration-none small">← Retour</a>

    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
        <h3 class="mb-0">➕ Nouveau produit</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0">{{ session('warning') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Catégorie</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Choisir…</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required id="typeSelect">
                            @foreach($types as $k=>$label)
                                <option value="{{ $k }}" @selected(old('type',$k)===$k)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Titre</label>
                        <input name="title" class="form-control" value="{{ old('title') }}" required maxlength="160">
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">WhatsApp support (optionnel)</label>
                        <input name="support_whatsapp" class="form-control" placeholder="ex: +2250700000000" value="{{ old('support_whatsapp') }}">
                        @error('support_whatsapp') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Prix (FCFA)</label>
                        <input name="price" type="number" min="0" class="form-control" value="{{ old('price', 0) }}" required>
                        @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Image de couverture (optionnelle au draft)</label>
                        <input name="cover_image" type="file" class="form-control" accept="image/*">
                        @error('cover_image') <div class="text-danger small">{{ $message }}</div> @enderror
                        <div class="form-text">Obligatoire uniquement au moment de “Soumettre”.</div>
                    </div>

                    {{-- ✅ NEW : fichier produit --}}
                    <div class="col-12">
                        <label class="form-label">Fichier du produit (optionnel au draft)</label>
                        <input name="file" type="file" class="form-control" id="fileInput">
                        @error('file') <div class="text-danger small">{{ $message }}</div> @enderror

                        <div class="form-text" id="fileHelp">
                            PDF → .pdf | ZIP → .zip/.rar | Vidéo → .mp4/.mov/.m4v/.avi
                            <br>Obligatoire au moment de “Soumettre”.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-dark">
                        Créer le brouillon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- petit helper UX (facultatif) --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const typeSelect = document.getElementById('typeSelect');
  const fileHelp = document.getElementById('fileHelp');
  const fileInput = document.getElementById('fileInput');

  function refreshHelp(){
    const t = typeSelect.value;
    if(t === 'pdf'){
      fileInput.accept = 'application/pdf';
      fileHelp.innerHTML = 'Type PDF : ajoute un fichier <strong>.pdf</strong> (optionnel au draft, obligatoire à la soumission).';
    } else if(t === 'zip'){
      fileInput.accept = '.zip,.rar,application/zip,application/x-rar-compressed';
      fileHelp.innerHTML = 'Type ZIP : ajoute un fichier <strong>.zip</strong> ou <strong>.rar</strong> (optionnel au draft, obligatoire à la soumission).';
    } else if(t === 'video'){
      fileInput.accept = 'video/mp4,video/quicktime,video/x-m4v,video/x-msvideo';
      fileHelp.innerHTML = 'Type Vidéo : ajoute un fichier <strong>.mp4</strong> / <strong>.mov</strong> / <strong>.m4v</strong> / <strong>.avi</strong>. (Tu pourras ensuite le mettre sur Cloudflare).';
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
