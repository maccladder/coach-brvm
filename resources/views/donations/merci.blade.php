@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,700&display=swap" rel="stylesheet">
<style>
  .don-merci-page { background: #060910; min-height: 80vh; display: flex; align-items: center; color: #E8EAF0; font-family: 'DM Sans', sans-serif; }
  .don-merci-inner { text-align: center; max-width: 560px; margin: 0 auto; padding: 80px 24px; }
  .don-merci-ico {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, rgba(201,168,76,.2), rgba(15,207,164,.15));
    border: 1px solid rgba(201,168,76,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 32px;
    box-shadow: 0 0 40px rgba(201,168,76,.15);
  }
  .don-merci-h1 {
    font-family: 'Fraunces', serif; font-weight: 500;
    font-size: clamp(2rem, 5vw, 3rem);
    line-height: 1.1; color: #C9A84C; margin-bottom: 18px;
  }
  .don-merci-sub { font-size: 1.08rem; color: #8892a4; line-height: 1.7; margin-bottom: 28px; }
  .don-merci-sub strong { color: #E8EAF0; }
  .don-merci-ref {
    display: inline-block;
    background: rgba(201,168,76,.08); border: 1px solid rgba(201,168,76,.2);
    border-radius: 8px; padding: 10px 20px;
    font-size: .88rem; color: #C9A84C; font-family: 'DM Mono', monospace, 'DM Sans', sans-serif;
    letter-spacing: .05em; margin-bottom: 36px;
  }
  .don-merci-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
  .don-btn-gold {
    font-family: 'DM Sans', sans-serif; font-size: .92rem; font-weight: 700;
    background: linear-gradient(135deg, #C9A84C, #9B6B15); color: #060910 !important;
    padding: 12px 24px; border-radius: 8px; text-decoration: none;
    transition: filter .2s, transform .15s;
  }
  .don-btn-gold:hover { filter: brightness(1.1); transform: translateY(-1px); }
  .don-btn-ghost {
    font-family: 'DM Sans', sans-serif; font-size: .92rem; font-weight: 600;
    background: transparent; color: #8892a4 !important;
    border: 1px solid rgba(255,255,255,.12);
    padding: 12px 24px; border-radius: 8px; text-decoration: none;
    transition: border-color .2s, color .2s;
  }
  .don-btn-ghost:hover { border-color: rgba(201,168,76,.4); color: #C9A84C !important; }
  .don-merci-note { font-size: .85rem; color: #5a6475; margin-top: 24px; }
</style>
@endpush

@section('content')
<div class="don-merci-page">
  <div class="container">
    <div class="don-merci-inner">
      <div class="don-merci-ico">🙏</div>

      <h1 class="don-merci-h1">Merci pour votre soutien !</h1>

      <p class="don-merci-sub">
        Votre don a bien été reçu.
        @if($donation)
          <strong>{{ number_format($donation->amount, 0, ',', ' ') }} FCFA</strong> — chaque franc compte et contribue directement à la mission.
        @else
          Chaque franc compte et contribue directement à la mission.
        @endif
        <br>Vous portez avec nous l'éducation financière d'une génération.
      </p>

      @if($donation)
        <div class="don-merci-ref">Référence : {{ $donation->reference }}</div>
      @endif

      <div class="don-merci-actions">
        <a href="{{ route('landing') }}" class="don-btn-gold">Retour à l'accueil</a>
        <a href="{{ route('donation.show') }}" class="don-btn-ghost">Faire un autre don</a>
      </div>

      <p class="don-merci-note">Un email de confirmation vous a été envoyé. Merci d'avoir cru en Coach BRVM.</p>
    </div>
  </div>
</div>
@endsection
