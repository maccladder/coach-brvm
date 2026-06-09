{{-- ════════ contact.blade.php ════════ --}}
{{-- resources/views/sections/contact.blade.php --}}
@extends('layouts.app')
@section('title','Contact – Boursiv')

@push('styles')
<style>
    .contact-page { background:#060910;min-height:100vh; }
    .contact-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .contact-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .contact-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .contact-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .contact-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .contact-hero-title em { font-style:italic;color:#C9A84C; }
    .contact-card { background:#0C1120;border:1px solid rgba(201,168,76,.1);border-radius:4px;overflow:hidden; }
    .contact-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,#0FCFA4,transparent); }
    .contact-card-body { padding:32px 36px; }
    .contact-name { font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#E8EAF0;margin-bottom:4px; }
    .contact-role { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:24px; }
    .contact-item { display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.04); }
    .contact-item:last-child { border-bottom:none; }
    .contact-item-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;min-width:100px;flex-shrink:0;margin-top:2px; }
    .contact-item-value { font-size:14px;color:#E8EAF0; }
    .contact-item-value a { color:#0FCFA4;text-decoration:none;transition:color .25s; }
    .contact-item-value a:hover { color:#63B3ED; }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="contact-page">
    <div class="contact-hero">
        <div class="contact-hero-grid"></div>
        <div class="container" style="max-width:800px;position:relative;z-index:1;">
            <p class="contact-hero-tag">Support</p>
            <h1 class="contact-hero-title">Contact <em>Boursiv</em></h1>
            <p style="font-size:14px;color:#6B7590;font-weight:300;">Une question sur les analyses, les BOC, les formations ou la plateforme ? Contactez notre équipe.</p>
        </div>
    </div>
    <div class="container py-5" style="max-width:800px;">
        <div class="contact-card cbr">
            <div class="contact-card-body">
                <div class="contact-name">Djaha Ghislian</div>
                <div class="contact-role">Support & Relations Clients — Boursiv</div>
                <div class="contact-item">
                    <span class="contact-item-label">📞 Téléphone</span>
                    <span class="contact-item-value"><a href="tel:+2250574023351">+225 05 74 02 33 51</a></span>
                </div>
                <div class="contact-item">
                    <span class="contact-item-label">💬 WhatsApp</span>
                    <span class="contact-item-value"><a href="https://wa.me/2250574023351" target="_blank">+225 05 74 02 33 51</a></span>
                </div>
                <div class="contact-item">
                    <span class="contact-item-label">✉️ Email</span>
                    <span class="contact-item-value"><a href="mailto:boursivoire@gmail.com">boursivoire@gmail.com</a></span>
                </div>
                <div class="contact-item">
                    <span class="contact-item-label">⏰ Horaires</span>
                    <span class="contact-item-value" style="color:#6B7590;">Lundi – Vendredi · 9h à 18h (GMT)</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
