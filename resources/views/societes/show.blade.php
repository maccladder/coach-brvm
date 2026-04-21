{{-- resources/views/societes/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .soc-show-page { background: #060910; min-height: 100vh; }

    .soc-breadcrumb { padding:24px 0 0;display:flex;align-items:center;gap:10px;font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase; }
    .soc-breadcrumb a { color:#6B7590;text-decoration:none;transition:color .25s; }
    .soc-breadcrumb a:hover { color:#C9A84C; }
    .soc-breadcrumb span { color:rgba(107,117,144,.4); }

    /* Hero société */
    .soc-show-hero { padding:32px 0 0; }
    .soc-show-logo {
        width:72px;height:72px;border-radius:8px;background:#121A2C;
        border:1px solid rgba(201,168,76,.15);display:flex;align-items:center;justify-content:center;
        font-family:'Playfair Display',serif;font-size:24px;font-weight:900;color:#C9A84C;
        flex-shrink:0;overflow:hidden;
    }
    .soc-show-logo img { width:100%;height:100%;object-fit:contain;padding:6px; }
    .soc-show-name { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,38px);font-weight:900;color:#E8EAF0;line-height:1.1;margin-bottom:4px; }
    .soc-show-ticker { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#C9A84C; }

    /* Cards */
    .soc-info-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;height:100%; }
    .soc-info-card-header { background:#121A2C;border-bottom:1px solid rgba(255,255,255,.05);padding:16px 22px; }
    .soc-info-card-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:8px; }
    .soc-info-card-body { padding:22px; }

    /* Article présentation */
    .soc-article { color:#9AA3B8;font-size:14px;line-height:1.85;text-align:justify;overflow-wrap:anywhere; }
    .soc-article p { margin-bottom:14px; }
    .soc-article p:last-child { margin-bottom:0; }

    /* Dividendes grid */
    .div-grid { display:grid;grid-template-columns:1fr 1fr;gap:1px;background:rgba(255,255,255,.04);margin-top:4px; }
    .div-cell { background:#0C1120;padding:14px 16px; }
    .div-cell-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:4px; }
    .div-cell-value { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0; }
    .div-cell-value.green { color:#1fbf4a; }
    .div-cell-value.gold { color:#C9A84C; }

    /* Rang */
    .div-rank-box { background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.1);border-radius:3px;padding:14px 18px;margin-top:16px; }
    .div-rank-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:6px; }

    /* Badge top */
    .rank-badge { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 10px;border-radius:100px; }
    .rank-1 { background:rgba(255,200,30,.1);color:#FFD700;border:1px solid rgba(255,200,30,.2); }
    .rank-2 { background:rgba(160,160,160,.1);color:#C0C0C0;border:1px solid rgba(160,160,160,.2); }
    .rank-3 { background:rgba(205,127,50,.1);color:#CD7F32;border:1px solid rgba(205,127,50,.2); }
    .rank-10 { background:rgba(31,191,74,.1);color:#1fbf4a;border:1px solid rgba(31,191,74,.2); }
    .rank-20 { background:rgba(15,207,164,.1);color:#0FCFA4;border:1px solid rgba(15,207,164,.2); }

    /* Info row */
    .info-row { padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04); }
    .info-row:last-child { border-bottom:none; }
    .info-row-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:3px; }
    .info-row-value { font-size:13.5px;color:#E8EAF0;font-weight:500; }

    /* Boutons */
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:9px 18px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }

    @media(max-width:600px){ .div-grid{grid-template-columns:1fr;} }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')
@php
    $descRaw    = trim((string)($societe['description'] ?? ''));
    $paragraphs = array_values(array_filter(preg_split("/\R{2,}/u", $descRaw)));
@endphp
<div class="soc-show-page">
<div class="container pb-5" style="max-width:1100px;">

    <div class="soc-breadcrumb cbr">
        <a href="{{ route('societes.index') }}">← Sociétés</a>
        <span>/</span>
        <span style="color:#E8EAF0;">{{ $societe['ticker'] }}</span>
    </div>

    {{-- Hero --}}
    <div class="soc-show-hero d-flex align-items-center gap-3 mb-4 cbr">
        <div class="soc-show-logo">
            @if(!empty($societe['logo']) && file_exists(public_path($societe['logo'])))
                <img src="{{ asset($societe['logo']) }}" alt="{{ $societe['name'] }}">
            @else
                {{ strtoupper(substr($societe['name'], 0, 1)) }}
            @endif
        </div>
        <div>
            <h1 class="soc-show-name">{{ $societe['name'] }}</h1>
            <div class="soc-show-ticker">Ticker : {{ $societe['ticker'] }}</div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Colonne gauche --}}
        <div class="col-lg-8">

            {{-- Présentation --}}
            <div class="soc-info-card cbr mb-4">
                <div class="soc-info-card-header">
                    <div class="soc-info-card-title">📌 Présentation</div>
                </div>
                <div class="soc-info-card-body">
                    @if($descRaw === '')
                        <p style="color:#6B7590;font-size:13px;">Description non disponible.</p>
                    @else
                        <div class="soc-article">
                            @foreach($paragraphs as $p)
                                <p>{!! e($p) !!}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dividendes --}}
            <div class="soc-info-card cbr cbr2">
                <div class="soc-info-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="soc-info-card-title">💰 Dividendes & Valorisation</div>
                        @php
                            $badge = null;
                            if (!empty($rank)) {
                                if ($rank===1)      $badge=['🥇 Top 1','rank-1'];
                                elseif ($rank===2)  $badge=['🥈 Top 2','rank-2'];
                                elseif ($rank===3)  $badge=['🥉 Top 3','rank-3'];
                                elseif ($rank<=10)  $badge=['🔥 Top 10','rank-10'];
                                elseif ($rank<=20)  $badge=['✅ Top 20','rank-20'];
                            }
                        @endphp
                        @if($isRanked && $badge)
                            <span class="rank-badge {{ $badge[1] }}">{{ $badge[0] }}</span>
                        @endif
                    </div>
                    <div style="font-size:11px;color:#6B7590;margin-top:4px;">Données issues des BOC (pages 3–4)</div>
                </div>
                <div class="soc-info-card-body">
                    @if($hasDividend)
                        <div class="div-grid">
                            <div class="div-cell">
                                <div class="div-cell-label">Dividende net</div>
                                <div class="div-cell-value gold">{{ number_format((float)$dividende->dividende_net,2,',','') }} F</div>
                            </div>
                            <div class="div-cell">
                                <div class="div-cell-label">Date de paiement</div>
                                <div class="div-cell-value" style="font-size:15px;">
                                    {{ $dividende->date_paiement ? \Carbon\Carbon::parse($dividende->date_paiement)->format('d/m/Y') : '—' }}
                                </div>
                            </div>
                            <div class="div-cell">
                                <div class="div-cell-label">Rendement net</div>
                                <div class="div-cell-value green">
                                    {{ $dividende->rendement_net !== null ? number_format((float)$dividende->rendement_net,2,',','').' %' : '—' }}
                                </div>
                            </div>
                            <div class="div-cell">
                                <div class="div-cell-label">PER</div>
                                <div class="div-cell-value">
                                    {{ $dividende->per !== null ? number_format((float)$dividende->per,2,',','') : '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="div-rank-box">
                            <div class="div-rank-label">Rang dividende {{ $rankingYear }}</div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0;">
                                    @if($isRanked && $rank && $totalPayeurs)
                                        {{ $rank }} / {{ $totalPayeurs }} <span style="font-family:'DM Sans',sans-serif;font-size:13px;color:#1fbf4a;margin-left:4px;">✅</span>
                                    @else
                                        <span style="font-size:13px;color:#6B7590;font-family:'DM Sans',sans-serif;">
                                            Non classé en {{ $rankingYear }}
                                            @if(!empty($dividende->date_paiement))
                                                (dernier paiement : {{ \Carbon\Carbon::parse($dividende->date_paiement)->format('Y') }})
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('dividendes.index', ['year' => $rankingYear]) }}" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                                    Voir le classement →
                                </a>
                            </div>
                        </div>

                        <div style="font-size:11px;color:#6B7590;margin-top:12px;font-family:'Syne',sans-serif;letter-spacing:.06em;">
                            Source : {{ $dividende->boc_date_reference ? 'BOC du '.\Carbon\Carbon::parse($dividende->boc_date_reference)->format('d/m/Y') : 'BOC' }}
                        </div>
                    @else
                        <div style="background:rgba(201,168,76,.04);border:1px solid rgba(201,168,76,.08);border-radius:3px;padding:18px;font-size:13px;color:#6B7590;">
                            ℹ️ Informations dividendes non disponibles pour ce titre.
                            <div style="margin-top:12px;">
                                <a href="{{ route('dividendes.index', ['year' => $rankingYear]) }}" class="cb-btn-outline" style="font-size:11px;padding:7px 14px;">
                                    Voir le classement complet →
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Colonne droite --}}
        <div class="col-lg-4">
            <div class="soc-info-card cbr cbr3">
                <div class="soc-info-card-header">
                    <div class="soc-info-card-title">ℹ️ Informations</div>
                </div>
                <div class="soc-info-card-body">
                    <div class="info-row">
                        <div class="info-row-label">Téléphone</div>
                        <div class="info-row-value">{{ $societe['telephone'] ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-row-label">Adresse</div>
                        <div class="info-row-value">{{ $societe['adresse'] ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-row-label">Ticker</div>
                        <div class="info-row-value" style="color:#C9A84C;font-family:'Playfair Display',serif;font-size:22px;font-weight:700;">{{ $societe['ticker'] }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-between align-items-center mt-2 mb-4 cbr">
        <a href="{{ route('societes.index') }}" class="cb-btn-outline">← Toutes les sociétés</a>
        <a href="{{ route('radar.index') }}" class="cb-btn-gold">📡 Radar Marché</a>
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
