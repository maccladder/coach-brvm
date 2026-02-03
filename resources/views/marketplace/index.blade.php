@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="fw-bold mb-1">🛍️ Marketplace</h2>
            <div class="text-muted">Livres (PDF), vidéos et logiciels disponibles sur Coach BRVM.</div>
        </div>

        @auth
            <a href="{{ route('my.products') }}" class="btn btn-outline-dark">
                <i class="bi bi-bag-check"></i> Mes produits
            </a>
        @endauth
    </div>

    {{-- ✅ NOUVEAU : Devenir vendeur --}}
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="fw-semibold">💼 Tu veux vendre tes produits ici ?</div>
            <div class="small opacity-75">
                Pour devenir vendeur sur la Marketplace Coach BRVM, contacte-nous :
                <span class="d-block">
                    ✉️ <a class="fw-semibold text-decoration-none" href="mailto:coachbrvm@gmail.com">coachbrvm@gmail.com</a>
                    • 📞 Téléphone / WhatsApp : <a class="fw-semibold text-decoration-none" href="tel:+2250788035432">+2250788035432</a>
                </span>
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

    {{-- Filtres --}}
    <form method="GET" action="{{ route('marketplace.index') }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Recherche</label>
                    <input type="text"
                           name="s"
                           value="{{ request('s') }}"
                           class="form-control"
                           placeholder="Titre produit...">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Tous</option>
                        <option value="book" @selected(request('type')==='book')>📘 Livre (PDF)</option>
                        <option value="video" @selected(request('type')==='video')>🎬 Vidéo</option>
                        <option value="software" @selected(request('type')==='software')>🧩 Logiciel</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Catégorie</label>
                    <select name="category" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)request('category')===(string)$c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit" title="Filtrer">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <a href="{{ route('marketplace.index') }}" class="btn btn-link text-decoration-none">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Résultats --}}
    @if($products->isEmpty())
        <div class="alert alert-light border">
            Aucun produit disponible pour l’instant.
        </div>
    @else
        <div class="row g-3">
            @foreach($products as $p)
                @php
                    $typeLabel = match($p->type) {
                        'book' => '📘 Livre (PDF)',
                        'video' => '🎬 Vidéo',
                        'software' => '🧩 Logiciel',
                        default => ucfirst($p->type),
                    };

                    $isOwnedCard = auth()->check()
                        && !empty($ownedIds)
                        && in_array($p->id, $ownedIds, true);
                @endphp

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">

                        {{-- ✅ Cover --}}
                        <div class="rounded-top overflow-hidden bg-light">
                            <div class="position-relative">

                                @if($isOwnedCard)
                                    <span class="market-badge-owned">
                                        <i class="bi bi-check2-circle"></i> Déjà acheté
                                    </span>
                                @endif

                                <div class="ratio ratio-16x9 bg-light">
                                    @if($p->cover_image_path)
                                        <img src="{{ asset('storage/'.$p->cover_image_path) }}"
                                             alt="{{ $p->title }}"
                                             class="w-100 h-100 d-block"
                                             style="object-fit:cover; object-position:center;">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                            <div class="text-center">
                                                <div style="font-size:28px;">📦</div>
                                                <div class="small">Aucune cover</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>

                        <div class="card-body d-flex flex-column">
                            {{-- Badges --}}
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-light border">{{ $typeLabel }}</span>

                                @if($p->category)
                                    <span class="badge text-bg-light border">
                                        <i class="bi bi-folder2-open"></i> {{ $p->category->name }}
                                    </span>
                                @endif

                                @if($p->is_featured)
                                    <span class="badge text-bg-warning">⭐ En vedette</span>
                                @endif
                            </div>

                            <div class="fw-bold mb-1" style="min-height:44px;">
                                {{ $p->title }}
                            </div>

                            @if($p->description)
                                <div class="text-muted small mb-2">
                                    {{ \Illuminate\Support\Str::limit($p->description, 90) }}
                                </div>
                            @endif

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="fw-bold">
                                    @if($isOwnedCard)
                                        <span class="text-success">
                                            <i class="bi bi-unlock"></i> Payé
                                        </span>
                                    @else
                                        @if((int)$p->price <= 0)
                                            Gratuit
                                        @else
                                            {{ number_format($p->price, 0, ',', ' ') }} FCFA
                                        @endif
                                    @endif
                                </div>

                                <div class="d-flex gap-2">
                                    {{-- ✅ Si déjà acheté: vidéo => visualiser / sinon => télécharger --}}
                                    @if($isOwnedCard)
                                        @if($p->type === 'video')
                                            <a href="{{ route('my.products.watch', $p) }}"
                                               class="btn btn-success btn-sm"
                                               title="Visualiser">
                                                <i class="bi bi-play-circle"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('my.products.download', $p) }}"
                                               class="btn btn-primary btn-sm"
                                               title="Télécharger">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif
                                    @endif

                                    <a href="{{ route('marketplace.show', $p->slug) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        Voir <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

</div>
@endsection
