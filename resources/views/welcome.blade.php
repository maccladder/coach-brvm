{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    /* ============================================
       WELCOME — variables locales
    ============================================ */
    .cb-hero {
        min-height: 92vh;
        background:
            radial-gradient(ellipse 90% 60% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            radial-gradient(ellipse 50% 40% at 85% 80%, rgba(15,207,164,.06) 0%, transparent 50%),
            #060910;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
    }

    .cb-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(201,168,76,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,168,76,.05) 1px, transparent 1px);
        background-size: 60px 60px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }

    .cb-hero-orb {
        position: absolute; border-radius: 50%;
        filter: blur(90px); pointer-events: none;
    }
    .cb-hero-orb-1 {
        width: 500px; height: 500px;
        top: -100px; left: -100px;
        background: rgba(201,168,76,.07);
        animation: orbF 14s ease-in-out infinite;
    }
    .cb-hero-orb-2 {
        width: 400px; height: 400px;
        bottom: -60px; right: -60px;
        background: rgba(15,207,164,.05);
        animation: orbF 18s ease-in-out infinite reverse;
    }
    @keyframes orbF {
        0%,100% { transform: translate(0,0); }
        50%      { transform: translate(20px,-30px); }
    }

    /* Hero badge */
    .cb-hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(201,168,76,.09);
        border: 1px solid rgba(201,168,76,.22);
        border-radius: 100px;
        padding: 6px 16px;
        font-family: 'Syne', sans-serif;
        font-size: 11px; font-weight: 600;
        letter-spacing: .12em; text-transform: uppercase;
        color: #C9A84C;
        margin-bottom: 28px;
    }
    .cb-hero-badge-dot {
        width: 6px; height: 6px;
        background: #0FCFA4; border-radius: 50%;
        animation: bdot 2s ease-in-out infinite;
    }
    @keyframes bdot {
        0%,100% { opacity:1; box-shadow: 0 0 6px #0FCFA4; }
        50%      { opacity:.2; box-shadow: none; }
    }

    /* Hero title */
    .cb-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(38px, 6vw, 76px);
        font-weight: 900;
        line-height: 1.05;
        letter-spacing: -.02em;
        color: #E8EAF0;
        margin-bottom: 12px;
    }
    .cb-hero-title .gold { color: #C9A84C; }

    .cb-hero-tag {
        font-family: 'Syne', sans-serif;
        font-size: 11px; font-weight: 600;
        letter-spacing: .24em; text-transform: uppercase;
        color: #6B7590; margin-bottom: 24px;
    }
    .cb-hero-tag span { color: #0FCFA4; }

    .cb-hero-desc {
        font-size: clamp(15px, 2vw, 17px);
        color: #6B7590; font-weight: 300;
        line-height: 1.75; max-width: 560px;
        margin-bottom: 40px;
    }
    .cb-hero-desc strong { color: #E8EAF0; font-weight: 500; }

    /* Hero CTA buttons */
    .cb-cta-primary {
        display: inline-flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, #C9A84C, #9B6B15);
        color: #050810 !important; font-family: 'Syne', sans-serif;
        font-weight: 800; font-size: 13px;
        letter-spacing: .06em; text-transform: uppercase;
        padding: 14px 28px; border-radius: 3px;
        text-decoration: none; transition: all .3s;
        border: none;
    }
    .cb-cta-primary:hover {
        box-shadow: 0 10px 32px rgba(201,168,76,.3);
        transform: translateY(-2px);
        color: #050810 !important;
    }

    .cb-cta-green {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(15,207,164,.1); color: #0FCFA4 !important;
        font-family: 'Syne', sans-serif; font-weight: 700;
        font-size: 13px; letter-spacing: .06em; text-transform: uppercase;
        padding: 13px 24px; border-radius: 3px;
        text-decoration: none;
        border: 1px solid rgba(15,207,164,.22);
        transition: all .3s;
    }
    .cb-cta-green:hover {
        background: rgba(15,207,164,.16);
        border-color: rgba(15,207,164,.4);
        color: #0FCFA4 !important;
    }

    .cb-cta-outline {
        display: inline-flex; align-items: center; gap: 8px;
        background: transparent; color: #E8EAF0 !important;
        font-family: 'Syne', sans-serif; font-weight: 600;
        font-size: 13px; letter-spacing: .06em; text-transform: uppercase;
        padding: 13px 22px; border-radius: 3px;
        text-decoration: none;
        border: 1px solid rgba(255,255,255,.12);
        transition: all .3s;
    }
    .cb-cta-outline:hover {
        border-color: #C9A84C; color: #C9A84C !important;
        background: rgba(201,168,76,.05);
    }

    /* Hero card (right) */
    .cb-hero-card {
        background: rgba(12,17,32,.9);
        border: 1px solid rgba(201,168,76,.12);
        border-radius: 6px;
        overflow: hidden;
        backdrop-filter: blur(12px);
    }
    .cb-hero-card-header {
        background: rgba(18,26,44,.9);
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 14px 20px;
        display: flex; align-items: center; gap: 10px;
    }
    .cb-hero-card-dot { width: 8px; height: 8px; border-radius: 50%; }
    .cb-hero-card-title {
        font-family: 'Syne', sans-serif; font-size: 11px;
        font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        color: #6B7590; flex: 1; text-align: center;
    }

    /* Tools list */
    .cb-tool-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 13px 20px;
        border-bottom: 1px solid rgba(255,255,255,.04);
        transition: background .2s;
    }
    .cb-tool-item:hover { background: rgba(201,168,76,.04); }
    .cb-tool-item:last-child { border-bottom: none; }

    .cb-tool-name {
        font-family: 'Syne', sans-serif; font-size: 13px;
        font-weight: 600; color: #E8EAF0;
    }
    .cb-tool-desc { font-size: 12px; color: #6B7590; margin-top: 1px; }

    .cb-chip {
        font-family: 'Syne', sans-serif; font-size: 9px;
        font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        padding: 3px 9px; border-radius: 100px;
        white-space: nowrap;
    }
    .cb-chip-free  { background: rgba(15,207,164,.08);  color: #0FCFA4; border: 1px solid rgba(15,207,164,.18); }
    .cb-chip-pay   { background: rgba(201,168,76,.08);  color: #C9A84C; border: 1px solid rgba(201,168,76,.18); }
    .cb-chip-new   { background: rgba(255,200,80,.08);  color: #FFC850; border: 1px solid rgba(255,200,80,.2); }
    .cb-chip-beta  { background: rgba(160,120,255,.08); color: #B090FF; border: 1px solid rgba(160,120,255,.2); }
    .cb-chip-ai    { background: rgba(99,179,237,.08);  color: #63B3ED; border: 1px solid rgba(99,179,237,.2); }

    /* Stats strip */
    .cb-stats {
        background: rgba(12,17,32,.95);
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 20px 0;
    }
    .cb-stat-num {
        font-family: 'Playfair Display', serif;
        font-size: clamp(24px, 3.5vw, 34px);
        font-weight: 700; color: #C9A84C;
        line-height: 1; display: block;
    }
    .cb-stat-lbl {
        font-family: 'Syne', sans-serif; font-size: 10px;
        letter-spacing: .14em; text-transform: uppercase;
        color: #6B7590; margin-top: 5px; display: block;
    }

    /* ============================================
       SECTIONS COMMUNES
    ============================================ */
    .cb-sec { padding: clamp(64px,9vw,120px) 0; }
    .cb-sec-alt { background: #0C1120; }
    .cb-sec-white { background: #f0f2f7; }

    .cb-sec-tag {
        font-family: 'Syne', sans-serif; font-size: 11px;
        letter-spacing: .2em; text-transform: uppercase;
        color: #0FCFA4; margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .cb-sec-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }

    .cb-sec-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 4.5vw, 48px);
        font-weight: 700; line-height: 1.1; color: #E8EAF0;
        margin-bottom: 16px;
    }
    .cb-sec-title em { font-style: italic; color: #C9A84C; }

    .cb-sec-title-dark {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 4.5vw, 48px);
        font-weight: 700; line-height: 1.1; color: #1a1f2e;
        margin-bottom: 16px;
    }
    .cb-sec-title-dark em { font-style: italic; color: #9B6B15; }

    .cb-divider { width: 52px; height: 1px; background: linear-gradient(90deg, #C9A84C, transparent); margin: 16px 0; }
    .cb-divider-dark { width: 52px; height: 1px; background: linear-gradient(90deg, #9B6B15, transparent); margin: 16px 0; }

    /* Feature cards (dark) */
    .cb-feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1px;
        background: rgba(201,168,76,.08);
        border: 1px solid rgba(201,168,76,.08);
        margin-top: 48px;
    }
    .cb-feature-card {
        background: #0C1120; padding: 32px 28px;
        position: relative; overflow: hidden;
        transition: background .35s;
        text-decoration: none; color: inherit;
        display: block;
    }
    .cb-feature-card:hover { background: rgba(16,22,40,1); color: inherit; }
    .cb-feature-card::before {
        content: ''; position: absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, #C9A84C, transparent);
        opacity: 0; transition: opacity .35s;
    }
    .cb-feature-card:hover::before { opacity: 1; }
    .cb-feature-num {
        position: absolute; top:18px; right:20px;
        font-family: 'Playfair Display', serif;
        font-size: 42px; font-weight: 900;
        color: rgba(201,168,76,.06); line-height: 1;
    }
    .cb-feature-emoji { font-size: 26px; margin-bottom: 18px; display: block; }
    .cb-feature-title {
        font-family: 'Syne', sans-serif; font-size: 15px;
        font-weight: 700; color: #E8EAF0; margin-bottom: 9px;
    }
    .cb-feature-desc { font-size: 13px; color: #6B7590; line-height: 1.65; }

    /* BOC highlight box */
    .cb-boc-box {
        background: rgba(15,207,164,.04);
        border: 1px solid rgba(15,207,164,.14);
        border-radius: 6px; padding: 36px 40px;
        position: relative; overflow: hidden;
    }
    .cb-boc-box::before {
        content:''; position:absolute; top:0;left:0;right:0;height:2px;
        background: linear-gradient(90deg, #0FCFA4, transparent);
    }

    /* How steps */
    .cb-how-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1px;
        background: rgba(201,168,76,.07);
        border: 1px solid rgba(201,168,76,.07);
        margin-top: 48px;
    }
    .cb-how-step { background: #0C1120; padding: 30px 26px; }
    .cb-how-n {
        font-family: 'Playfair Display', serif;
        font-size: 40px; font-weight: 900;
        color: rgba(201,168,76,.15); line-height: 1; margin-bottom: 14px;
    }
    .cb-how-title {
        font-family: 'Syne', sans-serif; font-size: 14px;
        font-weight: 700; color: #E8EAF0; margin-bottom: 8px;
    }
    .cb-how-desc { font-size: 13px; color: #6B7590; line-height: 1.65; }

    /* Pricing cards */
    .cb-price-card {
        background: #121A2C;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px; padding: 30px;
        position: relative; transition: all .35s;
        height: 100%;
    }
    .cb-price-card:hover { transform: translateY(-4px); border-color: rgba(201,168,76,.2); }
    .cb-price-card.featured {
        border-color: rgba(201,168,76,.22);
        background: rgba(18,22,36,1);
    }
    .cb-price-card.featured::before {
        content: 'RECOMMANDÉ';
        position: absolute; top:-1px; right:20px;
        font-family: 'Syne', sans-serif; font-size: 9px;
        font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
        background: #C9A84C; color: #050810; padding: 3px 10px;
    }
    .cb-price-label {
        font-family: 'Syne', sans-serif; font-size: 10px;
        letter-spacing: .14em; text-transform: uppercase;
        color: #6B7590; margin-bottom: 6px;
    }
    .cb-price-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 700; color: #E8EAF0; margin-bottom: 14px;
    }
    .cb-price-num {
        font-family: 'Playfair Display', serif;
        font-size: 34px; font-weight: 900; color: #C9A84C; margin-bottom: 10px;
        line-height: 1;
    }
    .cb-price-num span { font-size: 14px; color: #6B7590; font-family: 'DM Sans', sans-serif; font-weight: 300; }
    .cb-price-desc { font-size: 13px; color: #6B7590; line-height: 1.65; margin-bottom: 20px; }
    .cb-price-features { list-style: none; padding: 0; margin-bottom: 24px; }
    .cb-price-features li {
        font-size: 13px; color: #6B7590;
        padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,.04);
        display: flex; align-items: flex-start; gap: 9px;
    }
    .cb-price-features li::before { content: '✓'; color: #0FCFA4; font-weight: 700; flex-shrink: 0; }

    /* Annonces */
    .cb-ann-card {
        background: #121A2C;
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 4px; padding: 22px;
        text-decoration: none; color: inherit;
        display: block; transition: all .3s; height: 100%;
    }
    .cb-ann-card:hover { border-color: rgba(201,168,76,.18); transform: translateY(-3px); color: inherit; }
    .cb-ann-date {
        font-family: 'Syne', sans-serif; font-size: 10px;
        letter-spacing: .1em; text-transform: uppercase; color: #6B7590; margin-bottom: 7px;
    }
    .cb-ann-title {
        font-family: 'Syne', sans-serif; font-size: 14px;
        font-weight: 700; color: #E8EAF0; line-height: 1.4; margin-bottom: 9px;
    }
    .cb-ann-excerpt { font-size: 13px; color: #6B7590; line-height: 1.6; }
    .cb-ann-link {
        display: inline-flex; align-items: center; gap: 5px;
        margin-top: 14px; font-family: 'Syne', sans-serif;
        font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #C9A84C;
    }

    /* Social buttons */
    .cb-social-btn {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.08);
        color: #6B7590 !important; font-family: 'Syne', sans-serif;
        font-size: 11px; letter-spacing: .07em; text-transform: uppercase;
        padding: 8px 14px; border-radius: 100px;
        text-decoration: none; transition: all .3s;
    }
    .cb-social-btn:hover { border-color: #C9A84C; color: #C9A84C !important; background: rgba(201,168,76,.06); }

    /* Diaspora section */
    .cb-diaspora-badge {
        display: inline-flex; align-items: center; gap: 8px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: .14em; text-transform: uppercase; color: #25D366;
        border: 1px solid rgba(37,211,102,.3); border-radius: 100px;
        padding: 6px 16px; margin-bottom: 20px;
    }
    .cb-diaspora-dot {
        width: 7px; height: 7px; border-radius: 50%; background: #25D366;
        animation: cbDiasporaBlink 1.4s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes cbDiasporaBlink {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(37,211,102,.5); }
        50% { opacity: .4; box-shadow: 0 0 0 5px rgba(37,211,102,0); }
    }
    .cb-wa-cta {
        display: inline-flex; align-items: center; gap: 10px;
        background: #25D366; color: #fff !important; text-decoration: none;
        padding: 14px 32px; border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; letter-spacing: .06em;
        transition: all .3s;
        animation: cbWaGlow 2.5s ease-in-out infinite;
    }
    .cb-wa-cta i { font-size: 20px; }
    .cb-wa-cta:hover { background: #1ebe5a; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,211,102,.45); }
    @keyframes cbWaGlow {
        0%, 100% { box-shadow: 0 4px 20px rgba(37,211,102,.25); }
        50% { box-shadow: 0 4px 36px rgba(37,211,102,.55), 0 0 0 6px rgba(37,211,102,.08); }
    }

    /* CTA finale */
    .cb-cta-finale {
        padding: clamp(100px, 14vw, 180px) 0;
        background:
            radial-gradient(circle at 50% 50%, rgba(201,168,76,.07) 0%, transparent 60%),
            #060910;
        text-align: center;
    }
    .cb-cta-finale-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(36px, 7vw, 80px);
        font-weight: 900; line-height: 1.04; color: #E8EAF0;
    }
    .cb-cta-finale-title .g { color: #C9A84C; }

    /* Callout boxes */
    .cb-callout {
        background: #0C1120;
        border: 1px solid rgba(201,168,76,.1);
        border-radius: 4px; padding: 22px 28px;
        transition: border-color .3s;
    }
    .cb-callout:hover { border-color: rgba(201,168,76,.2); }
    .cb-callout-title {
        font-family: 'Syne', sans-serif; font-size: 14px;
        font-weight: 700; color: #E8EAF0; margin-bottom: 4px;
    }
    .cb-callout-desc { font-size: 13px; color: #6B7590; line-height: 1.6; }

    /* Footer dark */
    .cb-footer {
        background: #060910;
        border-top: 1px solid rgba(255,255,255,.05);
        padding: 40px 0 28px;
    }
    .cb-footer-logo {
        font-family: 'Playfair Display', serif;
        font-size: 20px; font-weight: 900; color: #C9A84C;
        text-decoration: none;
    }
    .cb-footer-logo em { font-style: normal; color: #E8EAF0; }
    .cb-footer-link {
        font-size: 12px; color: #6B7590 !important;
        text-decoration: none; font-family: 'Syne', sans-serif;
        letter-spacing: .06em; transition: color .25s;
    }
    .cb-footer-link:hover { color: #E8EAF0 !important; }
    .cb-footer-copy { font-size: 11px; color: #6B7590; font-family: 'Syne', sans-serif; }

    /* Radar animation */
    .cb-radar-wrap { position:relative; height:320px; display:flex; align-items:center; justify-content:center; }
    .cb-radar-ring { position:absolute; border-radius:50%; border:1px solid; animation:rp 3s ease-in-out infinite; }
    .r1{width:300px;height:300px;border-color:rgba(201,168,76,.06);animation-delay:0s}
    .r2{width:228px;height:228px;border-color:rgba(201,168,76,.1);animation-delay:.5s}
    .r3{width:156px;height:156px;border-color:rgba(201,168,76,.16);animation-delay:1s}
    .r4{width:84px;height:84px;border-color:rgba(201,168,76,.26);animation-delay:1.5s}
    @keyframes rp{0%,100%{opacity:.4;transform:scale(1)}50%{opacity:1;transform:scale(1.02)}}
    .cb-radar-sweep {
        position:absolute; width:300px; height:300px; border-radius:50%;
        background:conic-gradient(from 0deg,transparent 0deg,rgba(201,168,76,.07) 55deg,transparent 55deg);
        animation:sw 4.5s linear infinite;
    }
    @keyframes sw{from{transform:rotate(0)}to{transform:rotate(360deg)}}
    .cb-bubble {
        position:absolute; border-radius:50%; display:flex; align-items:center;
        justify-content:center; font-family:'Syne',sans-serif; font-size:9px;
        font-weight:700; cursor:default; transition:all .3s; text-align:center; line-height:1.2;
    }
    .cb-bubble:hover{z-index:10;transform:scale(1.2)!important}
    .bu1{width:68px;height:68px;top:28px;left:90px;background:rgba(15,207,164,.12);border:1.5px solid rgba(15,207,164,.35);color:#0FCFA4;animation:bf1 6s ease-in-out infinite}
    .bu2{width:52px;height:52px;top:50px;right:74px;background:rgba(201,168,76,.1);border:1.5px solid rgba(201,168,76,.3);color:#C9A84C;animation:bf2 7s ease-in-out infinite}
    .bu3{width:80px;height:80px;bottom:70px;left:50px;background:rgba(201,168,76,.09);border:1.5px solid rgba(201,168,76,.26);color:#C9A84C;animation:bf1 8s ease-in-out infinite 1s}
    .bu4{width:44px;height:44px;bottom:46px;right:90px;background:rgba(15,207,164,.09);border:1.5px solid rgba(15,207,164,.26);color:#0FCFA4;animation:bf2 5s ease-in-out infinite .5s}
    .bu5{width:56px;height:56px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.1);color:#E8EAF0;font-size:8px}
    @keyframes bf1{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
    @keyframes bf2{0%,100%{transform:translateY(0)}50%{transform:translateY(9px)}}

    /* Scroll reveal */
    .cbr { opacity:0; transform:translateY(24px); transition:all .8s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
    .cbr3 { transition-delay:.2s; }
</style>
@endpush

@section('content')

@auth
@php
    $showOtpWelcomeBanner = !auth()->user()->phone_reward_claimed
        && \Carbon\Carbon::parse(config('otp.reward_deadline'))->endOfDay()->isFuture();

    $_wu = auth()->user();
    $showWelcomeBonusGranted = !is_null($_wu->wallet_bonus_claimed_at)
        && (session('wallet_bonus_just_granted')
            || $_wu->wallet_bonus_claimed_at->gte(now()->subDays(7)));
    $showWelcomeBonusCta = is_null($_wu->wallet_bonus_claimed_at)
        && is_null($_wu->phone_verified_at)
        && $_wu->created_at < \Carbon\Carbon::parse(config('otp.eligibility_cutoff'))
        && now()->gte(\Carbon\Carbon::parse(config('otp.wallet_bonus_start')))
        && now()->lte(\Carbon\Carbon::parse(config('otp.wallet_bonus_end')));
    $wBonusAmount   = number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ');
    $wBonusEndLabel = \Carbon\Carbon::parse(config('otp.wallet_bonus_end'))->locale('fr')->isoFormat('D MMMM');
@endphp
@if($showOtpWelcomeBanner)
<div style="background:#080E1C;border-bottom:1px solid rgba(15,207,164,.12);padding:9px 0;text-align:center;">
    <div class="container d-flex align-items-center justify-content-center flex-wrap gap-2" style="max-width:1100px;">
        <span style="font-family:'Syne',sans-serif;font-size:11px;color:#6B7590;letter-spacing:.04em;">
            🎁 <strong style="color:#E8EAF0;font-weight:600;">Cadeau membre</strong>
            &nbsp;—&nbsp;Reçois Abidjan Run gratuitement
            <span style="color:rgba(201,168,76,.4);margin:0 6px;">·</span>
            <span style="color:#C9A84C;">Jusqu'au {{ \Carbon\Carbon::parse(config('otp.reward_deadline'))->locale('fr')->isoFormat('D MMMM') }}</span>
        </span>
        <a href="{{ route('phone.verify.form') }}"
           style="display:inline-flex;align-items:center;gap:5px;background:#C9A84C;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:5px 13px;border-radius:2px;text-decoration:none;flex-shrink:0;">
            Activer →
        </a>
    </div>
</div>
@endif

{{-- Bannière A — bonus wallet reçu (Promo 2) --}}
@if($showWelcomeBonusGranted)
<div style="background:rgba(15,207,164,.05);border-bottom:1px solid rgba(15,207,164,.18);padding:9px 0;text-align:center;">
    <div class="container d-flex align-items-center justify-content-center flex-wrap gap-2" style="max-width:1100px;">
        <span style="font-family:'Syne',sans-serif;font-size:11px;color:#6B7590;letter-spacing:.04em;">
            🎉 <strong style="color:#0FCFA4;font-weight:600;">Bonus activé</strong>
            &nbsp;—&nbsp;{{ $wBonusAmount }} FCFA virtuels crédités sur ton portefeuille
        </span>
        <a href="{{ route('wallet.index') }}"
           style="display:inline-flex;align-items:center;gap:5px;background:#0FCFA4;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:5px 13px;border-radius:2px;text-decoration:none;flex-shrink:0;">
            Simulateur →
        </a>
    </div>
</div>
@endif

{{-- Bannière B — CTA Segment B (éligible, non vérifié) --}}
@if($showWelcomeBonusCta)
<div style="background:#080E1C;border-bottom:1px solid rgba(201,168,76,.15);padding:9px 0;text-align:center;">
    <div class="container d-flex align-items-center justify-content-center flex-wrap gap-2" style="max-width:1100px;">
        <span style="font-family:'Syne',sans-serif;font-size:11px;color:#6B7590;letter-spacing:.04em;">
            🎁 <strong style="color:#E8EAF0;font-weight:600;">{{ $wBonusAmount }} FCFA virtuels offerts</strong>
            &nbsp;—&nbsp;Vérifie ton numéro avant le
            <span style="color:#C9A84C;">{{ $wBonusEndLabel }}</span>
        </span>
        <a href="{{ route('phone.verify.form') }}"
           style="display:inline-flex;align-items:center;gap:5px;background:#C9A84C;color:#060910 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:5px 13px;border-radius:2px;text-decoration:none;flex-shrink:0;">
            Activer →
        </a>
    </div>
</div>
@endif
@endauth

{{-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ --}}
<section class="cb-hero">
    <div class="cb-hero-grid"></div>
    <div class="cb-hero-orb cb-hero-orb-1"></div>
    <div class="cb-hero-orb cb-hero-orb-2"></div>

    <div class="container" style="max-width:1100px; position:relative; z-index:10; padding-top:40px; padding-bottom:40px;">
        <div class="row g-5 align-items-center">

            {{-- Texte --}}
            <div class="col-lg-7">
                <div class="cb-hero-badge">
                    <span class="cb-hero-badge-dot"></span>
                    ÉDUCATION FINANCIÈRE · POUR TOUS
                </div>

                <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#C9A84C;margin-bottom:16px;">
                    Plateforme d'éducation financière
                </p>

                <h1 class="cb-hero-title">
                    S'éduquer aujourd'hui,<br>
                    <span class="gold">prospérer</span> demain.
                </h1>

                <p class="cb-hero-tag">
                    <span>Comprends</span> &nbsp;·&nbsp;
                    <span>Analyse</span> &nbsp;·&nbsp;
                    <span>Progresse</span>
                </p>

                <p class="cb-hero-desc">
                    Boursiv est une plateforme d'éducation financière multiservice : formations à la bourse,
                    analyses du marché régional, portefeuille virtuel pour s'entraîner sans risque, et une
                    <strong>marketplace de produits variés</strong> (cours, e-books, outils, logiciels —
                    et même des jeux pour se détendre).<br>
                    Parce que <strong>l'avenir appartient à ceux qui se forment.</strong>
                </p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    @guest
                        <a href="{{ route('register') }}" class="cb-cta-primary">✦ S'inscrire gratuitement</a>
                    @endguest
                    @if(!empty($latestPublicBoc))
                        <a href="{{ route('client-bocs.latest.public') }}" class="cb-cta-green">📄 BOC gratuite (J-1)</a>
                    @endif
                    <a href="{{ route('radar.index') }}" class="cb-cta-outline">📡 Radar Marché</a>
                </div>

                <div style="font-size:12px;color:#6B7590;display:flex;flex-wrap:wrap;gap:16px;">
                    <span>✅ BOC (J-1) gratuite</span>
                    <span>✅ Résumé pédagogique</span>
                    <span>✅ Audio + vidéo si dispo</span>
                </div>
            </div>

            {{-- Card outils --}}
            <div class="col-lg-5">
                <div class="cb-hero-card">
                    <div class="cb-hero-card-header">
                        <span class="cb-hero-card-dot" style="background:#FF5F57;"></span>
                        <span class="cb-hero-card-dot" style="background:#FEBC2E;"></span>
                        <span class="cb-hero-card-dot" style="background:#28C840;"></span>
                        <span class="cb-hero-card-title">boursiv.com — Outils</span>
                    </div>

                    <a href="{{ route('announcements.index') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">📢 Annonces du marché</div>
                            <div class="cb-tool-desc">Communiqués, AG, infos importantes</div>
                        </div>
                        <span class="cb-chip cb-chip-free">Gratuit</span>
                    </a>
                    <a href="{{ route('radar.index') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">📡 Radar Marché</div>
                            <div class="cb-tool-desc">Performance 7 jours + comparaison</div>
                        </div>
                        <span class="cb-chip cb-chip-free">Gratuit</span>
                    </a>
                    <a href="{{ route('books.index') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">📚 Mini-cours en livre</div>
                            <div class="cb-tool-desc">Notions de bourse, page par page</div>
                        </div>
                        <span class="cb-chip cb-chip-free">Gratuit</span>
                    </a>
                    <a href="{{ route('marketplace.index') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">🛍️ Marketplace</div>
                            <div class="cb-tool-desc">PDF, vidéos, logiciels</div>
                        </div>
                        <span class="cb-chip cb-chip-new">Nouveau</span>
                    </a>
                    <a href="{{ route('formations.brvm') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">🎓 Formations bourse</div>
                            <div class="cb-tool-desc">Débutant → Intermédiaire</div>
                        </div>
                        <span class="cb-chip cb-chip-pay">Payant</span>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">💼 Portefeuille virtuel</div>
                            <div class="cb-tool-desc">Simulation sans risque</div>
                        </div>
                        <span class="cb-chip cb-chip-beta">Beta</span>
                    </a>
                    <a href="{{ route('client-bocs.create') }}" class="cb-tool-item" style="text-decoration:none;">
                        <div>
                            <div class="cb-tool-name">🤖 IA — BOC & États financiers</div>
                            <div class="cb-tool-desc">Analyse texte / audio / vidéo</div>
                        </div>
                        <span class="cb-chip cb-chip-ai">IA</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- STATS --}}
<div class="cb-stats">
    <div class="container" style="max-width:1100px;">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <span class="cb-stat-num">45+</span>
                <span class="cb-stat-lbl">Sociétés cotées</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="cb-stat-num">7</span>
                <span class="cb-stat-lbl">Pays UEMOA</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="cb-stat-num">BOC</span>
                <span class="cb-stat-lbl">Analyse IA quotidienne</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="cb-stat-num">0 F</span>
                <span class="cb-stat-lbl">Pour commencer</span>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     PILIERS
══════════════════════════════════════ --}}
<section class="cb-sec" style="background:#060910;">
    <div class="container" style="max-width:1100px;">
        <p class="cb-sec-tag" style="justify-content:center;">Ce que Boursiv te propose</p>
        <h2 class="cb-sec-title" style="text-align:center;">
            Tout ce dont tu as besoin<br><em>pour te former et progresser</em>
        </h2>
        <div class="cb-feature-grid cbr">
            <a href="{{ route('formations.brvm') }}" class="cb-feature-card" style="text-decoration:none;">
                <span class="cb-feature-num">01</span>
                <span class="cb-feature-emoji">🎓</span>
                <div class="cb-feature-title">Apprendre</div>
                <div class="cb-feature-desc">Formations vidéo débutant → intermédiaire, mini-cours en livre, ressources pédagogiques à ton rythme.</div>
            </a>
            <a href="{{ route('wallet.index') }}" class="cb-feature-card" style="text-decoration:none;">
                <span class="cb-feature-num">02</span>
                <span class="cb-feature-emoji">💼</span>
                <div class="cb-feature-title">S'entraîner</div>
                <div class="cb-feature-desc">Portefeuille virtuel sans risque, radar marché 7 jours, chocs de marché — pratique avant d'investir réel.</div>
            </a>
            <a href="{{ route('marketplace.index') }}" class="cb-feature-card" style="text-decoration:none;">
                <span class="cb-feature-num">03</span>
                <span class="cb-feature-emoji">🛍️</span>
                <div class="cb-feature-title">Échanger</div>
                <div class="cb-feature-desc">Marketplace ouverte : cours, e-books, logiciels, outils, jeux éducatifs — achetez et vendez entre membres.</div>
            </a>
            <a href="{{ route('announcements.index') }}" class="cb-feature-card" style="text-decoration:none;">
                <span class="cb-feature-num">04</span>
                <span class="cb-feature-emoji">📊</span>
                <div class="cb-feature-title">Comprendre le marché</div>
                <div class="cb-feature-desc">Annonces du marché, analyses IA des BOC, états financiers décryptés — toute l'info accessible.</div>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     MARKETPLACE BANNER
══════════════════════════════════════ --}}
<section style="background:#060910; border-top:1px solid rgba(201,168,76,.08); border-bottom:1px solid rgba(201,168,76,.08); padding:56px 0;">
    <div class="container" style="max-width:1100px;">
        <div class="cbr" style="
            background: linear-gradient(135deg, rgba(201,168,76,.07) 0%, rgba(12,17,32,.9) 60%);
            border: 1px solid rgba(201,168,76,.22);
            border-radius: 6px;
            padding: clamp(32px, 5vw, 56px) clamp(28px, 5vw, 56px);
            position: relative; overflow: hidden;
        ">
            {{-- Deco orb --}}
            <div style="position:absolute;top:-60px;right:-60px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle, rgba(201,168,76,.09) 0%, transparent 70%);pointer-events:none;"></div>

            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span style="display:inline-flex;align-items:center;gap:8px;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#C9A84C;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.22);padding:5px 14px;border-radius:100px;margin-bottom:18px;">
                        ✦ Nouveau
                    </span>
                    <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,38px);font-weight:900;color:#E8EAF0;line-height:1.1;margin-bottom:14px;">
                        La <em style="color:#C9A84C;font-style:italic;">Marketplace</em> Boursiv
                    </h2>
                    <p style="font-size:15px;color:#6B7590;line-height:1.75;max-width:520px;font-weight:300;margin-bottom:0;">
                        PDF, études de marché, logiciels, vidéos, jeux éducatifs —
                        des ressources sélectionnées pour votre <strong style="color:#E8EAF0;font-weight:500;">éducation financière et développement personnel</strong>.
                    </p>
                </div>
                <div class="col-lg-4 d-flex flex-column flex-sm-row flex-lg-column gap-2 align-items-start align-items-lg-end">
                    <a href="{{ route('marketplace.index') }}" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:linear-gradient(135deg,#C9A84C,#9B6B15);
                        color:#050810 !important; font-family:'Syne',sans-serif;
                        font-weight:800;font-size:13px;letter-spacing:.06em;text-transform:uppercase;
                        padding:13px 26px;border-radius:3px;text-decoration:none;
                        transition:all .3s;white-space:nowrap;
                    "
                    onmouseover="this.style.boxShadow='0 10px 32px rgba(201,168,76,.35)';this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.boxShadow='';this.style.transform=''">
                        🛍️ Découvrir la Marketplace
                    </a>
                    <a href="{{ route('marketplace.index') }}" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:transparent;color:#C9A84C !important;
                        font-family:'Syne',sans-serif;font-weight:600;font-size:12px;
                        letter-spacing:.07em;text-transform:uppercase;
                        padding:12px 20px;border-radius:3px;text-decoration:none;
                        border:1px solid rgba(201,168,76,.25);transition:all .3s;white-space:nowrap;
                    "
                    onmouseover="this.style.background='rgba(201,168,76,.08)'"
                    onmouseout="this.style.background='transparent'">
                        Voir les produits →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     BOC HIGHLIGHT
══════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container" style="max-width:1100px;">
        <div class="cbr">
            <p class="cb-sec-tag">IA & Bourse Ouest-Africaine</p>
            <h2 class="cb-sec-title">La BOC du jour, <em>résumée par l'IA</em></h2>
            <div class="cb-divider"></div>
            <p style="font-size:16px;color:#6B7590;max-width:520px;font-weight:300;line-height:1.75;">
                Marre de décortiquer seul les BOC ? Boursiv publie une interprétation gratuite de la dernière BOC disponible (J-1) chaque jour.
            </p>
        </div>

        <div class="cb-boc-box mt-4 cbr cbr2">
            <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:#0FCFA4;margin-bottom:10px;">
                📄 Bulletin Officiel de la Côte — J-1 disponible
            </p>
            <h3 style="font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,30px);font-weight:700;color:#E8EAF0;margin-bottom:12px;">
                Boursiv — l'IA qui résume la BOC pour toi
            </h3>
            <span style="display:inline-flex;align-items:center;gap:7px;font-family:'Syne',sans-serif;font-size:11px;color:#0FCFA4;background:rgba(15,207,164,.08);border:1px solid rgba(15,207,164,.18);padding:5px 12px;border-radius:100px;margin-bottom:18px;">
                ✅ Accès 100% gratuit — aucun compte requis
            </span>
            <p style="font-size:14px;color:#6B7590;line-height:1.75;max-width:560px;margin-bottom:24px;">
                Résumé pédagogique · Points clés · Tendances · Audio + avatar vidéo si disponible.<br>
                <em style="font-size:13px;">La BRVM ne publie pas de BOC le jour même — la dernière disponible est celle de J-1.</em>
            </p>
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($latestPublicBoc))
                    <a href="{{ route('client-bocs.latest.public') }}" class="cb-cta-green">
                        📄 Voir la BOC du {{ \Carbon\Carbon::parse($latestPublicBoc->boc_date)->format('d/m/Y') }} →
                    </a>
                @else
                    <a href="{{ route('landing') }}" class="cb-cta-green">📄 BOC (J-1) bientôt disponible</a>
                @endif
                <a href="{{ route('client-financials.create') }}" class="cb-cta-outline">📊 Analyser un état financier</a>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     RADAR SECTION
══════════════════════════════════════ --}}
<section class="cb-sec" style="background:#060910;">
    <div class="container" style="max-width:1100px;">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 cbr">
                <p class="cb-sec-tag">Radar Marché</p>
                <h2 class="cb-sec-title">Visualise les <em>opportunités</em> en 7 jours</h2>
                <div class="cb-divider"></div>
                <p style="font-size:16px;color:#6B7590;line-height:1.75;font-weight:300;margin-bottom:28px;">
                    Repère en un coup d'œil quelles sociétés performent, lesquelles reculent, et les tendances de la bourse ouest-africaine sur la semaine.
                </p>
                <a href="{{ route('radar.index') }}" class="cb-cta-primary">📡 Ouvrir le Radar Marché</a>
            </div>
            <div class="col-lg-6 cbr cbr2">
                <div class="cb-radar-wrap">
                    <div class="cb-radar-ring r1"></div>
                    <div class="cb-radar-ring r2"></div>
                    <div class="cb-radar-ring r3"></div>
                    <div class="cb-radar-ring r4"></div>
                    <div class="cb-radar-sweep"></div>
                    <div class="cb-bubble bu1">SNTS</div>
                    <div class="cb-bubble bu2">CBIBF</div>
                    <div class="cb-bubble bu3">ORAGO</div>
                    <div class="cb-bubble bu4">SIFCA</div>
                    <div class="cb-bubble bu5">BRVM<br>RADAR</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     CALLOUTS
══════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container" style="max-width:1100px;">
        <div class="row g-3">
            <div class="col-md-6 cbr">
                <div class="cb-callout h-100 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="cb-callout-title">🛍️ Marketplace Boursiv</div>
                        <div class="cb-callout-desc">PDF, vidéos, logiciels – achat instantané.<br>Paiement sécurisé par <strong style="color:#E8EAF0;">Mobile Money</strong>.</div>
                    </div>
                    <a href="{{ route('marketplace.index') }}" class="cb-cta-primary" style="white-space:nowrap;">Découvrir →</a>
                </div>
            </div>
            <div class="col-md-6 cbr cbr2">
                <div class="cb-callout h-100 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="cb-callout-title">⚡ Chocs de marché</div>
                        <div class="cb-callout-desc">Comprends pourquoi une action peut monter ou chuter subitement, par secteur.</div>
                    </div>
                    <a href="{{ route('chocs.index') }}" class="cb-cta-outline" style="white-space:nowrap;">Explorer →</a>
                </div>
            </div>
            <div class="col-md-6 cbr">
                <div class="cb-callout h-100 d-flex justify-content-between align-items-center gap-3 flex-wrap" style="border-color:rgba(201,168,76,.18);">
                    <div>
                        <div class="cb-callout-title">💬 Forum communautaire</div>
                        <div class="cb-callout-desc">Échangez avec les autres investisseurs — analyses, questions, actualités et expériences partagées.</div>
                    </div>
                    <a href="{{ route('forum.index') }}" class="cb-cta-primary" style="white-space:nowrap;">Rejoindre →</a>
                </div>
            </div>
            <div class="col-md-6 cbr cbr2">
                <div class="cb-callout h-100 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="cb-callout-title">📚 Mini-cours en livre</div>
                        <div class="cb-callout-desc">Apprends des notions de bourse rapidement, page par page, comme un vrai livre.</div>
                    </div>
                    <a href="{{ route('books.index') }}" class="cb-cta-green" style="white-space:nowrap;">Ouvrir →</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     CTA AFFILIATION
══════════════════════════════════════ --}}
<section style="background:linear-gradient(135deg,#0C1120 0%,#060910 100%);border-top:1px solid rgba(201,168,76,.1);border-bottom:1px solid rgba(201,168,76,.1);padding:52px 0;">
    <div class="container" style="max-width:1000px;">
        <div class="row g-4 align-items-center cbr">
            <div class="col-lg-7">
                <p style="font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;margin-bottom:10px;">Programme apporteur d'affaires</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0;line-height:1.2;margin-bottom:14px;">
                    Gagnez <em style="color:#C9A84C;font-style:italic;">10%</em> en parrainant<br>vos contacts sur Boursiv
                </h2>
                <p style="font-size:14px;color:#6B7590;line-height:1.7;margin-bottom:24px;max-width:520px;">
                    Partagez votre lien unique — vos contacts bénéficient de <strong style="color:#0FCFA4;">−10%</strong> à l'achat de formations et packs éligibles, et vous touchez <strong style="color:#C9A84C;">+10%</strong> de commission. Reversement dès 10 000 FCFA via mobile money.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('affiliate.landing') }}"
                       style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810;text-decoration:none;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:13px 24px;border-radius:3px;transition:all .3s;">
                        🤝 Devenir apporteur d'affaires
                    </a>
                </div>
            </div>
            <div class="col-lg-5 cbr cbr2">
                <div class="row g-3">
                    <div class="col-6">
                        <div style="background:#0C1120;border:1px solid rgba(201,168,76,.12);border-radius:4px;padding:18px;text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#C9A84C;">+10%</div>
                            <div style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-top:4px;">Pour vous</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#0C1120;border:1px solid rgba(15,207,164,.12);border-radius:4px;padding:18px;text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:#0FCFA4;">−10%</div>
                            <div style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-top:4px;">Pour l'acheteur</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div style="background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:14px 16px;font-size:12px;color:#9AA3B8;line-height:1.7;">
                            ✅ Lien + QR code uniques &nbsp;·&nbsp; ✅ Retrait mobile money &nbsp;·&nbsp; ✅ Tableau de bord dédié
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     ANNONCES BRVM
══════════════════════════════════════ --}}
<section class="cb-sec" style="background:#060910;">
    <div class="container" style="max-width:1100px;">
        <div class="cbr d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div>
                <p class="cb-sec-tag">Annonces récentes</p>
                <h2 class="cb-sec-title" style="margin-bottom:0;">Toute l'info du marché, <em>en temps réel</em></h2>
            </div>
            <a href="{{ route('announcements.index') }}" class="cb-cta-outline">Voir tout →</a>
        </div>

        <div class="row g-3 cbr cbr2">
            @forelse(($annonces ?? []) as $a)
                <div class="col-md-4">
                    <a href="{{ route('announcements.show', $a->id) }}" class="cb-ann-card">
                        <div class="cb-ann-date">
                            {{ optional($a->published_at ?? $a->created_at)->format('d/m/Y') }}
                        </div>
                        <div class="cb-ann-title">{{ $a->title ?? 'Annonce' }}</div>
                        @if(!empty($a->excerpt))
                            <div class="cb-ann-excerpt">{{ \Illuminate\Support\Str::limit($a->excerpt, 100) }}</div>
                        @elseif(!empty($a->content))
                            <div class="cb-ann-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 100) }}</div>
                        @endif
                        <span class="cb-ann-link">Lire l'annonce →</span>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div style="background:rgba(201,168,76,.04);border:1px solid rgba(201,168,76,.1);border-radius:4px;padding:24px;font-size:14px;color:#6B7590;">
                        Aucune annonce pour le moment. Les communiqués du marché apparaîtront ici dès publication.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     COMMENT ÇA MARCHE
══════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container" style="max-width:1100px;">
        <div class="cbr text-center" style="max-width:520px;margin:0 auto;">
            <p class="cb-sec-tag" style="justify-content:center;">Comment ça marche ?</p>
            <h2 class="cb-sec-title">Simple. <em>Direct. Efficace.</em></h2>
            <div class="cb-divider" style="margin:16px auto;"></div>
        </div>
        <div class="cb-how-grid cbr cbr2">
            <div class="cb-how-step">
                <div class="cb-how-n">01</div>
                <h3 class="cb-how-title">Voir la BOC (J-1)</h3>
                <p class="cb-how-desc">Un clic et tu lis l'interprétation gratuite de la dernière BOC disponible. Résumé clair, audio si disponible.</p>
            </div>
            <div class="cb-how-step">
                <div class="cb-how-n">02</div>
                <h3 class="cb-how-title">Explorer le marché</h3>
                <p class="cb-how-desc">Radar 7 jours, annonces, chocs de marché — tous les outils gratuits pour suivre la bourse en continu.</p>
            </div>
            <div class="cb-how-step">
                <div class="cb-how-n">03</div>
                <h3 class="cb-how-title">Se former & simuler</h3>
                <p class="cb-how-desc">Formations, livres, portefeuille virtuel — monte en compétence à ton rythme, sans risque financier.</p>
            </div>
            <div class="cb-how-step">
                <div class="cb-how-n">04</div>
                <h3 class="cb-how-title">Aller plus loin</h3>
                <p class="cb-how-desc">Analyse d'états financiers, marketplace de contenus, stratégies avancées pour l'investisseur sérieux.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     PRICING
══════════════════════════════════════ --}}
<section class="cb-sec" style="background:#060910;">
    <div class="container" style="max-width:1100px;">
        <div class="cbr">
            <p class="cb-sec-tag">Tarifs</p>
            <h2 class="cb-sec-title">Commence <em>gratuitement,</em> va plus loin si besoin</h2>
            <div class="cb-divider"></div>
        </div>
        <div class="row g-3 mt-2 cbr cbr2">
            <div class="col-md-4">
                <div class="cb-price-card">
                    <div class="cb-price-label">Pour débuter</div>
                    <div class="cb-price-title">📄 BOC (J-1) gratuite</div>
                    <div class="cb-price-num">0 <span>FCFA</span></div>
                    <p class="cb-price-desc">Idéal pour suivre rapidement le marché chaque jour.</p>
                    <ul class="cb-price-features">
                        <li>Interprétation IA de la dernière BOC (J-1)</li>
                        <li>Résumé clair en français simple</li>
                        <li>Audio + avatar vidéo si disponible</li>
                        <li>Radar Marché 7 jours</li>
                        <li>Mini-cours en livre gratuit</li>
                    </ul>
                    @if(!empty($latestPublicBoc))
                        <a href="{{ route('client-bocs.latest.public') }}" class="cb-cta-green d-block text-center">Voir la BOC gratuite →</a>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="cb-price-card featured">
                    <div class="cb-price-label">Pour aller plus loin</div>
                    <div class="cb-price-title">📊 Analyse état financier</div>
                    <div class="cb-price-num" style="font-size:24px;color:#C9A84C;">Payant <span>selon service</span></div>
                    <p class="cb-price-desc">Pour comprendre en profondeur une entreprise cotée.</p>
                    <ul class="cb-price-features">
                        <li>Décodage des chiffres clés</li>
                        <li>Points forts / points de vigilance</li>
                        <li>Résumé orienté investisseur long terme</li>
                        <li>Analyse pédagogique et claire</li>
                    </ul>
                    <a href="{{ route('client-financials.create') }}" class="cb-cta-primary d-block text-center">Analyser →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cb-price-card">
                    <div class="cb-price-label">Contenus premium</div>
                    <div class="cb-price-title">🛍️ Marketplace</div>
                    <div class="cb-price-num" style="font-size:24px;color:#0FCFA4;">Mobile <span>Money</span></div>
                    <p class="cb-price-desc">PDF, vidéos, logiciels — achat instantané.</p>
                    <ul class="cb-price-features">
                        <li>Livres PDF thématiques bourse</li>
                        <li>Vidéos de formation</li>
                        <li>Logiciels & outils pratiques</li>
                        <li>Accès immédiat après paiement</li>
                    </ul>
                    <a href="{{ route('marketplace.index') }}" class="cb-cta-outline d-block text-center">Ouvrir la Marketplace →</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     FORMATIONS
══════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container" style="max-width:1100px;">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 cbr">
                <p class="cb-sec-tag">Formations</p>
                <h2 class="cb-sec-title">Monte en niveau <em>à ton rythme</em></h2>
                <div class="cb-divider"></div>
                <p style="font-size:15px;color:#6B7590;line-height:1.75;font-weight:300;margin-bottom:24px;">
                    Cours débutant → intermédiaire, disponibles 24h/24 à vie. Accessibles smartphone, PC ou tablette. Adaptés à tous les niveaux.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('formations.brvm') }}" class="cb-cta-primary">🎓 Voir les formations</a>
                    <a href="{{ route('marketplace.index') }}" class="cb-cta-outline">🛍️ Marketplace</a>
                </div>
            </div>
            <div class="col-lg-6 cbr cbr2">
                <div style="background:#121A2C;border:1px solid rgba(201,168,76,.12);border-radius:4px;padding:28px;">
                    <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#6B7590;margin-bottom:10px;">Exemple de formation</p>
                    <h4 style="font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#E8EAF0;margin-bottom:10px;">
                        « Investir en bourse – Guide du débutant »
                    </h4>
                    <p style="font-size:13px;color:#6B7590;line-height:1.65;margin-bottom:18px;">
                        Comprendre les bases de la bourse régionale, ouvrir un compte-titres, placer tes premiers ordres en limitant les erreurs classiques.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="cb-chip cb-chip-free">Vidéo HD</span>
                        <span class="cb-chip cb-chip-pay">Cas pratiques</span>
                        <span class="cb-chip cb-chip-beta">Mises à jour</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     PACK BRVM COMPLET
══════════════════════════════════════ --}}
<section style="background:#060910; padding:72px 0; border-top:1px solid rgba(201,168,76,.08); border-bottom:1px solid rgba(201,168,76,.08);">
    <style>@keyframes packBadgePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(201,168,76,.5)}50%{opacity:.7;box-shadow:0 0 0 7px rgba(201,168,76,0)}}</style>
    <div class="container cbr" style="max-width:900px; text-align:center;">

        <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(201,168,76,.08); border:1px solid rgba(201,168,76,.28); border-radius:100px; padding:7px 18px; margin-bottom:22px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#C9A84C;animation:packBadgePulse 1.5s ease-in-out infinite;flex-shrink:0;"></span>
            <span style="font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#C9A84C;">Pack Exclusif</span>
        </div>

        <h2 style="font-family:'Playfair Display',serif; font-size:clamp(26px,5vw,42px); font-weight:900; color:#E8EAF0; margin-bottom:10px; line-height:1.1;">
            🎓 Pack Boursiv <em style="color:#C9A84C;">Complet</em>
        </h2>

        <p style="font-size:14px; color:#6B7590; line-height:1.75; max-width:600px; margin:0 auto 24px;">
            3 Cours + Groupe WhatsApp Privé + Aide à l'ouverture de compte titre
        </p>

        <div style="margin-bottom:28px;">
            <span style="font-size:17px; color:#6B7590; text-decoration:line-through; margin-right:12px;">50 000 FCFA</span>
            <span style="font-family:'Playfair Display',serif; font-size:clamp(32px,5vw,46px); font-weight:900; color:#C9A84C;">30 000 FCFA</span>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:36px;">
            <span style="font-size:13px; color:#E8EAF0; background:rgba(201,168,76,.06); border:1px solid rgba(201,168,76,.15); border-radius:4px; padding:7px 14px;">✅ Cours Débutant</span>
            <span style="font-size:13px; color:#E8EAF0; background:rgba(201,168,76,.06); border:1px solid rgba(201,168,76,.15); border-radius:4px; padding:7px 14px;">✅ Cours Intermédiaire</span>
            <span style="font-size:13px; color:#E8EAF0; background:rgba(201,168,76,.06); border:1px solid rgba(201,168,76,.15); border-radius:4px; padding:7px 14px;">✅ Cours Pratique</span>
            <span style="font-size:13px; color:#0FCFA4; background:rgba(15,207,164,.06); border:1px solid rgba(15,207,164,.2); border-radius:4px; padding:7px 14px;">💬 Groupe WhatsApp Privé</span>
        </div>

        <a href="{{ route('pack.show') }}"
           style="display:inline-flex; align-items:center; gap:10px;
                  background:linear-gradient(135deg,#C9A84C,#9B6B15);
                  color:#050810 !important; font-family:'Syne',sans-serif;
                  font-weight:800; font-size:13px; letter-spacing:.07em; text-transform:uppercase;
                  padding:15px 36px; border-radius:4px; text-decoration:none; transition:all .3s;"
           onmouseover="this.style.boxShadow='0 10px 32px rgba(201,168,76,.35)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.boxShadow='';this.style.transform=''">
            Découvrir le Pack →
        </a>

    </div>
</section>

{{-- ══════════════════════════════════════
     SERVICES — Financement + Formation présentielle
══════════════════════════════════════ --}}
<section class="cb-sec" style="background:#060910;">
    <div class="container" style="max-width:1100px;">
        <div class="cbr">
            <p class="cb-sec-tag">Services Boursiv</p>
            <h2 class="cb-sec-title">Bien plus qu'une plateforme — <em>un accompagnement</em></h2>
            <div class="cb-divider"></div>
        </div>
        <div class="row g-3 mt-2 cbr cbr2">

            {{-- Financement --}}
            <div class="col-md-6">
                <div style="
                    background:#0C1120; border:1px solid rgba(37,211,102,.15);
                    border-radius:6px; padding:clamp(28px,4vw,40px); height:100%;
                    display:flex; flex-direction:column; transition:border-color .3s;
                " onmouseover="this.style.borderColor='rgba(37,211,102,.3)'" onmouseout="this.style.borderColor='rgba(37,211,102,.15)'">
                    <div style="font-size:36px; margin-bottom:16px;">💰</div>
                    <p style="font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#25D366;margin-bottom:10px;">Facilitation de financement</p>
                    <h3 style="font-family:'Playfair Display',serif;font-size:clamp(18px,2.5vw,24px);font-weight:700;color:#E8EAF0;line-height:1.25;margin-bottom:12px;">
                        Besoin d'un prêt pour concrétiser votre projet ?
                    </h3>
                    <p style="font-size:14px;color:#6B7590;line-height:1.75;margin-bottom:24px;flex-grow:1;">
                        Boursiv joue le rôle de <strong style="color:#E8EAF0;">facilitateur</strong> entre les particuliers
                        et des structures financières agréées. Mise en relation gratuite, réponse sous 24h.
                    </p>
                    <a href="{{ route('financement') }}" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:transparent;color:#25D366 !important;
                        font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
                        padding:11px 22px;border-radius:4px;text-decoration:none;
                        border:1px solid rgba(37,211,102,.35);transition:all .3s;align-self:flex-start;
                    "
                    onmouseover="this.style.background='rgba(37,211,102,.08)';this.style.borderColor='rgba(37,211,102,.6)'"
                    onmouseout="this.style.background='transparent';this.style.borderColor='rgba(37,211,102,.35)'">
                        En savoir plus →
                    </a>
                </div>
            </div>

            {{-- Formation présentielle --}}
            <div class="col-md-6">
                <div style="
                    background:#0C1120; border:1px solid rgba(201,168,76,.15);
                    border-radius:6px; padding:clamp(28px,4vw,40px); height:100%;
                    display:flex; flex-direction:column; transition:border-color .3s;
                " onmouseover="this.style.borderColor='rgba(201,168,76,.35)'" onmouseout="this.style.borderColor='rgba(201,168,76,.15)'">
                    <div style="font-size:36px; margin-bottom:16px;">🎓</div>
                    <p style="font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#C9A84C;margin-bottom:10px;">Formation en présentiel</p>
                    <h3 style="font-family:'Playfair Display',serif;font-size:clamp(18px,2.5vw,24px);font-weight:700;color:#E8EAF0;line-height:1.25;margin-bottom:12px;">
                        Maîtrisez la bourse avec une formation en présentiel
                    </h3>
                    <p style="font-size:14px;color:#6B7590;line-height:1.75;margin-bottom:24px;flex-grow:1;">
                        Sessions animées par des experts, adaptées à tous les niveaux — du débutant à l'investisseur confirmé.
                        <strong style="color:#E8EAF0;">Attestation délivrée</strong> à l'issue.
                    </p>
                    <a href="{{ route('formation.presentielle') }}" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:linear-gradient(135deg,#C9A84C,#9B6B15);
                        color:#050810 !important;font-family:'Syne',sans-serif;
                        font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;
                        padding:11px 22px;border-radius:4px;text-decoration:none;
                        transition:all .3s;align-self:flex-start;
                    "
                    onmouseover="this.style.boxShadow='0 8px 24px rgba(201,168,76,.3)';this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.boxShadow='';this.style.transform=''">
                        En savoir plus →
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     FORUM COMMUNAUTAIRE
══════════════════════════════════════ --}}
<section class="cb-sec cb-sec-alt">
    <div class="container" style="max-width:1100px;">
        <div class="row g-5 align-items-center">

            <div class="col-lg-5 cbr">
                <p class="cb-sec-tag">Forum</p>
                <h2 class="cb-sec-title">La communauté des <em>investisseurs</em></h2>
                <div class="cb-divider"></div>
                <p style="font-size:15px;color:#6B7590;line-height:1.75;font-weight:300;margin-bottom:28px;">
                    Échangez, posez vos questions, partagez vos analyses avec une communauté d'investisseurs passionnés.
                    La lecture est libre — publiez en vous connectant.
                </p>
                <a href="{{ route('forum.index') }}" class="cb-cta-primary">💬 Rejoindre la communauté</a>
            </div>

            <div class="col-lg-7 cbr cbr2">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('forum.category', 'analyses') }}" style="
                            display:block; background:#060910; border:1px solid rgba(201,168,76,.1);
                            border-radius:6px; padding:22px 20px; text-decoration:none;
                            transition:border-color .3s, transform .3s;
                        "
                        onmouseover="this.style.borderColor='rgba(201,168,76,.3)';this.style.transform='translateY(-3px)'"
                        onmouseout="this.style.borderColor='rgba(201,168,76,.1)';this.style.transform=''">
                            <div style="font-size:24px;margin-bottom:10px;">📊</div>
                            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#E8EAF0;margin-bottom:4px;">Analyses</div>
                            <div style="font-size:12px;color:#6B7590;line-height:1.5;">Fondamentales, techniques, revues de sociétés</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('forum.category', 'questions-debutants') }}" style="
                            display:block; background:#060910; border:1px solid rgba(201,168,76,.1);
                            border-radius:6px; padding:22px 20px; text-decoration:none;
                            transition:border-color .3s, transform .3s;
                        "
                        onmouseover="this.style.borderColor='rgba(201,168,76,.3)';this.style.transform='translateY(-3px)'"
                        onmouseout="this.style.borderColor='rgba(201,168,76,.1)';this.style.transform=''">
                            <div style="font-size:24px;margin-bottom:10px;">💡</div>
                            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#E8EAF0;margin-bottom:4px;">Questions débutants</div>
                            <div style="font-size:12px;color:#6B7590;line-height:1.5;">Posez toutes vos questions sans hésiter</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('forum.category', 'actualites-brvm') }}" style="
                            display:block; background:#060910; border:1px solid rgba(201,168,76,.1);
                            border-radius:6px; padding:22px 20px; text-decoration:none;
                            transition:border-color .3s, transform .3s;
                        "
                        onmouseover="this.style.borderColor='rgba(201,168,76,.3)';this.style.transform='translateY(-3px)'"
                        onmouseout="this.style.borderColor='rgba(201,168,76,.1)';this.style.transform=''">
                            <div style="font-size:24px;margin-bottom:10px;">📰</div>
                            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#E8EAF0;margin-bottom:4px;">Actualités marché</div>
                            <div style="font-size:12px;color:#6B7590;line-height:1.5;">Infos, événements, nouvelles du marché</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('forum.category', 'experiences-investisseurs') }}" style="
                            display:block; background:#060910; border:1px solid rgba(201,168,76,.1);
                            border-radius:6px; padding:22px 20px; text-decoration:none;
                            transition:border-color .3s, transform .3s;
                        "
                        onmouseover="this.style.borderColor='rgba(201,168,76,.3)';this.style.transform='translateY(-3px)'"
                        onmouseout="this.style.borderColor='rgba(201,168,76,.1)';this.style.transform=''">
                            <div style="font-size:24px;margin-bottom:10px;">💼</div>
                            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#E8EAF0;margin-bottom:4px;">Expériences d'investisseurs</div>
                            <div style="font-size:12px;color:#6B7590;line-height:1.5;">Témoignages, stratégies, retours d'expérience</div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     DIASPORA
