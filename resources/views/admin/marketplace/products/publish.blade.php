@extends('layouts.admin')

@section('title', 'Pré-approuver la vidéo')

@section('content')
<div class="container py-5" style="max-width:900px;">

    <a href="{{ route('admin.marketplace.show', $product) }}" class="text-decoration-none small">← Retour</a>

    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
        <div>
            <h2 class="fw-bold mb-1">🎬 Pré-approuver : {{ $product->title }}</h2>
            <div class="text-muted">Télécharge le MP4, vérifie, upload sur Cloudflare, puis publie.</div>
        </div>
    </div>

    @if(session('warning')) <div class="alert alert-warning border-0">{{ session('warning') }}</div> @endif
    @if(session('success')) <div class="alert alert-success border-0">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger border-0">
            <ul class="mb-0">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    @php
        $file = $product->assets->firstWhere('kind', 'file');
        $currentVideoId = $stream?->url ?? '';
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="mb-2"><b>Fichier MP4 envoyé par le vendeur</b></div>

            @if($file)
                <a class="btn btn-outline-dark"
                   href="{{ route('admin.marketplace.assets.download', $file) }}">
                    ⬇️ Télécharger le MP4
                </a>
            @else
                <div class="text-danger">Aucun fichier MP4 trouvé.</div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.marketplace.publish', $product) }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <label class="form-label">Cloudflare Video ID *</label>
            <input name="cloudflare_video_id"
                   class="form-control"
                   value="{{ old('cloudflare_video_id', $currentVideoId) }}"
                   placeholder="Ex: 1ccbd5cea14c894b8c50c6d9d2aca6e">

            <div class="form-text">
                Colle le “Video ID” depuis Cloudflare Stream.
            </div>

            <div class="d-flex justify-content-end mt-3 gap-2">
                <a href="{{ route('admin.marketplace.show', $product) }}" class="btn btn-outline-secondary">Annuler</a>
                <button class="btn btn-primary">Publier ✅</button>
            </div>
        </div>
    </form>
</div>
@endsection
