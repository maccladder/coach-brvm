@extends('layouts.app')

@section('content')
<div class="bg-light">
    {{-- HERO --}}
    <section class="py-5 py-lg-6 border-bottom" style="background: radial-gradient(circle at top left, #0d6efd15, #ffffff);">
        <div class="container" style="max-width: 1100px;">
            <div class="row align-items-center g-4">
                {{-- Texte principal --}}
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">
                            IA & Bourse Ouest-Africaine
                        </span>
                        <span class="badge rounded-pill bg-success-subtle text-success fw-semibold">
                            Mobile Money • CinetPay
                        </span>
                    </div>

                    <h1 class="fw-bold mb-3" style="font-size: 2.4rem;">
                        Coach BRVM – L’IA qui lit vos BOC et états financiers à votre place.
                    </h1>

                    <p class="lead text-muted mb-4">
                        Marre de décortiquer seul les <strong>BOC</strong> et les <strong>états financiers</strong> ?
                        Uploadez votre document, payez par mobile money, et laissez votre coach virtuel
                        vous expliquer, en texte, en audio et en vidéo.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <a href="{{ route('client-bocs.create') }}" class="btn btn-primary btn-lg">
                            📄 Analyser ma BOC (500&nbsp;FCFA)
                        </a>

                        {{-- 🔥 Bouton état financier activé --}}
                        <a href="{{ route('client-financials.create') }}" class="btn btn-outline-secondary btn-lg">
                            📊 Analyser un état financier
                        </a>
                    </div>

                    <div class="text-muted small">
                        <span class="me-3">✅ Analyse détaillée + résumé pédagogique</span>
                        <span class="me-3">✅ Audio + avatar vidéo</span>
                        <span>⏱️ Résultat en quelques instants après paiement</span>
                    </div>
                </div>

                {{-- Visuel / “mockup” --}}
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ asset('avatars/coach.png') }}"
                                     alt="Coach BRVM"
                                     class="rounded-circle border shadow-sm me-3"
                                     style="width:58px;height:58px;object-fit:cover;">
                                <div>
                                    <div class="fw-semibold">Coach BRVM</div>
                                    <div class="text-muted small">Interprétation IA personnalisée</div>
                                </div>
                            </div>

                            {{-- Mini mock vidéo (on peut garder) --}}
                            <div class="ratio ratio-16x9 rounded mb-3" style="background:#000;">
                                <video
                                    src="{{ $exampleVideoUrl ?? '' }}"
                                    poster="{{ asset('img/mock-video-poster.png') }}"
                                    style="width:100%;border-radius:12px;object-fit:cover;"
                                    muted
                                ></video>
                            </div>

                            {{-- 🔎 Exemple de BOC analysé --}}
                            <div class="border rounded-3 overflow-hidden mb-2">
                                <img
                                    src="{{ asset('img/boc-exemple.png') }}"
                                    alt="Exemple de Bulletin Officiel de la Côte (BOC)"
                                    class="img-fluid"
                                    style="max-height:260px;object-fit:cover;width:100%;">
                            </div>
                            <p class="small text-muted mb-2">
                                Exemple de <strong>Bulletin Officiel de la Côte (BOC)</strong> tel que publié par la BRVM.
                                Coach BRVM analyse précisément ce format de PDF (une BOC par jour).
                            </p>

                            <div class="d-flex align-items-center small text-muted">
                                <span class="me-2">Compatible :</span>
                                <span class="badge bg-light text-dark border me-1">Ordinateur</span>
                                <span class="badge bg-light text-dark border me-1">Mobile</span>
                                <span class="badge bg-light text-dark border">Tablette</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bandeau BRVM / disclaimer --}}
            <div class="d-flex flex-wrap align-items-center gap-3 mt-4 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('img/brvm-logo.jpg') }}"
                         alt="BRVM"
                         style="height:32px;width:auto;">
                    <span class="small text-muted">
                        Coach BRVM est un service indépendant, non affilié officiellement à la BRVM.
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- COMMENT ÇA MARCHE --}}
    <section class="py-5">
        <div class="container" style="max-width: 1100px;">
            <div class="text-center mb-4">
                <h2 class="fw-semibold mb-2">Comment ça marche&nbsp;?</h2>
                <p class="text-muted mb-0">
                    3 étapes simples pour transformer un PDF illisible en décisions plus claires.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">1️⃣</div>
                            <h5 class="fw-semibold mb-2">Uploader votre document</h5>
                            <p class="text-muted small mb-0">
                                BOC ou état financier, vous choisissez le fichier à analyser
                                depuis votre ordinateur ou votre téléphone.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">2️⃣</div>
                            <h5 class="fw-semibold mb-2">Payer par mobile money</h5>
                            <p class="text-muted small mb-0">
                                Paiement sécurisé via CinetPay (Orange Money, MTN, Wave, cartes…).
                                Pour la BOC, à partir de <strong>1&nbsp;000 FCFA</strong>.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-5 mb-3">3️⃣</div>
                            <h5 class="fw-semibold mb-2">Recevoir l’analyse IA</h5>
                            <p class="text-muted small mb-0">
                                Vous obtenez une interprétation détaillée, un résumé, un audio
                                et une vidéo de votre coach virtuel. Imprimable ou à garder par mail.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- LES DEUX SERVICES --}}
    <section class="py-5 bg-white border-top">
        <div class="container" style="max-width: 1100px;">
            <div class="text-center mb-4">
                <h2 class="fw-semibold mb-2">Deux services pour les investisseurs BRVM</h2>
                <p class="text-muted mb-0">
                    Commence simple avec l’analyse de BOC, puis passe aux états financiers complets.
                </p>
            </div>

            <div class="row g-4">
                {{-- BOC --}}
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-semibold mb-1">📄 Analyse de votre BOC</h5>
                            <p class="text-muted small mb-2">
                                Idéal pour suivre rapidement votre portefeuille et les mouvements du jour.
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>Lecture IA de votre BOC</li>
                                <li>Résumé clair en français simple</li>
                                <li>Audio + avatar vidéo qui vous parle</li>
                                <li>Envoi possible par e-mail</li>
                            </ul>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">500&nbsp;FCFA</div>
                                    <div class="text-muted small">par BOC analysé</div>
                                </div>
                                <a href="{{ route('client-bocs.create') }}" class="btn btn-outline-primary">
                                    Commencer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- État financier --}}
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-semibold mb-1">📊 Analyse d’un état financier</h5>
                            <p class="text-muted small mb-2">
                                Pour comprendre en profondeur une entreprise cotée : compte de résultat,
                                bilan, cash-flow…
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>Décodage des chiffres clés</li>
                                <li>Points forts / points de vigilance</li>
                                <li>Résumé orienté investisseur de long terme</li>
                                <li>Recommandations pédagogiques (pas de conseil d’investissement personnel)</li>
                            </ul>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">~ 1&nbsp;000&nbsp;FCFA</div>
                                    <div class="text-muted small">par état financier (tarif indicatif)</div>
                                </div>
                                {{-- 🔥 Bouton actif vers le formulaire EF --}}
                                <a href="{{ route('client-financials.create') }}" class="btn btn-outline-secondary">
                                    Analyser un état financier
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION FORMATIONS --}}
    <section class="py-5 bg-light">
        <div class="container" style="max-width: 1100px;">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-semibold mb-2">Formations BRVM pour monter en niveau</h2>
                    <p class="text-muted mb-3">
                        En parallèle des analyses IA, tu peux te former sérieusement sur la BRVM :
                        cours débutant, cours intermédiaire, exemples concrets, cas pratiques…
                    </p>
                    <ul class="text-muted small mb-3">
                        <li>Cours disponibles 24h/24, à vie une fois achetés</li>
                        <li>Accès depuis ton smartphone, ton PC ou ta tablette</li>
                        <li>Adaptés aux débutants comme à ceux qui veulent aller plus loin</li>
                    </ul>

                    <a href="{{ route('formations.brvm') }}" class="btn btn-success">
                        🎓 Voir les formations BRVM
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">Exemple</h6>
                            <h5 class="fw-semibold mb-2">« Investir à la BRVM – Guide du débutant »</h5>
                            <p class="small text-muted mb-2">
                                Comprendre les bases de la bourse régionale, ouvrir un compte-titres,
                                placer tes premiers ordres en limitant les erreurs classiques.
                            </p>
                            <div class="d-flex flex-wrap gap-2 small text-muted">
                                <span class="badge bg-light border">Vidéo HD</span>
                                <span class="badge bg-light border">Cas pratiques</span>
                                <span class="badge bg-light border">Mises à jour</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-4 border-top bg-white">
        <div class="container" style="max-width: 1100px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    © {{ date('Y') }} Coach BRVM – Une solution de CHENGGONG SARL.
                </div>
                <div class="small text-muted d-flex gap-3">
                    <a href="#" class="text-decoration-none text-muted">Conditions d’utilisation</a>
                    <a href="#" class="text-decoration-none text-muted">Confidentialité</a>
                    <a href="#" class="text-decoration-none text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</div>
@endsection
