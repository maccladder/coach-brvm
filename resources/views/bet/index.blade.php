{{-- resources/views/bet/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .bet-page { background: #060910; min-height: 100vh; }

    /* Hero */
    .bet-hero {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px;
        position: relative; overflow: hidden;
    }
    .bet-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .bet-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #0FCFA4;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .bet-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .bet-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 5vw, 48px); font-weight: 900;
        color: #E8EAF0; line-height: 1.08; margin-bottom: 10px;
    }
    .bet-hero-title em { font-style: italic; color: #C9A84C; }
    .bet-hero-desc { font-size: 14px; color: #6B7590; font-weight: 300; line-height: 1.7; text-transform: capitalize; }

    /* Disclaimer */
    .bet-disclaimer {
        background: rgba(239,68,68,.06);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: 6px;
        padding: 16px 20px;
        color: #E8B4B4;
        font-size: 13px;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    /* Coupon card */
    .bet-coupon {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
    }
    .bet-coupon::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--bet-color, #C9A84C), transparent);
    }
    .bet-coupon-top {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; margin-bottom: 18px;
    }
    .bet-badge {
        font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
        letter-spacing: .06em; text-transform: uppercase;
        padding: 5px 12px; border-radius: 100px;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .bet-cote {
        font-family: 'Playfair Display', serif; font-weight: 900;
        font-size: 28px; color: #E8EAF0;
    }
    .bet-cote small { font-family: 'Syne', sans-serif; font-size: 11px; color: #6B7590; font-weight: 600; text-transform: uppercase; margin-right: 6px; }

    .bet-selection {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 11px 0;
        border-bottom: 1px solid rgba(255,255,255,.05);
        font-size: 13px;
    }
    .bet-selection:last-of-type { border-bottom: none; }
    .bet-selection-match { color: #E8EAF0; font-weight: 600; }
    .bet-selection-meta { color: #6B7590; font-size: 11px; margin-top: 2px; }
    .bet-selection-pari { color: #9AA3B8; text-align: right; }
    .bet-selection-cote { color: #0FCFA4; font-weight: 700; font-family: 'Syne', sans-serif; }

    .bet-quote {
        margin: 18px 0; padding: 14px 16px;
        background: rgba(201,168,76,.05);
        border-left: 3px solid #C9A84C;
        border-radius: 0 4px 4px 0;
        color: #9AA3B8; font-size: 13px; font-style: italic; line-height: 1.7;
    }

    .bet-cta {
        display: block; width: 100%; text-align: center;
        padding: 14px; border-radius: 6px; margin-top: 18px;
        font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px;
        letter-spacing: .04em; text-transform: uppercase;
        background: linear-gradient(135deg,#C9A84C,#9B6B15);
        color: #050810; text-decoration: none;
        transition: transform .2s;
    }
    .bet-cta:hover { transform: translateY(-2px); color: #050810; }

    .bet-share {
        display: block; width: 100%; text-align: center;
        padding: 12px; border-radius: 6px; margin-top: 10px;
        font-family: 'Syne', sans-serif; font-weight: 600; font-size: 13px;
        background: rgba(15,207,164,.08); color: #0FCFA4;
        border: 1px solid rgba(15,207,164,.18);
        text-decoration: none;
    }
    .bet-share:hover { background: rgba(15,207,164,.14); color: #0FCFA4; }

    .bet-views { text-align: center; margin-top: 12px; font-size: 11px; color: #4B5570; }

    .bet-empty {
        text-align: center; padding: 70px 20px;
        background: rgba(12,17,32,.6);
        border: 1px solid rgba(201,168,76,.08);
        border-radius: 8px;
        font-family: 'Syne', sans-serif; font-size: 13px;
        letter-spacing: .04em; color: #9AA3B8;
        margin-bottom: 28px;
    }

    /* Stats */
    .bet-stats-title, .bet-history-title {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .18em; text-transform: uppercase; color: #6B7590;
        margin: 40px 0 16px;
    }
    .bet-stat-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.05);
        border-radius: 8px; padding: 18px; text-align: center;
    }
    .bet-stat-taux { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 900; color: #E8EAF0; }
    .bet-stat-label { font-size: 11px; color: #6B7590; margin-top: 4px; }
    .bet-stat-detail { font-size: 11px; color: #4B5570; margin-top: 2px; }

    /* History */
    .bet-history-item { background: #0C1120; border: 1px solid rgba(255,255,255,.05); border-radius: 6px; margin-bottom: 8px; overflow: hidden; }
    .bet-history-head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 13px 16px; cursor: pointer; font-size: 13px; color: #E8EAF0;
        background: none; border: none; width: 100%; text-align: left;
    }
    .bet-history-result { font-size: 16px; }
    .bet-history-body { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
    .bet-history-body.open { max-height: 600px; }
    .bet-history-body-inner { padding: 4px 16px 14px; }
    .bet-history-detail-row { display: flex; justify-content: space-between; gap: 10px; font-size: 12px; color: #9AA3B8; padding: 6px 0; border-top: 1px solid rgba(255,255,255,.04); }

    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="bet-page">

    <div class="bet-hero">
        <div class="bet-hero-grid"></div>
        <div class="container" style="max-width:900px;position:relative;z-index:1;">
            <p class="bet-hero-tag">Betclic × Coach BRVM</p>
            <h1 class="bet-hero-title">Coupons <em>du jour</em></h1>
            <p class="bet-hero-desc">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
        </div>
    </div>

    <div class="container py-5" style="max-width:900px;">

        <div class="bet-disclaimer cbr">
            ⚠️ Les paris sportifs comportent des risques. Aucun gain n'est garanti — nos coupons sont des analyses, pas des certitudes.
            Ne pariez que ce que vous pouvez vous permettre de perdre. Réservé aux personnes majeures.
        </div>

        @forelse($coupons as $coupon)
            @php
                $badge = $coupon->badge();
                $waLines = collect($coupon->selections)->map(fn($s) => "• {$s['match']} — {$s['pari']} (@{$s['cote']})")->implode("\n");
                $waText = "🎯 Coupon {$badge['label']} du jour — cote totale {$coupon->cote_totale}\n\n{$waLines}\n\nVoir le coupon complet : " . route('bet.index');
            @endphp
            <div class="bet-coupon cbr" style="--bet-color: {{ $badge['color'] }};">
                <div class="bet-coupon-top">
                    <span class="bet-badge" style="background: {{ $badge['color'] }}1a; color: {{ $badge['color'] }}; border: 1px solid {{ $badge['color'] }}40;">
                        {{ $badge['emoji'] }} {{ $badge['label'] }}
                    </span>
                    <div class="bet-cote"><small>Cote totale</small>{{ number_format($coupon->cote_totale, 2) }}</div>
                </div>

                <div class="bet-selections">
                    @foreach($coupon->selections as $sel)
                        <div class="bet-selection">
                            <div>
                                <div class="bet-selection-match">{{ $sel['match'] }}</div>
                                <div class="bet-selection-meta">{{ \Carbon\Carbon::parse($sel['heure'])->setTimezone('Africa/Abidjan')->format('H:i') }} · {{ $sel['ligue'] }}</div>
                            </div>
                            <div class="text-end">
                                <div class="bet-selection-pari">{{ $sel['pari'] }}</div>
                                <div class="bet-selection-cote">{{ number_format($sel['cote'], 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($coupon->analyse)
                    <div class="bet-quote">{{ $coupon->analyse }}</div>
                @endif

                <a href="{{ $coupon->lien_betclic }}" target="_blank" rel="noopener" class="bet-cta">Parier sur Betclic 🎯</a>
                <a href="https://wa.me/?text={{ urlencode($waText) }}" target="_blank" rel="noopener" class="bet-share">
                    <i class="bi bi-whatsapp"></i> Partager sur WhatsApp
                </a>

                <div class="bet-views">👁 {{ $coupon->vues }} vues</div>
            </div>
        @empty
            <div class="bet-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">⚽</div>
                Les coupons du jour arrivent bientôt, reviens un peu plus tard !
            </div>
        @endforelse

        <div class="bet-stats-title cbr">Taux de réussite historique</div>
        <div class="row g-3 cbr">
            @foreach(['sur' => '🟢 Sûr', 'equilibre' => '🟡 Équilibré', 'jackpot' => '🔴 Jackpot'] as $niveau => $label)
                @php $s = $stats[$niveau]; @endphp
                <div class="col-4">
                    <div class="bet-stat-card">
                        <div class="bet-stat-taux">{{ $s['taux'] !== null ? $s['taux'].'%' : '—' }}</div>
                        <div class="bet-stat-label">{{ $label }}</div>
                        <div class="bet-stat-detail">
                            @if($s['total'] > 0)
                                {{ $s['gagnes'] }}/{{ $s['total'] }} gagnés
                            @else
                                Bientôt disponible
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($historique->isNotEmpty())
            <div class="bet-history-title cbr">Historique des coupons</div>
            <div class="cbr">
                @foreach($historique as $h)
                    @php $hBadge = $h->badge(); @endphp
                    <div class="bet-history-item">
                        <button type="button" class="bet-history-head" onclick="toggleBetHistory(this)">
                            <span>
                                {{ $h->date_coupon->locale('fr')->isoFormat('D MMM') }}
                                — {{ $hBadge['emoji'] }} {{ $hBadge['label'] }}
                                — cote {{ number_format($h->cote_totale, 2) }}
                            </span>
                            <span class="bet-history-result">{{ $h->resultat === 'gagne' ? '✅' : '❌' }}</span>
                        </button>
                        <div class="bet-history-body">
                            <div class="bet-history-body-inner">
                                @foreach(($h->details_resultat ?? []) as $d)
                                    <div class="bet-history-detail-row">
                                        <span>{{ $d['match'] ?? '' }} — {{ $d['pari'] ?? '' }}</span>
                                        <span>{{ $d['score'] ?? '—' }} {{ ($d['gagne'] ?? false) ? '✅' : '❌' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: 0.06 });
    cbEls.forEach(el => cbObs.observe(el));

    function toggleBetHistory(btn) {
        const body = btn.nextElementSibling;
        body.classList.toggle('open');
    }
</script>
@endpush
