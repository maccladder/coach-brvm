@extends('layouts.app')

@section('title', 'Lien WhatsApp — Coach BRVM')

@section('content')
<div style="background:#060910; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 15px;">
    <div style="max-width:520px; width:100%; text-align:center;">

        @if($reason === 'already_used')

            <div style="font-size:56px; margin-bottom:20px;">🔒</div>
            <h1 style="font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,34px); font-weight:900; color:#E8EAF0; margin-bottom:16px;">
                Lien déjà utilisé
            </h1>
            <p style="font-size:14px; color:#6B7590; line-height:1.75; margin-bottom:28px;">
                Ce lien d'accès au groupe WhatsApp a déjà été utilisé. Par sécurité, chaque lien n'est valable qu'une seule fois.
            </p>
            <div style="background:rgba(201,168,76,.06); border:1px solid rgba(201,168,76,.18); border-radius:6px; padding:16px 20px; font-size:13px; color:#9AA3B8; line-height:1.65; margin-bottom:28px;">
                Si vous avez perdu l'accès ou avez besoin d'un nouveau lien, contactez l'équipe Coach BRVM.
                Un administrateur peut vous renvoyer un nouvel email depuis votre profil.
            </div>

        @elseif($reason === 'wrong_user')

            <div style="font-size:56px; margin-bottom:20px;">⚠️</div>
            <h1 style="font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,34px); font-weight:900; color:#E8EAF0; margin-bottom:16px;">
                Ce lien ne vous appartient pas
            </h1>
            <p style="font-size:14px; color:#6B7590; line-height:1.75; margin-bottom:28px;">
                Ce lien d'invitation est associé à un autre compte. Veuillez vous connecter avec le compte qui a acheté le Pack BRVM.
            </p>

        @else {{-- invalid --}}

            <div style="font-size:56px; margin-bottom:20px;">❌</div>
            <h1 style="font-family:'Playfair Display',serif; font-size:clamp(24px,4vw,34px); font-weight:900; color:#E8EAF0; margin-bottom:16px;">
                Lien invalide
            </h1>
            <p style="font-size:14px; color:#6B7590; line-height:1.75; margin-bottom:28px;">
                Ce lien d'accès est invalide ou n'existe pas. Vérifiez que vous avez copié le lien complet depuis votre email de confirmation.
            </p>

        @endif

        <a href="{{ route('landing') }}"
           style="display:inline-flex; align-items:center; gap:8px;
                  background:transparent; color:#C9A84C !important;
                  font-family:'Syne',sans-serif; font-weight:700;
                  font-size:12px; letter-spacing:.08em; text-transform:uppercase;
                  padding:12px 24px; border:1px solid rgba(201,168,76,.35); border-radius:4px;
                  text-decoration:none; transition:all .3s;"
           onmouseover="this.style.background='rgba(201,168,76,.08)'"
           onmouseout="this.style.background='transparent'">
            ← Retour à l'accueil
        </a>

    </div>
</div>
@endsection
