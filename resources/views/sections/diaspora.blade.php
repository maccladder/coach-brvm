{{-- ════════ sections/diaspora.blade.php ════════ --}}
@extends('layouts.app')
@section('title','Investir depuis la diaspora – Coach BRVM')

@push('styles')
<style>
    .dia-page { background:#060910; min-height:100vh; }

    /* Hero */
    .dia-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(37,211,102,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(37,211,102,.1);
        padding: 56px 0 48px;
        position: relative; overflow: hidden;
    }
    .dia-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(37,211,102,.03) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(37,211,102,.03) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .dia-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #25D366;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .dia-tag::before { content: ''; width: 28px; height: 1px; background: #25D366; }
    .dia-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(30px, 5vw, 52px); font-weight: 900;
        color: #E8EAF0; line-height: 1.1; margin-bottom: 16px;
    }
    .dia-hero-title em { font-style: italic; color: #25D366; }
    .dia-hero-desc { font-size: 16px; color: #6B7590; line-height: 1.8; max-width: 560px; }
    .dia-hero-desc strong { color: #E8EAF0; }

    /* Disclaimer */
    .dia-disclaimer {
        background: rgba(201,168,76,.06); border: 1px solid rgba(201,168,76,.2);
        border-radius: 6px; padding: 16px 20px;
        display: flex; align-items: flex-start; gap: 12px;
        font-size: 13.5px; color: #9BA3B8; line-height: 1.7;
        margin: 28px 0 0;
    }
    .dia-disclaimer strong { color: #C9A84C; }

    /* Cards */
    .dia-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 6px; padding: 28px 24px; height: 100%;
        transition: border-color .3s, transform .3s;
    }
    .dia-card:hover { border-color: rgba(37,211,102,.25); transform: translateY(-3px); }
    .dia-card-icon { font-size: 28px; margin-bottom: 12px; }
    .dia-card-title {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase; color: #E8EAF0; margin-bottom: 8px;
    }
    .dia-card-desc { font-size: 13.5px; color: #6B7590; line-height: 1.75; margin: 0; }

    /* Steps */
    .dia-step { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.05); }
    .dia-step:last-child { border-bottom: none; }
    .dia-step-num {
        min-width: 38px; height: 38px; border-radius: 50%;
        background: rgba(37,211,102,.12); border: 1px solid rgba(37,211,102,.3);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 800; color: #25D366;
        flex-shrink: 0;
    }
    .dia-step-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 4px; }
    .dia-step-desc { font-size: 13.5px; color: #6B7590; line-height: 1.7; margin: 0; }

    /* FAQ */
    .dia-faq-item { border-bottom: 1px solid rgba(255,255,255,.06); padding: 18px 0; }
    .dia-faq-q { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #E8EAF0; margin-bottom: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .dia-faq-q span { color: #25D366; font-size: 18px; transition: transform .3s; }
    .dia-faq-a { font-size: 13.5px; color: #6B7590; line-height: 1.8; }

    /* CTA finale */
    .dia-cta-box {
        background: linear-gradient(135deg, rgba(37,211,102,.08) 0%, rgba(37,211,102,.03) 100%);
        border: 1px solid rgba(37,211,102,.25);
        border-radius: 8px; padding: clamp(36px,5vw,56px); text-align: center;
    }
    .dia-cta-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px,3.5vw,36px); font-weight: 700; color: #E8EAF0; margin-bottom: 12px;
    }
    .dia-cta-title em { font-style: italic; color: #25D366; }

    .dia-wa-btn {
        display: inline-flex; align-items: center; gap: 10px;
        background: #25D366; color: #fff !important; text-decoration: none !important;
        padding: 16px 36px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; letter-spacing: .06em;
        animation: diaWaGlow 2.5s ease-in-out infinite; transition: transform .2s, background .2s;
    }
    .dia-wa-btn i { font-size: 22px; }
    .dia-wa-btn:hover { background: #1ebe5a; transform: translateY(-2px); animation: none; box-shadow: 0 10px 32px rgba(37,211,102,.5); }
    @keyframes diaWaGlow {
        0%, 100% { box-shadow: 0 4px 20px rgba(37,211,102,.3); }
        50% { box-shadow: 0 4px 36px rgba(37,211,102,.6), 0 0 0 8px rgba(37,211,102,.09); }
    }

    .dia-dot-blink {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        background: #25D366; margin-right: 6px; vertical-align: middle;
        animation: diaBlink 1.4s ease-in-out infinite;
    }
    @keyframes diaBlink {
        0%, 100% { opacity: 1; } 50% { opacity: .2; }
    }
</style>
@endpush

@section('content')
<div class="dia-page">

    {{-- HERO --}}
    <section class="dia-hero">
        <div class="dia-hero-grid"></div>
        <div class="container" style="max-width:1100px; position:relative;">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="dia-tag"><span class="dia-dot-blink"></span> Diaspora africaine</div>
                    <h1 class="dia-hero-title">
                        Tu es à l'étranger et tu veux<br>
                        investir à la <em>BRVM</em> ?
                    </h1>
                    <p class="dia-hero-desc">
                        C'est possible, et Coach BRVM est là pour t'<strong>accompagner pas à pas</strong>
                        dans toutes les démarches — de zéro jusqu'à l'activation de ton compte-titres
                        auprès d'une SGI agréée.
                    </p>

                    <div class="dia-disclaimer">
                        <span style="font-size:20px; flex-shrink:0;">⚠️</span>
                        <div>
                            <strong>Transparence importante :</strong> Coach BRVM n'est pas une SGI
                            (Société de Gestion et d'Intermédiation). Nous ne gérons pas ton argent
                            et nous ne passons pas d'ordres en bourse à ta place.
                            Nous t'<strong>accompagnons uniquement dans les démarches administratives</strong>
                            d'ouverture de compte-titres, <strong>moyennant une commission minime</strong>
                            uniquement à l'ouverture réussie.
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div style="background:#0C1120; border:1px solid rgba(37,211,102,.15); border-radius:6px; padding:28px;">
                        <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">En bref</p>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Accompagnement 100% en ligne</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Valable depuis n'importe quel pays</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Commission minime, résultat garanti</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#25D366;font-size:18px;">✓</span>
                                <span style="font-size:14px;color:#9BA3B8;">Réponse sous 24h sur WhatsApp</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span style="color:#C9A84C;font-size:18px;">⚠</span>
                                <span style="font-size:14px;color:#9BA3B8;">Nous ne sommes <strong style="color:#C9A84C;">pas</strong> une SGI</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- QUI PEUT --}}
    <section style="background:#060910; padding:64px 0;">
        <div class="container" style="max-width:1100px;">
            <div class="text-center mb-5">
                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Pour qui ?</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:0;">
                    Ce service est fait pour toi si…
                </h2>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="dia-card">
                        <div class="dia-card-icon">🌍</div>
                        <div class="dia-card-title">Tu vis en diaspora</div>
                        <p class="dia-card-desc">Europe, Amérique, Asie, Océanie... Peu importe où tu es, on t'accompagne à distance pour ouvrir ton compte-titres à la BRVM.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dia-card">
                        <div class="dia-card-icon">🤷</div>
                        <div class="dia-card-title">Tu ne sais pas par où commencer</div>
                        <p class="dia-card-desc">Quelle SGI choisir ? Quels documents fournir ? Comment envoyer les fonds ? On te guide sur chaque étape sans jargon compliqué.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dia-card">
                        <div class="dia-card-icon">💼</div>
                        <div class="dia-card-title">Tu veux investir sans intermédiaire coûteux</div>
                        <p class="dia-card-desc">Pas de frais exorbitants. Notre commission est minime et ne s'applique qu'une fois ton compte-titres activé et opérationnel.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMMENT CA MARCHE --}}
    <section style="background:#0C1120; border-top:1px solid rgba(255,255,255,.05); border-bottom:1px solid rgba(255,255,255,.05); padding:64px 0;">
        <div class="container" style="max-width:1100px;">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Le processus</p>
                    <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,34px);font-weight:700;color:#E8EAF0;margin-bottom:16px;">
                        Comment ça <em style="color:#25D366;">se passe</em> ?
                    </h2>
                    <p style="font-size:14px;color:#6B7590;line-height:1.8;">
                        De ton premier message WhatsApp jusqu'à l'activation de ton compte-titres,
                        voici les grandes étapes de l'accompagnement.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="dia-step">
                        <div class="dia-step-num">01</div>
                        <div>
                            <div class="dia-step-title">Tu nous contactes sur WhatsApp</div>
                            <p class="dia-step-desc">Un simple message suffit. On te répond sous 24h pour faire connaissance et comprendre ton profil d'investisseur.</p>
                        </div>
                    </div>
                    <div class="dia-step">
                        <div class="dia-step-num">02</div>
                        <div>
                            <div class="dia-step-title">On identifie la bonne SGI pour toi</div>
                            <p class="dia-step-desc">Selon ton pays de résidence, tes objectifs et le montant envisagé, on te recommande la SGI agréée la plus adaptée.</p>
                        </div>
                    </div>
                    <div class="dia-step">
                        <div class="dia-step-num">03</div>
                        <div>
                            <div class="dia-step-title">Montage et envoi du dossier</div>
                            <p class="dia-step-desc">On te liste exactement les documents nécessaires, on vérifie ton dossier et on t'accompagne dans la soumission auprès de la SGI.</p>
                        </div>
                    </div>
                    <div class="dia-step">
                        <div class="dia-step-num">04</div>
                        <div>
                            <div class="dia-step-title">Suivi jusqu'à l'activation</div>
                            <p class="dia-step-desc">On suit ton dossier avec la SGI et on t'informe à chaque étape. Notre commission minime n'est due qu'une fois ton compte activé.</p>
                        </div>
                    </div>
                    <div class="dia-step">
                        <div class="dia-step-num">05</div>
                        <div>
                            <div class="dia-step-title">Tu investis en autonomie</div>
                            <p class="dia-step-desc">Compte activé, tu passes tes ordres directement avec ta SGI. Coach BRVM continue de t'accompagner dans l'analyse du marché BRVM.</p>
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
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(22px,3vw,32px);font-weight:700;color:#E8EAF0;">Ce que tu te demandes sûrement</h2>
            </div>
            <div>
                <div class="dia-faq-item">
                    <div class="dia-faq-q">C'est quoi une SGI et pourquoi en ai-je besoin ? <span>+</span></div>
                    <p class="dia-faq-a">Une SGI (Société de Gestion et d'Intermédiation) est un courtier agréé par la BRVM. C'est l'intermédiaire obligatoire pour acheter ou vendre des actions en bourse. Sans compte-titres chez une SGI, tu ne peux pas investir à la BRVM.</p>
                </div>
                <div class="dia-faq-item">
                    <div class="dia-faq-q">Combien coûte votre accompagnement ? <span>+</span></div>
                    <p class="dia-faq-a">Notre commission est minime et ne s'applique qu'une seule fois, lorsque ton compte-titres est activé et opérationnel. Si le compte n'est pas ouvert pour une raison indépendante de ta volonté, tu ne paies rien.</p>
                </div>
                <div class="dia-faq-item">
                    <div class="dia-faq-q">Quels documents sont généralement requis ? <span>+</span></div>
                    <p class="dia-faq-a">En général : une pièce d'identité valide, un justificatif de domicile récent, un RIB/coordonnées bancaires et parfois une déclaration de revenus. Les exigences varient selon la SGI choisie — on t'accompagne pour préparer exactement ce qu'il faut.</p>
                </div>
                <div class="dia-faq-item">
                    <div class="dia-faq-q">Est-ce légal d'investir à la BRVM depuis l'étranger ? <span>+</span></div>
                    <p class="dia-faq-a">Oui, tout à fait. La BRVM est ouverte aux investisseurs internationaux. Des SGI agréées ont même des procédures spécifiques pour les non-résidents. Notre rôle est de t'orienter vers les SGI qui facilitent ces démarches.</p>
                </div>
                <div class="dia-faq-item">
                    <div class="dia-faq-q">Combien de temps prend l'ouverture d'un compte-titres ? <span>+</span></div>
                    <p class="dia-faq-a">En général entre 1 et 4 semaines selon la SGI et la complétude de ton dossier. Avec notre accompagnement, les délais sont réduits car tu arrives avec un dossier complet et bien présenté.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINALE --}}
    <section style="background:#060910; padding:clamp(64px,10vw,100px) 0;">
        <div class="container" style="max-width:760px;">
            <div class="dia-cta-box">
                <div style="font-size:40px; margin-bottom:16px;">🌍</div>
                <h2 class="dia-cta-title">
                    Prêt à investir à la BRVM<br>depuis <em>là où tu es</em> ?
                </h2>
                <p style="font-size:15px;color:#6B7590;line-height:1.8;max-width:500px;margin:0 auto 32px;">
                    Envoie-nous un message WhatsApp. On t'explique tout, on évalue ta situation
                    et on t'accompagne de A à Z jusqu'à l'activation de ton compte-titres.
                </p>
                <a href="https://wa.me/2250574023351?text=Bonjour%2C%20je%20suis%20en%20diaspora%20et%20je%20voudrais%20investir%20%C3%A0%20la%20BRVM.%20Pouvez-vous%20m%27accompagner%20%3F"
                   target="_blank" rel="noopener"
                   class="dia-wa-btn">
                    <i class="bi bi-whatsapp"></i> Nous contacter sur WhatsApp
                </a>
                <p style="font-size:11px;color:rgba(107,117,144,.5);margin-top:20px;font-family:'Syne',sans-serif;letter-spacing:.1em;text-transform:uppercase;">
                    Réponse sous 24h &nbsp;·&nbsp; 100% en ligne &nbsp;·&nbsp; Commission minime
                </p>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
    // Simple FAQ accordion
    document.querySelectorAll('.dia-faq-q').forEach(q => {
        q.addEventListener('click', () => {
            const a = q.nextElementSibling;
            const icon = q.querySelector('span');
            const isOpen = a.style.display === 'block';
            document.querySelectorAll('.dia-faq-a').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.dia-faq-q span').forEach(el => { el.textContent = '+'; el.style.transform = ''; });
            if (!isOpen) {
                a.style.display = 'block';
                icon.textContent = '−';
            }
        });
    });
    // Close all FAQ on load
    document.querySelectorAll('.dia-faq-a').forEach(el => el.style.display = 'none');
</script>
@endpush
