@php
    $fmt = fn (int $n) => number_format($n, 0, ',', ' ');
    $amountLabel = $fmt($payable->amount);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0c1b33">
    <title>Paiement — {{ $payable->title }} | Boursiv</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0c1b33;
            --navy-2: #13284a;
            --gold: #f5c518;
            --teal: #1fb5a8;
            --teal-dark: #0f7d74;
            --bg: #f4f6fa;
            --ink: #14213d;
            --muted: #5b6577;
            --line: #dfe4ec;
            --danger: #c2410c;
            --alert: #f97316;
            --wa: #128c4a;
            --radius: 16px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        body.is-dark { background: var(--navy); }
        h1, h2, h3, .brand { font-family: 'Montserrat', 'Inter', sans-serif; }
        a { color: var(--teal-dark); }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        :focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }

        /* Header */
        .topbar {
            position: sticky; top: 0; z-index: 5;
            display: flex; align-items: center; justify-content: space-between;
            background: var(--navy); color: #fff;
            padding: 10px 12px; padding-top: max(10px, env(safe-area-inset-top));
        }
        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 12px;
            color: #fff; background: transparent; border: 0; text-decoration: none; cursor: pointer;
        }
        .icon-btn:hover { background: rgba(255,255,255,.08); }
        .brand { font-weight: 800; letter-spacing: .08em; font-size: 18px; color: #fff; text-decoration: none; }
        .brand span { color: var(--gold); }
        .lock { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; color: var(--gold); }

        main { max-width: 480px; margin: 0 auto; padding: 16px 16px 40px; }
        .screen[hidden] { display: none !important; }

        /* Carte produit */
        .card {
            background: #fff; border-radius: var(--radius);
            box-shadow: 0 1px 2px rgba(12,27,51,.06), 0 6px 20px rgba(12,27,51,.06);
            padding: 14px; display: flex; gap: 14px; align-items: center;
        }
        .thumb {
            flex: 0 0 76px; width: 76px; height: 76px; border-radius: 12px; overflow: hidden;
            background: linear-gradient(135deg, var(--navy), var(--navy-2));
            display: flex; align-items: center; justify-content: center; font-size: 34px;
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .card h1 { font-size: 17px; line-height: 1.3; margin: 0 0 4px; }
        .price { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 20px; color: var(--navy); }
        .badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; padding: 0; list-style: none; }
        .badges li {
            font-size: 12px; font-weight: 600; color: var(--teal-dark);
            background: #e6f6f4; border-radius: 999px; padding: 3px 10px;
        }
        .note { margin: 8px 0 0; font-weight: 600; color: var(--navy); }

        .step-title { font-size: 16px; margin: 26px 0 10px; }

        /* Opérateurs */
        .operators { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .op {
            min-height: 76px; border-radius: 14px; border: 3px solid transparent;
            font: 700 14px/1.2 'Inter', sans-serif; cursor: pointer;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
            box-shadow: 0 1px 2px rgba(12,27,51,.12);
            transition: transform .12s ease, border-color .12s ease;
        }
        .op .dot { width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,.55); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; }
        .op[aria-pressed="true"] { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(31,181,168,.25); transform: translateY(-1px); }
        .op[aria-pressed="true"] .dot::after { content: '✓'; color: var(--navy); font-weight: 800; }

        /* Téléphone */
        .phone-row {
            display: flex; align-items: stretch; background: #fff;
            border: 2px solid var(--line); border-radius: 14px; overflow: hidden;
        }
        .phone-row:focus-within { border-color: var(--teal); }
        .prefix { display: flex; align-items: center; padding: 0 12px; font-weight: 700; color: var(--navy); background: #eef1f6; border-right: 2px solid var(--line); }
        .phone-row input {
            flex: 1; min-width: 0; border: 0; padding: 14px 12px; font: 600 18px 'Inter', sans-serif;
            letter-spacing: .04em; color: var(--ink); background: transparent; outline: none;
        }
        .hint { font-size: 14px; color: var(--muted); margin: 8px 2px 0; }
        .error { color: var(--danger); font-size: 14px; font-weight: 600; margin: 8px 2px 0; min-height: 0; }
        .error:empty { display: none; }

        /* Boutons */
        .btn {
            display: flex; width: 100%; min-height: 52px; align-items: center; justify-content: center; gap: 8px;
            border: 0; border-radius: 14px; font: 700 17px 'Inter', sans-serif; cursor: pointer; text-decoration: none;
            padding: 12px 18px; text-align: center;
        }
        .btn-gold { background: var(--gold); color: var(--navy); box-shadow: 0 6px 18px rgba(245,197,24,.35); }
        .btn-gold:hover { filter: brightness(.97); }
        .btn-gold[disabled] { opacity: .65; cursor: progress; }
        .btn-wa { background: var(--wa); color: #fff; }
        .btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.35); }
        .btn + .btn { margin-top: 10px; }
        #pay-btn { margin-top: 24px; }

        .footer-links { margin-top: 20px; text-align: center; font-size: 14px; color: var(--muted); }
        .footer-links p { margin: 6px 0; }
        .linklike {
            background: none; border: 0; padding: 10px 6px; min-height: 44px; cursor: pointer;
            font: 500 14px 'Inter', sans-serif; color: var(--muted); text-decoration: underline;
        }
        .secure { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--ink); }
        .wa-link { display: inline-flex; align-items: center; min-height: 44px; font-weight: 600; color: var(--wa); }

        /* Écran attente (fond navy) */
        .dark { color: #fff; text-align: center; }
        .dark h2 { font-size: 21px; margin: 18px 0 6px; }
        .dark .sub { color: #c9d3e6; margin: 0 auto; max-width: 340px; }
        .dark a { color: var(--gold); }
        .timer { position: relative; width: 132px; height: 132px; margin: 14px auto 0; }
        .timer svg { transform: rotate(-90deg); }
        .timer .value {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            font: 800 30px 'Montserrat', sans-serif; color: #fff;
        }
        .steps { list-style: none; padding: 0; margin: 22px auto 0; text-align: left; max-width: 360px; }
        .steps li { display: flex; gap: 12px; align-items: flex-start; margin: 0 0 12px; color: #e6ebf5; }
        .steps .n {
            flex: 0 0 28px; height: 28px; border-radius: 50%; background: var(--gold); color: var(--navy);
            font: 800 14px 'Montserrat', sans-serif; display: flex; align-items: center; justify-content: center;
        }
        .instructions {
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
            border-radius: 14px; padding: 14px; margin: 16px 0 0; text-align: left; color: #fff;
        }
        .instructions[hidden] { display: none; }
        .instructions img { display: block; max-width: 220px; margin: 10px auto 0; background: #fff; padding: 8px; border-radius: 10px; }
        .otp-box { margin-top: 18px; text-align: left; }
        .otp-box label { font-weight: 600; display: block; margin-bottom: 6px; }
        .otp-box input {
            width: 100%; min-height: 52px; border-radius: 14px; border: 2px solid rgba(255,255,255,.35);
            background: #fff; color: var(--ink); font: 700 22px 'Inter', sans-serif; letter-spacing: .3em; text-align: center;
        }
        .dark .error { color: #fdba74; }
        .dark .linklike { color: #c9d3e6; }

        /* L'attribut hidden l'emporte toujours (sinon .btn / img en display:flex/block restent visibles) */
        [hidden] { display: none !important; }
        .raw { font-size: 12px; color: #9fb0cc; margin: 8px 0 0; font-style: italic; }
        .otp-icon { font-size: 44px; margin-top: 18px; }
        .otp-box input { min-height: 60px; font-size: 26px; }
        .classic-note { margin: 10px 2px 0; font-size: 14px; color: var(--navy); background: #e8f8fd; border: 1px solid #b6e9f8; border-radius: 12px; padding: 10px 12px; }
        .sim-banner { background: #fff3cd; color: #664d03; border: 1px solid #ffe69c; border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 600; margin-bottom: 12px; text-align: center; }

        /* Succès / échec */
        .result { text-align: center; padding-top: 18px; }
        .result h2 { font-size: 24px; margin: 14px 0 4px; }
        .badge-icon { width: 84px; height: 84px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
        .badge-icon.ok { background: #e6f6f4; color: var(--teal); }
        .badge-icon.ko { background: #fff1e7; color: var(--alert); }
        .recap { margin: 18px 0; text-align: left; }
        .recap .row { display: flex; justify-content: space-between; gap: 10px; font-size: 14px; color: var(--muted); padding: 4px 2px; }
        .recap .row strong { color: var(--ink); }
        .causes { text-align: left; background: #fff; border-radius: var(--radius); padding: 14px 16px; margin: 16px 0; }
        .causes h3 { font-size: 15px; margin: 0 0 6px; }
        .causes ul { margin: 0; padding-left: 20px; color: var(--muted); }
        .reassure { font-weight: 600; color: var(--teal-dark); }
        .pending-note { color: var(--muted); }
        .spinner { width: 18px; height: 18px; border: 3px solid rgba(12,27,51,.25); border-top-color: var(--navy); border-radius: 50%; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
        @media (min-width: 600px) { main { padding-top: 28px; } }
    </style>
</head>
<body>

<header class="topbar">
    <a class="icon-btn" href="{{ $backUrl }}" aria-label="Retour">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a class="brand" href="{{ url('/') }}" aria-label="Boursiv, accueil">BOURSI<span>V</span></a>
    <span class="lock" role="img" aria-label="Paiement sécurisé">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 10V7a4 4 0 118 0v3" stroke="currentColor" stroke-width="2"/></svg>
    </span>
</header>

<div class="sr-only" aria-live="polite" id="live"></div>

<main>
    {{-- ═══ Écran 1 : paiement ═══ --}}
    <section class="screen" id="screen-pay" aria-labelledby="pay-title">
        @if($simulating)
            <div class="sim-banner" role="status">Mode simulation : aucun appel à Paystack (numéros de test …0001 à …0005)</div>
        @endif
        <div class="card">
            <div class="thumb" aria-hidden="true">
                @if($payable->image)
                    <img src="{{ $payable->image }}" alt="">
                @else
                    {{ $payable->icon }}
                @endif
            </div>
            <div>
                <h1 id="pay-title" tabindex="-1">{{ $payable->title }}</h1>
                <div class="price">{{ $amountLabel }} F CFA</div>
                @if($payable->note)
                    <p class="note">{{ $payable->note }}</p>
                @endif
                @if($payable->badges)
                    <ul class="badges">
                        @foreach($payable->badges as $badge)
                            <li>{{ $badge }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <form id="pay-form" novalidate>
            <h2 class="step-title" id="op-title">1. Choisis ton opérateur</h2>
            <div class="operators" role="group" aria-labelledby="op-title">
                @foreach($operators as $key => $op)
                    <button type="button" class="op" data-operator="{{ $key }}" data-label="{{ $op['label'] }}"
                            @if($op['classic']) data-classic="1" aria-describedby="classic-note" @endif
                            aria-pressed="false"
                            style="background: {{ $op['color'] }}; color: {{ $op['text'] }};">
                        <span class="dot" aria-hidden="true"></span>
                        {{ $op['label'] }}
                    </button>
                @endforeach
            </div>
            <p class="error" id="op-error" role="alert"></p>
            <p class="classic-note" id="classic-note" hidden>Tu vas être redirigé vers la page sécurisée Paystack pour payer avec Wave.</p>

            <div id="phone-block">
            <h2 class="step-title"><label for="phone" id="phone-label">2. Ton numéro Mobile Money</label></h2>
            <div class="phone-row">
                <span class="prefix" aria-hidden="true">{{ $phonePrefix }}</span>
                <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national"
                       placeholder="07 07 12 34 56" maxlength="14" aria-describedby="phone-hint phone-error" required>
            </div>
            <p class="hint" id="phone-hint">Garde ton téléphone à portée : tu vas recevoir une demande de validation.</p>
            <p class="error" id="phone-error" role="alert"></p>
            </div>

            <button type="submit" class="btn btn-gold" id="pay-btn">
                <span id="pay-btn-text">Payer {{ $amountLabel }} F →</span>
            </button>
            <p class="error" id="pay-error" role="alert"></p>
        </form>

        <form method="POST" action="{{ route('checkout.mm.classic', $type) }}" id="classic-op-form" hidden>
            @csrf
            @foreach($payable->params as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="operator" id="classic-op" value="">
        </form>

        <div class="footer-links">
            <p class="secure">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 10V7a4 4 0 118 0v3" stroke="currentColor" stroke-width="2"/></svg>
                Paiement sécurisé par Paystack
            </p>
            <p><a class="wa-link" href="{{ $whatsapp }}" target="_blank" rel="noopener">Besoin d'aide ? Écris-nous sur WhatsApp</a></p>
            <form method="POST" action="{{ $classic['action'] }}">
                @csrf
                @foreach($classic['fields'] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <button type="submit" class="linklike">Autre moyen de paiement</button>
            </form>
        </div>
    </section>

    {{-- ═══ Écran 2a : code de paiement (send_otp, ex. Orange Money) — pas de compte à rebours ═══ --}}
    <section class="screen dark" id="screen-otp" aria-labelledby="otp-title" hidden>
        <div class="otp-icon" aria-hidden="true">🔐</div>
        <h2 id="otp-title" tabindex="-1">Saisis ton code de paiement</h2>
        <p class="sub" id="otp-help"></p>

        <form class="otp-box" id="otp-form" novalidate>
            <label for="otp">Code de paiement</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                   maxlength="8" aria-describedby="otp-error otp-raw">
            <p class="error" id="otp-error" role="alert"></p>
            <button type="submit" class="btn btn-gold" id="otp-btn" style="margin-top:12px;">Valider le paiement</button>
        </form>
        <p class="raw" id="otp-raw" hidden></p>

        <div class="footer-links">
            <p><button type="button" class="linklike" data-change>Changer de numéro ou d'opérateur</button></p>
            <p><a class="wa-link" href="{{ $whatsapp }}" target="_blank" rel="noopener" style="color:#7ee2a8;">Un souci ? Écris-nous sur WhatsApp</a></p>
        </div>
    </section>

    {{-- ═══ Écran 2b : attente de validation ═══ --}}
    <section class="screen dark" id="screen-wait" aria-labelledby="wait-title" hidden>
        <div class="timer" role="timer" aria-label="Temps restant pour valider">
            <svg width="132" height="132" viewBox="0 0 132 132" aria-hidden="true">
                <circle cx="66" cy="66" r="58" stroke="rgba(255,255,255,.15)" stroke-width="8" fill="none"/>
                <circle id="timer-ring" cx="66" cy="66" r="58" stroke="#f5c518" stroke-width="8" fill="none"
                        stroke-linecap="round" stroke-dasharray="364.4" stroke-dashoffset="0"/>
            </svg>
            <div class="value" id="timer-value">3:00</div>
        </div>

        <h2 id="wait-title" tabindex="-1">Valide le paiement sur ton téléphone</h2>
        <p class="sub" id="wait-sub"></p>

        <ol class="steps">
            <li><span class="n" aria-hidden="true">1</span><span>Garde ton téléphone déverrouillé, une demande va s'afficher.</span></li>
            <li><span class="n" aria-hidden="true">2</span><span id="step-2">Ouvre la notification ou l'application de ton opérateur.</span></li>
            <li><span class="n" aria-hidden="true">3</span><span>Confirme avec ton code secret. Cette page se met à jour toute seule.</span></li>
        </ol>

        {{-- Rempli seulement avec ce que Paystack renvoie réellement (texte, lien valide, image valide) --}}
        <div class="instructions" id="instructions" hidden>
            <p id="display-text" style="margin:0;" hidden></p>
            <p class="raw" id="display-raw" hidden></p>
            <a id="action-url" class="btn btn-gold" href="#" target="_blank" rel="noopener" style="margin-top:12px;" hidden></a>
            <img id="qr" alt="QR code à scanner avec ton application" hidden>
        </div>

        <div class="footer-links">
            <p><button type="button" class="linklike" data-change>Changer de numéro ou d'opérateur</button></p>
            <p><a class="wa-link" href="{{ $whatsapp }}" target="_blank" rel="noopener" style="color:#7ee2a8;">Un souci ? Écris-nous sur WhatsApp</a></p>
        </div>
    </section>

    {{-- ═══ Écran 3 : succès ═══ --}}
    <section class="screen result" id="screen-success" aria-labelledby="success-title" hidden>
        <div class="badge-icon ok" aria-hidden="true">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h2 id="success-title" tabindex="-1">C'est payé !</h2>
        <p class="note" id="success-note" hidden></p>

        <div class="card recap">
            <div class="thumb" aria-hidden="true">
                @if($payable->image)<img src="{{ $payable->image }}" alt="">@else{{ $payable->icon }}@endif
            </div>
            <div style="flex:1;">
                <strong>{{ $payable->title }}</strong>
                <div class="row"><span>Montant</span><strong>{{ $amountLabel }} F CFA</strong></div>
                <div class="row"><span>Payé avec</span><strong id="success-op"></strong></div>
            </div>
        </div>

        <a class="btn btn-gold" id="success-btn" href="{{ $payable->successUrl }}">{{ $payable->successLabel }}</a>
    </section>

    {{-- ═══ Écran 4 : échec / expiration / paiement non démarré ═══ --}}
    <section class="screen result" id="screen-fail" aria-labelledby="fail-title" hidden>
        <div class="badge-icon ko" aria-hidden="true">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none"><path d="M12 8v5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="12" cy="17" r="1.6" fill="currentColor"/><path d="M10.3 3.9L2.6 17.5A2 2 0 004.3 20.5h15.4a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <h2 id="fail-title" tabindex="-1">Le paiement n'est pas passé</h2>
        {{-- Uniquement si Paystack confirme failed/abandoned sur une transaction réellement traitée --}}
        <p class="reassure" id="fail-not-debited" hidden>Tu n'as pas été débité.</p>
        <p class="pending-note" id="fail-pending" hidden>Si tu as validé sur ton téléphone, ton achat s'activera automatiquement dès que l'opérateur confirme.</p>
        <p class="pending-note" id="fail-message" hidden></p>

        <div class="causes" id="fail-causes">
            <h3>Causes fréquentes</h3>
            <ul>
                <li>Demande non confirmée dans les 3 minutes</li>
                <li>Solde insuffisant sur ton compte Mobile Money</li>
                <li>Code secret ou code de paiement erroné</li>
            </ul>
        </div>

        <button type="button" class="btn btn-gold" id="retry-btn">Réessayer</button>
        <a class="btn btn-wa" href="{{ $whatsapp }}" target="_blank" rel="noopener">
            {{ $payable->offerActivation ? "On t'active l'accès, tu paies après" : 'Un souci ? Écris-nous sur WhatsApp' }}
        </a>
    </section>
</main>

<script>
(() => {
    const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
    const PAY_URL     = @json(route('checkout.mm.pay', $type));
    const STATUS_URL  = @json(route('checkout.mm.status', '__REF__'));
    const OTP_URL     = @json(route('checkout.mm.otp', '__REF__'));
    const PARAMS      = @json((object) $payable->params);
    const WINDOW      = {{ (int) $window }};
    const POLL_MS     = 4000;
    const OTP_POLL_MS = 6000;
    const RING        = 364.4;

    const $ = (id) => document.getElementById(id);

    // Retour arrière (cache de page iOS) : pas de champ refocalisé, donc pas de clavier
    window.addEventListener('pageshow', (e) => {
        const el = document.activeElement;
        if (e.persisted && el && el.matches('input, select, textarea')) el.blur();
    });
    const screens = ['screen-pay', 'screen-otp', 'screen-wait', 'screen-success', 'screen-fail'];

    let operator = null, operatorLabel = '', reference = null;
    let pollTimer = null, countdownTimer = null, deadline = 0, lateChecks = 0;

    function show(id, focusId) {
        screens.forEach(s => { $(s).hidden = s !== id; });
        document.body.classList.toggle('is-dark', id === 'screen-wait' || id === 'screen-otp');
        window.scrollTo(0, 0);
        // Focus sur un titre (jamais sur un champ : pas de clavier automatique)
        if (focusId) $(focusId).focus();
    }

    function announce(text) { $('live').textContent = text; }

    function formatPhone(digits) {
        return digits.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
    }

    function setText(id, text) {
        $(id).textContent = text || '';
        $(id).hidden = !text;
    }

    // ── Écran 1 ──────────────────────────────────────────────
    document.querySelectorAll('.op').forEach(btn => {
        btn.addEventListener('click', () => selectOperator(btn.dataset.operator));
    });

    let operatorClassic = false;

    function selectOperator(key) {
        document.querySelectorAll('.op').forEach(b => {
            const on = b.dataset.operator === key;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (on) { operatorLabel = b.dataset.label; operatorClassic = b.dataset.classic === '1'; }
        });
        operator = key;
        $('phone-label').textContent = '2. Ton numéro ' + operatorLabel;
        $('op-error').textContent = '';

        // Opérateur via la page Paystack classique : pas de numéro à saisir ici
        $('classic-note').hidden = !operatorClassic;
        $('phone-block').hidden = operatorClassic;
        $('pay-btn-text').textContent = operatorClassic
            ? 'Continuer avec ' + operatorLabel + ' →'
            : @json('Payer ' . $amountLabel . ' F →');
    }

    $('phone').addEventListener('input', (e) => {
        const digits = e.target.value.replace(/\D/g, '').slice(0, 10);
        e.target.value = formatPhone(digits);
        $('phone-error').textContent = '';
    });

    $('pay-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (operatorClassic) {
            $('classic-op').value = operator;
            setPaying(true);
            $('classic-op-form').submit();
            return;
        }

        const phone = $('phone').value.replace(/\D/g, '');
        let ok = true;
        if (!operator) { $('op-error').textContent = 'Choisis ton opérateur.'; ok = false; }
        if (phone.length !== 10) { $('phone-error').textContent = 'Le numéro doit faire 10 chiffres (ex. 07 07 12 34 56).'; ok = false; }
        if (!ok) return;

        setPaying(true);
        $('pay-error').textContent = '';

        try {
            const res = await fetch(PAY_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(Object.assign({}, PARAMS, { operator, phone })),
            });
            const data = await res.json().catch(() => ({}));

            if (res.status === 409 && data.redirect) { window.location.href = data.redirect; return; }
            if (res.status === 422) {
                const errs = data.errors || {};
                $('op-error').textContent = (errs.operator || [''])[0];
                $('phone-error').textContent = (errs.phone || [''])[0];
                return;
            }
            if (res.status === 429) { $('pay-error').textContent = 'Trop de tentatives. Patiente une minute puis réessaie.'; return; }
            if (!res.ok) { $('pay-error').textContent = 'Le paiement n\'a pas pu démarrer. Réessaie dans un instant.'; return; }

            handle(data);
        } catch (err) {
            $('pay-error').textContent = 'Problème de connexion. Vérifie ton réseau et réessaie.';
        } finally {
            setPaying(false);
        }
    });

    function setPaying(on) {
        $('pay-btn').disabled = on;
        $('pay-btn-text').textContent = on ? 'Envoi de la demande…' : @json('Payer ' . $amountLabel . ' F →');
    }

    // ── États renvoyés par le serveur ────────────────────────
    function handle(d) {
        reference = d.reference;

        if (d.status === 'success') return showSuccess(d);
        if (d.status === 'failed' || d.status === 'expired') return showFail(d);
        if (d.status === 'send_otp') return showOtp(d);
        showWait(d);
    }

    function showOtp(d) {
        if ($('screen-otp').hidden) {
            stopTimers();
            $('otp-title').textContent = 'Saisis ton code de paiement ' + d.operator_label;
            $('otp-help').textContent = d.otp_help;
            $('otp').value = '';
            show('screen-otp', 'otp-title');
            announce('Saisis ton code de paiement ' + d.operator_label + '.');
        }
        setText('otp-error', d.message);
        setText('otp-raw', d.display_text_raw);

        // Pas de compte à rebours ici ; vérification discrète au cas où le paiement passe autrement
        clearTimeout(pollTimer);
        pollTimer = setTimeout(async () => {
            const s = await fetchStatus();
            if (s && !$('screen-otp').hidden) handle(s);
        }, OTP_POLL_MS);
    }

    function showWait(d) {
        if ($('screen-wait').hidden) {
            show('screen-wait', 'wait-title');
            startCountdown(d.seconds_left);
            startPolling();
            announce('Demande envoyée. Valide le paiement sur ton téléphone.');
        }
        $('wait-sub').textContent = 'Une demande de ' + Number(d.amount).toLocaleString('fr-FR') + ' F a été envoyée sur ton '
            + d.operator_label + ' ' + formatPhone(d.phone) + '.';
        $('step-2').textContent = 'Ouvre la notification ou l\'application ' + d.operator_label + '.';

        // Texte traduit si connu, sinon l'original en petit ; lien et image seulement s'ils sont valides
        setText('display-text', d.display_text);
        setText('display-raw', d.display_text_raw);
        $('action-url').hidden = !d.action_url;
        if (d.action_url) { $('action-url').href = d.action_url; $('action-url').textContent = 'Ouvrir ' + d.operator_label; }
        $('qr').hidden = !d.qr_code;
        if (d.qr_code) $('qr').src = d.qr_code; else $('qr').removeAttribute('src');
        $('instructions').hidden = !(d.display_text || d.display_text_raw || d.action_url || d.qr_code);
    }

    function showSuccess(d) {
        stopTimers();
        $('success-title').textContent = d.success_title || 'C\'est payé !';
        $('success-op').textContent = d.operator_label;
        $('success-btn').href = d.success_url;
        $('success-btn').textContent = d.success_label;
        setText('success-note', d.note);
        show('screen-success', 'success-title');
        announce('Paiement confirmé.');
    }

    function showFail(d) {
        stopTimers();
        // Erreur API : le paiement n'a jamais démarré, on n'affirme rien sur le débit
        $('fail-title').textContent = d.start_failed ? 'Le paiement n\'a pas pu démarrer' : 'Le paiement n\'est pas passé';
        $('fail-not-debited').hidden = d.start_failed || !d.not_debited;
        $('fail-pending').hidden = d.start_failed || d.not_debited;
        $('fail-causes').hidden = !!d.start_failed;
        setText('fail-message', d.message);
        show('screen-fail', 'fail-title');
        announce(d.start_failed ? 'Le paiement n\'a pas pu démarrer.' : 'Le paiement n\'est pas passé.');

        // Expiré sans confirmation de Paystack : on continue de vérifier discrètement
        // (une validation tardive sur le téléphone fera passer l'écran en succès)
        if (!d.not_debited && d.status === 'expired' && lateChecks < 40) {
            pollTimer = setTimeout(async () => {
                lateChecks++;
                const s = await fetchStatus();
                if (s && s.status === 'success') showSuccess(s);
                else if (s) showFail(s);
            }, 15000);
        }
    }

    // ── Polling + compte à rebours ───────────────────────────
    async function fetchStatus() {
        try {
            const res = await fetch(STATUS_URL.replace('__REF__', encodeURIComponent(reference)), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return null;
            return await res.json();
        } catch (e) { return null; }
    }

    function startPolling() {
        clearTimeout(pollTimer);
        const tick = async () => {
            const d = await fetchStatus();
            if (d) handle(d);
            if (!$('screen-wait').hidden) pollTimer = setTimeout(tick, POLL_MS);
        };
        pollTimer = setTimeout(tick, POLL_MS);
    }

    function startCountdown(secondsLeft) {
        clearInterval(countdownTimer);
        deadline = Date.now() + Math.max(0, secondsLeft) * 1000;
        const render = () => {
            const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
            $('timer-value').textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
            $('timer-ring').setAttribute('stroke-dashoffset', String(RING * (1 - left / WINDOW)));
            if (left === 0) {
                clearInterval(countdownTimer);
                fetchStatus().then(d => { if (d) handle(d); });
            }
        };
        render();
        countdownTimer = setInterval(render, 1000);
    }

    function stopTimers() {
        clearTimeout(pollTimer);
        clearInterval(countdownTimer);
    }

    // ── Code de paiement (send_otp) ──────────────────────────
    $('otp').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 8); });

    $('otp-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const otp = $('otp').value.replace(/\D/g, '');
        if (otp.length < 3) { setText('otp-error', 'Saisis le code de paiement généré sur ton téléphone.'); return; }
        setText('otp-error', '');
        $('otp-btn').disabled = true;
        $('otp-btn').textContent = 'Validation…';
        try {
            const res = await fetch(OTP_URL.replace('__REF__', encodeURIComponent(reference)), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ otp }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 422) { setText('otp-error', ((data.errors || {}).otp || ['Code invalide.'])[0]); return; }
            if (res.status === 429) { setText('otp-error', 'Trop de tentatives. Patiente une minute.'); return; }
            if (res.ok) handle(data);
        } catch (err) {
            setText('otp-error', 'Problème de connexion. Réessaie.');
        } finally {
            $('otp-btn').disabled = false;
            $('otp-btn').textContent = 'Valider le paiement';
        }
    });

    // ── Navigation ───────────────────────────────────────────
    function backToForm() {
        stopTimers();
        show('screen-pay', 'pay-title');
    }
    document.querySelectorAll('[data-change]').forEach(b => b.addEventListener('click', backToForm));
    $('retry-btn').addEventListener('click', backToForm);
})();
</script>
</body>
</html>
