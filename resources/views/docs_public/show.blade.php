@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <a href="{{ route('docs.public.index') }}" class="text-decoration-none">&larr; Retour</a>

    <div class="card mt-3 border-0 shadow-sm">
        <div class="card-body">

            <div class="mb-2">
                <span class="badge bg-light text-dark">{{ $types[$document->type] ?? $document->type }}</span>
                @if($document->country)
                    <span class="badge bg-secondary">{{ $document->country }}</span>
                @endif
            </div>

            <h2 class="fw-bold">{{ $document->title }}</h2>

            @if($document->description)
                <p class="text-muted mt-2 mb-3">{{ $document->description }}</p>
            @endif

            <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                <div class="fs-4 fw-bold">
                    {{ number_format($document->price,0,',',' ') }} FCFA
                </div>

                @auth
                    @if($document->isBoughtBy(auth()->user()))
                        <a href="{{ route('documents.download', $document->id) }}" class="btn btn-success">
                            ⬇️ Télécharger
                        </a>
                        <a href="{{ route('documents.mine') }}" class="btn btn-outline-secondary">
                            📁 Mes documents
                        </a>
                    @else
                        <form method="POST" action="{{ route('documents.buy', $document) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Acheter
                            </button>
                        </form>
                    @endif
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Se connecter pour acheter
                    </a>
                @endguest
            </div>

            <hr class="my-4">

            <div class="text-muted">
                Après paiement, le document sera disponible dans <b>Mon espace → Mes documents</b>.
            </div>

        </div>
    </div>
</div>
@endsection
