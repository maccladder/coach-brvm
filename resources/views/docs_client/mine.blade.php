{{-- resources/views/docs_client/mine.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-0">📚 Mes documents</h3>
            <div class="text-muted">
                Retrouve ici tes études & business plans achetés.
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                ← Retour Mon espace
            </a>
            <a href="{{ route('docs.public.index') }}" class="btn btn-outline-dark btn-sm">
                Parcourir le catalogue
            </a>
        </div>
    </div>

    {{-- Ici tu injecteras $documents (achetés par l’utilisateur) quand tu branches la logique achat --}}
    @if(empty($documents ?? null) || (is_countable($documents) && count($documents) === 0))
        <div class="alert alert-light border shadow-sm">
            Tu n’as pas encore de document ici.
            <div class="text-muted small mt-1">
                Va sur le catalogue pour acheter une étude / un business plan.
            </div>
            <a href="{{ route('docs.public.index') }}" class="btn btn-sm btn-primary mt-2">
                Ouvrir le catalogue
            </a>
        </div>
    @else
        <div class="row g-3">
            @foreach($documents as $doc)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="fw-semibold">{{ $doc->title }}</div>

                            @if(!empty($doc->description))
                                <div class="text-muted small mt-2">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($doc->description), 100) }}
                                </div>
                            @endif

                            <div class="mt-auto pt-3">
                                <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-primary btn-sm">
                                    ⬇️ Télécharger
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $documents->links() ?? '' }}
        </div>
    @endif

</div>
@endsection
