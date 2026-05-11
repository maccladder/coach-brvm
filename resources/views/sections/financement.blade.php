{{-- ════════ sections/financement.blade.php ════════ --}}
@extends('layouts.app')
@section('title','Facilitation de financement – Coach BRVM')

@push('styles')
<style>
    .fin-page { background:#060910; min-height:100vh; }

    /* Hero */
    .fin-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(37,211,102,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(37,211,102,.1);
        padding: 56px 0 48px;
        position: relative; overflow: hidden;
    }
    .fin-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(37,211,102,.03) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(37,211,102,.03) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .fin-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #25D366;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .fin-tag::before { content: ''; width: 28px; height: 1px; background: #25D366; }
    .fin-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(30px, 5vw, 52px); font-weight: 900;
        color: #E8EAF0; line-height: 1.1; margin-bottom: 16px;
    }
    .fin-hero-title em { font-style: italic; color: #25D366; }
    .fin-hero-desc { font-size: 16px; color: #6B7590; line-height: 1.8; max-width: 560px; }
    .fin-hero-desc strong { color: #E8EAF0; }

    /* Disclaimer */
    .fin-disclaimer {
        background: rgba(201,168,76,.06); border: 1px solid rgba(201,168,76,.2);
        border-radius: 6px; padding: 16px 20px;
        display: flex; align-items: flex-start; gap: 12px;
        font-size: 13.5px; color: #9BA3B8; line-height: 1.7;
        margin: 28px 0 0;
    }
    .fin-disclaimer strong { color: #C9A84C; }

    /* Cards */
    .fin-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 28px 24px; height: 100%;
        transition: border-color .3s, transform .3s;
    }
    .fin-card:hover { border-color: rgba(37,211,102,.25); transform: translateY(-3px); }
    .fin-card-icon { font-size: 28px; margin-bottom: 12px; }
    .fin-card-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase; color: #E8EAF0; margin-bottom: 8px;
    }
    .fin-card-desc { font-size: 13.5px; color: #6B7590; line-height: 1.75; margin: 0; }

    /* Steps */
    .fin-step { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.05); }
    .fin-step:last-child { border-bottom: none; }
    .fin-step-num {
        min-width: 38px; height: 38px; border-radius: 50%;
        background: rgba(37,211,102,.12); border: 1px solid rgba(37,211,102,.3);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 800; color: #25D366;
        flex-shrink: 0;
    }
    .fin-step-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 4px; }
    .fin-step-desc { font-size: 13.5px; color: #6B7590; line-height: 1.7; margin: 0; }

    /* FAQ */
    .fin-faq-item { border-bottom: 1px solid rgba(255,255,255,.06); padding: 18px 0; }
    .fin-faq-q { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .fin-faq-q span { color: #25D366; font-size: 18px; transition: transform .3s; }
    .fin-faq-a { font-size: 13.5px; color: #6B7590; line-height: 1.8; }

    /* CTA finale */
    .fin-cta-box {
        background: linear-gradient(135deg, rgba(37,211,102,.08) 0%, rgba(37,211,102,.03) 100%);
        border: 1px solid rgba(37,211,102,.25);
        border-radius: 8px; padding: clamp(36px,5vw,56px); text-align: center;
    }
    .fin-cta-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px,3.5vw,36px); font-weight: 700; color: #E8EAF0; margin-bottom: 12px;
    }
    .fin-cta-title em { font-style: italic; color: #25D366; }

    .fin-wa-btn {
        display: inline-flex; align-items: center; gap: 10px;
        background: #25D366; color: #fff !important; text-decoration: none !important;
        padding: 16px 36px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; letter-spacing: .06em;
        animation: finWaGlow 2.5s ease-in-out infinite; transition: transform .2s, background .2s;
    }
    .fin-wa-btn i { font-size: 22px; }
    .fin-wa-btn:hover { background: #1ebe5a; transform: translateY(-2px); animation: none; box-shadow: 0 10px 32px rgba(37,211,102,.5); }
    @keyframes finWaGlow {
        0%, 100% { box-shadow: 0 4px 20px rgba(37,211,102,.3); }
        50% { box-shadow: 0 4px 36px rgba(37,211,102,.6), 0 0 0 8px rgba(37,211,102,.09); }
    }

    .fin-dot-blink {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        background: #25D366; margin-right: 6px; vertical-align: middle;
        animation: finBlink 1.4s ease-in-out infinite;
    }
    @keyframes finBlink {
        0%, 100% { opacity: 1; } 50% { opacity: .2; }
    }
</style>
@endpush

@section('content')
<div class="fin-page">

    {{-- HERO --}}
    <section class="fin-hero">
        <div class="fin-hero-grid"></div>
        <div class="container" style="max-width:1100px; position:relative;">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="fin-tag"><span class="fin-dot-blink"></span> Facilitation de financement</div>
                    <h1 class="fin-hero-title">
                        Vous avez un projet et besoin<br>
                        d'un <em>financement</em> ?
                    </h1>
                    <p class="fin-hero-desc">
                        Coach BRVM joue le rôle de <strong>facilitateur</strong> entre les particuliers
                        et des structures financières agréées, pour vous aider à obtenir le prêt
                        adapté à votre situation.
                    </p>

                    <div class="fin-disclaimer">
                        <span style="font-size:20px; flex-shrink:0;">⚠️</span>
                        <div>
                            <strong>Transparence importante :</strong> Coach BRVM n'est pas une banque
                            ni un organisme de crédit. Nous ne prêtons pas d'argent et ne collectons
                            aucun fonds. Nous vous <strong>mettons en relation</strong> avec des structures
                            financières agréées et vous accompagnons dans les démarches.
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div style="background:#0C1120; border:1px solid rgba(37,211,102,.15); border-radius:6px; padding:28px;">
                        <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">En bref</p>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Mise en relation gratuite</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Structures financières agréées uniquement</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Réponse sous 24h sur WhatsApp</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">⚠</span>
                                <span style="font-size:14px;color:#9BA3B8;">Nous ne sommes <strong style="color:#C9A84C;">pas</strong> une banque</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- POUR QUI --}}
    <section style="background:#060910; padding:64px 0;">
        <div class="container" style="max-width:1100px;">
            <div class="text-center mb-5">
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Pour qui ?</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:0;">
                    Ce service est fait pour vous si…
                </h2>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="fin-card">
                        <div class="fin-card-icon">🏠</div>
                        <div class="fin-card-title">Vous avez un projet personnel</div>
                        <p class="fin-card-desc">Achat immobilier, véhicule, financement d'études, événement de vie… On vous accompagne pour trouver la structure adaptée à votre besoin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fin-card">
                        <div class="fin-card-icon">💼</div>
                        <div class="fin-card-title">Vous êtes entrepreneur</div>
                        <p class="fin-card-desc">Financement de démarrage, trésorerie, investissement matériel… On vous met en relation avec des structures qui accompagnent les entrepreneurs.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fin-card">
                        <div class="fin-card-icon">🔄</div>
                        <div class="fin-card-title">Vous cherchez un refinancement</div>
                        <p class="fin-card-desc">Regroupement de crédits, restructuration de dettes… On identifie les structures pouvant vous aider à simplifier et optimiser vos engagements financiers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMMENT ÇA MARCHE --}}
    <section style="background:#0C1120; border-top:1px solid rgba(255,255,255,.05); border-bottom:1px solid rgba(255,255,255,.05); padding:64px 0;">
        <div class="container" style="max-width:1100px;">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Le processus</p>
                    <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:16px;">
                        Comment ça <em style="color:#25D366;">se passe</em> ?
                    </h2>
                    <p style="font-size:14px;color:#6B7590;line-height:1.8;">
                        De votre premier message WhatsApp jusqu'à la mise en relation avec la
                        structure financière adaptée, voici les grandes étapes.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="fin-step">
                        <div class="fin-step-num">01</div>
                        <div>
                            <div class="fin-step-title">Vous nous contactez sur WhatsApp</div>
                            <p class="fin-step-desc">Un simple message suffit. On vous répond sous 24h pour faire le point sur votre projet et votre situation financière.</p>
                        </div>
                    </div>
                    <div class="fin-step">
                        <div class="fin-step-num">02</div>
                        <div>
                            <div class="fin-step-title">On évalue votre besoin et votre profil</div>
                            <p class="fin-step-desc">Montant recherché, durée, type de projet, situation professionnelle… On analyse votre dossier pour identifier les meilleures options.</p>
                        </div>
                    </div>
                    <div class="fin-step">
                        <div class="fin-step-num">03</div>
                        <div>
                            <div class="fin-step-title">On identifie la structure financière adaptée</div>
                            <p class="fin-step-desc">Parmi nos partenaires agréés, on sélectionne la structure la mieux positionnée pour votre profil et votre projet.</p>
                        </div>
                    </div>
                    <div class="fin-step">
                        <div class="fin-step-num">04</div>
                        <div>
                            <div class="fin-step-title">On vous accompagne dans la constitution du dossier</div>
                            <p class="fin-step-desc">On vous indique exactement les documents à préparer pour maximiser vos chances d'obtenir le financement.</p>
                        </div>
                    </div>
                    <div class="fin-step">
                        <div class="fin-step-num">05</div>
                        <div>
                            <div class="fin-step-title">La structure vous contacte directement</div>
                            <p class="fin-step-desc">Une fois mis en relation, la structure financière prend le relais et instruit votre demande de financement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section style="background:#060910; padding:64px 0;">
        <div class="container" style="max-width:760px;">
            <div class="text-center mb-5">
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Questions fréquentes</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(22px,3vw,32px);font-weight:700;color:#E8EAF0;">Ce que vous vous demandez sûrement</h2>
            </div>
            <div>
                <div class="fin-faq-item">
                    <div class="fin-faq-q">Coach BRVM est-il une banque ou un organisme de crédit ? <span>+</span></div>
                    <p class="fin-faq-a">Non. Coach BRVM est un facilitateur. Nous ne prêtons pas d'argent et ne collectons aucun fonds. Notre rôle est de vous mettre en relation avec des structures financières agréées et de vous accompagner dans la préparation de votre dossier.</p>
                </div>
                <div class="fin-faq-item">
                    <div class="fin-faq-q">Quels types de projets peuvent bénéficier d'un financement ? <span>+</span></div>
                    <p class="fin-faq-a">Tout type de projet : achat immobilier, véhicule, financement d'activité professionnelle, études, regroupement de crédits… Contactez-nous pour évaluer votre situation et les options disponibles.</p>
                </div>
                <div class="fin-faq-item">
                    <div class="fin-faq-q">Y a-t-il des frais pour la mise en relation ? <span>+</span></div>
                    <p class="fin-faq-a">La mise en relation est gratuite. Les conditions de financement (taux d'intérêt, frais de dossier, etc.) dépendent exclusivement de la structure financière retenue et sont négociées directement avec elle.</p>
                </div>
                <div class="fin-faq-item">
                    <div class="fin-faq-q">Combien de temps pour obtenir une réponse ? <span>+</span></div>
                    <p class="fin-faq-a">Nous répondons à votre message sous 24h. La durée d'instruction du dossier varie ensuite selon la structure financière et le type de financement demandé (généralement entre quelques jours et quelques semaines).</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINALE --}}
    <section style="background:#060910; padding:clamp(64px,10vw,100px) 0;">
        <div class="container" style="max-width:760px;">
            <div class="fin-cta-box">
                <div style="font-size:40px; margin-bottom:16px;">💰</div>
                <h2 class="fin-cta-title">
                    Prêt à concrétiser<br>votre <em>projet</em> ?
                </h2>
                <p style="font-size:15px;color:#6B7590;line-height:1.8;max-width:500px;margin:0 auto 32px;">
                    Envoyez-nous un message WhatsApp. On évalue votre besoin et on vous met
                    en relation avec la structure financière la mieux adaptée à votre situation.
                </p>
                <a href="https://wa.me/2250574023351?text=Bonjour%20Coach%20BRVM%2C%20je%20souhaite%20%C3%AAtre%20mis%20en%20relation%20avec%20une%20structure%20de%20financement%20agr%C3%A9%C3%A9e."
                   target="_blank" rel="noopener"
                   class="fin-wa-btn">
                    <i class="bi bi-whatsapp"></i> Nous contacter sur WhatsApp
                </a>
                <p style="font-size:11px;color:rgba(107,117,144,.5);margin-top:20px;font-family:'Syne',sans-serif;letter-spacing:.1em;text-transform:uppercase;">
                    Mise en relation gratuite &nbsp;·&nbsp; Réponse sous 24h &nbsp;·&nbsp; Structures agréées
                </p>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.fin-faq-q').forEach(q => {
        q.addEventListener('click', () => {
            const a = q.nextElementSibling;
            const icon = q.querySelector('span');
            const isOpen = a.style.display === 'block';
            document.querySelectorAll('.fin-faq-a').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.fin-faq-q span').forEach(el => { el.textContent = '+'; });
            if (!isOpen) { a.style.display = 'block'; icon.textContent = '−'; }
        });
    });
    document.querySelectorAll('.fin-faq-a').forEach(el => el.style.display = 'none');
</script>
@endpush
