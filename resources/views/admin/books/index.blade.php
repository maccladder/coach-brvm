@extends('layouts.admin')

@section('title', 'Livres & Études de marché – Admin')

@push('styles')
<style>
    .book-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .65rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        transition: transform .15s, box-shadow .15s;
        background: #fff;
    }
    .book-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,.11);
    }
    .book-cover {
        width: 48px; height: 64px;
        border-radius: .4rem;
        background: linear-gradient(135deg, #0d6efd 0%, #20c997 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .book-cover.premium {
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
    }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width:1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">📚 Livres & Études de marché</h4>
            <p class="text-muted mb-0" style="font-size:.88rem;">
                {{ $books->count() }} livre{{ $books->count() > 1 ? 's' : '' }} au total
            </p>
        </div>
        <a href="{{ route('admin.books.create') }}" class="btn btn-dark btn-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i>Nouveau livre
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($books->isEmpty())
        <div class="text-center py-5 text-muted">
            <div style="font-size:3rem;">📖</div>
            <p class="mt-2">Aucun livre pour l'instant.</p>
            <a href="{{ route('admin.books.create') }}" class="btn btn-dark btn-sm">Créer le premier</a>
        </div>
    @else
        <div class="row g-3">
            @foreach($books as $book)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="book-card p-3 h-100">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="book-cover {{ $book->is_free ? '' : 'premium' }}">
                                {{ $book->is_free ? '📗' : '🔒' }}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate mb-1">{{ $book->title }}</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @if($book->is_published)
                                        <span class="badge bg-success" style="font-size:.7rem;">Publié</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size:.7rem;">Brouillon</span>
                                    @endif
                                    @if($book->is_free)
                                        <span class="badge bg-info text-dark" style="font-size:.7rem;">Gratuit</span>
                                    @else
                                        <span class="badge bg-warning text-dark" style="font-size:.7rem;">Premium</span>
                                    @endif
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                                        {{ $book->pages_count }} page{{ $book->pages_count > 1 ? 's' : '' }}
                                    </span>
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                                        ~{{ $book->estimated_minutes }} min
                                    </span>
                                </div>
                                @if($book->description)
                                    <p class="text-muted mb-2" style="font-size:.8rem; line-height:1.4;">
                                        {{ Str::limit($book->description, 80) }}
                                    </p>
                                @endif
                                <div class="d-flex gap-1 mt-auto">
                                    <a href="{{ route('admin.books.show', $book) }}"
                                       class="btn btn-sm btn-dark fw-semibold">
                                        <i class="bi bi-pencil me-1"></i>Gérer
                                    </a>
                                    <a href="{{ route('books.show', $book->slug) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(session('is_admin'))
                                        <form method="POST" action="{{ route('admin.books.destroy', $book) }}"
                                              onsubmit="return confirm('Supprimer ce livre et toutes ses pages ?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
