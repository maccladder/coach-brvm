@extends('layouts.app')

@section('title', 'SGI (Courtiers) – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 1200px;">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">📌 SGI (Courtiers agréés)</h1>
                <div class="text-muted">
                    Trouvez une SGI par pays, puis contactez-la directement.
                    <span class="d-block small">Source officielle : BRVM</span>
                </div>
            </div>

            <div class="small text-muted">
                {{ $sgis->total() }} SGI affichées
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('sgis.index') }}" class="row g-2 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">Pays</label>
                        <select name="country" class="form-select">
                            <option value="">Toutes les SGI</option>
                            @foreach($countries as $c)
                                <option value="{{ $c }}" @selected(request('country') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-semibold">Recherche</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            class="form-control"
                            placeholder="Nom, ville, email..."
                        >
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100">
                            🔎 Rechercher
                        </button>

                        <a href="{{ route('sgis.index') }}" class="btn btn-outline-secondary w-100">
                            Réinitialiser
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Onglets pays (rapide) --}}
        @if($countries->count())
            <div class="mb-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('sgis.index') }}"
                       class="btn btn-sm {{ request('country') ? 'btn-outline-dark' : 'btn-dark' }}">
                        Toutes
                    </a>

                    @foreach($countries as $c)
                        <a href="{{ route('sgis.index', array_filter(['country' => $c, 'q' => request('q')])) }}"
                           class="btn btn-sm {{ request('country') === $c ? 'btn-dark' : 'btn-outline-dark' }}">
                            {{ $c }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Liste --}}
        @if($sgis->count() === 0)
            <div class="alert alert-warning">
                Aucune SGI trouvée avec ces critères.
            </div>
        @else
            <div class="row g-3">
                @foreach($sgis as $sgi)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">

                                {{-- Titre + pays --}}
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div class="fw-bold">
                                            {{ $sgi->name }}
                                        </div>
                                        <div class="text-muted small">
                                            🌍 {{ $sgi->country }}
                                            @if($sgi->city)
                                                • 📍 {{ $sgi->city }}
                                            @endif
                                        </div>
                                    </div>

                                    <span class="badge text-bg-success">Agréée</span>
                                </div>

                                {{-- Adresse --}}
                                @if($sgi->address)
                                    <div class="small text-muted mb-2">
                                        📌 {{ $sgi->address }}
                                    </div>
                                @endif

                                @if($sgi->po_box)
                                    <div class="small text-muted mb-2">
                                        📮 {{ $sgi->po_box }}
                                    </div>
                                @endif

                                <hr class="my-2">

                                {{-- Contacts --}}
                                <div class="small">
                                    @if($sgi->email)
                                        <div class="mb-1">
                                            ✉️ <a href="mailto:{{ $sgi->email }}">{{ $sgi->email }}</a>
                                        </div>
                                    @endif

                                    @if($sgi->phone)
                                        <div class="mb-1">
                                            📞 <a href="tel:{{ preg_replace('/\s+/', '', $sgi->phone) }}">{{ $sgi->phone }}</a>
                                        </div>
                                    @endif

                                    @if($sgi->phone2)
                                        <div class="mb-1">
                                            ☎️ <a href="tel:{{ preg_replace('/\s+/', '', $sgi->phone2) }}">{{ $sgi->phone2 }}</a>
                                        </div>
                                    @endif

                                    @if($sgi->website)
                                        <div class="mb-1">
                                            🔗 <a href="{{ $sgi->website }}" target="_blank" rel="noopener">
                                                {{ Str::of($sgi->website)->replace(['https://','http://'], '')->limit(28) }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-auto pt-3 d-flex gap-2">
                                    <a href="{{ route('sgis.show', $sgi->slug) }}" class="btn btn-outline-primary w-100">
                                        Voir la fiche →
                                    </a>

                                    @if($sgi->email)
                                        <a href="mailto:{{ $sgi->email }}" class="btn btn-primary">
                                            ✉️
                                        </a>
                                    @endif
                                </div>

                                <div class="text-muted small mt-2">
                                    Source : {{ $sgi->source_name }}
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $sgis->links() }}
            </div>
        @endif

        {{-- Petit disclaimer --}}
        <div class="text-muted small mt-4">
            ℹ️ Les informations proviennent de la BRVM et sont présentées à titre informatif. Contactez la SGI pour confirmer les conditions d’ouverture de compte et les frais.
        </div>

    </div>
</div>
@endsection
