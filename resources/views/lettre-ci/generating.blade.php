{{-- resources/views/lettre-ci/generating.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page {
        background: var(--cb-paper); min-height: 100vh;
        display: flex; align-items: center; justify-content: center;
        padding: 40px 0;
    }

    .lci-gen-box {
        max-width: 520px; width: 100%;
        background: var(--cb-card); border: 1px solid rgba(176,134,46,.14);
        border-radius: 6px; overflow: hidden;
    }
    .lci-gen-box::before {
        content:'';display:block;height:2px;
        background:linear-gradient(90deg,var(--cb-gold),var(--cb-forest),transparent);
    }
    .lci-gen-inner { padding: 48px 40px; text-align: center; }

    .lci-gen-icon {
        font-size: 56px; margin-bottom: 20px; display: block;
        filter: drop-shadow(0 0 24px rgba(176,134,46,.35));
        animation: iconPulse 2s ease-in-out infinite;
    }
    @keyframes iconPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(.94);opacity:.7} }

    .lci-gen-title {
        font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 900;
        color: var(--cb-ink); margin-bottom: 8px;
    }
    .lci-gen-sub { font-size: 14px; color: var(--cb-muted); line-height: 1.65; margin-bottom: 36px; }

    .lci-steps { margin-bottom: 32px; }
    .lci-step-row {
        display: flex; align-items: center; gap: 14px;
        padding: 12px 16px; border-radius: 4px; margin-bottom: 4px;
        background: rgba(15,92,67,.03); border: 1px solid var(--cb-border);
        transition: all .5s ease;
    }
    .lci-step-row.active { background: rgba(176,134,46,.05); border-color: rgba(176,134,46,.15); }
    .lci-step-row.done   { background: rgba(15,92,67,.04); border-color: rgba(15,92,67,.12); }
    .lci-step-indicator {
        width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; font-family: 'Syne', sans-serif;
        transition: all .5s;
    }
    .lci-step-indicator.pending { background: rgba(15,92,67,.04); color: var(--cb-muted); border: 1px solid var(--cb-border); }
    .lci-step-indicator.active  { background: rgba(176,134,46,.15); color: var(--cb-gold); border: 1px solid rgba(176,134,46,.3);
        animation: spinPulse 1.2s ease-in-out infinite; }
    .lci-step-indicator.done    { background: rgba(15,92,67,.12); color: var(--cb-forest); border: 1px solid rgba(15,92,67,.25); }
    @keyframes spinPulse { 0%,100%{opacity:1} 50%{opacity:.5} }
    .lci-step-label {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 600;
        color: var(--cb-muted); transition: color .5s; flex: 1; text-align: left;
    }
    .lci-step-row.active .lci-step-label { color: var(--cb-gold); }
    .lci-step-row.done   .lci-step-label { color: var(--cb-forest); }
    .lci-step-time {
        font-family: 'Syne', sans-serif; font-size: 10px; color: var(--cb-muted);
        letter-spacing: .06em; text-transform: uppercase; flex-shrink: 0;
    }

    .lci-progress { height: 2px; background: rgba(26,33,29,.08); border-radius: 1px; overflow: hidden; margin-bottom: 24px; }
    .lci-progress-fill {
        height: 100%; background: linear-gradient(90deg, var(--cb-gold), var(--cb-forest));
        border-radius: 1px; width: 0%; transition: width .8s ease;
    }

    .lci-gen-status {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .14em; text-transform: uppercase; color: var(--cb-muted);
    }
    .lci-gen-status.failed { color: var(--cb-down); }

    .cb-btn-gold {
        display:inline-flex;align-items:center;gap:8px;justify-content:center;
        background:linear-gradient(135deg,var(--cb-gold),#9B6B15);
        color:#050810 !important;font-family:'Syne',sans-serif;
        font-weight:800;font-size:13px;letter-spacing:.07em;text-transform:uppercase;
        padding:13px 28px;border:none;border-radius:3px;cursor:pointer;
        text-decoration:none;transition:all .3s;
    }
    .cb-btn-gold:hover { box-shadow:0 8px 28px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-outline {
        display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--cb-ink) !important;
        font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;
        padding:11px 22px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .25s;
    }
    .cb-btn-outline:hover { border-color:var(--cb-gold);color:var(--cb-gold) !important; }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="container">
        <div class="lci-gen-box mx-auto">
            <div class="lci-gen-inner">
                <span class="lci-gen-icon" id="genIcon">🤖</span>
                <h1 class="lci-gen-title">Génération en cours…</h1>
                <p class="lci-gen-sub">Notre IA Claude rédige votre lettre.<br>Cela prend généralement moins de 30 secondes.</p>

                <div class="lci-steps" id="stepsBlock">
                    <div class="lci-step-row active" id="step1">
                        <div class="lci-step-indicator active" id="ind1">⏳</div>
                        <span class="lci-step-label">Analyse de votre demande</span>
                        <span class="lci-step-time" id="t1"></span>
                    </div>
                    <div class="lci-step-row" id="step2">
                        <div class="lci-step-indicator pending" id="ind2">2</div>
                        <span class="lci-step-label">Rédaction par l'IA Claude</span>
                        <span class="lci-step-time" id="t2"></span>
                    </div>
                    <div class="lci-step-row" id="step3">
                        <div class="lci-step-indicator pending" id="ind3">3</div>
                        <span class="lci-step-label">Mise en forme finale</span>
                        <span class="lci-step-time" id="t3"></span>
                    </div>
                </div>

                <div class="lci-progress">
                    <div class="lci-progress-fill" id="progressFill"></div>
                </div>
                <div class="lci-gen-status" id="genStatus">Initialisation…</div>
                <div id="genActions" class="mt-4" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const STATUS_URL  = @json(route('lettreci.status', $letter));
    const PREVIEW_URL = @json(route('lettreci.preview', $letter));
    const NEW_URL     = @json(route('lettreci.new'));
    const HIST_URL    = @json(route('lettreci.history'));

    const genIcon    = document.getElementById('genIcon');
    const status     = document.getElementById('genStatus');
    const actions    = document.getElementById('genActions');
    const progress   = document.getElementById('progressFill');
    let attempts = 0, maxAttempts = 40, elapsed = 0;

    function setStep(n, state) {
        const row = document.getElementById('step'+n);
        const ind = document.getElementById('ind'+n);
        row.className = 'lci-step-row ' + state;
        if (state === 'done')   { ind.className = 'lci-step-indicator done'; ind.textContent = '✓'; }
        if (state === 'active') { ind.className = 'lci-step-indicator active'; ind.textContent = '⏳'; }
    }
    function setTime(n, t) { document.getElementById('t'+n).textContent = t; }

    // Animate steps progressively
    setTimeout(() => { setStep(1,'done'); setTime(1,'ok'); setStep(2,'active'); progress.style.width='35%'; status.textContent = 'Rédaction en cours…'; }, 3000);
    setTimeout(() => { setStep(2,'done'); setTime(2,'ok'); setStep(3,'active'); progress.style.width='70%'; status.textContent = 'Mise en forme…'; }, 8000);

    function poll() {
        fetch(STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.ready) {
                    setStep(1,'done'); setStep(2,'done'); setStep(3,'done');
                    progress.style.width = '100%';
                    genIcon.textContent = '✅';
                    genIcon.style.animation = 'none';
                    status.textContent = 'Lettre prête !';
                    window.location.href = PREVIEW_URL;
                } else if (data.failed) {
                    genIcon.textContent = '❌';
                    genIcon.style.animation = 'none';
                    status.className = 'lci-gen-status failed';
                    status.textContent = data.error || 'La génération a échoué.';
                    actions.innerHTML = '<a href="'+NEW_URL+'" class="cb-btn-gold me-2">Réessayer</a><a href="'+HIST_URL+'" class="cb-btn-outline">Historique</a>';
                    actions.style.display = 'flex'; actions.style.justifyContent = 'center'; actions.style.gap = '10px';
                } else {
                    attempts++;
                    elapsed += 3;
                    if (attempts < maxAttempts) setTimeout(poll, 3000);
                    else {
                        status.textContent = 'Délai dépassé. Vérifiez votre historique.';
                        actions.innerHTML = '<a href="'+HIST_URL+'" class="cb-btn-gold">Voir l\'historique</a>';
                        actions.style.display = 'block';
                    }
                }
            })
            .catch(() => { attempts++; if (attempts < maxAttempts) setTimeout(poll, 3000); });
    }

    setTimeout(poll, 3000);
})();
</script>
@endpush
