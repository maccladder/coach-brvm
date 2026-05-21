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

    .btn-whatsapp {
        display: inline-flex; align-items: center; gap: 9px;
        background: rgba(37,211,102,.1); border: 1px solid rgba(37,211,102,.35);
        color: #25D366 !important; text-decoration: none;
        font-family: 'Syne', sans-serif; font-size: 12.5px; font-weight: 700;
        letter-spacing: .04em; padding: 11px 26px; border-radius: 4px;
        transition: all .28s;
    }
    .btn-whatsapp:hover {
        background: rgba(37,211,102,.18); border-color: rgba(37,211,102,.6);
        box-shadow: 0 6px 22px rgba(37,211,102,.18); transform: translateY(-1px);
    }
    .btn-whatsapp svg { flex-shrink:0; }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.12s; }

    .course-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;height:100%;display:flex;flex-direction:column;transition:all .32s; }
    .course-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.35); }
    .course-cover { position:relative;background:#121A2C;aspect-ratio:16/9;overflow:hidden; }
    .course-cover img { width:100%;height:100%;object-fit:cover;transition:transform .4s; }
    .course-card:hover .course-cover img { transform:scale(1.03); }
    .course-play { position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54px;height:54px;background:rgba(0,0,0,.6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;backdrop-filter:blur(4px);transition:all .25s; }
    .course-card:hover .course-play { background:rgba(201,168,76,.8);transform:translate(-50%,-50%) scale(1.1); }
    .course-badge-pack { position:absolute;top:10px;left:10px;font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:rgba(201,168,76,.92);color:#050810;padding:4px 10px;border-radius:100px; }
    .course-body { padding:18px 20px;flex:1;display:flex;flex-direction:column; }
    .course-title { font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#E8EAF0;margin-bottom:6px;line-height:1.35; }
    .course-desc { font-size:12px;color:#6B7590;line-height:1.6; }
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

            {{-- WhatsApp contact --}}
            <div style="margin-top:28px; padding-top:24px; border-top:1px solid rgba(255,255,255,.06);">
                <p style="font-size:12.5px; color:#6B7590; margin-bottom:12px;">Une question avant d'acheter ? On vous répond sur WhatsApp.</p>
                <a href="https://wa.me/2250767123451?text=Bonjour%2C%20j%27ai%20une%20question%20sur%20le%20Pack%20BRVM"
                   target="_blank" rel="noopener" class="btn-whatsapp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                    </svg>
                    Contacter sur WhatsApp
                </a>
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

        {{-- 3 cartes cours --}}
        <div class="row g-4 mb-4">
            @foreach($courses as $course)
                @php
                    $covers = [
                        'brvm-debutant'                                      => asset('courses/brvm-debutant.jpg'),
                        'brvm-intermediaire'                                 => asset('courses/brvm-intermediaire.jpg'),
                        'brvm-pratique-outils-analyse-portefeuille-virtuel' => asset('courses/brvm-pratique.jpg'),
                    ];
                    $cover = $covers[$course->slug] ?? asset('courses/brvm-debutant.jpg');
                @endphp
                <div class="col-md-4">
                    <div class="course-card">
                        <div class="course-cover">
                            <img src="{{ $cover }}" alt="{{ $course->title }}">
                            <div class="course-play">▶</div>
                            <span class="course-badge-pack">✓ Inclus dans le Pack</span>
                        </div>
                        <div class="course-body">
                            <div class="course-title">{{ $course->title }}</div>
                            <div class="course-desc">{{ \Illuminate\Support\Str::limit($course->description, 120) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bonus : WhatsApp + compte titre --}}
        <div class="row g-3 mb-5">

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

            {{-- WhatsApp contact --}}
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(255,255,255,.06);">
                <p style="font-size:12.5px; color:#6B7590; margin-bottom:12px;">Une question avant d'acheter ? On vous répond sur WhatsApp.</p>
                <a href="https://wa.me/2250767123451?text=Bonjour%2C%20j%27ai%20une%20question%20sur%20le%20Pack%20BRVM"
                   target="_blank" rel="noopener" class="btn-whatsapp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                    </svg>
                    Contacter sur WhatsApp
                </a>
            </div>
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
