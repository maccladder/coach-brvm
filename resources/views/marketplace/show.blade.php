{{-- resources/views/marketplace/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('marketplace.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>

        @auth
            <a href="{{ route('my.products') }}" class="btn btn-outline-dark">
                <i class="bi bi-bag-check"></i> Mes produits
            </a>
        @endauth
    </div>

    {{-- ✅ Devenir vendeur --}}
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="fw-semibold">💼 Tu veux vendre sur la Marketplace ?</div>
            <div class="small opacity-75">
                Il suffit de contacter :
                ✉️ <a class="fw-semibold text-decoration-none" href="mailto:coachbrvm@gmail.com">coachbrvm@gmail.com</a>
                • 📞 Téléphone / WhatsApp : <a class="fw-semibold text-decoration-none" href="tel:+2250788035432">+2250788035432</a>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-dark fw-semibold"
               href="mailto:coachbrvm@gmail.com?subject=Devenir%20vendeur%20sur%20la%20Marketplace%20Coach%20BRVM">
                Envoyer un mail →
            </a>
            <a class="btn btn-sm btn-outline-dark fw-semibold"
               href="https://wa.me/2250788035432" target="_blank" rel="noopener">
                WhatsApp
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="row g-0">

            {{-- Cover --}}
            <div class="col-lg-5 bg-light">
                <div class="position-relative">

                    @if(!empty($isOwned) && $isOwned)
                        <span class="market-badge-owned">
                            <i class="bi bi-check2-circle"></i> Déjà acheté
                        </span>
                    @endif

                    <div class="ratio ratio-4x3 bg-light">
                        @if($product->cover_image_path)
                            <img src="{{ asset('storage/'.$product->cover_image_path) }}"
                                 alt="{{ $product->title }}"
                                 class="w-100 h-100 d-block"
                                 style="object-fit:cover; object-position:center;">
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                <div class="text-center">
                                    <div style="font-size:38px;">📦</div>
                                    <div class="small">Aucune cover</div>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Infos --}}
            <div class="col-lg-7">
                <div class="card-body p-4">

                    @php
                        $typeLabel = match($product->type) {
                            'book' => '📘 Livre (PDF)',
                            'video' => '🎬 Vidéo',
                            'software' => '🧩 Logiciel',
                            default => ucfirst($product->type),
                        };

                        // ✅ WhatsApp dev (visible pour TOUS si software + numéro)
                        $wa = preg_replace('/[^0-9]/', '', (string) ($product->support_whatsapp ?? ''));
                        $waText = rawurlencode("Bonjour, je suis intéressé par votre logiciel \"{$product->title}\" sur Coach BRVM Marketplace.");
                    @endphp

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge text-bg-light border">{{ $typeLabel }}</span>

                        @if($product->category)
                            <span class="badge text-bg-light border">
                                <i class="bi bi-folder2-open"></i> {{ $product->category->name }}
                            </span>
                        @endif

                        @if($product->is_featured)
                            <span class="badge text-bg-warning">⭐ En vedette</span>
                        @endif
                    </div>

                    <h2 class="fw-bold mb-2">{{ $product->title }}</h2>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="fs-4 fw-bold">
                            @if((int)$product->price <= 0)
                                Gratuit
                            @else
                                {{ number_format($product->price, 0, ',', ' ') }} FCFA
                            @endif
                        </div>

                        <span class="text-muted small">
                            Réf: <span class="font-monospace">{{ $product->slug }}</span>
                        </span>
                    </div>

                    @if($product->description)
                        <div class="text-muted mb-3" style="white-space:pre-line;">
                            {{ $product->description }}
                        </div>
                    @endif

                    <div class="text-muted small mb-3">
                        <i class="bi bi-lightning-charge"></i>
                        Après achat :
                        <strong>
                            {{ $product->type === 'video' ? 'lecture en ligne' : 'téléchargement immédiat' }}
                        </strong>.
                    </div>

                    {{-- ✅ CTA --}}
                    <div class="d-flex flex-wrap gap-2">
                        @auth
                            @if(!empty($isOwned) && $isOwned)

                                @if($product->type === 'video')
                                    <a href="{{ route('my.products.watch', $product) }}" class="btn btn-success">
                                        <i class="bi bi-play-circle"></i> Visualiser
                                    </a>
                                @else
                                    <a href="{{ route('my.products.download', $product) }}" class="btn btn-success">
                                        <i class="bi bi-download"></i> Télécharger
                                    </a>
                                @endif

                            @else
                                {{-- ✅ CinetPay --}}
                                {{-- <form method="POST" action="{{ route('marketplace.buy', $product) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-credit-card"></i> Payer avec CinetPay
                                    </button>
                                </form> --}}

                                {{-- ✅ Paystack --}}
                                <form method="POST" action="{{ route('paystack.marketplace.buy', $product) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-dark">
                                        <i class="bi bi-shield-check"></i> Payer
                                    </button>
                                </form>
                            @endif
                        @endauth

                        @guest
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Se connecter pour acheter
                            </a>
                        @endguest

                        {{-- ✅ Contacter le développeur : TOUJOURS visible (guest / payé / non payé) --}}
                        @if($product->type === 'software' && !empty($wa))
                            <a class="btn btn-outline-success"
                               href="https://wa.me/{{ $wa }}?text={{ $waText }}"
                               target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp"></i> Contacter le développeur
                            </a>
                        @endif

                        <a href="{{ route('marketplace.index') }}" class="btn btn-outline-secondary">
                            Continuer à explorer
                        </a>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-2">📦 Ce que tu reçois</h5>

                    @if($product->assets->isEmpty())
                        <div class="alert alert-light border mb-0">
                            Les fichiers / liens seront ajoutés par l’admin.
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($product->assets as $a)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">
                                            @if($a->kind === 'file')
                                                <i class="bi bi-file-earmark"></i>
                                            @else
                                                <i class="bi bi-link-45deg"></i>
                                            @endif
                                            {{ $a->label ?: ($a->kind === 'file' ? 'Fichier' : 'Lien') }}
                                        </div>

                                        <div class="text-muted small">
                                            @if(!empty($isOwned) && $isOwned)
                                                Accès débloqué ✅
                                            @else
                                                Accès après achat 🔒
                                            @endif
                                        </div>
                                    </div>

                                    @if(!empty($isOwned) && $isOwned)
                                        @if($product->type === 'video')
                                            <a href="{{ route('my.products.watch', $product) }}"
                                               class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-play-circle"></i> Accéder
                                            </a>
                                        @else
                                            <a href="{{ route('my.products.download', $product) }}"
                                               class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-unlock"></i> Accéder
                                            </a>
                                        @endif
                                    @else
                                        @auth
                                            <div class="d-flex flex-wrap gap-2">
                                                {{-- ✅ CinetPay --}}
                                                {{-- <form method="POST" action="{{ route('marketplace.buy', $product) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        Débloquer (CinetPay)
                                                    </button>
                                                </form> --}}

                                                {{-- ✅ Paystack --}}
                                                <form method="POST" action="{{ route('paystack.marketplace.buy', $product) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-dark btn-sm">
                                                        Débloquer
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                                                Débloquer
                                            </a>
                                        @endauth
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($isOwned) && $isOwned)
                            <div class="alert alert-success border mt-3 mb-0">
                                <i class="bi bi-shield-check"></i>

                                @if($product->type === 'video')
                                    Produit déjà payé : tu peux le re-visualiser à tout moment depuis
                                    <a class="fw-semibold" href="{{ route('my.products') }}">Mes produits</a>.
                                @else
                                    Produit déjà payé : tu peux le re-télécharger à tout moment depuis
                                    <a class="fw-semibold" href="{{ route('my.products') }}">Mes produits</a>.
                                @endif
                            </div>
                        @endif
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Produits similaires --}}
    @if(!empty($related) && $related->count())
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0">🔥 Produits similaires</h5>
                <a href="{{ route('marketplace.index') }}" class="small text-decoration-none">
                    Voir tout <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-3">
                @foreach($related as $p)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('marketplace.show', $p->slug) }}" class="text-decoration-none text-dark">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="ratio ratio-16x9 bg-light rounded-top overflow-hidden position-relative">
                                    @if($p->cover_image_path)
                                        <img src="{{ asset('storage/'.$p->cover_image_path) }}"
                                             alt="{{ $p->title }}"
                                             class="w-100 h-100 d-block"
                                             style="object-fit:cover; object-position:center;">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                            📦
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="fw-bold mb-1">{{ $p->title }}</div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            @if((int)$p->price <= 0)
                                                Gratuit
                                            @else
                                                {{ number_format($p->price, 0, ',', ' ') }} FCFA
                                            @endif
                                        </div>
                                        <span class="badge text-bg-light border">Voir</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
