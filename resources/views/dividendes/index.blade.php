{{-- resources/views/dividendes/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .div-page { background: #060910; min-height: 100vh; }

    .div-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.12) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .div-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .div-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .div-hero-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .div-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,52px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .div-hero-title em { font-style:italic;color:#C9A84C; }
    .div-hero-sub { font-size:14px;color:#6B7590;font-weight:300; }

    /* Classement */
    .div-ranking-card { background:#0C1120;border:1px solid rgba(201,168,76,.1);border-radius:4px;overflow:hidden;margin-top:32px; }
    .div-ranking-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,rgba(15,207,164,.6),transparent); }

    .div-item {
        display: flex; align-items: center; gap: 16px;
        padding: 18px 24px;
        border-bottom: 1px solid rgba(255,255,255,.04);
        transition: background .25s;
    }
    .div-item:hover { background: rgba(201,168,76,.03); }
    .div-item:last-child { border-bottom: none; }

    /* Rang */
    .div-rank {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 900;
        min-width: 44px; text-align: center;
        flex-shrink: 0;
    }
    .div-rank.r1  { color: #FFD700; }
    .div-rank.r2  { color: #C0C0C0; }
    .div-rank.r3  { color: #CD7F32; }
    .div-rank.rn  { color: rgba(201,168,76,.35); }

    /* Société */
    .div-soc-name { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:2px; }
    .div-soc-ticker { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    /* Montant */
    .div-amount { font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#C9A84C;white-space:nowrap;flex-shrink:0; }

    /* Barre */
    .div-bar-wrap { flex: 1; min-width: 0; }
    .div-bar-track { height: 6px; background: rgba(255,255,255,.06); border-radius: 100px; overflow: hidden; }
    .div-bar-fill { height: 100%; border-radius: 100px; background: linear-gradient(90deg, #C9A84C, #9B6B15); transition: width .8s cubic-bezier(.16,1,.3,1); }
    .div-bar-fill.top3 { background: linear-gradient(90deg, #FFD700, #C9A84C); }

    /* Empty */
    .div-empty { text-align:center;padding:80px 20px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    /* Source */
    .div-source { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-top:20px;text-align:right; }

    /* Boutons */
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    @media(max-width:600px) {
        .div-item { flex-wrap:wrap; }
        .div-bar-wrap { order:4;width:100%;margin-top:8px; }
    }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
</style>
@endpush

@section('content')
<div class="div-page">

    <div class="div-hero">
        <div class="div-hero-grid"></div>
        <div class="container" style="max-width:1000px;position:relative;z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="div-hero-tag">BRVM {{ $year }}</p>
                    <h1 class="div-hero-title">🏆 Classement des <em>dividendes</em></h1>
                    <p class="div-hero-sub">
                        <strong style="color:#E8EAF0;">{{ $total }}</strong> sociétés ont versé un dividende en {{ $year }} —
                        Classement par montant net (FCFA)
                    </p>
                </div>
                <a href="{{ route('societes.index') }}" class="cb-btn-outline">← Sociétés</a>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1000px;">

        @if($dividendes->isEmpty())
            <div class="div-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">🏆</div>
                Aucun dividende enregistré pour l'année {{ $year }}
            </div>
        @else
            <div class="div-ranking-card cbr">
                @foreach($dividendes as $d)
                    @php
                        $rankClass = match(true) {
                            $d->rank === 1 => 'r1',
                            $d->rank === 2 => 'r2',
                            $d->rank === 3 => 'r3',
                            default        => 'rn',
                        };
                        $medal = match($d->rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '#'.$d->rank };
                    @endphp
                    <div class="div-item">
                        <div class="div-rank {{ $rankClass }}">{{ $medal }}</div>
                        <div style="flex:1;min-width:0;">
                            <div class="div-soc-name">{{ $d->societe }}</div>
                            <div class="div-soc-ticker">{{ $d->ticker }}</div>
                        </div>
                        <div class="div-bar-wrap">
                            <div class="div-bar-track">
                                <div class="div-bar-fill {{ $d->rank <= 3 ? 'top3' : '' }}"
                                     style="width:{{ $d->bar_percent }}%"></div>
                            </div>
                        </div>
                        <div class="div-amount">{{ number_format($d->dividende_net,2,',','') }} F</div>
                    </div>
                @endforeach
            </div>

            <div class="div-source cbr cbr2">
                Source : Bulletins Officiels de Cote (BOC) — Année {{ $year }}
            </div>
        @endif

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
