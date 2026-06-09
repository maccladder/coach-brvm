@extends('layouts.app')

@section('title', 'Pack Boursiv activé — Merci !')

@section('content')
<div style="background:#060910; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 15px;">
    <div style="max-width:600px; width:100%; text-align:center;">

        <div style="font-size:64px; margin-bottom:24px;">🎉</div>

        <h1 style="font-family:'Playfair Display',serif; font-size:clamp(28px,5vw,42px); font-weight:900; color:#E8EAF0; margin-bottom:16px; line-height:1.15;">
            Votre Pack Boursiv est <em style="color:#C9A84C;">activé</em> !
        </h1>

        <p style="font-size:15px; color:#6B7590; line-height:1.75; margin-bottom:32px;">
            Paiement confirmé. Vos 3 formations sont maintenant disponibles dans votre espace.
        </p>

        {{-- Email notice --}}
        <div style="background:rgba(201,168,76,.06); border:1px solid rgba(201,168,76,.2); border-radius:8px; padding:24px 28px; margin-bottom:32px;">
            <div style="font-size:28px; margin-bottom:12px;">📧</div>
            <p style="font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:#C9A84C; letter-spacing:.06em; text-transform:uppercase; margin-bottom:8px;">
                Consultez votre email
            </p>
            <p style="font-size:13px; color:#9AA3B8; line-height:1.7;">
                Un email de confirmation vous a été envoyé avec votre <strong style="color:#E8EAF0;">lien unique</strong> pour rejoindre le groupe WhatsApp privé.
                Ce lien est personnel et à usage unique.
            </p>
            <p style="font-size:12px; color:#6B7590; margin-top:10px;">
                Vérifiez aussi vos spams si vous ne le recevez pas dans les 5 minutes.
            </p>
        </div>

        {{-- Ce qui est disponible maintenant --}}
        <div style="background:#0C1120; border:1px solid rgba(255,255,255,.06); border-radius:6px; padding:20px 24px; margin-bottom:32px; text-align:left;">
            <p style="font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#6B7590; margin-bottom:14px;">Disponible maintenant</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#E8EAF0;">
                    <span style="color:#0FCFA4;">✓</span> Cours Débutant BRVM
                </div>
                <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#E8EAF0;">
                    <span style="color:#0FCFA4;">✓</span> Cours Intermédiaire BRVM
                </div>
                <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#E8EAF0;">
                    <span style="color:#0FCFA4;">✓</span> Cours Pratique : outils & portefeuille virtuel
                </div>
                <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#9AA3B8;">
                    <span style="color:#C9A84C;">✉</span> Groupe WhatsApp — lien dans l'email
                </div>
            </div>
        </div>

        <a href="{{ route('courses.my') }}"
           style="display:inline-flex; align-items:center; gap:10px;
                  background:linear-gradient(135deg,#C9A84C,#9B6B15);
                  color:#050810 !important; font-family:'Syne',sans-serif;
                  font-weight:800; font-size:13px; letter-spacing:.07em; text-transform:uppercase;
                  padding:15px 36px; border-radius:4px; text-decoration:none; transition:all .3s;"
           onmouseover="this.style.boxShadow='0 8px 28px rgba(201,168,76,.35)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.boxShadow='';this.style.transform=''">
            ▶ Accéder à mes formations
        </a>

    </div>
</div>
@endsection
