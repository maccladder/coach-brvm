{{-- ═══════════════════════════════════════════════════════════
     formations-brvm.blade.php
═══════════════════════════════════════════════════════════ --}}
{{-- resources/views/sections/formations-brvm.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .form-page { background: var(--cb-paper); min-height: 100vh; }
    .form-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(15,92,67,.08) 0%,transparent 55%),var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:48px 0 36px;position:relative;overflow:hidden; }
    .form-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(15,92,67,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(15,92,67,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .form-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-forest);display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .form-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-forest); }
    .form-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:var(--cb-ink);line-height:1.08;margin-bottom:10px; }
    .form-hero-title em { font-style:italic;color:var(--cb-gold); }

    .form-info-box { background:rgba(15,92,67,.05);border:1px solid rgba(15,92,67,.15);border-radius:4px;padding:20px 24px;margin-bottom:32px; }
    .form-info-box-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-forest);margin-bottom:10px; }
    .form-info-box-text { font-size:13.5px;color:var(--cb-muted);line-height:1.75; }
    .form-info-box-text strong { color:var(--cb-ink); }

    .course-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;overflow:hidden;margin-bottom:24px; }
    .course-card.featured { border-color:rgba(176,134,46,.2); }
    .course-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,var(--cb-gold),transparent); }
    .course-card.featured::before { background:linear-gradient(90deg,var(--cb-gold),var(--cb-forest),transparent); }
    .course-card-body { padding:28px 32px; }
    .course-level { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:3px 10px;border-radius:100px;margin-bottom:14px;display:inline-block; }
    .level-debut { background:rgba(15,92,67,.08);color:var(--cb-forest);border:1px solid rgba(15,92,67,.2); }
    .level-inter { background:rgba(176,134,46,.08);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }
    .level-prat  { background:rgba(99,179,237,.1);color:#2B7FC4;border:1px solid rgba(99,179,237,.3); }
    .course-title { font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,28px);font-weight:700;color:var(--cb-ink);margin-bottom:8px; }
    .course-subtitle { font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:var(--cb-gold);margin-bottom:14px; }
    .course-desc { font-size:14px;color:var(--cb-muted);line-height:1.75;margin-bottom:16px; }
    .course-features { list-style:none;padding:0;margin-bottom:20px; }
    .course-features li { font-size:13px;color:var(--cb-muted);padding:6px 0;border-bottom:1px solid var(--cb-border);display:flex;align-items:center;gap:10px; }
    .course-features li::before { content:'✔';color:var(--cb-forest);font-weight:700;flex-shrink:0; }
    .course-hint { font-size:12px;color:var(--cb-muted);margin-top:10px;font-style:italic; }

    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--cb-gold),#7A5412);color:var(--cb-paper) !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:8px;background:rgba(15,92,67,.08);color:var(--cb-forest) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 20px;border:1px solid rgba(15,92,67,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,92,67,.14);border-color:rgba(15,92,67,.4); }

    .course-video-wrap { background:var(--cb-paper);border:1px solid var(--cb-border);border-radius:3px;overflow:hidden;display:flex;align-items:center;justify-content:center; }
    .course-video-wrap video { max-width:100%;border-radius:3px; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="form-hero">
        <div class="form-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="form-hero-tag">Formations en ligne</p>
            <h1 class="form-hero-title">🎓 Monte en niveau, <em>à ton rythme</em></h1>
            <p style="font-size:14px;color:var(--cb-muted);font-weight:300;max-width:520px;">Cours débutant, intermédiaire, pratique — disponibles 24h/24, à vie une fois achetés.</p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        <div class="form-info-box cbr">
            <div class="form-info-box-title">✅ Deux façons d'acheter (accès à vie dans les deux cas)</div>
            <div class="form-info-box-text">
                • <strong>Sur Boursiv</strong> : paiement par <strong>Mobile Money</strong> (Wave, Orange Money, MTN, Moov).<br>
                • <strong>Sur Udemy</strong> : paiement par <strong>carte bancaire</strong> (plateforme internationale).<br>
                <span style="color:var(--cb-muted);">Dans les deux cas, l'accès est à vie après achat.</span>
            </div>
        </div>

        <!-- DÉBUTANT -->
        <div class="course-card cbr">
            <div class="course-card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <span class="course-level level-debut">🚀 Niveau Débutant</span>
                        <h2 class="course-title">Investir à la BRVM</h2>
                        <div class="course-subtitle">« Le guide du débutant »</div>
                        <p class="course-desc">Idéal si tu démarres de zéro : comprendre la BRVM, ouvrir ton compte-titres, passer tes premiers ordres en toute confiance.</p>
                        <ul class="course-features">
                            <li>Durée ~ 1h de vidéo</li>
                            <li>Explications simples, exemples concrets</li>
                            <li>Parfait pour éviter les erreurs de débutant</li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="cb-btn-gold">Acheter (Mobile Money)</a>
                            <a href="https://www.udemy.com/course/investir-a-la-brvm-le-guide-du-debutant/?couponCode=E71A16F6B15F7654FC27" target="_blank" class="cb-btn-green">Acheter sur Udemy</a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="course-video-wrap">
                            <video class="img-fluid" controls preload="metadata" style="max-height:200px;width:100%;">
                                <source src="{{ asset('previews/brvm-debutant-preview.mp4') }}" type="video/mp4">
                            </video>
                        </div>
                        <div class="course-hint">🎬 Aperçu ~3 min du cours</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- INTERMÉDIAIRE -->
        <div class="course-card cbr">
            <div class="course-card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <span class="course-level level-inter">📈 Niveau Intermédiaire</span>
                        <h2 class="course-title">BRVM — Stratégies</h2>
                        <div class="course-subtitle">« Stratégies d'investissement intermédiaire »</div>
                        <p class="course-desc">Pour passer au niveau supérieur : analyse plus poussée, stratégies, gestion du risque et construction de portefeuille.</p>
                        <ul class="course-features">
                            <li>Durée ~ 2h de vidéo</li>
                            <li>Stratégies concrètes & cas pratiques</li>
                            <li>Complément parfait du cours débutant</li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="cb-btn-gold">Acheter (Mobile Money)</a>
                            <a href="https://www.udemy.com/course/brvm-strategies-dinvestissement-intermediaire/?couponCode=77B14D32720FB58FCF1C" target="_blank" class="cb-btn-green">Acheter sur Udemy</a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="course-video-wrap">
                            <video class="img-fluid" controls preload="metadata" style="max-height:200px;width:100%;">
                                <source src="{{ asset('previews/brvm-intermediare-preview.mp4') }}" type="video/mp4">
                            </video>
                        </div>
                        <div class="course-hint">🎬 Aperçu ~3 min du cours</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRATIQUE -->
        <div class="course-card featured cbr">
            <div class="course-card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <span class="course-level level-prat">🧰 BRVM Pratique</span>
                        <h2 class="course-title">BRVM Pratique</h2>
                        <div class="course-subtitle">« Outils d'analyse et portefeuille virtuel »</div>
                        <p class="course-desc">Un cours orienté action : outils simples, lecture rapide des infos clés, et simulation via un portefeuille virtuel avant d'investir en réel.</p>
                        <ul class="course-features">
                            <li>Outils d'analyse concrets</li>
                            <li>Portefeuille virtuel (sans risque)</li>
                            <li>Démo réelle sur la plateforme Coach-BRVM</li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="cb-btn-gold">Acheter (Mobile Money)</a>
                            <a href="https://www.udemy.com/course/brvm-pratique-outils-danalyse-et-portefeuille-virtuel/?referralCode=187E6FB3DA9B0BF308AE" target="_blank" class="cb-btn-green">Acheter sur Udemy</a>
                        </div>
                        <div class="course-hint">💡 Conseil : fais la démo, puis choisis le mode de paiement qui te convient.</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="course-video-wrap">
                            <video class="img-fluid" controls preload="metadata" style="max-height:200px;width:100%;">
                                <source src="{{ asset('previews/brvm-pratique-preview.mp4') }}" type="video/mp4">
                            </video>
                        </div>
                        <div class="course-hint">🎬 Démo / introduction du cours</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); },{threshold:.06}).observe(el);
    });
</script>
@endpush
