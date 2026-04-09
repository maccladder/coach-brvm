@extends('layouts.admin')

@section('title', $book->title . ' – Admin')

@push('styles')
<style>
    .page-item-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .5rem;
        background: #fff;
        transition: box-shadow .15s;
    }
    .page-item-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .page-editor { display: none; }
    .page-editor.open { display: block; }
    textarea.page-content { min-height: 200px; font-family: monospace; font-size: .85rem; }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width:1000px;">

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
        <a href="{{ route('admin.books.index') }}" class="btn btn-sm btn-outline-secondary mt-1">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-1">{{ $book->title }}</h4>
            <div class="d-flex flex-wrap gap-1">
                @if($book->is_published)
                    <span class="badge bg-success">Publié</span>
                @else
                    <span class="badge bg-secondary">Brouillon</span>
                @endif
                @if($book->is_free)
                    <span class="badge bg-info text-dark">Gratuit</span>
                @else
                    <span class="badge bg-warning text-dark">Premium</span>
                @endif
                <span class="badge bg-light text-dark border">~{{ $book->estimated_minutes }} min</span>
                <span class="badge bg-light text-dark border">{{ $pages->count() }} page(s)</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-sm btn-outline-dark fw-semibold">
                <i class="bi bi-pencil me-1"></i>Modifier
            </a>
            <a href="{{ route('books.show', $book->slug) }}" target="_blank"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye me-1"></i>Voir
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($book->description)
        <div class="card border-0 shadow-sm mb-4" style="border-radius:.55rem;">
            <div class="card-body px-4 py-3">
                <p class="text-muted mb-0" style="font-size:.9rem;">{{ $book->description }}</p>
            </div>
        </div>
    @endif

    {{-- ── PAGES EXISTANTES ─────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Pages ({{ $pages->count() }})</h6>
        <button class="btn btn-sm btn-dark fw-semibold" onclick="toggleNewPage()">
            <i class="bi bi-plus-lg me-1"></i>Ajouter une page
        </button>
    </div>

    {{-- Formulaire nouvelle page --}}
    <div id="new-page-form" class="card border-0 shadow-sm mb-4" style="border-radius:.55rem; display:none;">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">Nouvelle page</h6>
            <form method="POST" action="{{ route('admin.books.pages.store', $book) }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold small">N° de page</label>
                        <input type="number" name="page_no" class="form-control form-control-sm"
                               value="{{ $pages->max('page_no') + 1 }}" min="1" required>
                    </div>
                    <div class="col-sm-9">
                        <label class="form-label fw-semibold small">Titre (optionnel)</label>
                        <input type="text" name="title" class="form-control form-control-sm"
                               placeholder="Introduction, Chapitre 1, etc.">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Contenu</label>
                    <textarea name="content" class="form-control page-content" required
                              placeholder="Contenu de la page en texte brut ou HTML…"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="toggleNewPage()">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des pages --}}
    @forelse($pages as $page)
        <div class="page-item-card mb-2 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="fw-bold text-muted" style="min-width:2.2rem; font-size:.85rem;">
                    p.{{ $page->page_no }}
                </div>
                <div class="flex-grow-1">
                    @if($page->title)
                        <div class="fw-semibold">{{ $page->title }}</div>
                    @endif
                    <div class="text-muted" style="font-size:.8rem;">
                        {{ Str::limit(strip_tags($page->content), 100) }}
                    </div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="toggleEdit({{ $page->id }})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    @if(session('is_admin'))
                        <form method="POST"
                              action="{{ route('admin.books.pages.destroy', [$book, $page]) }}"
                              onsubmit="return confirm('Supprimer cette page ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Éditeur inline --}}
            <div id="edit-{{ $page->id }}" class="page-editor mt-3 pt-3 border-top">
                <form method="POST" action="{{ route('admin.books.pages.store', $book) }}">
                    @csrf
                    <input type="hidden" name="page_no" value="{{ $page->page_no }}">
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Titre</label>
                        <input type="text" name="title" class="form-control form-control-sm"
                               value="{{ $page->title }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Contenu</label>
                        <textarea name="content" class="form-control page-content"
                                  required>{{ $page->content }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-dark fw-semibold">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="toggleEdit({{ $page->id }})">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted border rounded" style="border-radius:.5rem!important;">
            <i class="bi bi-file-earmark-text" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">Aucune page pour l'instant. Ajoutez la première !</p>
        </div>
    @endforelse

</div>
@endsection

@push('scripts')
<script>
function toggleEdit(id) {
    const el = document.getElementById('edit-' + id);
    el.classList.toggle('open');
}
function toggleNewPage() {
    const el = document.getElementById('new-page-form');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
