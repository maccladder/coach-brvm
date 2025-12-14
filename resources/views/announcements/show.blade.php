@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route('announcements.index') }}" class="text-decoration-none">
            ← Retour aux annonces
        </a>

        <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-secondary">
            📣 Toutes les annonces
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">

            {{-- Header annonce + branding --}}
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="fw-bold mb-1">{{ $announcement->title }}</h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-light text-dark border">
                            {{ $announcement->public_date }}
                        </span>
                        <span class="badge bg-primary-subtle text-primary border">
                            Annonce BRVM
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('img/coach-brvm-logo.png') }}"
                         alt="Coach BRVM"
                         style="height:36px;width:auto;">
                </div>
            </div>

            @if($announcement->excerpt)
                <p class="text-muted mb-4">{{ $announcement->excerpt }}</p>
            @endif

            {{-- Pièce jointe (image/pdf) --}}
            @if($announcement->attachment_url)
                <div class="mb-4">
                    @if($announcement->attachment_type === 'image')
                        <div class="border rounded-4 p-2 bg-light">
                            <img
                                src="{{ $announcement->attachment_url }}"
                                alt="Pièce jointe"
                                class="w-100 rounded-3"
                                style="max-height:520px; object-fit:contain;"
                            >
                        </div>

                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm"
                               target="_blank"
                               href="{{ $announcement->attachment_url }}">
                                🖼️ Ouvrir l’image
                            </a>
                        </div>

                    @elseif($announcement->attachment_type === 'pdf')
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border rounded-4 p-3 bg-light">
                            <div class="text-muted small">
                                📄 Pièce jointe PDF disponible
                            </div>
                            <a class="btn btn-primary btn-sm" target="_blank" href="{{ $announcement->attachment_url }}">
                                Ouvrir le PDF
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Contenu --}}
            <div class="mt-3" style="line-height:1.8; font-size:1.05rem;">
                {!! nl2br(e($announcement->content)) !!}
            </div>

            {{-- Signature Coach BRVM --}}
            <div class="mt-5 pt-4 border-top">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('avatars/coach.png') }}"
                         alt="Coach"
                         class="rounded-circle border"
                         style="width:52px;height:52px;object-fit:cover;">

                    <div class="flex-grow-1">
                        <div class="fw-semibold">Coach BRVM</div>
                        <div class="text-muted small">
                            Service indépendant – analyses & explications pédagogiques.
                        </div>
                    </div>

                    <img src="{{ asset('img/coach-brvm-logo.png') }}"
                         alt="Coach BRVM"
                         style="height:34px;width:auto;">
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
