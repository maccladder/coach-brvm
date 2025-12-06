@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 900px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-5 text-center">

            {{-- Petites bulles animées --}}
            <div class="mb-4">
                <span class="badge rounded-circle bg-primary" style="width:14px;height:14px;">&nbsp;</span>
                <span class="badge rounded-circle bg-primary opacity-50 mx-2" style="width:10px;height:10px;">&nbsp;</span>
                <span class="badge rounded-circle bg-primary opacity-25" style="width:8px;height:8px;">&nbsp;</span>
            </div>

            <h2 class="mb-2">Paiement confirmé ✅</h2>

            <p class="lead mb-2">Merci ! Votre paiement a été accepté.</p>

            <p class="text-muted mb-4">
                L’IA est en train d’analyser vos états financiers
                @if($financial->title)
                    <strong>« {{ $financial->title }} »</strong>
                @endif
                .
            </p>

            {{-- Barre de progression --}}
            <div class="mb-2">
                <div class="progress" style="height: 12px;">
                    <div id="progressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width: 5%;"
                         aria-valuenow="5"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="small text-muted mb-4">
                <span id="progressPercent">5%</span> – L’IA lit votre document et prépare l’interprétation…
            </div>

            <p class="small text-muted mb-0">
                Cette étape peut prendre entre 1 et 2 minutes.<br>
                Gardez cette page ouverte, vous serez automatiquement redirigé vers le résultat.
            </p>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const statusUrl   = @json($statusUrl);
    const redirectUrl = @json($showUrl);

    const bar   = document.getElementById('progressBar');
    const label = document.getElementById('progressPercent');

    let pct = 5;

    // Fake progression jusqu'à 90%
    const fakeTimer = setInterval(() => {
        if (pct < 90) {
            pct += Math.floor(Math.random() * 5) + 1;
            if (pct > 90) pct = 90;
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', pct);
            label.textContent = pct + '%';
        }
    }, 2000);

    // Timeout sécurité 3 minutes
    const forceTimer = setTimeout(() => {
        window.location.href = redirectUrl;
    }, 180000);

    async function checkStatus() {
        try {
            const resp = await fetch(statusUrl, {
                headers: {'Accept': 'application/json'}
            });
            const data = await resp.json();

            if (data.ready) {
                // STOP timers
                clearInterval(fakeTimer);
                clearTimeout(forceTimer);

                // Finir la barre
                bar.style.width = '100%';
                bar.setAttribute('aria-valuenow', 100);
                label.textContent = '100%';

                // Redirection
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 800);

                return;
            }

        } catch (e) {
            console.warn('Erreur status financial', e);
        }

        setTimeout(checkStatus, 5000);
    }

    checkStatus();

});
</script>

@endsection