══════════════════════════════════════ --}}
<section style="background:linear-gradient(180deg,#060910 0%,#071210 60%,#060910 100%); padding:clamp(64px,10vw,120px) 0; position:relative; overflow:hidden;" class="cbr">
    <div style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 50%, rgba(37,211,102,.06) 0%, transparent 65%);"></div>
    <div class="container" style="max-width:860px; position:relative;">
        <div style="border:1px solid rgba(37,211,102,.2); border-radius:8px; padding:clamp(32px,5vw,56px) clamp(24px,5vw,56px); background:rgba(37,211,102,.025); text-align:center;">

            <div class="cb-diaspora-badge cbr">
                <span class="cb-diaspora-dot"></span>
                Investir depuis la diaspora
            </div>

            <h2 style="font-family:'Playfair Display',serif; font-size:clamp(26px,4vw,38px); font-weight:700; color:#E8EAF0; margin:0 0 14px;" class="cbr cbr2">
                Tu es à l'étranger et tu veux<br>investir dans la <span style="color:#25D366;">bourse ouest-africaine</span> ?
            </h2>

            <p style="font-size:clamp(14px,2vw,16px); color:#6B7590; line-height:1.85; max-width:600px; margin:0 auto 28px;" class="cbr">
                Boursiv <strong style="color:#E8EAF0;">n'est pas une SGI</strong>. Nous t'accompagnons pas à pas dans les démarches
                d'ouverture de ton compte-titres auprès d'une <strong style="color:#E8EAF0;">SGI agréée BRVM</strong> — que tu sois en Europe, en Amérique, ou ailleurs.
                Tout se fait en ligne, de A à Z.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4 cbr cbr2">
                <div style="background:rgba(255,255,255,.04); border:1px solid rgba(37,211,102,.12); border-radius:6px; padding:14px 20px; text-align:left; min-width:220px; flex:1; max-width:280px;">
                    <div style="font-size:20px; margin-bottom:6px;">🌍</div>
                    <div style="font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#25D366; margin-bottom:4px;">Qui peut en bénéficier</div>
                    <p style="font-size:13px; color:#9BA3B8; margin:0; line-height:1.65;">Diaspora africaine, Européens, Américains... Toute personne hors UEMOA souhaitant investir dans la bourse ouest-africaine.</p>
                </div>
                <div style="background:rgba(255,255,255,.04); border:1px solid rgba(37,211,102,.12); border-radius:6px; padding:14px 20px; text-align:left; min-width:220px; flex:1; max-width:280px;">
                    <div style="font-size:20px; margin-bottom:6px;">📋</div>
                    <div style="font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#25D366; margin-bottom:4px;">Ce qu'on fait</div>
                    <p style="font-size:13px; color:#9BA3B8; margin:0; line-height:1.65;">Identification de la SGI, accompagnement du dossier, suivi jusqu'à l'activation de ton compte-titres.</p>
                </div>
                <div style="background:rgba(255,255,255,.04); border:1px solid rgba(37,211,102,.12); border-radius:6px; padding:14px 20px; text-align:left; min-width:220px; flex:1; max-width:280px;">
                    <div style="font-size:20px; margin-bottom:6px;">✅</div>
                    <div style="font-family:'Syne',sans-serif; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#25D366; margin-bottom:4px;">100% en ligne</div>
                    <p style="font-size:13px; color:#9BA3B8; margin:0; line-height:1.65;">Valable depuis n'importe quel pays. Réponse sur WhatsApp sous 24h, sans déplacement.</p>
                </div>
            </div>

            <a href="{{ route('diaspora') }}" class="cb-wa-cta">
                <i class="bi bi-whatsapp"></i> En savoir plus &amp; nous contacter
            </a>
            <p style="font-size:11px; color:rgba(107,117,144,.5); margin-top:14px; font-family:'Syne',sans-serif; letter-spacing:.08em; text-transform:uppercase;">
                Réponse sous 24h &nbsp;·&nbsp; 100% en ligne &nbsp;·&nbsp; Accompagnement personnalisé
            </p>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(255,255,255,.06);">
                <p style="font-size:13px; color:#6B7590; margin-bottom:8px;">
                    Besoin d'un financement pour votre projet ?
                </p>
                <a href="{{ route('financement') }}" style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.06em;color:#25D366;text-decoration:none;">
                    Découvrez notre service de facilitation →
                </a>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════
     CTA FINALE
