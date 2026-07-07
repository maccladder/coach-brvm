{{-- resources/views/marketplace/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    /* ── Page base ── */
    .mp-page { background: var(--cb-paper); min-height: 100vh; }

    /* ── Hero marketplace ── */
    .mp-hero {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(176,134,46,.08) 0%, transparent 55%),
            var(--cb-paper);
        border-bottom: 1px solid var(--cb-border);
        padding: 56px 0 40px;
        position: relative; overflow: hidden;
    }
    .mp-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(176,134,46,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(176,134,46,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .mp-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 600; letter-spacing: .2em; text-transform: uppercase;
        color: var(--cb-forest); display: flex; align-items: center; gap: 10px;
        margin-bottom: 14px;
    }
    .mp-hero-tag::before { content:''; width:28px; height:1px; background:var(--cb-forest); }
    .mp-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(32px, 5vw, 52px); font-weight: 900;
        color: var(--cb-ink); line-height: 1.08; margin-bottom: 12px;
    }
    .mp-hero-title em { font-style: italic; color: var(--cb-gold); }
    .mp-hero-desc { font-size: 15px; color: var(--cb-muted); font-weight: 300; line-height: 1.7; max-width: 520px; }

    /* ── Vendeur callout ── */
    .mp-vendor-box {
        background: rgba(176,134,46,.05);
        border: 1px solid rgba(176,134,46,.14);
        border-radius: 4px; padding: 20px 24px;
        position: relative; overflow: hidden;
    }
    .mp-vendor-box::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, var(--cb-gold), transparent);
    }
    .mp-vendor-title {
        font-family: 'Syne', sans-serif; font-size: 13px;
        font-weight: 700; color: var(--cb-ink); margin-bottom: 4px;
    }
    .mp-vendor-desc { font-size: 13px; color: var(--cb-muted); line-height: 1.6; }
    .mp-vendor-desc a { color: var(--cb-gold); text-decoration: none; }
    .mp-vendor-desc a:hover { text-decoration: underline; }

    /* ── Filtres ── */
    .mp-filters {
        background: var(--cb-card);
        border: 1px solid var(--cb-border);
        border-radius: 4px; padding: 20px 24px;
    }
    .mp-filters label {
        font-family: 'Syne', sans-serif; font-size: 10px;
        font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
        color: var(--cb-muted); display: block; margin-bottom: 6px;
    }
    .mp-filters input,
    .mp-filters select {
        background: var(--cb-paper) !important;
        border: 1px solid var(--cb-border) !important;
        color: var(--cb-ink) !important;
        border-radius: 3px !important;
        font-family: 'DM Sans', sans-serif !important;
        font-size: 13px !important;
        padding: 9px 12px !important;
        width: 100%;
        transition: border-color .25s;
        outline: none;
    }
    .mp-filters input:focus,
    .mp-filters select:focus {
        border-color: rgba(176,134,46,.4) !important;
        box-shadow: 0 0 0 3px rgba(176,134,46,.07) !important;
    }
    .mp-filters input::placeholder { color: var(--cb-muted) !important; }
    .mp-filters select option { background: var(--cb-card); color: var(--cb-ink); }

    /* ── Boutons ── */
    .cb-btn-gold {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, var(--cb-gold), #7A5412);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 12px; letter-spacing: .07em;
        text-transform: uppercase; padding: 10px 20px;
        border: none; border-radius: 3px; cursor: pointer;
        text-decoration: none; transition: all .3s;
    }
    .cb-btn-gold:hover { box-shadow: 0 6px 20px rgba(176,134,46,.3); transform: translateY(-1px); color: #050810 !important; }

    .cb-btn-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: transparent; color: var(--cb-ink) !important;
        font-family: 'Syne', sans-serif; font-weight: 600;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid var(--cb-border);
        border-radius: 3px; text-decoration: none; transition: all .3s;
        cursor: pointer;
    }
    .cb-btn-outline:hover { border-color: var(--cb-gold); color: var(--cb-gold) !important; background: rgba(176,134,46,.05); }

    .cb-btn-green {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(15,92,67,.08); color: var(--cb-forest) !important;
        font-family: 'Syne', sans-serif; font-weight: 700;
        font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
        padding: 9px 18px; border: 1px solid rgba(15,92,67,.2);
        border-radius: 3px; text-decoration: none; transition: all .3s;
        cursor: pointer;
    }
    .cb-btn-green:hover { background: rgba(15,92,67,.14); border-color: rgba(15,92,67,.4); }

    .cb-btn-filter {
        background: rgba(176,134,46,.1);
        border: 1px solid rgba(176,134,46,.2);
        color: var(--cb-gold); border-radius: 3px;
        padding: 9px 14px; cursor: pointer;
        transition: all .25s; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .cb-btn-filter:hover { background: rgba(176,134,46,.18); }

    /* ── Produit card ── */
    .mp-card {
        background: var(--cb-card);
        border: 1px solid var(--cb-border);
        border-radius: 4px; overflow: hidden;
        transition: all .35s; height: 100%;
        display: flex; flex-direction: column;
        text-decoration: none; color: inherit;
    }
    .mp-card:hover {
        border-color: rgba(176,134,46,.2);
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
        color: inherit;
    }
    .mp-card-img {
        position: relative;
        background: var(--cb-paper);
        aspect-ratio: 16/9;
        overflow: hidden;
    }
    .mp-card-img img {
        width: 100%; height: 100%;
        object-fit: cover; object-position: center;
        transition: transform .4s;
    }
    .mp-card:hover .mp-card-img img { transform: scale(1.03); }

    .mp-card-img-placeholder {
        width: 100%; height: 100%;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        color: var(--cb-muted); font-size: 28px;
    }

    .mp-badge-owned {
        position: absolute; top: 10px; left: 10px; z-index: 5;
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(15,92,67,.9);
        color: #fff; font-family: 'Syne', sans-serif;
        font-size: 10px; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; padding: 4px 10px;
        border-radius: 100px;
        backdrop-filter: blur(6px);
    }
    .mp-badge-featured {
        position: absolute; top: 10px; right: 10px; z-index: 5;
        background: rgba(176,134,46,.9); color: #050810;
        font-family: 'Syne', sans-serif; font-size: 9px;
        font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        padding: 3px 8px; border-radius: 100px;
    }

    .mp-card-body { padding: 18px 18px 16px; flex: 1; display: flex; flex-direction: column; }

    .mp-card-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .mp-chip {
        font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        padding: 3px 8px; border-radius: 100px;
    }
    .mp-chip-book     { background:rgba(43,127,196,.08);  color:#2B7FC4; border:1px solid rgba(43,127,196,.2); }
    .mp-chip-video    { background:rgba(200,90,40,.08);   color:#C85A28; border:1px solid rgba(200,90,40,.2); }
    .mp-chip-software { background:rgba(120,80,200,.08);  color:#7850C8; border:1px solid rgba(120,80,200,.2); }
    .mp-chip-game     { background:rgba(80,50,180,.08);   color:#5032B4; border:1px solid rgba(80,50,180,.2); }
    .mp-chip-cat      { background:rgba(15,92,67,.04);    color:var(--cb-muted); border:1px solid var(--cb-border); }

    .mp-card-title {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: var(--cb-ink); line-height: 1.4; margin-bottom: 8px;
        flex: 1;
    }
    .mp-card-desc { font-size: 12px; color: var(--cb-muted); line-height: 1.6; margin-bottom: 14px; }

    .mp-card-footer {
        display: flex; justify-content: space-between;
        align-items: center; gap: 8px; flex-wrap: wrap;
        margin-top: auto; padding-top: 14px;
        border-top: 1px solid var(--cb-border);
    }
    .mp-card-price {
        font-family: 'Playfair Display', serif;
        font-size: 18px; font-weight: 700; color: var(--cb-gold);
    }
    .mp-card-price.free  { color: var(--cb-forest); }
    .mp-card-price.owned { color: var(--cb-forest); font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; }

    .mp-card-actions { display: flex; gap: 6px; }
    .mp-btn-sm {
        display: inline-flex; align-items: center; gap: 5px;
        font-family: 'Syne', sans-serif; font-size: 10px;
        font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        padding: 7px 12px; border-radius: 3px; text-decoration: none;
        transition: all .25s; border: 1px solid; cursor: pointer;
    }
    .mp-btn-see  { background: rgba(176,134,46,.08); color: var(--cb-gold); border-color: rgba(176,134,46,.2); }
    .mp-btn-see:hover { background: rgba(176,134,46,.16); color: var(--cb-gold); }
    .mp-btn-dl   { background: rgba(15,92,67,.08); color: var(--cb-forest); border-color: rgba(15,92,67,.2); }
    .mp-btn-dl:hover { background: rgba(15,92,67,.16); color: var(--cb-forest); }

    /* ── Empty state ── */
    .mp-empty {
        text-align: center; padding: 80px 20px;
        background: var(--cb-card);
        border: 1px solid var(--cb-border);
        border-radius: 4px;
    }
    .mp-empty-icon { font-size: 48px; margin-bottom: 16px; opacity: .5; }
    .mp-empty-title {
        font-family: 'Syne', sans-serif; font-size: 16px;
        font-weight: 700; color: var(--cb-ink); margin-bottom: 8px;
    }
    .mp-empty-desc { font-size: 14px; color: var(--cb-muted); }

    /* ── Pagination override ── */
    .pagination .page-link {
        background: var(--cb-card) !important;
        border-color: var(--cb-border) !important;
        color: var(--cb-muted) !important;
        font-family: 'Syne', sans-serif;
        font-size: 12px;
    }
    .pagination .page-link:hover {
        background: rgba(176,134,46,.08) !important;
        color: var(--cb-gold) !important;
        border-color: rgba(176,134,46,.2) !important;
    }
    .pagination .active .page-link {
        background: linear-gradient(135deg, var(--cb-gold), #7A5412) !important;
        border-color: transparent !important;
        color: #050810 !important;
    }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(20px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="mp-page">

    {{-- ── HERO ── --}}
    <div class="mp-hero">
        <div class="mp-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <p class="mp-hero-tag">Contenus premium</p>
                    <h1 class="mp-hero-title">
                        La <em>Marketplace</em><br>Boursiv
                    </h1>
                    <p class="mp-hero-desc">
                        Livres PDF, vidéos et logiciels pour progresser sur la BRVM.
                        Achat instantané, paiement sécurisé par <strong style="color:var(--cb-ink);">Mobile Money</strong>.
                    </p>
                </div>
                <div class="col-lg-5 d-flex justify-content-lg-end gap-2 flex-wrap">
                    @auth
                        <a href="{{ route('my.products') }}" class="cb-btn-outline">
                            <i class="bi bi-bag-check"></i> Mes produits
                        </a>
                    @endauth
                    @guest
                        <a href="{{ route('register') }}" class="cb-btn-gold">✦ S'inscrire gratuitement</a>
                        <a href="{{ route('login') }}" class="cb-btn-outline">Se connecter</a>
                    @endguest
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        {{-- ── VENDEUR CALLOUT ── --}}
        @php
            $isAuth   = auth()->check();
            $isVendor = $isAuth && (bool)(auth()->user()->is_vendor ?? false);
            $viewMode = session('view_mode', 'user');
        @endphp

        <div class="mp-vendor-box mb-4 cbr">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="mp-vendor-title">💼 Tu veux vendre tes produits ici ?</div>
                    <div class="mp-vendor-desc">
                        Publie tes livres PDF, vidéos ou logiciels sur la marketplace Boursiv.<br>
                        ✉️ <a href="mailto:boursivoire@gmail.com">boursivoire@gmail.com</a>
                        &nbsp;·&nbsp; 📞 <a href="tel:+2250788035432">+225 07 88 03 54 32</a>
                    </div>
                </div>
                <div class="col-lg-4 d-flex flex-wrap gap-2 justify-content-lg-end">
                    @guest
                        <a href="{{ route('login') }}" class="cb-btn-gold">Se connecter</a>
                        <a href="{{ route('register') }}" class="cb-btn-outline">S'inscrire</a>
                    @else
                        @if(!$isVendor)
                            <form method="POST" action="{{ route('vendor.become') }}">
                                @csrf
                                <button type="submit" class="cb-btn-gold">🛍️ Devenir vendeur</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route($viewMode === 'user' ? 'switch.vendor' : 'switch.vendor') }}">
                                @csrf
                                <button type="submit" class="cb-btn-gold">
                                    {{ $viewMode === 'user' ? '🛍️ Vue vendeur' : '🧾 Dashboard vendeur' }}
                                </button>
                            </form>
                        @endif
                        <a href="mailto:boursivoire@gmail.com?subject=Devenir%20vendeur" class="cb-btn-outline">Mail →</a>
                        <a href="https://wa.me/2250788035432" target="_blank" class="cb-btn-outline">WhatsApp</a>
                    @endguest
                </div>
            </div>
        </div>

        {{-- ── FILTRES ── --}}
        <form method="GET" action="{{ route('marketplace.index') }}" class="mp-filters mb-5 cbr">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label>Recherche</label>
                    <input type="text" name="s" value="{{ request('s') }}" placeholder="Titre produit...">
                </div>
                <div class="col-md-3">
                    <label>Type</label>
                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="book"     @selected(request('type')==='book')>📘 Livre (PDF)</option>
                        <option value="video"    @selected(request('type')==='video')>🎬 Vidéo</option>
                        <option value="software" @selected(request('type')==='software')>🧩 Logiciel</option>
                        <option value="game"     @selected(request('type')==='game')>🎮 Jeu</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Catégorie</label>
                    <select name="category">
                        <option value="">Toutes</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)request('category')===(string)$c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="cb-btn-filter">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
                @if(request('s') || request('type') || request('category'))
                    <div class="col-12 d-flex justify-content-end">
                        <a href="{{ route('marketplace.index') }}"
                           style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.08em;
                                  text-transform:uppercase;color:var(--cb-muted);text-decoration:none;">
                            ✕ Réinitialiser les filtres
                        </a>
                    </div>
                @endif
            </div>
        </form>

        {{-- ── PRODUITS ── --}}
        @if($products->isEmpty())
            <div class="mp-empty cbr">
                <div class="mp-empty-icon">🛍️</div>
                <div class="mp-empty-title">Aucun produit disponible</div>
                <div class="mp-empty-desc">Les premiers contenus apparaîtront ici très bientôt.</div>
            </div>
        @else
            <div class="row g-3 cbr">
                @foreach($products as $p)
                    @php
                        $typeLabel = match($p->type) {
                            'book'     => '📘 Livre PDF',
                            'video'    => '🎬 Vidéo',
                            'software' => '🧩 Logiciel',
                            'game'     => '🎮 Jeu',
                            default    => ucfirst($p->type),
                        };
                        $chipClass = match($p->type) {
                            'book'     => 'mp-chip-book',
                            'video'    => 'mp-chip-video',
                            'software' => 'mp-chip-software',
                            'game'     => 'mp-chip-game',
                            default    => 'mp-chip-cat',
                        };
                        $isOwnedCard = auth()->check()
                            && !empty($ownedIds)
                            && in_array($p->id, $ownedIds, true);
                    @endphp

                    <div class="col-md-6 col-lg-4">
                        <div class="mp-card">

                            {{-- Cover --}}
                            <div class="mp-card-img">
                                @if($isOwnedCard)
                                    <span class="mp-badge-owned">✓ Déjà acheté</span>
                                @endif
                                @if($p->is_featured)
                                    <span class="mp-badge-featured">⭐ En vedette</span>
                                @endif

                                @if($p->cover_image_path)
                                    <img src="{{ asset('storage/'.$p->cover_image_path) }}"
                                         alt="{{ $p->title }}">
                                @else
                                    <div class="mp-card-img-placeholder">
                                        <span>📦</span>
                                        <span style="font-size:11px;margin-top:6px;">Aucune cover</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Body --}}
                            <div class="mp-card-body">
                                <div class="mp-card-chips">
                                    <span class="mp-chip {{ $chipClass }}">{{ $typeLabel }}</span>
                                    @if($p->category)
                                        <span class="mp-chip mp-chip-cat">{{ $p->category->name }}</span>
                                    @endif
                                </div>

                                <div class="mp-card-title">{{ $p->title }}</div>

                                @if($p->description)
                                    <div class="mp-card-desc">
                                        {{ \Illuminate\Support\Str::limit($p->description, 85) }}
                                    </div>
                                @endif

                                <div class="mp-card-footer">
                                    <div>
                                        @if($isOwnedCard)
                                            <span class="mp-card-price owned">✓ Payé</span>
                                        @elseif((int)$p->price <= 0)
                                            <span class="mp-card-price free">Gratuit</span>
                                        @else
                                            <span class="mp-card-price">{{ number_format($p->price,0,',',' ') }} F</span>
                                        @endif
                                    </div>
                                    <div class="mp-card-actions">
                                        @if($isOwnedCard)
                                            @if($p->type === 'game')
                                                <a href="{{ route('my.products.play', $p) }}" class="mp-btn-sm mp-btn-dl">
                                                    🎮 Jouer
                                                </a>
                                            @elseif($p->type === 'video')
                                                <a href="{{ route('my.products.watch', $p) }}" class="mp-btn-sm mp-btn-dl">
                                                    <i class="bi bi-play-circle"></i> Voir
                                                </a>
                                            @else
                                                <a href="{{ route('my.products.download', $p) }}" class="mp-btn-sm mp-btn-dl">
                                                    <i class="bi bi-download"></i> DL
                                                </a>
                                            @endif
                                        @endif
                                        <a href="{{ route('marketplace.show', $p->slug) }}" class="mp-btn-sm mp-btn-see">
                                            Voir <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-5 d-flex justify-content-center cbr">
                {{ $products->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: 0.07 });
    cbEls.forEach(el => cbObs.observe(el));
</script>
@endpush
