{{-- resources/views/market/live.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .ml-page { background:#060910; min-height:100vh; }

    /* Hero */
    .ml-hero { background:#0C1120; border-bottom:1px solid rgba(201,168,76,.08); padding:36px 0 28px; }
    .ml-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:#C9A84C; display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .ml-hero-tag::before { content:''; width:28px; height:1px; background:#C9A84C; }
    .ml-hero-title { font-family:'Playfair Display',serif; font-size:clamp(22px,4vw,34px); font-weight:900; color:#E8EAF0; margin-bottom:6px; }
    .ml-hero-sub { font-size:13px; color:#6B7590; }

    /* Toolbar */
    .ml-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
    .ml-time { font-family:'Syne',sans-serif; font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:#6B7590; }
    .ml-time span { color:#C9A84C; }
    .ml-search { background:rgba(6,9,16,.9); border:1px solid rgba(255,255,255,.1); color:#E8EAF0; border-radius:3px; font-family:'DM Sans',sans-serif; font-size:13px; padding:9px 14px; outline:none; transition:border-color .25s; width:260px; max-width:100%; }
    .ml-search:focus { border-color:rgba(201,168,76,.4); box-shadow:0 0 0 3px rgba(201,168,76,.07); }
    .ml-search::placeholder { color:#454D63; }

    /* Bouton actualiser */
    .ml-refresh { display:inline-flex; align-items:center; gap:7px; background:transparent; color:#6B7590; font-family:'Syne',sans-serif; font-weight:600; font-size:11px; letter-spacing:.07em; text-transform:uppercase; padding:8px 14px; border:1px solid rgba(255,255,255,.1); border-radius:3px; text-decoration:none; transition:all .25s; cursor:pointer; }
    .ml-refresh:hover { border-color:#C9A84C; color:#C9A84C; background:rgba(201,168,76,.05); }

    /* Stats bar */
    .ml-stats { display:flex; gap:24px; flex-wrap:wrap; margin-bottom:22px; }
    .ml-stat { font-family:'Syne',sans-serif; font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:#6B7590; }
    .ml-stat strong { color:#E8EAF0; margin-right:4px; }

    /* Table wrapper */
    .ml-card { background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:4px; overflow:hidden; }
    .ml-card-header { background:#121A2C; border-bottom:1px solid rgba(255,255,255,.05); padding:14px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    .ml-card-title { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#C9A84C; }
    .ml-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }

    /* Table */
    .ml-table { width:100%; border-collapse:collapse; min-width:680px; }
    .ml-table th { font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#454D63; padding:11px 14px; border-bottom:1px solid rgba(255,255,255,.06); text-align:right; white-space:nowrap; }
    .ml-table th:first-child, .ml-table th:nth-child(2) { text-align:left; }
    .ml-table td { padding:13px 14px; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; color:#9AA3B8; text-align:right; vertical-align:middle; }
    .ml-table td:first-child { text-align:left; font-family:'Syne',sans-serif; font-weight:700; font-size:12px; color:#E8EAF0; letter-spacing:.04em; }
    .ml-table td:nth-child(2) { text-align:left; color:#6B7590; font-size:12.5px; }
    .ml-table tr:last-child td { border-bottom:none; }
    .ml-table tr:hover td { background:rgba(201,168,76,.025); }
    .ml-table tr.ml-hidden { display:none; }

    /* Variation badges */
    .var-up   { color:#0FCFA4; font-weight:700; }
    .var-dn   { color:#FF6B6B; font-weight:700; }
    .var-flat { color:#6B7590; font-weight:600; }

    /* Bouton acheter */
    .ml-btn-buy { display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#C9A84C,#9B6B15); color:#050810 !important; font-family:'Syne',sans-serif; font-weight:800; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:7px 13px; border:none; border-radius:3px; text-decoration:none; white-space:nowrap; transition:all .25s; }
    .ml-btn-buy:hover { box-shadow:0 4px 16px rgba(201,168,76,.3); transform:translateY(-1px); color:#050810 !important; }
    .ml-btn-login { display:inline-flex; align-items:center; gap:6px; background:transparent; color:#6B7590 !important; font-family:'Syne',sans-serif; font-weight:600; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:7px 13px; border:1px solid rgba(255,255,255,.1); border-radius:3px; text-decoration:none; white-space:nowrap; transition:all .25s; }
    .ml-btn-login:hover { border-color:#C9A84C; color:#C9A84C !important; }

    /* Error */
    .ml-error { background:rgba(255,107,107,.07); border:1px solid rgba(255,107,107,.2); border-radius:4px; padding:20px 24px; font-family:'DM Sans',sans-serif; font-size:14px; color:#FF6B6B; text-align:center; }

    /* Empty search */
    .ml-no-results { text-align:center; padding:36px; font-family:'Syne',sans-serif; font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:#454D63; display:none; }

    /* Disclaimer */
    .ml-disclaimer { font-family:'Syne',sans-serif; font-size:10px; letter-spacing:.07em; text-transform:uppercase; color:rgba(107,117,144,.4); margin-top:16px; text-align:center; }

    /* Animation entrée */
    .cbr { opacity:0; transform:translateY(16px); transition:all .65s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.07s; }
    .cbr3 { transition-delay:.14s; }
</style>
@endpush

@section('content')
<div class="ml-page">

    {{-- Hero --}}
    <div class="ml-hero">
        <div class="container" style="max-width:1200px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="ml-hero-tag">Marché BRVM</p>
                    <h1 class="ml-hero-title">Marché BRVM en direct</h1>
                    <p class="ml-hero-sub">Simulation pédagogique — les cours sont réels, les achats sont virtuels.</p>
                </div>
                <a href="{{ url()->current() }}" class="ml-refresh" style="margin-top:4px;">
                    ↻ Actualiser les cours
                </a>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width:1200px;">

        @if($error)
            {{-- Erreur scraping --}}
            <div class="ml-error cbr">
                ⚠️ {{ $error }}
                <div style="margin-top:10px;">
                    <a href="{{ url()->current() }}" class="ml-refresh" style="display:inline-flex;">↻ Réessayer</a>
                </div>
            </div>
        @else

            {{-- Toolbar --}}
            <div class="ml-toolbar cbr">
                <div class="ml-time">Chargé à <span>{{ $loadedAt }}</span> (heure Abidjan)</div>
                <input id="mlSearch"
                       class="ml-search"
                       type="search"
                       placeholder="Filtrer par ticker ou société…"
                       autocomplete="off">
            </div>

            {{-- Stats rapides --}}
            @php
                $total    = count($stocks);
                $hausse   = collect($stocks)->filter(fn($s) => ($s['change'] ?? 0) > 0)->count();
                $baisse   = collect($stocks)->filter(fn($s) => ($s['change'] ?? 0) < 0)->count();
                $neutre   = $total - $hausse - $baisse;
            @endphp
            <div class="ml-stats cbr cbr2">
                <div class="ml-stat"><strong>{{ $total }}</strong> valeurs</div>
                <div class="ml-stat" style="color:#0FCFA4;"><strong style="color:#0FCFA4;">{{ $hausse }}</strong> en hausse</div>
                <div class="ml-stat" style="color:#FF6B6B;"><strong style="color:#FF6B6B;">{{ $baisse }}</strong> en baisse</div>
                <div class="ml-stat"><strong>{{ $neutre }}</strong> stables / NC</div>
            </div>

            {{-- Tableau --}}
            <div class="ml-card cbr cbr3">
                <div class="ml-card-header">
                    <div class="ml-card-title">📊 Cours du jour — BRVM</div>
                    <div style="font-size:11px;color:#454D63;font-family:'Syne',sans-serif;letter-spacing:.05em;">Trié par variation décroissante</div>
                </div>
                <div class="ml-scroll">
                    <table class="ml-table" id="mlTable">
                        <thead>
                            <tr>
                                <th>Ticker</th>
                                <th>Société</th>
                                <th>Cours</th>
                                <th>Ouverture</th>
                                <th>Clôture préc.</th>
                                <th>Variation</th>
                                <th>Volume</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="mlBody">
                        @forelse($stocks as $s)
                            @php
                                $ticker = $s['ticker'] ?? '';
                                $name   = $s['name']   ?? '—';
                                $cours  = $s['buy_price'] ?? ($s['open'] ?? ($s['close'] ?? null));
                                $open   = $s['open']   ?? null;
                                $prev   = $s['prev']   ?? null;
                                $change = $s['change'] ?? null;
                                $volume = $s['volume'] ?? null;

                                $varClass = 'var-flat';
                                $arrow    = '—';
                                if (!is_null($change)) {
                                    if ($change > 0)      { $varClass = 'var-up'; $arrow = '▲'; }
                                    elseif ($change < 0)  { $varClass = 'var-dn'; $arrow = '▼'; }
                                    else                  { $arrow = '—'; }
                                }

                                $fmt = fn($v) => is_null($v) ? '<span style="color:#454D63;">NC</span>' : number_format($v, 0, ',', ' ') . ' F';
                                $fmtVol = fn($v) => is_null($v) ? '<span style="color:#454D63;">—</span>' : number_format($v, 0, ',', ' ');
                            @endphp
                            <tr data-search="{{ strtolower($ticker . ' ' . $name) }}">
                                <td>{{ $ticker }}</td>
                                <td>{{ $name }}</td>
                                <td>{!! $fmt($cours) !!}</td>
                                <td>{!! $fmt($open) !!}</td>
                                <td>{!! $fmt($prev) !!}</td>
                                <td class="{{ $varClass }}">
                                    {{ $arrow }}
                                    @if(!is_null($change))
                                        {{ ($change >= 0 ? '+' : '') . number_format($change, 2, ',', ' ') }} %
                                    @else
                                        NC
                                    @endif
                                </td>
                                <td>{!! $fmtVol($volume) !!}</td>
                                <td>
                                    @auth
                                        <a href="{{ route('wallet.index') }}?ticker={{ urlencode($ticker) }}"
                                           class="ml-btn-buy">
                                            ＋ Acheter
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="ml-btn-login">
                                            Connexion
                                        </a>
                                    @endauth
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:32px;color:#454D63;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.08em;text-transform:uppercase;">
                                    Aucune donnée disponible
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="ml-no-results" id="mlNoResults">
                        Aucun résultat pour cette recherche.
                    </div>
                </div>
            </div>

            <p class="ml-disclaimer">
                Les cours proviennent de brvm.org et sont affichés à titre pédagogique.
                Aucun ordre réel n'est passé — simulation uniquement.
            </p>

        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Animation entrée
    document.querySelectorAll('.cbr').forEach(function (el) {
        requestAnimationFrame(function () { el.classList.add('on'); });
    });

    // Filtre côté client
    var search  = document.getElementById('mlSearch');
    var noRes   = document.getElementById('mlNoResults');
    if (!search) return;

    search.addEventListener('input', function () {
        var q    = this.value.trim().toLowerCase();
        var rows = document.querySelectorAll('#mlBody tr[data-search]');
        var visible = 0;

        rows.forEach(function (row) {
            var match = !q || row.dataset.search.includes(q);
            row.classList.toggle('ml-hidden', !match);
            if (match) visible++;
        });

        if (noRes) noRes.style.display = (visible === 0 && q) ? 'block' : 'none';
    });
})();
</script>
@endpush
