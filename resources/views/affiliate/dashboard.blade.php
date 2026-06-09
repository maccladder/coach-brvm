@extends('layouts.app')

@push('styles')
<style>
    .af-page { background:#060910; min-height:100vh; }
    .af-hero { background:#0C1120; border-bottom:1px solid rgba(201,168,76,.08); padding:36px 0 28px; }
    .af-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:#C9A84C; display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .af-hero-tag::before { content:''; width:28px; height:1px; background:#C9A84C; }
    .af-hero-title { font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,36px); font-weight:900; color:#E8EAF0; }
    .af-hero-sub { font-size:13px; color:#6B7590; margin-top:4px; }

    .af-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px; }
    @media(max-width:768px){ .af-kpi-grid{grid-template-columns:1fr 1fr;} }
    .af-kpi { background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:4px; padding:18px 20px; position:relative; overflow:hidden; transition:border-color .25s; }
    .af-kpi::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--kc),transparent); }
    .af-kpi:hover { border-color:rgba(201,168,76,.15); }
    .af-kpi-label { font-family:'Syne',sans-serif; font-size:10px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:#6B7590; margin-bottom:8px; }
    .af-kpi-value { font-family:'Playfair Display',serif; font-size:clamp(20px,3vw,28px); font-weight:900; color:var(--kc); line-height:1; margin-bottom:4px; }
    .af-kpi-sub { font-size:11px; color:#6B7590; line-height:1.6; }

    .af-section { background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:4px; overflow:hidden; margin-bottom:20px; }
    .af-section-header { background:#121A2C; border-bottom:1px solid rgba(255,255,255,.05); padding:14px 20px; display:flex; justify-content:space-between; align-items:center; }
    .af-section-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#C9A84C; }
    .af-section-body { padding:24px; }

    /* Code + lien */
    .af-code-box { background:#060910; border:1px solid rgba(201,168,76,.15); border-radius:3px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .af-code-text { font-family:'Syne',sans-serif; font-size:22px; font-weight:900; color:#C9A84C; letter-spacing:.1em; }
    .af-link-box { background:#060910; border:1px solid rgba(255,255,255,.08); border-radius:3px; padding:10px 14px; font-family:'DM Sans',sans-serif; font-size:12px; color:#9AA3B8; word-break:break-all; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex; align-items:center; gap:7px; background:linear-gradient(135deg,#C9A84C,#9B6B15); color:#050810 !important; font-family:'Syne',sans-serif; font-weight:800; font-size:11px; letter-spacing:.07em; text-transform:uppercase; padding:9px 16px; border:none; border-radius:3px; cursor:pointer; text-decoration:none; transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3); transform:translateY(-1px); }
    .cb-btn-outline { display:inline-flex; align-items:center; gap:7px; background:transparent; color:#E8EAF0 !important; font-family:'Syne',sans-serif; font-weight:600; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:8px 14px; border:1px solid rgba(255,255,255,.12); border-radius:3px; text-decoration:none; transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; background:rgba(201,168,76,.05); }
    .cb-btn-whatsapp { display:inline-flex; align-items:center; gap:7px; background:#25D366; color:#fff !important; font-family:'Syne',sans-serif; font-weight:700; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:9px 16px; border:none; border-radius:3px; cursor:pointer; text-decoration:none; transition:all .3s; }
    .cb-btn-whatsapp:hover { background:#1ebe5d; }
    .cb-btn-fb { display:inline-flex; align-items:center; gap:7px; background:#1877F2; color:#fff !important; font-family:'Syne',sans-serif; font-weight:700; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:9px 16px; border:none; border-radius:3px; cursor:pointer; text-decoration:none; transition:all .3s; }
    .cb-btn-fb:hover { background:#0d6ce4; }

    /* QR */
    .af-qr { background:#fff; border-radius:4px; padding:10px; display:inline-block; }
    .af-qr svg { display:block; }

    /* Table */
    .af-table { width:100%; border-collapse:collapse; }
    .af-table th { font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#6B7590; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,.06); white-space:nowrap; }
    .af-table td { padding:13px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; color:#9AA3B8; vertical-align:middle; }
    .af-table tr:last-child td { border-bottom:none; }
    .af-table tr:hover td { background:rgba(201,168,76,.02); }
    .af-table td:first-child { color:#E8EAF0; }
    .af-table-footer { padding:14px 20px; border-top:1px solid rgba(255,255,255,.05); }

    .af-price { font-family:'Playfair Display',serif; font-size:15px; font-weight:700; color:#C9A84C; }
    .af-empty { text-align:center; padding:36px; font-family:'Syne',sans-serif; font-size:11px; letter-spacing:.1em; text-transform:uppercase; color:#6B7590; }

    /* Badges statut */
    .af-badge { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:3px 10px; border-radius:100px; }
    .af-b-pending   { background:rgba(255,200,30,.08);  color:#FFC850; border:1px solid rgba(255,200,30,.2); }
    .af-b-validated { background:rgba(15,207,164,.08);  color:#0FCFA4; border:1px solid rgba(15,207,164,.2); }
    .af-b-paid      { background:rgba(99,179,237,.08);  color:#63B3ED; border:1px solid rgba(99,179,237,.2); }
    .af-b-cancelled { background:rgba(255,107,107,.08); color:#FF6B6B; border:1px solid rgba(255,107,107,.2); }
    .af-b-approved  { background:rgba(99,179,237,.08);  color:#63B3ED; border:1px solid rgba(99,179,237,.2); }
    .af-b-rejected  { background:rgba(255,107,107,.08); color:#FF6B6B; border:1px solid rgba(255,107,107,.2); }

    .af-alert-ok   { background:rgba(15,207,164,.07);  border:1px solid rgba(15,207,164,.2);  border-radius:3px; padding:12px 16px; font-size:13px; color:#0FCFA4; margin-bottom:16px; }
    .af-alert-warn { background:rgba(255,200,30,.06);  border:1px solid rgba(255,200,30,.15); border-radius:3px; padding:12px 16px; font-size:13px; color:#FFC850; margin-bottom:16px; }
    .af-alert-err  { background:rgba(255,107,107,.07); border:1px solid rgba(255,107,107,.2); border-radius:3px; padding:12px 16px; font-size:13px; color:#FF6B6B; margin-bottom:16px; }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.08s; } .cbr3 { transition-delay:.16s; } .cbr4 { transition-delay:.24s; }

    /* Raccourcis produits */
    .af-shortcut-row { display:flex; align-items:center; justify-content:space-between; gap:12px; background:#060910; border:1px solid rgba(255,255,255,.06); border-radius:3px; padding:12px 16px; flex-wrap:wrap; }
    .af-shortcut-row:hover { border-color:rgba(201,168,76,.12); }
    .af-shortcut-info { display:flex; align-items:center; gap:10px; flex:1; min-width:0; flex-wrap:wrap; }
    .af-shortcut-badge { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; padding:3px 9px; border-radius:100px; border:1px solid; white-space:nowrap; }
    .af-shortcut-label { font-family:'Syne',sans-serif; font-size:12px; font-weight:600; color:#E8EAF0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:240px; }
    .af-shortcut-url { font-size:10px; color:#6B7590; word-break:break-all; flex:1; min-width:0; }
    .af-shortcut-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }

    /* Accordéon FAQ */
    .af-faq-item { border-bottom:1px solid rgba(255,255,255,.05); }
    .af-faq-item:last-child { border-bottom:none; }
    .af-faq-trigger { width:100%; background:transparent; border:none; text-align:left; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer; transition:background .2s; }
    .af-faq-trigger:hover { background:rgba(201,168,76,.03); }
    .af-faq-trigger:focus-visible { outline:2px solid rgba(201,168,76,.4); outline-offset:-2px; }
    .af-faq-num { font-family:'Playfair Display',serif; font-size:17px; font-weight:900; color:#C9A84C; min-width:22px; flex-shrink:0; }
    .af-faq-q { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; flex:1; }
    .af-faq-icon { font-size:22px; font-weight:300; color:#C9A84C; line-height:1; transition:transform .3s cubic-bezier(.16,1,.3,1); flex-shrink:0; }
    .af-faq-trigger[aria-expanded="true"] .af-faq-icon { transform:rotate(45deg); }
    .af-faq-trigger[aria-expanded="true"] .af-faq-q { color:#C9A84C; }
    .af-faq-body { overflow:hidden; max-height:0; transition:max-height .38s cubic-bezier(.16,1,.3,1); }
    .af-faq-body.open { max-height:240px; }
    .af-faq-body p { font-family:'DM Sans',sans-serif; font-size:13.5px; color:#9AA3B8; line-height:1.75; margin:0; padding:0 20px 18px calc(20px + 22px + 14px); }
</style>
@endpush

@section('content')
@php
    $available  = (int)($totals['available'] ?? 0);
    $pending    = (int)($totals['pending'] ?? 0);
    $paidOut    = (int)($totals['paid_out'] ?? 0);
    $minPayout  = (int)($totals['min_payout'] ?? 10000);
    $canRequest = $available >= $minPayout;

    $whatsappText = urlencode("Rejoins Boursiv et investis à la bourse BRVM ! Utilise mon code parrain pour −10% sur ton premier achat : {$affiliateUrl}");
    $fbUrl = urlencode($affiliateUrl);
@endphp
<div class="af-page">

    <div class="af-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="af-hero-tag">Apporteur d'affaires</p>
                    <h1 class="af-hero-title">Mon espace partenaire</h1>
                    <p class="af-hero-sub">Code : <strong style="color:#C9A84C;">{{ $affiliate->code }}</strong> · Commission : <strong style="color:#C9A84C;">10%</strong> · Remise acheteur : <strong style="color:#0FCFA4;">10%</strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width:1100px;">

        @if(session('success')) <div class="af-alert-ok">✅ {{ session('success') }}</div> @endif
        @if(session('error'))   <div class="af-alert-err">⚠️ {{ session('error') }}</div>   @endif
        @if(session('warning')) <div class="af-alert-warn">⚠️ {{ session('warning') }}</div> @endif

        {{-- KPIs --}}
        <div class="af-kpi-grid cbr">
            <div class="af-kpi" style="--kc:#C9A84C;">
                <div class="af-kpi-label">🖱️ Clics lien</div>
                <div class="af-kpi-value">{{ number_format($affiliate->click_count, 0, ',', ' ') }}</div>
                <div class="af-kpi-sub">Visites via ton lien</div>
            </div>
            <div class="af-kpi" style="--kc:#FFC850;">
                <div class="af-kpi-label">⏳ En attente</div>
                <div class="af-kpi-value">{{ number_format($pending, 0, ',', ' ') }}</div>
                <div class="af-kpi-sub">FCFA · Sûreté 10 j</div>
            </div>
            <div class="af-kpi" style="--kc:#0FCFA4;">
                <div class="af-kpi-label">🟢 Disponible</div>
                <div class="af-kpi-value">{{ number_format($available, 0, ',', ' ') }}</div>
                <div class="af-kpi-sub">FCFA retirable (min {{ number_format($minPayout,0,',',' ') }} F)</div>
            </div>
            <div class="af-kpi" style="--kc:#63B3ED;">
                <div class="af-kpi-label">✅ Total reversé</div>
                <div class="af-kpi-value">{{ number_format($paidOut, 0, ',', ' ') }}</div>
                <div class="af-kpi-sub">FCFA déjà versés</div>
            </div>
        </div>

        {{-- Code + Lien + Partage + QR --}}
        <div class="af-section cbr cbr2">
            <div class="af-section-header">
                <div class="af-section-title">🔗 Mon lien d'apporteur</div>
            </div>
            <div class="af-section-body">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-8">
                        {{-- Code --}}
                        <label class="af-kpi-label mb-2">Mon code</label>
                        <div class="af-code-box mb-3">
                            <span class="af-code-text" id="af-code-val">{{ $affiliate->code }}</span>
                            <button class="cb-btn-outline" onclick="copyText('{{ $affiliate->code }}', this)">📋 Copier le code</button>
                        </div>

                        {{-- Lien --}}
                        <label class="af-kpi-label mb-2">Mon lien de parrainage</label>
                        <div class="af-link-box mb-3" id="af-link-val">{{ $affiliateUrl }}</div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="cb-btn-gold" onclick="copyText('{{ $affiliateUrl }}', this)">📋 Copier le lien</button>
                            <a href="https://wa.me/?text={{ $whatsappText }}" target="_blank" rel="noopener" class="cb-btn-whatsapp">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $fbUrl }}" target="_blank" rel="noopener" class="cb-btn-fb">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex flex-column align-items-center">
                        <label class="af-kpi-label mb-2">QR Code</label>
                        <div class="af-qr">{!! $qrSvg !!}</div>
                        <div class="af-help mt-2 text-center" style="color:#6B7590;font-size:11px;">Scanne pour ouvrir ton lien</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Raccourcis — parrainer un produit précis --}}
        @php
            $hasShortcuts = config('affiliate.pack_brvm_eligible', true)
                || $eligibleCourses->isNotEmpty()
                || $eligibleProducts->isNotEmpty();
        @endphp
        @if($hasShortcuts)
        <div class="af-section cbr cbr3">
            <div class="af-section-header">
                <div class="af-section-title">🎯 Parrainer un produit précis</div>
                <span style="font-size:11px;color:#6B7590;">Lien direct — cookie posé + redirection sur la page du produit</span>
            </div>
            <div class="af-section-body">
                <p style="font-size:12px;color:#6B7590;margin-bottom:16px;">
                    Ces liens posent ton cookie de parrainage <strong style="color:#C9A84C;">ET</strong> redirigent l'acheteur directement sur la page du produit — plus efficace que le lien générique.
                </p>
                <div style="display:flex;flex-direction:column;gap:12px;">

                    {{-- Pack BRVM --}}
                    @if(config('affiliate.pack_brvm_eligible', true))
                    @php
                        $packUrl   = url('/r/' . $affiliate->code . '?p=pack');
                        $packWa    = urlencode('Rejoins Boursiv et investis à la BRVM ! Obtiens le Pack Boursiv complet avec -10% via mon lien : ' . $packUrl);
                        $packFb    = urlencode($packUrl);
                    @endphp
                    <div class="af-shortcut-row">
                        <div class="af-shortcut-info">
                            <span class="af-shortcut-badge" style="background:rgba(201,168,76,.12);color:#C9A84C;border-color:rgba(201,168,76,.25);">🎓 Pack</span>
                            <span class="af-shortcut-label">Pack Boursiv Complet</span>
                            <span class="af-shortcut-url">{{ $packUrl }}</span>
                        </div>
                        <div class="af-shortcut-actions">
                            <button class="cb-btn-outline" style="font-size:10px;padding:6px 10px;" onclick="copyText('{{ $packUrl }}', this)">📋 Copier</button>
                            <a href="https://wa.me/?text={{ $packWa }}" target="_blank" rel="noopener" class="cb-btn-whatsapp" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WA
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $packFb }}" target="_blank" rel="noopener" class="cb-btn-fb" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                FB
                            </a>
                        </div>
                    </div>
                    @endif

                    {{-- Formations éligibles --}}
                    @foreach($eligibleCourses as $course)
                    @php
                        $cUrl = url('/r/' . $affiliate->code . '?course=' . $course->slug);
                        $cWa  = urlencode('Découvre cette formation BRVM avec -10% via mon lien : ' . $cUrl);
                        $cFb  = urlencode($cUrl);
                    @endphp
                    <div class="af-shortcut-row">
                        <div class="af-shortcut-info">
                            <span class="af-shortcut-badge" style="background:rgba(15,207,164,.08);color:#0FCFA4;border-color:rgba(15,207,164,.2);">🎥 Formation</span>
                            <span class="af-shortcut-label">{{ $course->title }}</span>
                            <span class="af-shortcut-url">{{ $cUrl }}</span>
                        </div>
                        <div class="af-shortcut-actions">
                            <button class="cb-btn-outline" style="font-size:10px;padding:6px 10px;" onclick="copyText('{{ $cUrl }}', this)">📋 Copier</button>
                            <a href="https://wa.me/?text={{ $cWa }}" target="_blank" rel="noopener" class="cb-btn-whatsapp" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WA
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $cFb }}" target="_blank" rel="noopener" class="cb-btn-fb" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                FB
                            </a>
                        </div>
                    </div>
                    @endforeach

                    {{-- Produits marketplace Boursiv éligibles --}}
                    @foreach($eligibleProducts as $mp)
                    @php
                        $mpUrl = url('/r/' . $affiliate->code . '?product=' . $mp->slug);
                        $mpWa  = urlencode('Découvre ce produit avec -10% via mon lien : ' . $mpUrl);
                        $mpFb  = urlencode($mpUrl);
                    @endphp
                    <div class="af-shortcut-row">
                        <div class="af-shortcut-info">
                            <span class="af-shortcut-badge" style="background:rgba(99,179,237,.08);color:#63B3ED;border-color:rgba(99,179,237,.2);">🛍️ Marketplace</span>
                            <span class="af-shortcut-label">{{ $mp->title }}</span>
                            <span class="af-shortcut-url">{{ $mpUrl }}</span>
                        </div>
                        <div class="af-shortcut-actions">
                            <button class="cb-btn-outline" style="font-size:10px;padding:6px 10px;" onclick="copyText('{{ $mpUrl }}', this)">📋 Copier</button>
                            <a href="https://wa.me/?text={{ $mpWa }}" target="_blank" rel="noopener" class="cb-btn-whatsapp" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WA
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $mpFb }}" target="_blank" rel="noopener" class="cb-btn-fb" style="font-size:10px;padding:6px 10px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                FB
                            </a>
                        </div>
                    </div>
                    @endforeach

                </div>
            </div>
        </div>
        @endif

        {{-- Commissions --}}
        <div class="af-section cbr cbr3">
            <div class="af-section-header">
                <div class="af-section-title">💰 Mes commissions</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="af-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Montant base</th>
                            <th>Commission</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $c)
                            @php
                                $cls = match($c->status) {
                                    'pending'   => 'af-b-pending',
                                    'validated' => 'af-b-validated',
                                    'paid'      => 'af-b-paid',
                                    'cancelled' => 'af-b-cancelled',
                                    default     => 'af-b-pending',
                                };
                                $ico = match($c->status) {
                                    'pending'   => '⏳',
                                    'validated' => '✅',
                                    'paid'      => '💸',
                                    'cancelled' => '❌',
                                    default     => '—',
                                };
                                $typeLabel = match($c->product_type) {
                                    'pack_brvm'   => 'Pack Boursiv',
                                    'course'      => 'Formation',
                                    'marketplace' => 'Marketplace',
                                    default       => $c->product_type,
                                };
                            @endphp
                            <tr>
                                <td style="font-size:12px;color:#6B7590;">{{ $c->created_at->format('d/m/Y') }}</td>
                                <td style="font-size:12px;">{{ $typeLabel }}</td>
                                <td><span class="af-price">{{ number_format($c->base_amount,0,',',' ') }}</span> <span style="font-size:11px;color:#6B7590;">F</span></td>
                                <td><span class="af-price">{{ number_format($c->commission_amount,0,',',' ') }}</span> <span style="font-size:11px;color:#6B7590;">F</span></td>
                                <td><span class="af-badge {{ $cls }}">{{ $ico }} {{ $c->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="af-empty">Aucune commission pour l'instant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($commissions->hasPages())
                <div class="af-table-footer d-flex justify-content-center">
                    {{ $commissions->links() }}
                </div>
            @endif
        </div>

        {{-- Formulaire demande de reversement --}}
        @if($canRequest)
        <div class="af-section cbr cbr4" style="border-color:rgba(15,207,164,.15);">
            <div class="af-section-header">
                <div class="af-section-title">📤 Demander un reversement</div>
                <span style="font-size:11px;color:#0FCFA4;">{{ number_format($available,0,',',' ') }} FCFA disponibles</span>
            </div>
            <div class="af-section-body">
                <form method="POST" action="{{ route('affiliate.payout.request') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="af-label">Montant (FCFA)</label>
                        <input type="number" name="amount"
                               min="{{ $minPayout }}" max="{{ $available }}"
                               value="{{ old('amount', $minPayout) }}"
                               class="af-input form-control" required>
                        <div class="af-help">Min {{ number_format($minPayout,0,',',' ') }} F · Max {{ number_format($available,0,',',' ') }} F</div>
                    </div>
                    <div class="col-md-3">
                        <label class="af-label">Méthode</label>
                        <select name="payout_method" class="af-input form-select" required>
                            @foreach(config('affiliate.payout_methods', ['wave']) as $m)
                                <option value="{{ $m }}" @selected(old('payout_method')===$m)>
                                    {{ strtoupper(str_replace('_',' ',$m)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="af-label">Numéro de téléphone</label>
                        <input type="tel" name="payout_phone"
                               value="{{ old('payout_phone') }}"
                               class="af-input form-control"
                               placeholder="Ex : 07xxxxxxxx" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="cb-btn-gold w-100">
                            📤 Envoyer la demande
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Historique reversements --}}
        <div class="af-section cbr cbr4">
            <div class="af-section-header">
                <div class="af-section-title">🧾 Mes reversements</div>
                @if(!$canRequest)
                    <span style="font-size:11px;color:#6B7590;">Min {{ number_format($minPayout,0,',',' ') }} FCFA · Disponible : {{ number_format($available,0,',',' ') }} F</span>
                @endif
            </div>
            <div style="overflow-x:auto;">
                <table class="af-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $p)
                            @php
                                $pcls = match($p->status) {
                                    'pending'  => 'af-b-pending',
                                    'approved' => 'af-b-approved',
                                    'paid'     => 'af-b-paid',
                                    'rejected' => 'af-b-rejected',
                                    default    => 'af-b-pending',
                                };
                                $pico = match($p->status) {
                                    'pending'=>'⏳','approved'=>'🔵','paid'=>'✅','rejected'=>'❌',default=>'—'
                                };
                            @endphp
                            <tr>
                                <td style="font-size:12px;color:#6B7590;">{{ optional($p->requested_at ?? $p->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="af-price">{{ number_format((int)$p->amount,0,',',' ') }}</span> <span style="font-size:11px;color:#6B7590;">F</span></td>
                                <td style="font-size:12px;color:#9AA3B8;">{{ strtoupper((string)$p->payout_method) }}</td>
                                <td><span class="af-badge {{ $pcls }}">{{ $pico }} {{ $p->status }}</span></td>
                                <td style="font-size:12px;color:#6B7590;max-width:200px;">{{ $p->admin_note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="af-empty">Aucune demande de reversement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payouts->hasPages())
                <div class="af-table-footer d-flex justify-content-center">
                    {{ $payouts->links() }}
                </div>
            @endif
        </div>

        {{-- Accordéon : Comment ça marche ? --}}
        <div class="af-section cbr cbr4">
            <div class="af-section-header">
                <div class="af-section-title">❓ Comment ça marche ?</div>
            </div>
            <div style="padding:8px 0;">

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">1</span>
                        <span class="af-faq-q">Le principe</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Tu gagnes 10% de commission sur chaque vente réalisée via ton lien, et l'acheteur bénéficie de 10% de remise. Gagnant-gagnant.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">2</span>
                        <span class="af-faq-q">Ton lien et ton code</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Partage ton lien /r/TONCODE ou donne ton code au moment de l'achat. Tu as aussi des raccourcis pour parrainer un produit précis (Pack, formation…) qui amènent directement sur la bonne page.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">3</span>
                        <span class="af-faq-q">Produits éligibles</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Le Pack Boursiv, les formations et les produits Boursiv. Les produits de vendeurs tiers ne donnent pas de commission.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">4</span>
                        <span class="af-faq-q">Comment une commission est créée</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Dès qu'une personne achète un produit éligible via ton lien ou ton code (et que ce n'est pas toi-même), une commission de 10% est créée automatiquement.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">5</span>
                        <span class="af-faq-q">Le délai de 10 jours</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Une nouvelle commission est d'abord « en attente » pendant 10 jours, puis devient « disponible » (retirable). Ce délai correspond au temps de règlement des paiements.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">6</span>
                        <span class="af-faq-q">Retirer tes gains</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Dès que ton solde disponible atteint 10 000 FCFA, demande un reversement par mobile money (Wave, Orange Money, MTN, Moov). L'équipe valide, effectue le paiement, et ton solde est débité.</p>
                    </div>
                </div>

                <div class="af-faq-item">
                    <button class="af-faq-trigger" aria-expanded="false" onclick="afFaqToggle(this)">
                        <span class="af-faq-num">7</span>
                        <span class="af-faq-q">Bon à savoir</span>
                        <span class="af-faq-icon">+</span>
                    </button>
                    <div class="af-faq-body">
                        <p>Tu ne peux pas utiliser ton propre code pour tes propres achats (pas d'autoparrainage).</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Copier dans le presse-papier
    function copyText(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '✅ Copié !';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        }).catch(() => {
            prompt('Copie manuelle :', text);
        });
    }

    // Accordéon FAQ
    function afFaqToggle(btn) {
        const body = btn.nextElementSibling;
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        body.classList.toggle('open', !open);
    }

    // Animations entrée
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => {
            if (e.isIntersecting) el.classList.add('on');
        }, { threshold: .06 }).observe(el);
    });
</script>
@endpush
