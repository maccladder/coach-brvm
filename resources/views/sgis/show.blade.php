@extends('layouts.app')

@section('title', $sgi->name . ' – SGI – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 1000px;">

        <a href="{{ route('sgis.index', array_filter(['country' => $sgi->country])) }}" class="text-decoration-none small">
            ← Retour aux SGI ({{ $sgi->country }})
        </a>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">{{ $sgi->name }}</h1>
                <div class="text-muted">
                    🌍 {{ $sgi->country }}
                    @if($sgi->city) • 📍 {{ $sgi->city }} @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                @if($sgi->website)
                    <a href="{{ $sgi->website }}" target="_blank" rel="noopener" class="btn btn-outline-dark">
                        🔗 Site web
                    </a>
                @endif

                @if($sgi->email)
                    <a href="mailto:{{ $sgi->email }}" class="btn btn-primary">
                        ✉️ Écrire
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-3">
            {{-- Bloc infos --}}
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">📌 Informations & Contacts</h5>

                        @if($sgi->address)
                            <div class="mb-2">
                                <div class="small text-muted">Adresse</div>
                                <div class="fw-semibold">{{ $sgi->address }}</div>
                            </div>
                        @endif

                        @if($sgi->po_box)
                            <div class="mb-2">
                                <div class="small text-muted">Boîte postale</div>
                                <div class="fw-semibold">{{ $sgi->po_box }}</div>
                            </div>
                        @endif

                        <hr>

                        <div class="mb-2">
                            <div class="small text-muted">Email</div>
                            @if($sgi->email)
                                <div class="fw-semibold">
                                    <a href="mailto:{{ $sgi->email }}">{{ $sgi->email }}</a>
                                    <button class="btn btn-sm btn-outline-secondary ms-2"
                                            type="button"
                                            onclick="navigator.clipboard.writeText('{{ $sgi->email }}')">
                                        Copier
                                    </button>
                                </div>
                            @else
                                <div class="text-muted">Non renseigné</div>
                            @endif
                        </div>

                        <div class="mb-2">
                            <div class="small text-muted">Téléphone</div>
                            @if($sgi->phone)
                                <div class="fw-semibold">
                                    <a href="tel:{{ preg_replace('/\s+/', '', $sgi->phone) }}">{{ $sgi->phone }}</a>
                                </div>
                            @else
                                <div class="text-muted">Non renseigné</div>
                            @endif

                            @if($sgi->phone2)
                                <div class="fw-semibold">
                                    <a href="tel:{{ preg_replace('/\s+/', '', $sgi->phone2) }}">{{ $sgi->phone2 }}</a>
                                </div>
                            @endif
                        </div>

                        <div class="mb-2">
                            <div class="small text-muted">Site web</div>
                            @if($sgi->website)
                                <div class="fw-semibold">
                                    <a href="{{ $sgi->website }}" target="_blank" rel="noopener">{{ $sgi->website }}</a>
                                </div>
                            @else
                                <div class="text-muted">Non renseigné</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bloc “Coach-BRVM value add” --}}
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2">✅ Ce que fait une SGI</h5>
                        <div class="text-muted">
                            Une SGI (Société de Gestion et d’Intermédiation) est un intermédiaire agréé qui permet
                            d’acheter/vendre des titres cotés à la BRVM et d’ouvrir un compte-titres.
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2">📚 Source</h5>
                        <div class="text-muted small">
                            Données issues de : <span class="fw-semibold">{{ $sgi->source_name }}</span>
                            @if($sgi->source_url)
                                <div class="mt-2">
                                    <a href="{{ $sgi->source_url }}" target="_blank" rel="noopener">Voir la source ↗</a>
                                </div>
                            @endif
                        </div>

                        <hr>

                        <div class="text-muted small">
                            ℹ️ Coach-BRVM affiche ces informations à titre indicatif. Pour les frais, procédures et documents
                            nécessaires, contactez directement la SGI.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-bold">Tu débutes à la BRVM ?</div>
                    <div class="text-muted small">On t’aide à comprendre le marché (BOC, indices, actions, dividendes…).</div>
                </div>
                <a href="{{ url('/annonces') }}" class="btn btn-dark">
                    Voir les annonces BRVM →
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
