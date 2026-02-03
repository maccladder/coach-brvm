@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('my.products') }}" class="btn btn-outline-secondary">
            ← Mes produits
        </a>

        <a href="{{ route('marketplace.show', $product->slug) }}" class="btn btn-outline-primary">
            Voir la fiche
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h3 class="fw-bold mb-1">▶ {{ $product->title }}</h3>
            <div class="text-muted mb-3">Lecture en ligne (Cloudflare Stream) • pas de téléchargement</div>

            @php
                $sub = config('services.cloudflare_stream.customer_subdomain'); // ex: customer-xxxx.cloudflarestream.com
                $videoId = $cloudflareVideoId;
                $iframeSrc = $sub ? "https://{$sub}/{$videoId}/iframe" : null;
            @endphp

            @if(!$iframeSrc)
                <div class="alert alert-danger mb-0">
                    Config manquante : <code>services.cloudflare_stream.customer_subdomain</code>
                    (CLOUDFLARE_STREAM_CUSTOMER_SUBDOMAIN).
                </div>
            @else
                <div class="ratio ratio-16x9 rounded overflow-hidden border bg-dark">
                    <iframe
                        src="{{ $iframeSrc }}"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                        allowfullscreen
                        style="border:0; width:100%; height:100%;"
                    ></iframe>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
