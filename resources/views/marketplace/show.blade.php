{{-- resources/views/marketplace/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .mp-page { background: #060910; min-height: 100vh; }

    /* Breadcrumb */
    .mp-breadcrumb {
        padding: 20px 0 0;
        display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
    }
    .mp-breadcrumb a { color: #6B7590; text-decoration: none; transition: color .25s; }
    .mp-breadcrumb a:hover { color: #C9A84C; }
    .mp-breadcrumb span { color: #6B7590; }
    .mp-breadcrumb .current { color: #E8EAF0; }

    /* Vendeur callout */
    .mp-vendor-box {
        background: rgba(201,168,76,.05);
        border: 1px solid rgba(201,168,76,.12);
        border-radius: 4px; padding: 18px 22px;
        position: relative; overflow: hidden; margin-bottom: 28px;
    }
    .mp-vendor-box::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, #C9A84C, transparent);
    }
    .mp-vendor-title { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; margin-bottom:3px; }
    .mp-vendor-desc  { font-size:12px; color:#6B7590; }
    .mp-vendor-desc a { color:#C9A84C; text-decoration:none; }

    /* Boutons */
    .cb-btn-gold {
        display:inline-flex; align-items:center; gap:8px;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important; font-family:'Syne',sans-serif;
        font-weight:800; font-size:12px; letter-spacing:.07em; text-transform:uppercase;
        padding:11px 22px; border:none; border-radius:3px; cursor:pointer;
        text-decoration:none; transition:all .3s;
    }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3); transform:translateY(-1px); color:#050810 !important; }

    .cb-btn-outline {
        display:inline-flex; align-items:center; gap:8px;
        background:transparent; color:#E8EAF0 !important;
        font-family:'Syne',sans-serif; font-weight:600;
        font-size:12px; letter-spacing:.06em; text-transform:uppercase;
        padding:10px 18px; border:1px solid rgba(255,255,255,.12);
        border-radius:3px; text-decoration:none; transition:all .3s; cursor:pointer;
    }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }

    .cb-btn-green {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(15,207,164,.1); color:#0FCFA4 !important;
        font-family:'Syne',sans-serif; font-weight:700;
        font-size:12px; letter-spacing:.06em; text-transform:uppercase;
        padding:10px 18px; border:1px solid rgba(15,207,164,.2);
        border-radius:3px; text-decoration:none; transition:all .3s; cursor:pointer;
    }
    .cb-btn-green:hover { background:rgba(15,207,164,.16); border-color:rgba(15,207,164,.4); }

    .cb-btn-success {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(15,207,164,.12); color:#0FCFA4 !important;
        font-family:'Syne',sans-serif; font-weight:700;
        font-size:13px; letter-spacing:.06em; text-transform:uppercase;
        padding:13px 24px; border:1px solid rgba(15,207,164,.25);
        border-radius:3px; text-decoration:none; transition:all .3s; cursor:pointer;
    }
    .cb-btn-success:hover { background:rgba(15,207,164,.2); border-color:rgba(15,207,164,.45); }

    .cb-btn-pay {
        display:inline-flex; align-items:center; gap:8px;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important; font-family:'Syne',sans-serif;
        font-weight:800; font-size:14px; letter-spacing:.07em; text-transform:uppercase;
        padding:14px 28px; border:none; border-radius:3px; cursor:pointer;
        text-decoration:none; transition:all .3s; width:100%; justify-content:center;
    }
    .cb-btn-pay:hover { box-shadow:0 8px 28px rgba(201,168,76,.3); transform:translateY(-1px); }

    /* Main layout */
    .mp-show-grid {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 28px;
        align-items: start;
        margin-top: 28px;
    }
    @media(max-width:900px) {
        .mp-show-grid { grid-template-columns: 1fr; }
    }

    /* Cover card */
    .mp-cover-card {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; overflow: hidden;
        position: sticky; top: 80px;
    }
    .mp-cover-img {
        position: relative; background: #121A2C;
        display: flex; align-items: center; justify-content: center;
        min-height: 300px;
    }
    .mp-cover-img img {
        width: 100%; max-height: 360px;
        object-fit: contain; object-position: center;
        display: block;
    }
    .mp-cover-placeholder {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        color: #6B7590; font-size: 48px; padding: 60px;
    }
    .mp-badge-owned {
        position: absolute; top: 12px; left: 12px;
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(15,207,164,.9); color: #050810;
        font-family: 'Syne', sans-serif; font-size: 10px;
        font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        padding: 4px 10px; border-radius: 100px;
    }
    .mp-cover-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,.05); }

    /* Prix */
    .mp-price-big {
        font-family: 'Playfair Display', serif;
        font-size: 36px; font-weight: 900; color: #C9A84C; line-height: 1;
        margin-bottom: 4px;
    }
    .mp-price-big.free { color: #0FCFA4; }
    .mp-price-sub { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #6B7590; margin-bottom: 18px; }

    /* Info card */
    .mp-info-card {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px;
    }
    .mp-info-header {
        padding: 24px 28px 0;
        border-bottom: 1px solid rgba(255,255,255,.05);
        padding-bottom: 20px;
    }
    .mp-chip {
        font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        padding: 3px 9px; border-radius: 100px; display: inline-block; margin-right: 6px; margin-bottom: 6px;
    }
    .mp-chip-book     { background:rgba(99,179,237,.08);  color:#63B3ED; border:1px solid rgba(99,179,237,.2); }
    .mp-chip-video    { background:rgba(252,129,74,.08);  color:#FC814A; border:1px solid rgba(252,129,74,.2); }
    .mp-chip-software { background:rgba(160,120,255,.08); color:#B090FF; border:1px solid rgba(160,120,255,.2); }
    .mp-chip-cat      { background:rgba(255,255,255,.05); color:#6B7590; border:1px solid rgba(255,255,255,.08); }
    .mp-chip-feat     { background:rgba(201,168,76,.1);   color:#C9A84C; border:1px solid rgba(201,168,76,.2); }

    .mp-product-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(22px, 3.5vw, 34px); font-weight: 700;
        color: #E8EAF0; line-height: 1.15; margin: 14px 0 6px;
    }
    .mp-product-ref { font-family: 'Syne', sans-serif; font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: #6B7590; }

    .mp-info-body { padding: 24px 28px; }
    .mp-desc { font-size: 14px; color: #6B7590; line-height: 1.75; white-space: pre-line; margin-bottom: 20px; }

    .mp-delivery-box {
        background: rgba(15,207,164,.04);
        border: 1px solid rgba(15,207,164,.1);
        border-radius: 3px; padding: 12px 16px;
        font-family: 'Syne', sans-serif; font-size: 12px;
        font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
        color: #0FCFA4; margin-bottom: 20px;
        display: flex; align-items: center; gap: 8px;
    }

    /* Assets list */
    .mp-section-title {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
        color: #6B7590; margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .mp-section-title::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.06); }

    .mp-asset-item {
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,.04);
    }
    .mp-asset-item:last-child { border-bottom: none; }
    .mp-asset-name { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 600; color: #E8EAF0; }
    .mp-asset-sub  { font-size: 12px; color: #6B7590; margin-top: 2px; }

    /* Owned success */
    .mp-owned-box {
        background: rgba(15,207,164,.05);
        border: 1px solid rgba(15,207,164,.15);
        border-radius: 3px; padding: 14px 18px;
        margin-top: 16px;
        font-size: 13px; color: #6B7590; line-height: 1.65;
    }
    .mp-owned-box a { color: #0FCFA4; text-decoration: none; font-weight: 600; }
    .mp-owned-box a:hover { text-decoration: underline; }

    /* Preview images */
    .mp-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; margin-top: 12px; }
    .mp-preview-grid a img {
        width: 100%; border-radius: 3px;
        border: 1px solid rgba(255,255,255,.08);
        transition: border-color .25s;
    }
    .mp-preview-grid a:hover img { border-color: rgba(201,168,76,.3); }

    /* Related */
    .mp-related-card {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px; overflow: hidden;
        text-decoration: none; color: inherit;
        display: block; transition: all .3s;
    }
    .mp-related-card:hover { border-color: rgba(201,168,76,.2); transform: translateY(-3px); color: inherit; }
    .mp-related-img { aspect-ratio:16/9; background:#121A2C; overflow:hidden; }
    .mp-related-img img { width:100%;height:100%;object-fit:cover; transition:transform .35s; }
    .mp-related-card:hover .mp-related-img img { transform:scale(1.04); }
    .mp-related-body { padding:14px; }
    .mp-related-title { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; margin-bottom:8px; line-height:1.35; }
    .mp-related-price { font-family:'Playfair Display',serif; font-size:16px; font-weight:700; color:#C9A84C; }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(20px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="mp-page">
<div class="container pb-5" style="max-width:1100px;">

    {{-- Breadcrumb --}}
    <div class="mp-breadcrumb">
        <a href="{{ route('marketplace.index') }}">← Marketplace</a>
        <span>/</span>
        <span class="current">{{ \Illuminate\Support\Str::limit($product->title, 40) }}</span>
    </div>

    {{-- Vendeur callout --}}
    @php
        $isAuth   = auth()->check();
        $isVendor = $isAuth && (bool)(auth()->user()->is_vendor ?? false);
        $viewMode = session('view_mode', 'user');
        $typeLabel = match($product->type) {
            'book'     => '📘 Livre PDF',
            'video'    => '🎬 Vidéo',
            'software' => '🧩 Logiciel',
            default    => ucfirst($product->type),
        };
        $chipClass = match($product->type) {
            'book'     => 'mp-chip-book',
            'video'    => 'mp-chip-video',
            'software' => 'mp-chip-software',
            default    => 'mp-chip-cat',
        };
        $wa = preg_replace('/[^0-9]/', '', (string)($product->support_whatsapp ?? ''));
        $waText = rawurlencode("Bonjour, je suis intéressé par \"{$product->title}\" sur Coach BRVM Marketplace.");
        $assetsToShow = $product->type === 'video'
            ? $product->assets->filter(fn($a) => $a->kind === 'stream')->values()
            : $product->assets;
        $previewUrls = $previewUrls ?? [];
        $hasPreview  = $product->type === 'book' && empty($isOwned) && !empty($previewUrls);
    @endphp

    <div class="mp-vendor-box cbr">
        <div class="row align-items-center g-2">
            <div class="col-lg-8">
                <div class="mp-vendor-title">💼 Tu veux vendre tes produits ici ?</div>
                <div class="mp-vendor-desc">
                    ✉️ <a href="mailto:coachbrvm@gmail.com">coachbrvm@gmail.com</a>
                    &nbsp;·&nbsp; 📞 <a href="tel:+2250788035432">+225 07 88 03 54 32</a>
                </div>
            </div>
            <div class="col-lg-4 d-flex flex-wrap gap-2 justify-content-lg-end">
                @guest
                    <a href="{{ route('login') }}" class="cb-btn-gold">Se connecter</a>
                @else
                    @if(!$isVendor)
                        <form method="POST" action="{{ route('vendor.become') }}">
                            @csrf
                            <button type="submit" class="cb-btn-gold">🛍️ Devenir vendeur</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('switch.vendor') }}">
                            @csrf
                            <button type="submit" class="cb-btn-gold">
                                {{ $viewMode === 'user' ? '🛍️ Vue vendeur' : '🧾 Dashboard' }}
                            </button>
                        </form>
                    @endif
                    <a href="https://wa.me/2250788035432" target="_blank" class="cb-btn-outline">WhatsApp</a>
                @endguest
            </div>
        </div>
    </div>

    {{-- Main grid --}}
    <div class="mp-show-grid cbr">

        {{-- ── COLONNE GAUCHE : Cover + Prix + CTA ── --}}
        <div class="mp-cover-card">
            <div class="mp-cover-img">
                @if(!empty($isOwned))
                    <span class="mp-badge-owned">✓ Déjà acheté</span>
                @endif

                @if($product->cover_image_path)
                    <a href="{{ asset('storage/'.$product->cover_image_path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$product->cover_image_path) }}"
                             alt="{{ $product->title }}">
                    </a>
                @else
                    <div class="mp-cover-placeholder">📦</div>
                @endif
            </div>

            <div class="mp-cover-footer">
                {{-- Prix --}}
                @if(!empty($isOwned))
                    <div class="mp-price-big free" style="font-size:22px;">✓ Payé</div>
                    <div class="mp-price-sub">Accès débloqué</div>
                @elseif((int)$product->price <= 0)
                    <div class="mp-price-big free">Gratuit</div>
                    <div class="mp-price-sub">Accès libre</div>
                @else
                    <div class="mp-price-big">{{ number_format($product->price,0,',',' ') }} FCFA</div>
                    <div class="mp-price-sub">Paiement Mobile Money sécurisé</div>
                @endif

                {{-- CTA principal --}}
                @auth
                    @if(!empty($isOwned))
                        @if($product->type === 'video')
                            <a href="{{ route('my.products.watch', $product) }}" class="cb-btn-success" style="width:100%;justify-content:center;">
                                <i class="bi bi-play-circle"></i> Visualiser maintenant
                            </a>
                        @else
                            <a href="{{ route('my.products.download', $product) }}" class="cb-btn-success" style="width:100%;justify-content:center;">
                                <i class="bi bi-download"></i> Télécharger
                            </a>
                        @endif
                    @else
                        <form method="POST" action="{{ route('paystack.marketplace.buy', $product) }}">
                            @csrf
                            <button type="submit"
                                    class="cb-btn-pay js-pay-btn"
                                    data-mp-id="{{ $product->id }}"
                                    data-mp-price="{{ (int)$product->price }}"
                                    data-mp-currency="XOF">
                                <i class="bi bi-shield-check"></i> Payer maintenant
                            </button>
                        </form>
                    @endif
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="cb-btn-gold" style="width:100%;justify-content:center;">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter pour acheter
                    </a>
                @endguest

                @if($product->type === 'software' && !empty($wa))
                    <a class="cb-btn-outline mt-2" style="width:100%;justify-content:center;"
                       href="https://wa.me/{{ $wa }}?text={{ $waText }}"
                       target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp"></i> Contacter le développeur
                    </a>
                @endif

                <div style="margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.05);
                            font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.08em;
                            text-transform:uppercase;color:#6B7590;text-align:center;">
                    Après achat :
                    <strong style="color:#E8EAF0;">
                        {{ $product->type === 'video' ? 'Lecture en ligne' : 'Téléchargement immédiat' }}
                    </strong>
                </div>
            </div>
        </div>

        {{-- ── COLONNE DROITE : Infos ── --}}
        <div>
            <div class="mp-info-card">
                <div class="mp-info-header">
                    <div>
                        <span class="mp-chip {{ $chipClass }}">{{ $typeLabel }}</span>
                        @if($product->category)
                            <span class="mp-chip mp-chip-cat">{{ $product->category->name }}</span>
                        @endif
                        @if(!empty($product->pages_count) && $product->type === 'book')
                            <span class="mp-chip mp-chip-cat">{{ (int)$product->pages_count }} pages</span>
                        @endif
                        @if($product->is_featured)
                            <span class="mp-chip mp-chip-feat">⭐ En vedette</span>
                        @endif
                    </div>

                    <h1 class="mp-product-title">{{ $product->title }}</h1>
                    <div class="mp-product-ref">Réf : {{ $product->slug }}</div>
                </div>

                <div class="mp-info-body">

                    @if($product->description)
                        <div class="mp-section-title">Description</div>
                        <p class="mp-desc">{{ $product->description }}</p>
                    @endif

                    <div class="mp-delivery-box">
                        <i class="bi bi-lightning-charge"></i>
                        {{ $product->type === 'video' ? 'Lecture en ligne immédiate après achat' : 'Téléchargement immédiat après achat' }}
                    </div>

                    {{-- Aperçu PDF --}}
                    @if($hasPreview)
                        <div class="mp-section-title" style="margin-top:20px;">Aperçu (5 premières pages)</div>
                        <div class="mp-preview-grid">
                            @foreach($previewUrls as $url)
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Aperçu">
                                </a>
                            @endforeach
                        </div>
                        <p style="font-size:12px;color:#6B7590;margin-top:8px;">
                            Aperçu basse résolution. Accès complet après achat.
                        </p>
                    @endif

                    {{-- Assets --}}
                    <div class="mp-section-title" style="margin-top:24px;">Ce que tu reçois</div>

                    @if($assetsToShow->isEmpty())
                        <div style="font-size:13px;color:#6B7590;padding:16px;background:rgba(255,255,255,.02);border-radius:3px;border:1px solid rgba(255,255,255,.05);">
                            @if($product->type === 'video')
                                La vidéo sera disponible en lecture après validation.
                            @else
                                Les fichiers seront ajoutés par l'admin.
                            @endif
                        </div>
                    @else
                        @foreach($assetsToShow as $a)
                            <div class="mp-asset-item">
                                <div>
                                    <div class="mp-asset-name">
                                        <i class="bi {{ $a->kind === 'file' ? 'bi-file-earmark' : 'bi-link-45deg' }}"></i>
                                        {{ $a->label ?: ($product->type === 'video' ? 'Vidéo (stream)' : ($a->kind === 'file' ? 'Fichier' : 'Lien')) }}
                                    </div>
                                    <div class="mp-asset-sub">
                                        @if(!empty($isOwned)) Accès débloqué ✅
                                        @else Accès après achat 🔒
                                        @endif
                                    </div>
                                </div>

                                @if(!empty($isOwned))
                                    @if($product->type === 'video')
                                        <a href="{{ route('my.products.watch', $product) }}" class="cb-btn-green" style="font-size:11px;padding:7px 14px;">
                                            <i class="bi bi-play-circle"></i> Voir
                                        </a>
                                    @else
                                        <a href="{{ route('my.products.download', $product) }}" class="cb-btn-green" style="font-size:11px;padding:7px 14px;">
                                            <i class="bi bi-unlock"></i> Accéder
                                        </a>
                                    @endif
                                @else
                                    @auth
                                        <form method="POST" action="{{ route('paystack.marketplace.buy', $product) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="cb-btn-outline js-pay-btn"
                                                    style="font-size:11px;padding:7px 14px;"
                                                    data-mp-id="{{ $product->id }}"
                                                    data-mp-price="{{ (int)$product->price }}"
                                                    data-mp-currency="XOF">
                                                Débloquer
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                                            Débloquer
                                        </a>
                                    @endauth
                                @endif
                            </div>
                        @endforeach
                    @endif

                    {{-- Owned success --}}
                    @if(!empty($isOwned))
                        <div class="mp-owned-box">
                            <i class="bi bi-shield-check" style="color:#0FCFA4;"></i>
                            Produit déjà payé —
                            @if($product->type === 'video')
                                re-visualise-le à tout moment depuis
                            @else
                                re-télécharge-le à tout moment depuis
                            @endif
                            <a href="{{ route('my.products') }}">Mes produits</a>.
                        </div>
                    @endif

                </div>
            </div>

            {{-- Back --}}
            <div class="mt-3">
                <a href="{{ route('marketplace.index') }}" class="cb-btn-outline">
                    ← Continuer à explorer
                </a>
            </div>
        </div>

    </div>

    {{-- ── PRODUITS SIMILAIRES ── --}}
    @if(!empty($related) && $related->count())
        <div class="mt-5 cbr">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#6B7590;margin:0;display:flex;align-items:center;gap:10px;">
                    <span style="width:28px;height:1px;background:#6B7590;display:inline-block;"></span>
                    Produits similaires
                </p>
                <a href="{{ route('marketplace.index') }}" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                    Voir tout →
                </a>
            </div>
            <div class="row g-3">
                @foreach($related as $p)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('marketplace.show', $p->slug) }}" class="mp-related-card">
                            <div class="mp-related-img">
                                @if($p->cover_image_path)
                                    <img src="{{ asset('storage/'.$p->cover_image_path) }}" alt="{{ $p->title }}">
                                @else
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#6B7590;font-size:28px;">📦</div>
                                @endif
                            </div>
                            <div class="mp-related-body">
                                <div class="mp-related-title">{{ $p->title }}</div>
                                <div class="mp-related-price">
                                    @if((int)$p->price <= 0) Gratuit
                                    @else {{ number_format($p->price,0,',',' ') }} F
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Scroll reveal
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: 0.07 });
    cbEls.forEach(el => cbObs.observe(el));

    // Facebook Pixel
    if (typeof fbq !== 'function') return;

    // ViewContent au chargement de la page produit
    fbq('track', 'ViewContent', {
        content_type: 'product',
        content_ids: ['{{ $product->id }}'],
        content_name: @json($product->title),
        content_category: @json(optional($product->category)->name),
        value: {{ (int) $product->price }},
        currency: 'XOF'
    });

    // ── InitiateCheckout au clic sur "Payer maintenant" / "Débloquer" ──
    // e.preventDefault() bloque la redirection immédiate vers Paystack
    // setTimeout de 400ms laisse le temps au pixel d'envoyer l'événement à Meta
    // puis form.submit() déclenche la redirection normalement
    document.querySelectorAll('.js-pay-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (btn.dataset.fired === '1') return;
            btn.dataset.fired = '1';
            e.preventDefault();
            var form = btn.closest('form');
            fbq('track', 'InitiateCheckout', {
                content_type: 'product',
                content_ids: [btn.dataset.mpId],
                value: parseInt(btn.dataset.mpPrice || '0', 10) || 0,
                currency: 'XOF'
            });
            setTimeout(function() { form.submit(); }, 400);
        });
    });

    // Purchase après retour Paystack (via session Laravel)
    @if(session()->has('fb_purchase'))
        fbq('track', 'Purchase', @json(session('fb_purchase')));
    @endif
});
</script>
@endpush