══════════════════════════════════════ --}}
<section class="cb-cta-finale">
    <div class="container cbr" style="max-width:1100px;">
        <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:#C9A84C;margin-bottom:22px;">
            Rejoins dès maintenant
        </p>
        <h2 class="cb-cta-finale-title">
            Passe à un autre niveau<br>
            <span class="g">d'investisseur</span>
        </h2>
        <p style="font-size:clamp(15px,2vw,17px);color:#6B7590;max-width:420px;margin:20px auto 40px;line-height:1.75;font-weight:300;">
            Boursiv t'accompagne à chaque étape. Commence avec la BOC gratuite, puis va aussi loin que tu veux.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('register') }}" class="cb-cta-primary" style="font-size:15px;padding:16px 36px;">✦ S'inscrire — c'est gratuit</a>
            @if(!empty($latestPublicBoc))
                <a href="{{ route('client-bocs.latest.public') }}" class="cb-cta-green" style="padding:15px 28px;font-size:15px;">📄 Voir la BOC gratuite</a>
            @endif
        </div>
        <p style="font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#6B7590;margin-top:32px;border-top:1px solid rgba(255,255,255,.06);padding-top:24px;">
            boursiv.com &nbsp;·&nbsp; <strong style="color:#C9A84C;">Comprends. Analyse. Progresse.</strong>
        </p>
    </div>
