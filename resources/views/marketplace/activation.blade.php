@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">

    <a href="{{ route('my.products') }}" class="btn btn-outline-secondary mb-3">
        ← Mes produits
    </a>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h3 class="fw-bold mb-2">✅ Activation du logiciel</h3>
            <div class="text-muted mb-3">
                Produit : <strong>{{ $product->title }}</strong>
            </div>

            <div class="alert alert-info border-0">
                <div class="fw-semibold mb-1">Voici comment activer votre licence :</div>
                <ol class="mb-0">
                    <li>Envoyez votre email + preuve d’achat (transaction) au support.</li>
                    <li>Vous recevrez votre clé/licence et les instructions d’installation.</li>
                </ol>
            </div>

            @if(!empty($wa))
                <a class="btn btn-success"
                   href="https://wa.me/{{ $wa }}?text={{ $waText }}"
                   target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i> Contacter le support WhatsApp
                </a>
            @endif

            <a class="btn btn-outline-dark ms-2"
               href="mailto:boursivoire@gmail.com?subject=Activation%20logiciel%20{{ urlencode($product->title) }}">
                Envoyer un mail
            </a>
        </div>
    </div>

</div>
@endsection
