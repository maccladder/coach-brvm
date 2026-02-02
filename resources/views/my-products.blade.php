@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1050px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="fw-bold mb-1">🧾 Mes produits</h2>
            <div class="text-muted">Retrouve ici tes achats (livres, vidéos, logiciels).</div>
        </div>
        <a href="{{ route('marketplace.index') }}" class="btn btn-outline-primary">
            Aller au Marketplace
        </a>
    </div>

    @if($products->isEmpty())
        <div class="alert alert-light border">
            Aucun produit acheté pour l’instant.
        </div>
    @else
        <div class="row g-3">
            @foreach($products as $p)
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div style="width:80px; height:80px; flex:0 0 80px;">
                                    @if($p->cover_image_path)
                                        <img src="{{ asset('storage/'.$p->cover_image_path) }}"
                                             class="rounded w-100 h-100" style="object-fit:cover;">
                                    @else
                                        <div class="rounded bg-light w-100 h-100 d-flex align-items-center justify-content-center">
                                            📦
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ $p->title }}</div>
                                    <div class="small text-muted">
                                        Type: {{ $p->type }} •
                                        Catégorie: {{ $p->category?->name ?? '—' }}
                                    </div>

                                    <div class="mt-2 d-flex gap-2">
                                        <a class="btn btn-primary btn-sm"
                                           href="{{ route('my.products.download', $p) }}">
                                            ⬇️ Télécharger
                                        </a>

                                        <a class="btn btn-outline-secondary btn-sm"
                                           href="{{ route('marketplace.show', $p->slug) }}">
                                            Voir
                                        </a>
                                    </div>
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
