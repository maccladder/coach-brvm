{{-- ════════════════════════════════════════════════
     resources/views/game-leaderboard.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .lb-page { background:#060910; min-height:100vh; }

    .lb-hero {
        background: radial-gradient(ellipse 60% 40% at 50% 0%, rgba(120,80,255,.12) 0%, transparent 60%), #060910;
        border-bottom: 1px solid rgba(120,80,255,.12);
        padding: 40px 0 32px;
    }
    .lb-hero-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:#A070FF; display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .lb-hero-tag::before { content:''; width:24px; height:1px; background:#A070FF; }
    .lb-hero-title { font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,38px); font-weight:900; color:#E8EAF0; margin-bottom:4px; }
    .lb-hero-sub   { font-size:13px; color:#6B7590; }

    /* ── Ma position ── */
    .lb-my-card {
        background: rgba(120,80,255,.06);
        border: 1px solid rgba(120,80,255,.2);
        border-radius: 6px; padding: 16px 20px;
        display: flex; align-items: center; gap: 16px;
        flex-wrap: wrap;
    }
    .lb-my-rank { font-family:'Playfair Display',serif; font-size:28px; font-weight:900; color:#A070FF; min-width:50px; text-align:center; }
    .lb-my-info { flex:1; }
    .lb-my-name { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; }
    .lb-my-meta { font-size:12px; color:#6B7590; margin-top:2px; }
    .lb-my-score { font-family:'Playfair Display',serif; font-size:28px; font-weight:900; color:#C9A84C; }

    /* ── Table ── */
    .lb-table-wrap { background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:6px; overflow:hidden; }
    .lb-table { width:100%; border-collapse:collapse; }
    .lb-table th { font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#6B7590; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,.06); text-align:left; }
    .lb-table td { padding:13px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; color:#9AA3B8; vertical-align:middle; }
    .lb-table tr:last-child td { border-bottom: none; }
    .lb-table tr:hover td { background: rgba(255,255,255,.02); }

    /* Ligne du joueur connecte */
    .lb-table tr.is-me td { background: rgba(120,80,255,.06); color: #E8EAF0; }
    .lb-table tr.is-me td:first-child { border-left: 3px solid #A070FF; }

    .lb-rank-badge {
        display: inline-flex; align-items:center; justify-content:center;
        width: 28px; height: 28px; border-radius: 50%;
        font-family:'Syne',sans-serif; font-size:11px; font-weight:800;
    }
    .lb-rank-1 { background:rgba(255,215,0,.15); color:#FFD700; border:1px solid rgba(255,215,0,.3); }
    .lb-rank-2 { background:rgba(192,192,192,.12); color:#C0C0C0; border:1px solid rgba(192,192,192,.25); }
    .lb-rank-3 { background:rgba(205,127,50,.12); color:#CD7F32; border:1px solid rgba(205,127,50,.25); }
    .lb-rank-n { background:rgba(255,255,255,.05); color:#6B7590; border:1px solid rgba(255,255,255,.08); }

    .lb-name { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#E8EAF0; }
    .lb-score { font-family:'Playfair Display',serif; font-size:16px; font-weight:700; color:#C9A84C; }
    .lb-score-me { color:#A070FF; }
    .lb-medal-1::before { content:'🥇 '; }
    .lb-medal-2::before { content:'🥈 '; }
    .lb-medal-3::before { content:'🥉 '; }

    .lb-empty { text-align:center; padding:60px 20px; color:#6B7590; font-size:14px; }

    .cb-btn-purple { display:inline-flex; align-items:center; gap:7px; background:rgba(120,80,255,.1); color:#A070FF !important; font-family:'Syne',sans-serif; font-weight:700; font-size:11px; letter-spacing:.07em; text-transform:uppercase; padding:9px 18px; border:1px solid rgba(120,80,255,.25); border-radius:3px; text-decoration:none; transition:all .3s; }
    .cb-btn-purple:hover { background:rgba(120,80,255,.2); }
    .cb-btn-outline { display:inline-flex; align-items:center; gap:7px; background:transparent; color:#6B7590 !important; font-family:'Syne',sans-serif; font-weight:600; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:9px 18px; border:1px solid rgba(255,255,255,.1); border-radius:3px; text-decoration:none; transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C; color:#C9A84C !important; }

    .cbr { opacity:0; transform:translateY(16px); transition:all .6s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.05s; } .cbr3 { transition-delay:.1s; }
</style>
@endpush

@section('content')
<div class="lb-page">

    {{-- Hero --}}
    <div class="lb-hero">
        <div class="container" style="max-width:860px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="lb-hero-tag">Classement</p>
                    <h1 class="lb-hero-title">🏆 {{ $product->title }}</h1>
                    <p class="lb-hero-sub">Top 20 des meilleurs scores</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('my.products.play', $product) }}" class="cb-btn-purple">
                        🎮 Jouer
                    </a>
                    <a href="{{ route('my.products') }}" class="cb-btn-outline">
                        ← Mes produits
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:860px;">

        {{-- Ma position (si hors top 20) --}}
        @if($myBest && ($myRank > 20 || !$top->contains('user_id', $user->id)))
            <div class="lb-my-card mb-4 cbr">
                <div class="lb-my-rank">#{{ $myBest['rank'] }}</div>
                <div class="lb-my-info">
                    <div class="lb-my-name">👤 Toi — {{ $user->name }}</div>
                    <div class="lb-my-meta">{{ $myBest['games_played'] }} partie(s) jouée(s)</div>
                </div>
                <div class="lb-my-score">{{ number_format($myBest['best_score'], 0, ',', ' ') }}</div>
            </div>
        @endif

        {{-- Top 20 --}}
        <div class="lb-table-wrap cbr cbr2">
            @if($top->isEmpty())
                <div class="lb-empty">
                    <div style="font-size:36px;margin-bottom:12px;opacity:.4;">🎮</div>
                    Aucun score enregistré pour l'instant.<br>
                    <a href="{{ route('my.products.play', $product) }}" class="cb-btn-purple" style="margin-top:16px;display:inline-flex;">Être le premier !</a>
                </div>
            @else
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th style="width:52px;">Rang</th>
                            <th>Joueur</th>
                            <th class="text-end">Score</th>
                            <th class="text-end d-none d-md-table-cell">{{ $secondaryMetricLabel ?? 'Distance' }}</th>
                            <th class="text-end d-none d-md-table-cell">Parties</th>
                            <th class="text-end d-none d-sm-table-cell">Dernière partie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top as $row)
                            @php $isMe = $user && $row['user_id'] === $user->id; @endphp
                            <tr class="{{ $isMe ? 'is-me' : '' }}">
                                <td>
                                    <span class="lb-rank-badge {{ match($row['rank']) { 1=>'lb-rank-1', 2=>'lb-rank-2', 3=>'lb-rank-3', default=>'lb-rank-n' } }}">
                                        {{ $row['rank'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="lb-name {{ match($row['rank']) { 1=>'lb-medal-1', 2=>'lb-medal-2', 3=>'lb-medal-3', default=>'' } }}">
                                        {{ $isMe ? '👤 '.$row['name'].' (Toi)' : $row['name'] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="lb-score {{ $isMe ? 'lb-score-me' : '' }}">
                                        {{ number_format($row['best_score'], 0, ',', ' ') }}
                                    </span>
                                </td>
                                <td class="text-end d-none d-md-table-cell" style="color:#6B7590;">
                                    {{ number_format($row['best_distance'], 0, ',', ' ') }}{{ $secondaryMetricUnit ?? ' m' }}
                                </td>
                                <td class="text-end d-none d-md-table-cell" style="color:#6B7590;">
                                    {{ $row['games_played'] }}
                                </td>
                                <td class="text-end d-none d-sm-table-cell" style="color:#6B7590;font-size:11px;">
                                    {{ \Carbon\Carbon::parse($row['last_played'])->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($myBest)
            <div class="mt-3 text-center cbr cbr3" style="font-size:12px;color:#6B7590;">
                Ton meilleur score : <strong style="color:#C9A84C;">{{ number_format($myBest['best_score'], 0, ',', ' ') }}</strong>
                · Rang <strong style="color:#A070FF;">#{{ $myBest['rank'] }}</strong>
                · {{ $myBest['games_played'] }} partie(s)
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cbr').forEach(el => {
    new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, {threshold:.06}).observe(el);
});
</script>
@endpush
