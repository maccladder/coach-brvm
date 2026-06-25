{{-- ════════════════════════════════════════════════
     resources/views/my-products/index.blade.php
     (ou products/index.blade.php selon ton chemin)
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .myp-page { background: #060910; min-height: 100vh; }

    .myp-hero {
        background: #0C1120;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 36px 0 28px;
    }
    .myp-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #C9A84C;
        display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
    }
    .myp-hero-tag::before { content:''; width:28px; height:1px; background:#C9A84C; }
    .myp-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px, 4vw, 36px); font-weight: 900; color: #E8EAF0;
    }

    /* Cards */
    .myp-card {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px; padding: 20px; height: 100%;
        display: flex; gap: 16px;
        transition: all .32s; position: relative; overflow: hidden;
    }
    .myp-card::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background:linear-gradient(90deg,#C9A84C,transparent);
        opacity:0; transition:opacity .32s;
    }
    .myp-card:hover { border-color:rgba(201,168,76,.2); transform:translateY(-2px); }
    .myp-card:hover::before { opacity:1; }

    .myp-card-thumb {
        width: 80px; height: 80px; flex-shrink: 0;
        border-radius: 3px; overflow: hidden;
        background: #121A2C;
        border: 1px solid rgba(255,255,255,.06);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
    }
    .myp-card-thumb img { width:100%; height:100%; object-fit:cover; }

    .myp-card-info { flex: 1; min-width: 0; }
    .myp-card-title {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: #E8EAF0; margin-bottom: 4px; line-height: 1.35;
    }
    .myp-card-meta { font-size: 11px; color: #6B7590; margin-bottom: 12px; }
    .myp-card-meta span { display: inline-flex; align-items: center; gap: 4px; margin-right: 12px; }

    .myp-chip {
        font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        padding: 2px 8px; border-radius: 100px; margin-right: 6px;
    }
    .myp-chip-book  { background:rgba(99,179,237,.08);  color:#63B3ED; border:1px solid rgba(99,179,237,.2); }
    .myp-chip-video { background:rgba(252,129,74,.08);  color:#FC814A; border:1px solid rgba(252,129,74,.2); }
    .myp-chip-soft  { background:rgba(160,120,255,.08); color:#B090FF; border:1px solid rgba(160,120,255,.2); }
    .myp-chip-game  { background:rgba(120,80,255,.08);  color:#A070FF; border:1px solid rgba(120,80,255,.2); }

    .cb-btn-purple {
        display:inline-flex; align-items:center; gap:7px;
        background:rgba(120,80,255,.12); color:#A070FF !important;
        font-family:'Syne',sans-serif; font-weight:700;
        font-size:11px; letter-spacing:.06em; text-transform:uppercase;
        padding:7px 14px; border:1px solid rgba(120,80,255,.25);
        border-radius:3px; text-decoration:none; transition:all .3s;
    }
    .cb-btn-purple:hover { background:rgba(120,80,255,.2); border-color:rgba(120,80,255,.45); }

    .myp-card-actions { display: flex; flex-wrap: wrap; gap: 8px; }

    /* Boutons */
    .cb-btn-gold {
        display:inline-flex; align-items:center; gap:7px;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important; font-family:'Syne',sans-serif;
        font-weight:800; font-size:11px; letter-spacing:.07em; text-transform:uppercase;
        padding:8px 16px; border:none; border-radius:3px; cursor:pointer;
        text-decoration:none; transition:all .3s;
    }
    .cb-btn-gold:hover { box-shadow:0 5px 16px rgba(201,168,76,.3); transform:translateY(-1px); }

    .cb-btn-green {
        display:inline-flex; align-items:center; gap:7px;
        background:rgba(15,207,164,.1); color:#0FCFA4 !important;
        font-family:'Syne',sans-serif; font-weight:700;
        font-size:11px; letter-spacing:.06em; text-transform:uppercase;
        padding:7px 14px; border:1px solid rgba(15,207,164,.2);
        border-radius:3px; text-decoration:none; transition:all .3s;
    }
    .cb-btn-green:hover { background:rgba(15,207,164,.16); border-color:rgba(15,207,164,.4); }

    .cb-btn-outline {
        display:inline-flex; align-items:center; gap:7px;
        background:transparent; color:#E8EAF0 !important;
        font-family:'Syne',sans-serif; font-weight:600;
        font-size:11px; letter-spacing:.06em; text-transform:uppercase;
        padding:7px 12px; border:1px solid rgba(255,255,255,.12);
        border-radius:3px; text-decoration:none; transition:all .3s;
    }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }

    /* Empty */
    .myp-empty {
        text-align:center; padding:80px 20px;
        background:rgba(12,17,32,.6);
        border:1px solid rgba(201,168,76,.08); border-radius:4px;
    }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.06s; }
</style>
@endpush

@section('content')
<div class="myp-page">

    <div class="myp-hero">
        <div class="container" style="max-width:1050px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <p class="myp-hero-tag">Mon espace</p>
                    <h1 class="myp-hero-title">🧾 Mes produits</h1>
                    <p style="font-size:14px;color:#6B7590;font-weight:300;margin-top:4px;">
                        Tes achats — livres, vidéos, logiciels et jeux.
                    </p>
                </div>
                <a href="{{ route('marketplace.index') }}" class="cb-btn-gold">
                    🛍️ Marketplace
                </a>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1050px;">

        @if($products->isEmpty())
            <div class="myp-empty cbr">
                <div style="font-size:36px;margin-bottom:14px;opacity:.4;">🛍️</div>
                <div style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:20px;">
                    Aucun produit acheté pour l'instant
                </div>
                <a href="{{ route('marketplace.index') }}" class="cb-btn-gold">
                    Découvrir la Marketplace →
                </a>
            </div>
        @else
            <div class="row g-3 cbr">
                @foreach($products as $p)
                    @php
                        $chipClass = match($p->type) {
                            'video'    => 'myp-chip-video',
                            'software' => 'myp-chip-soft',
                            'game'     => 'myp-chip-game',
                            default    => 'myp-chip-book',
                        };
                        $typeLabel = match($p->type) {
                            'video'    => '🎬 Vidéo',
                            'software' => '🧩 Logiciel',
                            'game'     => '🎮 Jeu',
                            default    => '📘 Livre PDF',
                        };
                    @endphp
                    <div class="col-md-6">
                        <div class="myp-card">

                            {{-- Miniature --}}
                            <div class="myp-card-thumb">
                                @if($p->cover_image_path)
                                    <img src="{{ asset('storage/'.$p->cover_image_path) }}"
                                         alt="{{ $p->title }}">
                                @else
                                    📦
                                @endif
                            </div>

                            {{-- Infos --}}
                            <div class="myp-card-info">
                                <div class="myp-card-title">{{ $p->title }}</div>
                                <div class="myp-card-meta">
                                    <span class="myp-chip {{ $chipClass }}">{{ $typeLabel }}</span>
                                    @if($p->category)
                                        <span style="color:#6B7590;font-size:11px;">{{ $p->category->name }}</span>
                                    @endif
                                </div>

                                <div class="myp-card-actions">
                                    @if($p->type === 'game')
                                        @if($p->slug === 'garba-master')
                                            <a href="{{ route('jeu') }}" class="cb-btn-purple">
                                                🎮 Jouer
                                            </a>
                                        @else
                                            <a href="{{ route('my.products.play', $p) }}" class="cb-btn-purple">
                                                🎮 Jouer
                                            </a>
                                        @endif
                                    @elseif($p->type === 'video')
                                        <a href="{{ route('my.products.watch', $p) }}"
                                           class="cb-btn-green">
                                            ▶ Visualiser
                                        </a>
                                    @else
                                        <a href="{{ route('my.products.download', $p) }}"
                                           class="cb-btn-gold">
                                            <i class="bi bi-download"></i> Télécharger
                                        </a>
                                    @endif

                                    <a href="{{ route('marketplace.show', $p->slug) }}"
                                       class="cb-btn-outline">
                                        Voir la fiche
                                    </a>
                                </div>

                                @if($p->type === 'game')
                                    <div style="font-size:11px;color:#6B7590;margin-top:8px;font-family:'Syne',sans-serif;letter-spacing:.06em;">
                                        🔒 Jouer en ligne · sans téléchargement
                                    </div>
                                @elseif($p->type === 'video')
                                    <div style="font-size:11px;color:#6B7590;margin-top:8px;font-family:'Syne',sans-serif;letter-spacing:.06em;">
                                        🔒 Lecture en ligne · sans téléchargement
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => {
            if(e.isIntersecting) el.classList.add('on');
        }, { threshold: .06 }).observe(el);
    });
</script>
@endpush
