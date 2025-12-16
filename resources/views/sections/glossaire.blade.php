@extends('layouts.app')

@push('styles')
<style>
    .glossary-letters a{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width: 38px;
        height: 34px;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
    }
    .glossary-letters a.active{
        background: #0d6efd;
        color: #fff;
    }
    .glossary-letters a.inactive{
        background: #f1f3f5;
        color: #6c757d;
        pointer-events: none;
        opacity: .7;
    }
    .term-card{
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 14px;
        background: #fff;
    }
    .term-card .term-title{
        font-weight: 700;
    }
    html { scroll-behavior: smooth; }
</style>
@endpush

@section('content')
<div class="container py-5" style="max-width: 1000px;">

    {{-- Header --}}
    <div class="d-flex align-items-start gap-3 mb-3">
        <div style="font-size: 28px; line-height: 1;">📘</div>
        <div>
            <h2 class="fw-bold mb-1">Glossaire BRVM</h2>
            <div class="text-muted">
                Comprendre facilement les termes techniques de la bourse régionale.
            </div>
        </div>
    </div>

    {{-- Recherche --}}
    <form method="GET" class="mb-3">
        <div class="input-group">
            <span class="input-group-text">🔎</span>
            <input type="text"
                   name="q"
                   value="{{ $search }}"
                   class="form-control"
                   placeholder="Rechercher un terme (ex: dividende, PER, obligation…)">
            @if(!empty($search))
                <a class="btn btn-outline-secondary" href="{{ route('aide.glossaire') }}">Effacer</a>
            @endif
        </div>
    </form>

    {{-- Ancres A-Z (cliquables si la lettre existe) --}}
    @php
        $alphabet = range('A','Z');
        $available = $items->keys()->map(fn($k) => strtoupper($k))->toArray();
    @endphp

    <div class="bg-white border rounded-4 p-3 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">Aller à la lettre :</div>
            <div class="d-flex flex-wrap gap-2 glossary-letters">
                @foreach($alphabet as $L)
                    @if(in_array($L, $available))
                        <a class="active" href="#lettre-{{ $L }}">{{ $L }}</a>
                    @else
                        <a class="inactive" href="#">{{ $L }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Résultats --}}
    @forelse($items as $lettre => $termes)
        @php $L = strtoupper($lettre); @endphp

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 id="lettre-{{ $L }}" class="fw-bold mb-0">{{ $L }}</h4>
            <a href="#top" class="small text-decoration-none">↑ Haut</a>
        </div>

        <div class="row g-3 mb-4">
            @foreach($termes as $item)
                <div class="col-12 col-md-6">
                    <div class="term-card p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="term-title">{{ $item->terme }}</div>
                            <span class="badge text-bg-light border">{{ $L }}</span>
                        </div>
                        <div class="text-muted mt-2">
                            {{ $item->definition }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @empty
        <div class="bg-white border rounded-4 p-4 text-center">
            <div class="fw-semibold">Aucun terme trouvé</div>
            <div class="text-muted">Essaie un autre mot-clé, ou enlève la recherche.</div>
        </div>
    @endforelse

</div>

{{-- ancre haut --}}
<div id="top"></div>
@endsection
