@extends('layouts.app')

@push('styles')
<style>
    .pack-page { background:#060910; min-height:100vh; }

    .pack-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.12) 0%, transparent 60%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 72px 0 56px;
        position: relative; overflow: hidden;
    }
    .pack-hero-grid {
        position:absolute; inset:0;
        background-image: linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .pack-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(201,168,76,.08); border: 1px solid rgba(201,168,76,.28);
        border-radius: 100px; padding: 7px 18px; margin-bottom: 24px;
    }
    .pack-badge-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #C9A84C;
        animation: packPulse 1.5s ease-in-out infinite;
    }
    @keyframes packPulse {
        0%,100% { opacity:1; box-shadow: 0 0 0 0 rgba(201,168,76,.5); }
        50%      { opacity:.7; box-shadow: 0 0 0 7px rgba(201,168,76,0); }
    }
    .pack-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(32px, 6vw, 56px);
        font-weight: 900; color: #E8EAF0; line-height: 1.08; margin-bottom: 12px;
    }
    .pack-title em { font-style: italic; color: #C9A84C; }

    .pack-price-old {
        font-size: 20px; color: #6B7590; text-decoration: line-through; margin-right: 14px;
    }
    .pack-price-new {
        font-family: 'Playfair Display', serif;
        font-size: clamp(36px, 6vw, 52px); font-weight: 900; color: #C9A84C;
    }
    .pack-price-economy {
        display: inline-block;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .1em; text-transform: uppercase;
        background: rgba(15,207,164,.1); color: #0FCFA4;
        border: 1px solid rgba(15,207,164,.25); border-radius: 100px;
        padding: 4px 14px; margin-left: 10px; vertical-align: middle;
    }

    .pack-item {
        display: flex; align-items: flex-start; gap: 14px;
        background: #0C1120; border: 1px solid rgba(201,168,76,.1);
        border-radius: 6px; padding: 20px 22px; transition: border-color .3s;
    }
    .pack-item:hover { border-color: rgba(201,168,76,.25); }
    .pack-item-icon { font-size: 22px; flex-shrink: 0; margin-top: 2px; }
    .pack-item-title {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
        color: #E8EAF0; margin-bottom: 4px;
    }
    .pack-item-desc { font-size: 12.5px; color: #6B7590; line-height: 1.6; }
    .pack-item-price {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        color: #C9A84C; margin-top: 6px;
    }

    .cb-btn-gold-lg {
        display: inline-flex; align-items: center; gap: 10px;
        background: linear-gradient(135deg,#C9A84C,#9B6B15);
        color: #050810 !important;
        font-family: 'Syne', sans-serif; font-weight: 800;
        font-size: 13px; letter-spacing: .07em; text-transform: uppercase;
        padding: 16px 40px; border: none; border-radius: 4px;
        cursor: pointer; text-decoration: none; transition: all .3s;
    }
    .cb-btn-gold-lg:hover {
        box-shadow: 0 10px 32px rgba(201,168,76,.35);
        transform: translateY(-2px);
    }

    .pack-guarantee {
        font-size: 12px; color: #6B7590;
        display: flex; align-items: center; gap: 6px; margin-top: 14px;
    }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.12s; }
</style>
@endpush

@section('content')
<div class="pack-page">

    {{-- HERO --}}
    <div class="pack-hero">
        <div class="pack-hero-grid"></div>
        <div class="container cbr" style="max-width:860px; position:relative; z-index:1; text-align:center;">

            <div class="pack-badge">
                <span class="pack-badge-dot"></span>
                <span style="font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#C9A84C;">Pack Exclusif</span>
            </div>

            <h1 class="pack-title">Pack BRVM <em>Complet</em></h1>
            <p style="font-size:15px; color:#6B7590; line-height:1.75; max-width:580px; margin:0 auto 32px;">
                Tout ce qu'il faut pour investir à la BRVM — 3 formations complètes, un groupe privé d'accompagnement et une aide pour ouvrir votre compte titre.
            </p>

            {{-- Prix --}}
            <div style="margin-bottom:16px;">
                <span class="pack-price-old">50 000 FCFA</span>
                <span class="pack-price-new">30 000 FCFA</span>
                <span class="pack-price-economy">−40%</span>
            </div>
            <p style="font-size:12px; color:#6B7590; margin-bottom:40px;">Accès à vie · Paiement unique · Livraison immédiate</p>

            {{-- Message si déjà tous les cours --}}
            @auth
                @if($hasAllCourses)
                    <div style="background:rgba(15,207,164,.06); border:1px solid rgba(15,207,164,.18); border-radius:6px; padding:14px 20px; margin-bottom:32px; font-size:13px; color:#0FCFA4; max-width:560px; margin-left:auto; margin-right:auto;">
                        ℹ️ Vous possédez déjà les 3 cours — achetez le Pack uniquement pour accéder au groupe WhatsApp privé d'accompagnement.
                    </div>
                @endif
            @endauth

            {{-- CTA --}}
            @auth
                <form method="POST" action="{{ route('pack.buy.paystack') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="cb-btn-gold-lg">
                        🎓 Obtenir le Pack — 30 000 FCFA
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}?redirect={{ urlencode(route('pack.show')) }}" class="cb-btn-gold-lg">
                    🎓 Obtenir le Pack — 30 000 FCFA
                </a>
            @endauth

            <div class="pack-guarantee">
                <span>🔒</span>
                <span>Paiement sécurisé via Paystack · Accès instantané après confirmation</span>
            </div>

            @if(session('error'))
                <div style="background:rgba(255,107,107,.08); border:1px solid rgba(255,107,107,.2); border-radius:4px; padding:14px 18px; margin-top:20px; font-size:13px; color:#FF6B6B;">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    {{-- CE QUI EST INCLUS --}}
    <div class="container cbr cbr2" style="max-width:860px; padding:64px 15px;">

        <p style="font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#C9A84C; text-align:center; margin-bottom:10px;">Ce qui est inclus</p>
        <h2 style="font-family:'Playfair Display',serif; font-size:clamp(22px,4vw,34px); font-weight:900; color:#E8EAF0; text-align:center; margin-bottom:40px;">
            3 formations + accompagnement <em style="color:#C9A84C;">personnalisé</em>
        </h2>

        <div class="row g-3 mb-5">

            <div class="col-md-6">
                <div class="pack-item">
                    <div class="pack-item-icon">🎓</div>
                    <div>
                        <div class="pack-item-title">Cours Débutant BRVM</div>
                        <div class="pack-item-desc">Comprendre les bases de la bourse régionale, ouvrir un compte-titres, placer tes premiers ordres.</div>
                        <div class="pack-item-price">Valeur : 5 000 FCFA</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="pack-item">
                    <div class="pack-item-icon">📈</div>
                    <div>
                        <div class="pack-item-title">Cours Intermédiaire BRVM</div>
                        <div class="pack-item-desc">Analyse fondamentale, lecture des états financiers, construction d'un portefeuille équilibré.</div>
                        <div class="pack-item-price">Valeur : 5 000 FCFA</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="pack-item">
                    <div class="pack-item-icon">🛠️</div>
                    <div>
                        <div class="pack-item-title">Cours Pratique : outils & portefeuille virtuel</div>
                        <div class="pack-item-desc">Maîtrise des outils d'analyse et simulation de portefeuille en conditions réelles.</div>
                        <div class="pack-item-price">Valeur : 5 000 FCFA</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="pack-item" style="border-color:rgba(15,207,164,.2);">
                    <div class="pack-item-icon">💬</div>
                    <div>
                        <div class="pack-item-title" style="color:#0FCFA4;">Groupe WhatsApp privé</div>
                        <div class="pack-item-desc">Accès au groupe d'accompagnement privé — échanges directs avec l'équipe Coach BRVM.</div>
                        <div class="pack-item-price" style="color:#0FCFA4;">Lien unique envoyé par email</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="pack-item" style="border-color:rgba(15,207,164,.2);">
                    <div class="pack-item-icon">🏦</div>
                    <div>
                        <div class="pack-item-title" style="color:#0FCFA4;">Aide à l'ouverture de compte titre</div>
                        <div class="pack-item-desc">Accompagnement personnalisé pour choisir votre SGI et ouvrir votre compte titre BRVM.</div>
                        <div class="pack-item-price" style="color:#0FCFA4;">Inclus dans le Pack</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Récap prix + CTA bas de page --}}
        <div style="background:#0C1120; border:1px solid rgba(201,168,76,.15); border-radius:8px; padding:clamp(28px,4vw,44px); text-align:center;">
            <p style="font-size:13px; color:#6B7590; margin-bottom:8px;">Valeur totale : <s>50 000 FCFA</s></p>
            <div style="margin-bottom:6px;">
                <span class="pack-price-new" style="font-size:clamp(32px,5vw,46px);">30 000 FCFA</span>
                <span class="pack-price-economy">Économisez 20 000 F</span>
            </div>
            <p style="font-size:12px; color:#6B7590; margin-bottom:28px;">Accès à vie · Paiement unique</p>

            @auth
                <form method="POST" action="{{ route('pack.buy.paystack') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="cb-btn-gold-lg">
                        🎓 Obtenir le Pack — 30 000 FCFA
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}?redirect={{ urlencode(route('pack.show')) }}" class="cb-btn-gold-lg">
                    🎓 Obtenir le Pack — 30 000 FCFA
                </a>
            @endauth
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    const els = document.querySelectorAll('.cbr');
    const io  = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('on'); }), { threshold: .1 });
    els.forEach(el => io.observe(el));
</script>
@endpush
