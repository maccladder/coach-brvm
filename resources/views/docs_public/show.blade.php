{{-- resources/views/docs/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .doc-show-page { background: #060910; min-height: 100vh; }

    .doc-breadcrumb { padding:24px 0 0; display:flex; align-items:center; gap:10px; font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; }
    .doc-breadcrumb a { color:#6B7590; text-decoration:none; transition:color .25s; }
    .doc-breadcrumb a:hover { color:#C9A84C; }
    .doc-breadcrumb span { color:rgba(107,117,144,.4); }

    .doc-show-card {
        background: #0C1120; border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; overflow: hidden;
        margin-top: 24px; margin-bottom: 48px;
    }
    .doc-show-card::before { content:''; display:block; height:3px; background:linear-gradient(90deg,#C9A84C,rgba(15,207,164,.6),transparent); }

    .doc-show-body { padding: 32px 36px; }

    .doc-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
    .doc-chip { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:3px 10px; border-radius:100px; }
    .doc-chip-type { background:rgba(201,168,76,.08); color:#C9A84C; border:1px solid rgba(201,168,76,.18); }
    .doc-chip-country { background:rgba(15,207,164,.08); color:#0FCFA4; border:1px solid rgba(15,207,164,.18); }

    .doc-show-title { font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,38px); font-weight:700; color:#E8EAF0; line-height:1.15; margin-bottom:14px; }
    .doc-show-desc { font-size:14px; color:#6B7590; line-height:1.75; font-weight:300; margin-bottom:24px; }

    .doc-price-row { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .doc-price-big { font-family:'Playfair Display',serif; font-size:38px; font-weight:900; color:#C9A84C; line-height:1; }
    .doc-price-sub { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#6B7590; }

    .doc-divider { height:1px; background:rgba(255,255,255,.05); margin:24px 0; }

    .doc-delivery-box { display:flex; align-items:center; gap:10px; padding:14px 18px; background:rgba(15,207,164,.04); border:1px solid rgba(15,207,164,.1); border-radius:3px; margin-top:20px; font-family:'Syne',sans-serif; font-size:12px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:#0FCFA4; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#C9A84C,#9B6B15); color:#050810 !important; font-family:'Syne',sans-serif; font-weight:800; font-size:13px; letter-spacing:.07em; text-transform:uppercase; padding:13px 28px; border:none; border-radius:3px; cursor:pointer; text-decoration:none; transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 8px 24px rgba(201,168,76,.3); transform:translateY(-1px); }

    .cb-btn-green { display:inline-flex; align-items:center; gap:8px; background:rgba(15,207,164,.1); color:#0FCFA4 !important; font-family:'Syne',sans-serif; font-weight:700; font-size:13px; letter-spacing:.06em; text-transform:uppercase; padding:12px 24px; border:1px solid rgba(15,207,164,.2); border-radius:3px; text-decoration:none; transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,207,164,.16); border-color:rgba(15,207,164,.4); }

    .cb-btn-outline { display:inline-flex; align-items:center; gap:8px; background:transparent; color:#E8EAF0 !important; font-family:'Syne',sans-serif; font-weight:600; font-size:13px; letter-spacing:.06em; text-transform:uppercase; padding:12px 22px; border:1px solid rgba(255,255,255,.12); border-radius:3px; text-decoration:none; transition:all .3s; cursor:pointer; }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }

    @media(max-width:600px){ .doc-show-body { padding:24px 20px; } }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="doc-show-page">
<div class="container" style="max-width:860px;">

    <div class="doc-breadcrumb cbr">
        <a href="{{ route('docs.public.index') }}">← Documents</a>
        <span>/</span>
        <span style="color:#E8EAF0;">{{ \Illuminate\Support\Str::limit($document->title, 40) }}</span>
    </div>

    <div class="doc-show-card cbr">
        <div class="doc-show-body">

            <div class="doc-chips">
                <span class="doc-chip doc-chip-type">{{ $types[$document->type] ?? $document->type }}</span>
                @if($document->country)
                    <span class="doc-chip doc-chip-country">{{ $document->country }}</span>
                @endif
            </div>

            <h1 class="doc-show-title">{{ $document->title }}</h1>

            @if($document->description)
                <p class="doc-show-desc">{{ $document->description }}</p>
            @endif

            <div class="doc-divider"></div>

            <div class="doc-price-row">
                <div>
                    <div class="doc-price-big">{{ number_format($document->price,0,',',' ') }} <span style="font-size:18px;color:#6B7590;font-family:'DM Sans',sans-serif;font-weight:300;">FCFA</span></div>
                    <div class="doc-price-sub" style="margin-top:4px;">Paiement Mobile Money sécurisé</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @auth
                    @if($document->isBoughtBy(auth()->user()))
                        <a href="{{ route('documents.download', $document->id) }}" class="cb-btn-green">
                            <i class="bi bi-download"></i> Télécharger
                        </a>
                        <a href="{{ route('documents.mine') }}" class="cb-btn-outline">
                            <i class="bi bi-folder2-open"></i> Mes documents
                        </a>
                    @else
                        <form method="POST" action="{{ route('documents.buy', $document) }}" class="m-0">
                            @csrf
                            <button type="submit" class="cb-btn-gold">
                                <i class="bi bi-shield-check"></i> Acheter maintenant
                            </button>
                        </form>
                    @endif
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="cb-btn-gold">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter pour acheter
                    </a>
                @endguest
            </div>

            <div class="doc-delivery-box">
                <i class="bi bi-lightning-charge"></i>
                Après paiement, le document est disponible dans <strong style="margin-left:4px;">Mon espace → Mes documents</strong>
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-between pb-5 cbr">
        <a href="{{ route('docs.public.index') }}" class="cb-btn-outline">← Retour aux documents</a>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, {threshold:.07}).observe(el);
    });
</script>
@endpush
