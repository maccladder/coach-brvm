@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 700px;">

    <div class="card shadow-sm border-0">
        <div class="card-body text-center p-4">

            <div class="mb-3">
                {{-- Mets un petit loader (gif ou svg) dans public/img/ --}}
                <img src="{{ asset('img/loader-dots.gif') }}"
                     alt="Chargement…"
                     style="width:90px;height:auto;">
            </div>

            <h3 class="fw-bold mb-2">Paiement confirmé ✅</h3>

            <p class="text-muted mb-3">
                Merci ! Votre paiement a été accepté.<br>
                L’IA est en train d’analyser votre BOC
                @if($boc->title)
                    <strong>« {{ $boc->title }} »</strong>
                @endif
                .
            </p>

            {{-- Barre de progression "fake" pour rassurer --}}
            <div class="mb-2">
                <div class="progress" style="height: 10px;">
                    <div id="progressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width: 5%;"
                         aria-valuenow="5" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="small text-muted mb-3">
                <span id="progressPercent">5%</span> – L’IA lit votre document et prépare l’interprétation…
            </div>

            <p class="small text-muted mb-0">
                Cette étape peut prendre entre 1 et 2 minutes.<br>
                Gardez cette page ouverte, vous serez automatiquement redirigé vers le résultat.
            </p>
        </div>
    </div>
</div>

{{-- JS : animation de la barre + polling de l'état --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const statusUrl   = @json($statusUrl);
        const redirectUrl = @json($showUrl);

        const bar    = document.getElementById('progressBar');
        const label  = document.getElementById('progressPercent');

        // Animation "fake" du pourcentage pour rassurer visuellement
        let pct = 5;
        const fakeTimer = setInterval(() => {
            if (pct < 90) {
                pct += Math.floor(Math.random() * 5) + 1; // +1 à +5
                if (pct > 90) pct = 90;
                bar.style.width = pct + '%';
                bar.setAttribute('aria-valuenow', pct);
                label.textContent = pct + '%';
            }
        }, 2000);

        // Polling serveur : on vérifie toutes les 5s si l'analyse est prête
        async function checkStatus() {
            try {
                const resp = await fetch(statusUrl, {
                    headers: {'Accept': 'application/json'}
                });
                const data = await resp.json();

                if (data.ready) {
                    clearInterval(fakeTimer);
                    bar.style.width = '100%';
                    bar.setAttribute('aria-valuenow', 100);
                    label.textContent = '100%';

                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 800);
                    return;
                }
            } catch (e) {
                console.warn('Erreur status BOC', e);
            }

            setTimeout(checkStatus, 5000);
        }

        checkStatus();

        // Sécurité : forcer la redirection après 3 min au cas où
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 180000);
    });
</script>
@endsection
