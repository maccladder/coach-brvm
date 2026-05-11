{{-- ════════ sections/formation-presentielle.blade.php ════════ --}}
@extends('layouts.app')
@section('title','Formation en présentiel – Coach BRVM')

@push('styles')
<style>
    .fp-page { background:#060910; min-height:100vh; }

    /* Hero */
    .fp-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.1);
        padding: 56px 0 48px;
        position: relative; overflow: hidden;
    }
    .fp-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(201,168,76,.03) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(201,168,76,.03) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .fp-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #C9A84C;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .fp-tag::before { content: ''; width: 28px; height: 1px; background: #C9A84C; }
    .fp-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(30px, 5vw, 52px); font-weight: 900;
        color: #E8EAF0; line-height: 1.1; margin-bottom: 16px;
    }
    .fp-hero-title em { font-style: italic; color: #C9A84C; }
    .fp-hero-desc { font-size: 16px; color: #6B7590; line-height: 1.8; max-width: 560px; }
    .fp-hero-desc strong { color: #E8EAF0; }

    /* Cards */
    .fp-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 28px 24px; height: 100%;
        transition: border-color .3s, transform .3s;
    }
    .fp-card:hover { border-color: rgba(201,168,76,.25); transform: translateY(-3px); }
    .fp-card-icon { font-size: 28px; margin-bottom: 12px; }
    .fp-card-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase; color: #E8EAF0; margin-bottom: 8px;
    }
    .fp-card-desc { font-size: 13.5px; color: #6B7590; line-height: 1.75; margin: 0; }

    /* Steps */
    .fp-step { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.05); }
    .fp-step:last-child { border-bottom: none; }
    .fp-step-num {
        min-width: 38px; height: 38px; border-radius: 50%;
        background: rgba(201,168,76,.12); border: 1px solid rgba(201,168,76,.3);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 800; color: #C9A84C;
        flex-shrink: 0;
    }
    .fp-step-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 4px; }
    .fp-step-desc { font-size: 13.5px; color: #6B7590; line-height: 1.7; margin: 0; }

    /* FAQ */
    .fp-faq-item { border-bottom: 1px solid rgba(255,255,255,.06); padding: 18px 0; }
    .fp-faq-q { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .fp-faq-q span { color: #C9A84C; font-size: 18px; transition: transform .3s; }
    .fp-faq-a { font-size: 13.5px; color: #6B7590; line-height: 1.8; }

    /* CTA finale */
    .fp-cta-box {
        background: linear-gradient(135deg, rgba(201,168,76,.08) 0%, rgba(201,168,76,.03) 100%);
        border: 1px solid rgba(201,168,76,.25);
        border-radius: 8px; padding: clamp(36px,5vw,56px); text-align: center;
    }
    .fp-cta-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px,3.5vw,36px); font-weight: 700; color: #E8EAF0; margin-bottom: 12px;
    }
    .fp-cta-title em { font-style: italic; color: #C9A84C; }

    .fp-wa-btn {
        display: inline-flex; align-items: center; gap: 10px;
        background: #25D366; color: #fff !important; text-decoration: none !important;
        padding: 16px 36px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; letter-spacing: .06em;
        animation: fpWaGlow 2.5s ease-in-out infinite; transition: transform .2s, background .2s;
    }
    .fp-wa-btn i { font-size: 22px; }
    .fp-wa-btn:hover { background: #1ebe5a; transform: translateY(-2px); animation: none; box-shadow: 0 10px 32px rgba(37,211,102,.5); }
    @keyframes fpWaGlow {
        0%, 100% { box-shadow: 0 4px 20px rgba(37,211,102,.3); }
        50% { box-shadow: 0 4px 36px rgba(37,211,102,.6), 0 0 0 8px rgba(37,211,102,.09); }
    }

    .fp-dot-blink {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        background: #C9A84C; margin-right: 6px; vertical-align: middle;
        animation: fpBlink 1.4s ease-in-out infinite;
    }
    @keyframes fpBlink {
        0%, 100% { opacity: 1; } 50% { opacity: .2; }
    }
</style>
@endpush

@section('content')
<div class="fp-page">

    {{-- HERO --}}
    <section class="fp-hero">
        <div class="fp-hero-grid"></div>
        <div class="container" style="max-width:1100px; position:relative;">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="fp-tag"><span class="fp-dot-blink"></span> Formation en présentiel</div>
                    <h1 class="fp-hero-title">
                        Apprenez à investir à la BRVM<br>
                        avec une <em>formation</em> en présentiel
                    </h1>
                    <p class="fp-hero-desc">
                        Coach BRVM propose des sessions de formation en présentiel pour vous aider à
                        <strong>maîtriser les fondamentaux de l'investissement en bourse</strong>
                        et développer une stratégie adaptée à votre profil.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div style="background:#0C1120; border:1px solid rgba(201,168,76,.15); border-radius:6px; padding:28px;">
                        <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">Ce que vous apprendrez</p>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Fonctionnement de la BRVM</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Lecture et analyse d'actions</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Construction d'un portefeuille</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Stratégies d'investissement</span>
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
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;margin-bottom:10px;">Pour qui ?</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:0;">
                    Cette formation est faite pour vous si…
                </h2>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="fp-card">
                        <div class="fp-card-icon">🎓</div>
                        <div class="fp-card-title">Vous êtes débutant</div>
                        <p class="fp-card-desc">Vous voulez comprendre la bourse, apprendre à lire les marchés et commencer à investir à la BRVM avec des bases solides.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fp-card">
                        <div class="fp-card-icon">📊</div>
                        <div class="fp-card-title">Vous êtes investisseur</div>
                        <p class="fp-card-desc">Vous investissez déjà mais souhaitez affiner votre analyse fondamentale, améliorer vos performances et éviter les erreurs classiques.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fp-card">
                        <div class="fp-card-icon">🏢</div>
                        <div class="fp-card-title">Vous représentez une entreprise</div>
                        <p class="fp-card-desc">Vous souhaitez former vos équipes, vos cadres ou vos dirigeants sur les marchés financiers et l'investissement à la BRVM.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMMENT ÇA SE PASSE --}}
    <section style="background:#0C1120; border-top:1px solid rgba(255,255,255,.05); border-bottom:1px solid rgba(255,255,255,.05); padding:64px 0;">
        <div class="container" style="max-width:1100px;">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;margin-bottom:10px;">Le processus</p>
                    <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:16px;">
                        Comment ça <em style="color:#C9A84C;">se passe</em> ?
                    </h2>
                    <p style="font-size:14px;color:#6B7590;line-height:1.8;">
                        De votre premier message jusqu'à votre participation à la session de formation,
                        voici les grandes étapes.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="fp-step">
                        <div class="fp-step-num">01</div>
                        <div>
                            <div class="fp-step-title">Vous nous contactez sur WhatsApp</div>
                            <p class="fp-step-desc">Un simple message suffit. On vous répond rapidement pour comprendre votre niveau actuel et vos objectifs d'apprentissage.</p>
                        </div>
                    </div>
                    <div class="fp-step">
                        <div class="fp-step-num">02</div>
                        <div>
                            <div class="fp-step-title">On définit vos objectifs et votre niveau</div>
                            <p class="fp-step-desc">Débutant, intermédiaire, professionnel ou entreprise — on adapte le programme et vous proposons la session la mieux adaptée.</p>
                        </div>
                    </div>
                    <div class="fp-step">
                        <div class="fp-step-num">03</div>
                        <div>
                            <div class="fp-step-title">On planifie la session selon vos disponibilités</div>
                            <p class="fp-step-desc">On convient ensemble d'une date, d'un lieu et d'un programme sur-mesure en fonction de vos contraintes et de vos attentes.</p>
                        </div>
                    </div>
                    <div class="fp-step">
                        <div class="fp-step-num">04</div>
                        <div>
                            <div class="fp-step-title">Vous participez à la formation en présentiel</div>
                            <p class="fp-step-desc">Session interactive avec cas pratiques, exercices sur données réelles et questions-réponses. Une attestation vous est remise à l'issue.</p>
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
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;margin-bottom:10px;">Questions fréquentes</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(22px,3vw,32px);font-weight:700;color:#E8EAF0;">Ce que vous vous demandez sûrement</h2>
            </div>
            <div>
                <div class="fp-faq-item">
                    <div class="fp-faq-q">Où se déroulent les formations ? <span>+</span></div>
                    <p class="fp-faq-a">Les formations se tiennent à Abidjan et dans les principales villes de la zone UEMOA. Contactez-nous sur WhatsApp pour connaître les lieux et dates disponibles.</p>
                </div>
                <div class="fp-faq-item">
                    <div class="fp-faq-q">Quelle est la durée d'une session de formation ? <span>+</span></div>
                    <p class="fp-faq-a">Les sessions durent généralement de 1 à 2 journées complètes selon le programme choisi. Un programme sur-mesure peut être organisé selon vos besoins spécifiques.</p>
                </div>
                <div class="fp-faq-item">
                    <div class="fp-faq-q">Le programme convient-il aux débutants absolus ? <span>+</span></div>
                    <p class="fp-faq-a">Absolument. Nous proposons des programmes adaptés à tous les niveaux, du grand débutant qui n'a jamais entendu parler de bourse à l'investisseur confirmé qui veut perfectionner sa technique d'analyse.</p>
                </div>
                <div class="fp-faq-item">
                    <div class="fp-faq-q">Une attestation est-elle délivrée à l'issue de la formation ? <span>+</span></div>
                    <p class="fp-faq-a">Oui, une attestation de participation est remise à chaque participant à l'issue de la session de formation.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINALE --}}
    <section style="background:#060910; padding:clamp(64px,10vw,100px) 0;">
        <div class="container" style="max-width:760px;">
            <div class="fp-cta-box">
                <div style="font-size:40px; margin-bottom:16px;">🎓</div>
                <h2 class="fp-cta-title">
                    Prêt à passer<br>à l'<em>action</em> ?
                </h2>
                <p style="font-size:15px;color:#6B7590;line-height:1.8;max-width:500px;margin:0 auto 32px;">
                    Contactez-nous sur WhatsApp pour vous renseigner sur les prochaines sessions
                    disponibles, les tarifs et le programme adapté à votre niveau.
                </p>
                <a href="https://wa.me/2250574023351?text=Bonjour%20Coach%20BRVM%2C%20je%20suis%20int%C3%A9ress%C3%A9(e)%20par%20une%20formation%20en%20pr%C3%A9sentiel%20et%20souhaite%20avoir%20plus%20d%27informations."
                   target="_blank" rel="noopener"
                   class="fp-wa-btn">
                    <i class="bi bi-whatsapp"></i> Nous contacter sur WhatsApp
                </a>
                <p style="font-size:11px;color:rgba(107,117,144,.5);margin-top:20px;font-family:'Syne',sans-serif;letter-spacing:.1em;text-transform:uppercase;">
                    Tous niveaux acceptés &nbsp;·&nbsp; Programme sur-mesure &nbsp;·&nbsp; Attestation délivrée
                </p>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.fp-faq-q').forEach(q => {
        q.addEventListener('click', () => {
            const a = q.nextElementSibling;
            const icon = q.querySelector('span');
            const isOpen = a.style.display === 'block';
            document.querySelectorAll('.fp-faq-a').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.fp-faq-q span').forEach(el => { el.textContent = '+'; });
            if (!isOpen) { a.style.display = 'block'; icon.textContent = '−'; }
        });
    });
    document.querySelectorAll('.fp-faq-a').forEach(el => el.style.display = 'none');
</script>
@endpush
