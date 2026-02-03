@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-3">Études de marché & Business Plans</h2>
    <p class="text-muted mb-4">Choisis un document prêt à l’emploi. Paiement → accès dans ton espace.</p>

    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2" method="GET" action="{{ route('docs.public.index') }}">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Rechercher...">
                </div>

                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected(request('type')===$k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-control" placeholder="Prix min">
                </div>

                <div class="col-md-2">
                    <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-control" placeholder="Prix max">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($documents as $doc)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="badge bg-light text-dark">{{ $types[$doc->type] ?? $doc->type }}</span>
                            @if($doc->country)
                                <span class="badge bg-secondary">{{ $doc->country }}</span>
                            @endif
                        </div>

                        <h5 class="fw-bold mb-2">{{ $doc->title }}</h5>
                        <p class="text-muted small mb-3" style="min-height:48px;">
                            {{ \Illuminate\Support\Str::limit($doc->description, 110) }}
                        </p>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-bold">{{ number_format($doc->price,0,',',' ') }} FCFA</div>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('docs.public.show', $doc->slug) }}">
                                Voir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border">Aucun document trouvé.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $documents->links() }}
    </div>
</div>
@endsection