</section>

{{-- ══════════════════════════════════════
     BANDE INDÉPENDANCE & TRANSPARENCE
══════════════════════════════════════ --}}
<div style="background:#060910;border-top:1px solid rgba(255,255,255,.05);padding:28px 0;">
    <div class="container" style="max-width:860px;text-align:center;">
        <p style="font-size:12px;color:#4A5068;line-height:1.85;font-weight:300;">
            Boursiv est une plateforme indépendante d'éducation financière. Elle n'est pas affiliée à la BRVM ni à aucune autorité, bourse ou société de gestion. Les formations et contenus sont proposés à but pédagogique par un investisseur autonome partageant son expérience et ses méthodes personnelles ; ils ne constituent pas un conseil en investissement.
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ --}}
<footer class="cb-footer">
    <div class="container" style="max-width:1100px;">
        <div class="row g-4 align-items-center mb-4">
            <div class="col-lg-4">
                <a href="{{ route('landing') }}" class="cb-footer-logo">Boursiv</a>
                <p style="font-size:12px;color:#6B7590;margin-top:10px;line-height:1.6;">
                    Service indépendant, non affilié officiellement à la BRVM.<br>
                    Une solution de CHENGGONG SARL.
                </p>
            </div>
            <div class="col-lg-4">
                <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#6B7590;margin-bottom:12px;">Suivre Boursiv</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://t.me/coachbrvm" target="_blank" class="cb-social-btn"><i class="bi bi-telegram"></i> Telegram</a>
                    <a href="https://x.com/coachbrvm" target="_blank" class="cb-social-btn"><i class="bi bi-twitter-x"></i> X</a>
                    <a href="https://youtube.com/@coachbrvm" target="_blank" class="cb-social-btn"><i class="bi bi-youtube"></i> YouTube</a>
                    <a href="https://www.linkedin.com/company/coach-brvm/" target="_blank" class="cb-social-btn"><i class="bi bi-linkedin"></i> LinkedIn</a>
                    <a href="https://chat.whatsapp.com/JOz4th9OnLnJSFcABUFHPI" target="_blank" class="cb-social-btn"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                    <a href="https://www.facebook.com/share/17q4KouHax/" target="_blank" class="cb-social-btn"><i class="bi bi-facebook"></i> Facebook</a>
                </div>
            </div>
            <div class="col-lg-4">
                <p style="font-family:'Syne',sans-serif;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#6B7590;margin-bottom:12px;">Liens utiles</p>
                <div class="d-flex flex-wrap gap-x-4 gap-2">
                    <a href="{{ route('notre.histoire') }}" class="cb-footer-link">Notre histoire</a>
                    <a href="{{ route('faq') }}" class="cb-footer-link">FAQ</a>
                    <a href="{{ route('aide.glossaire') }}" class="cb-footer-link">Glossaire</a>
                    <a href="{{ route('conditions') }}" class="cb-footer-link">CGU</a>
                    <a href="{{ route('confidentialite') }}" class="cb-footer-link">Confidentialité</a>
                    <a href="{{ route('contact') }}" class="cb-footer-link">Contact</a>
                </div>
            </div>
        </div>
        <div class="border-top pt-3" style="border-color:rgba(255,255,255,.05)!important;">
            <div class="cb-footer-copy">© {{ date('Y') }} Boursiv · CHENGGONG SARL</div>
        </div>
    </div>
</footer>

@push('scripts')
<script>
    // Scroll reveal
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('on'); });
    }, { threshold: 0.07 });
    cbEls.forEach(el => cbObs.observe(el));
</script>
@endpush

@endsection
