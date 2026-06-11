{{-- resources/views/lettre-ci/landing.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page { background: var(--cb-paper); min-height: 100vh; }

    .cb-cta-primary {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, var(--cb-gold), #9B6B15);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 13px; letter-spacing: .06em; text-transform: uppercase;
        padding: 14px 28px; border-radius: 3px; text-decoration: none; transition: all .3s; border: none;
        width: 100%; justify-content: center; cursor: pointer;
    }
    .cb-cta-primary:hover { box-shadow: 0 10px 32px rgba(176,134,46,.3); transform: translateY(-2px); color: #050810 !important; }

    .lci-pay-box {
        max-width: 520px; margin: 60px auto;
        background: var(--cb-card);
        border: 1px solid rgba(176,134,46,.14);
        border-radius: 6px; overflow: hidden;
        position: relative;
    }
    .lci-pay-box::before {
        content:''; display:block; height:2px;
        background: linear-gradient(90deg, var(--cb-gold), var(--cb-forest), transparent);
    }
    .lci-pay-inner { padding: 40px 40px 36px; }

    .lci-pay-icon { font-size: 52px; margin-bottom: 20px; display: block; text-align: center; }
    .lci-pay-title {
        font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 900;
        color: var(--cb-ink); text-align: center; margin-bottom: 8px;
    }
    .lci-pay-title em { font-style: italic; color: var(--cb-gold); }
    .lci-pay-sub { font-size: 14px; color: var(--cb-muted); text-align: center; line-height: 1.65; margin-bottom: 28px; }

    .lci-price-row {
        display: flex; align-items: center; gap: 14px;
        background: rgba(176,134,46,.05); border: 1px solid rgba(176,134,46,.12);
        border-radius: 4px; padding: 16px 20px; margin-bottom: 24px;
    }
    .lci-price-row .amount {
        font-family: 'Playfair Display', serif; font-size: 34px; font-weight: 900; color: var(--cb-gold); line-height: 1;
    }
    .lci-price-row .details { flex: 1; font-size: 12px; color: var(--cb-muted); line-height: 1.5; }
    .lci-price-row .details strong { color: var(--cb-ink); display: block; font-size: 13px; font-family: 'Syne', sans-serif; font-weight: 700; }

    .lci-perks { list-style: none; padding: 0; margin: 20px 0 0; border-top: 1px solid var(--cb-border); }
    .lci-perks li {
        font-size: 13px; color: var(--cb-muted); padding: 9px 0;
        border-bottom: 1px solid var(--cb-border);
        display: flex; align-items: center; gap: 10px;
    }
    .lci-perks li:last-child { border-bottom: none; }
    .lci-perks li::before { content: '✓'; color: var(--cb-forest); font-weight: 700; flex-shrink: 0; }

    .lci-breadcrumb {
        padding: 24px 0 0; display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .08em; text-transform: uppercase;
    }
    .lci-breadcrumb a { color: var(--cb-muted); text-decoration: none; transition: color .25s; }
    .lci-breadcrumb a:hover { color: var(--cb-gold); }
    .lci-breadcrumb span { color: var(--cb-muted); }
    .lci-breadcrumb .current { color: var(--cb-ink); }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="container">
        <nav class="lci-breadcrumb">
            <a href="{{ route('marketplace.index') }}">Marketplace</a>
            <span>›</span>
            <a href="{{ route('lettreci.showcase') }}">LettreCI</a>
            <span>›</span>
            <span class="current">Accès</span>
        </nav>

        @if(session('warning'))
        <div class="alert alert-warning mt-4">{{ session('warning') }}</div>
        @endif

        <div class="lci-pay-box cbr mt-4">
            <div class="lci-pay-inner">
                <span class="lci-pay-icon">📄</span>
                <h1 class="lci-pay-title">Débloquer <em>LettreCI</em></h1>
                <p class="lci-pay-sub">
                    Générez vos lettres officielles en quelques secondes grâce à l'IA Claude —
                    adaptées au contexte ivoirien, exportables en PDF.
                </p>

                @php $lciPrice = number_format(config('services.lettreci.price', 5000), 0, ',', ' '); @endphp
                <div class="lci-price-row">
                    <div class="amount">{{ $lciPrice }}</div>
                    <div class="details">
                        <strong>FCFA · Accès à vie</strong>
                        15 modèles · Générations illimitées · Export PDF
                    </div>
                </div>

                <form method="POST" action="{{ route('lettreci.checkout') }}">
                    @csrf
                    <button type="submit" class="cb-cta-primary">
                        Payer maintenant — {{ $lciPrice }} FCFA →
                    </button>
                </form>

                <ul class="lci-perks">
                    <li>15 types de lettres couvrant toutes les situations</li>
                    <li>Génération IA en <strong style="color:var(--cb-ink);">moins de 30 secondes</strong></li>
                    <li>Édition libre avant export PDF professionnel</li>
                    <li>Paiement sécurisé via Paystack (carte, Mobile Money)</li>
                    <li>Accès permanent — <strong style="color:var(--cb-ink);">aucun abonnement</strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) e.target.classList.add('on'); }, {threshold:.1}).observe(el);
    });
</script>
@endpush
