@extends('layouts.app')

@push('meta')
<meta name="description" content="Soutenir Coach BRVM — éduquer la jeunesse d'Afrique de l'Ouest à la finance et à la bourse.">
@endpush

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<style>
  /* ── Variables locales (harmonisées avec le layout) ── */
  :root {
    --don-or:       #C9A84C;
    --don-or-clair: #e9c986;
    --don-vert:     #0FCFA4;
    --don-vert-fonce: #163d33;
    --don-dark:     #060910;
    --don-dark2:    #0C1120;
    --don-carte:    #111827;
    --don-carte2:   #161f2e;
    --don-bord:     rgba(201,168,76,.14);
    --don-texte:    #E8EAF0;
    --don-doux:     #8892a4;
    --don-faible:   #5a6475;
  }

  .don-page { background: var(--don-dark); color: var(--don-texte); font-family: 'DM Sans', sans-serif; }

  /* ── HERO ── */
  .don-hero {
    position: relative;
    padding: 80px 0 64px;
    background:
      radial-gradient(ellipse 70% 60% at 80% 0%, rgba(201,168,76,.12), transparent 70%),
      radial-gradient(ellipse 60% 50% at 0% 30%, rgba(15,207,164,.07), transparent 70%),
      var(--don-dark);
    overflow: hidden;
  }
  .don-hero::before {
    content: "";
    position: absolute; inset: 0;
    background-image:
      linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
    background-size: 54px 54px;
    -webkit-mask-image: radial-gradient(ellipse 70% 70% at 50% 30%, #000, transparent);
            mask-image: radial-gradient(ellipse 70% 70% at 50% 30%, #000, transparent);
  }
  .don-hero-inner { position: relative; max-width: 700px; }

  .don-pastille {
    display: inline-flex; align-items: center; gap: 9px;
    border: 1px solid var(--don-bord);
    background: rgba(201,168,76,.07);
    color: var(--don-or-clair);
    font-size: 11px; letter-spacing: .18em; text-transform: uppercase; font-weight: 700;
    padding: 8px 18px; border-radius: 99px; margin-bottom: 28px;
  }
  .don-pastille .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--don-vert); box-shadow: 0 0 8px var(--don-vert); }

  .don-surtitre { font-size: 12px; letter-spacing: .22em; text-transform: uppercase; color: var(--don-vert); font-weight: 700; margin-bottom: 16px; }

  .don-h1 {
    font-family: 'Fraunces', serif;
    font-weight: 500;
    font-size: clamp(2.2rem, 5.5vw, 3.8rem);
    line-height: 1.06;
    letter-spacing: -.01em;
    margin-bottom: 24px;
    color: var(--don-texte);
  }
  .don-h1 .or { color: var(--don-or); }
  .don-h1 em { font-style: italic; color: var(--don-or-clair); }

  .don-lede { font-size: 1.1rem; color: var(--don-doux); max-width: 54ch; line-height: 1.7; }
  .don-lede strong { color: var(--don-texte); font-weight: 600; }

  /* ── SECTIONS ── */
  .don-section { padding: 56px 0; }
  .don-trait { width: 44px; height: 3px; background: var(--don-or); border-radius: 2px; margin-bottom: 22px; }
  .don-h2 { font-family: 'Fraunces', serif; font-weight: 500; font-size: 1.9rem; letter-spacing: -.01em; margin-bottom: 14px; color: var(--don-texte); }
  .don-h2 em { font-style: italic; color: var(--don-or); }
  .don-body { font-size: 1.05rem; color: var(--don-doux); margin-bottom: 14px; max-width: 62ch; line-height: 1.7; }
  .don-body strong { color: var(--don-texte); }

  /* ── CARDS MISSION ── */
  .don-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 28px; }
  .don-card {
    background: var(--don-carte);
    border: 1px solid var(--don-bord);
    border-radius: 14px; padding: 26px;
    transition: border-color .2s, transform .2s;
  }
  .don-card:hover { border-color: rgba(201,168,76,.35); transform: translateY(-3px); }
  .don-card .ico { font-size: 1.4rem; margin-bottom: 14px; }
  .don-card h3 { font-size: 1.05rem; margin-bottom: 7px; font-weight: 600; color: var(--don-texte); }
  .don-card p { font-size: .95rem; color: var(--don-doux); line-height: 1.6; }

  /* ── BLOC VISION ── */
  .don-vision {
    position: relative;
    background: radial-gradient(ellipse 80% 100% at 100% 0%, rgba(15,207,164,.09), transparent 70%), var(--don-carte);
    border: 1px solid var(--don-bord);
    border-radius: 20px; padding: 44px 40px; overflow: hidden;
  }
  .don-vision::after { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, var(--don-or), var(--don-vert)); }
  .don-vision .pin { display: inline-block; font-size: 11px; letter-spacing: .2em; text-transform: uppercase; color: var(--don-vert); font-weight: 700; margin-bottom: 16px; }
  .don-vision p { color: var(--don-doux); font-size: 1.05rem; max-width: 60ch; line-height: 1.7; }
  .don-vision p + p { margin-top: 12px; }
  .don-signature { margin-top: 24px; display: flex; align-items: center; gap: 14px; }
  .don-signature .av { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--don-or), var(--don-vert-fonce)); display: flex; align-items: center; justify-content: center; font-family: 'Fraunces', serif; font-weight: 600; color: var(--don-dark); font-size: 1rem; flex-shrink: 0; }
  .don-signature .qui b { display: block; color: var(--don-texte); font-size: .96rem; }
  .don-signature .qui span { font-size: .87rem; color: var(--don-faible); }

  /* ── PALIERS ── */
  .don-paliers { display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 16px; margin-top: 28px; }
  .don-palier {
    position: relative; background: var(--don-carte); border: 1px solid var(--don-bord);
    border-radius: 14px; padding: 24px 18px; text-align: center;
    cursor: pointer; transition: all .2s ease; user-select: none;
  }
  .don-palier:hover { border-color: var(--don-or); transform: translateY(-4px); }
  .don-palier.actif { border-color: var(--don-or); background: linear-gradient(180deg, rgba(201,168,76,.1), var(--don-carte)); box-shadow: 0 12px 30px rgba(201,168,76,.13); }
  .don-palier .montant { font-family: 'Fraunces', serif; font-size: 1.65rem; color: var(--don-or); font-weight: 600; }
  .don-palier .label { font-size: .88rem; color: var(--don-doux); margin-top: 5px; }
  .don-palier .tag { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--don-vert); color: var(--don-dark); font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; padding: 2px 11px; border-radius: 99px; }

  /* ── FORMULAIRE ── */
  .don-form-card { background: var(--don-carte); border: 1px solid var(--don-bord); border-radius: 18px; padding: 36px 32px; margin-top: 28px; }
  .don-label { font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--don-doux); margin-bottom: 8px; display: block; }
  .don-input {
    width: 100%; font-family: 'DM Sans', sans-serif; font-size: 1rem;
    padding: 14px 16px; border: 1px solid var(--don-bord);
    border-radius: 11px; background: var(--don-carte2); color: var(--don-texte);
    transition: border-color .2s, box-shadow .2s;
  }
  .don-input::placeholder { color: var(--don-faible); }
  .don-input:focus { outline: none; border-color: var(--don-or); box-shadow: 0 0 0 3px rgba(201,168,76,.11); }
  .don-input.error { border-color: #FF6B6B; }
  .don-divider { height: 1px; background: var(--don-bord); margin: 24px 0; }

  .don-cta {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; margin-top: 18px;
    background: linear-gradient(135deg, var(--don-or), #9B6B15);
    color: var(--don-dark); border: none;
    font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 1.05rem;
    padding: 17px; border-radius: 12px; cursor: pointer;
    transition: filter .2s, transform .15s;
  }
  .don-cta:hover { filter: brightness(1.08); transform: translateY(-2px); }
  .don-cta:disabled { opacity: .6; cursor: not-allowed; transform: none; }
  .don-note { text-align: center; font-size: .87rem; color: var(--don-faible); margin-top: 12px; }

  /* ── BARRE PROGRESSION ── */
  .don-barre { background: var(--don-carte2); border: 1px solid var(--don-bord); border-radius: 99px; height: 14px; overflow: hidden; margin: 14px 0 8px; }
  .don-barre .rempli { height: 100%; width: 40%; background: linear-gradient(90deg, var(--don-vert), var(--don-or)); border-radius: 99px; }
  .don-chiffres { display: flex; justify-content: space-between; font-size: .93rem; color: var(--don-doux); }
  .don-chiffres b { color: var(--don-texte); }

  .don-autres { background: var(--don-carte); border: 1px dashed var(--don-bord); border-radius: 14px; padding: 24px; margin-top: 24px; }
  .don-autres p { font-size: 1rem; color: var(--don-doux); line-height: 1.7; }
  .don-autres strong { color: var(--don-or-clair); }

  /* ── FAQ ── */
  .don-faq { margin-top: 12px; }
  .don-qa { border-top: 1px solid var(--don-bord); padding: 18px 0; }
  .don-qa h4 { font-size: 1rem; color: var(--don-texte); margin-bottom: 6px; font-weight: 600; }
  .don-qa p { font-size: .95rem; color: var(--don-doux); line-height: 1.65; }

  /* ── FOOTER LOCAL ── */
  .don-footer-local { border-top: 1px solid var(--don-bord); text-align: center; padding: 48px 0 60px; }
  .don-footer-local .merci { font-family: 'Fraunces', serif; font-style: italic; font-size: 1.35rem; color: var(--don-or); margin-bottom: 8px; }
  .don-footer-local p { font-size: .9rem; color: var(--don-faible); }

  /* Alerte erreur */
  .don-alert-error { background: rgba(255,107,107,.1); border: 1px solid rgba(255,107,107,.3); color: #FF9999; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: .95rem; }
  .don-error-msg { font-size: .82rem; color: #FF6B6B; margin-top: 5px; }

  @media (max-width: 576px) {
    .don-form-card { padding: 24px 18px; }
    .don-vision { padding: 30px 22px; }
    .don-h1 { font-size: 2rem; }
  }
</style>
@endpush

@section('content')
<div class="don-page">

  {{-- HERO --}}
  <div class="don-hero">
    <div class="container" style="max-width:1080px;">
      <div class="don-hero-inner">
        <div class="don-pastille"><span class="dot"></span>Soutenir le projet</div>
        <div class="don-surtitre">Coach BRVM · développé sur fonds propres</div>
        <h1 class="don-h1">Si notre <span class="or">vision</span> vous parle,<br><em>donnez-lui</em> de l'élan.</h1>
        <p class="don-lede">Coach BRVM est né d'une conviction simple : <strong>la jeunesse d'Afrique de l'Ouest mérite de comprendre la finance et la bourse.</strong> Le projet se construit aujourd'hui sur fonds propres. Un don, ce n'est pas payer un service — c'est soutenir une mission à laquelle vous croyez.</p>
      </div>
    </div>
  </div>

  {{-- MISSION --}}
  <div class="don-section">
    <div class="container" style="max-width:1080px;">
      <div class="don-trait"></div>
      <h2 class="don-h2">Ce que votre soutien <em>fait grandir</em></h2>
      <p class="don-body">Coach BRVM regroupe annonces, radar marché, annuaires, portefeuille virtuel, mini-cours et bien plus. Construire et maintenir tout cela demande du temps, des outils et des données. Votre don alimente directement la mission — pas un compte, une mission.</p>
      <div class="don-grid">
        <div class="don-card">
          <div class="ico">📚</div>
          <h3>Éduquer la jeunesse</h3>
          <p>Produire des cours, des analyses et des contenus clairs pour que la finance cesse d'être réservée à quelques-uns.</p>
        </div>
        <div class="don-card">
          <div class="ico">🛠️</div>
          <h3>Faire vivre les outils</h3>
          <p>Serveurs, données de marché, radar et simulateur : les briques qui rendent la plateforme rapide et fiable.</p>
        </div>
        <div class="don-card">
          <div class="ico">🌍</div>
          <h3>Garder l'accès large</h3>
          <p>Maintenir une part essentielle du contenu ouverte à tous, pour ne laisser personne au bord de la route.</p>
        </div>
      </div>
    </div>
  </div>

  {{-- VISION --}}
  <div class="don-section" style="padding-top:0;">
    <div class="container" style="max-width:1080px;">
      <div class="don-vision">
        <span class="pin">Notre engagement</span>
        <h2 class="don-h2">Un don, un <em>soutien</em> — pas un achat.</h2>
        <p>Soyons transparents : Coach BRVM propose des contenus gratuits <em>et</em> des contenus payants. Donner ne débloque rien, ne remplace aucun abonnement et ne vous donne aucun avantage caché. Ce n'est pas une transaction.</p>
        <p>Le projet est porté sur fonds propres, par conviction. Si vous choisissez de contribuer, c'est parce que la vision vous parle : éduquer financièrement une génération entière. Vous ne payez pas un produit — vous portez une idée avec nous.</p>
        <div class="don-signature">
          <div class="av">CB</div>
          <div class="qui">
            <b>L'équipe Coach BRVM</b>
            <span>Au service des investisseurs d'Afrique de l'Ouest</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FORMULAIRE DE DON --}}
  <div class="don-section" style="padding-top:0;">
    <div class="container" style="max-width:700px;">
      <div class="don-trait"></div>
      <h2 class="don-h2">Choisir un <em>montant</em></h2>
      <p class="don-body">Quelques repères, simplement pour situer ce que représente un geste. Le montant libre est tout aussi bienvenu — chaque contribution compte.</p>

      {{-- Paliers --}}
      <div class="don-paliers" id="paliers">
        <div class="don-palier" onclick="choisir(this, 1000)">
          <div class="montant">1 000 F</div>
          <div class="label">Un garba ou un café ☕</div>
        </div>
        <div class="don-palier" onclick="choisir(this, 2000)">
          <div class="montant">2 000 F</div>
          <div class="label">Un premier geste</div>
        </div>
        <div class="don-palier actif" onclick="choisir(this, 10000)">
          <div class="tag">Populaire</div>
          <div class="montant">10 000 F</div>
          <div class="label">Une semaine de serveurs</div>
        </div>
        <div class="don-palier" onclick="choisir(this, 25000)">
          <div class="montant">25 000 F</div>
          <div class="label">Un nouveau mini-cours</div>
        </div>
        <div class="don-palier" onclick="choisir(this, 0)" id="palier-libre">
          <div class="montant">Libre</div>
          <div class="label">Votre montant</div>
        </div>
      </div>

      {{-- Formulaire --}}
      <div class="don-form-card">
        @if(session('error'))
          <div class="don-alert-error">⚠️ {{ session('error') }}</div>
        @endif

        <form action="{{ route('donation.initiate') }}" method="POST" id="donForm">
          @csrf

          <input type="hidden" name="amount" id="montantHidden" value="10000">

          {{-- Montant libre --}}
          <div class="mb-4" id="wrapMontantLibre" style="display:none;">
            <label class="don-label" for="montantLibre">Montant en FCFA</label>
            <input class="don-input @error('amount') error @enderror"
                   type="number" id="montantLibre" placeholder="Ex: 5000" min="500"
                   oninput="document.getElementById('montantHidden').value = this.value">
            @error('amount')<div class="don-error-msg">{{ $message }}</div>@enderror
          </div>

          <div class="don-divider"></div>

          @auth
            {{-- Utilisateur connecté : champs masqués, info affichée en lecture seule --}}
            <input type="hidden" name="donor_name"  value="{{ auth()->user()->name }}">
            <input type="hidden" name="donor_email" value="{{ auth()->user()->email }}">
            <div class="d-flex align-items-center gap-3 mb-3 p-3"
                 style="background:rgba(15,207,164,.07);border:1px solid rgba(15,207,164,.18);border-radius:10px;">
              <span style="font-size:1.3rem;">👤</span>
              <div>
                <div style="font-size:.85rem;font-weight:700;color:var(--don-vert);letter-spacing:.08em;text-transform:uppercase;margin-bottom:2px;">Connecté en tant que</div>
                <div style="font-size:.96rem;color:var(--don-texte);font-weight:600;">{{ auth()->user()->name }}</div>
                <div style="font-size:.85rem;color:var(--don-doux);">{{ auth()->user()->email }}</div>
              </div>
            </div>
          @else
            {{-- Visiteur : saisie libre --}}
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="don-label" for="donor_name">Votre prénom / nom</label>
                <input class="don-input @error('donor_name') error @enderror"
                       type="text" name="donor_name" id="donor_name"
                       placeholder="Ex: Kouassi Aimé"
                       value="{{ old('donor_name') }}" required>
                @error('donor_name')<div class="don-error-msg">{{ $message }}</div>@enderror
              </div>
              <div class="col-sm-6">
                <label class="don-label" for="donor_email">Votre adresse email</label>
                <input class="don-input @error('donor_email') error @enderror"
                       type="email" name="donor_email" id="donor_email"
                       placeholder="vous@exemple.com"
                       value="{{ old('donor_email') }}" required>
                @error('donor_email')<div class="don-error-msg">{{ $message }}</div>@enderror
              </div>
            </div>
          @endauth

          <button type="submit" class="don-cta" id="donBtn">
            Soutenir Coach BRVM &nbsp;→
          </button>
          <p class="don-note">Paiement sécurisé via Paystack · Mobile Money, carte ou virement · aucune donnée bancaire conservée</p>
        </form>
      </div>
    </div>
  </div>

  {{-- TRANSPARENCE --}}
  <div class="don-section">
    <div class="container" style="max-width:1080px;">
      <div class="don-trait"></div>
      <h2 class="don-h2">Où va <em>chaque franc</em></h2>
      <p class="don-body">La transparence fait partie de la vision. Cet objectif finance le déploiement complet du projet : infrastructure, données de marché, et une véritable bibliothèque de contenus pédagogiques pour toute la sous-région.</p>
      <div class="don-barre"><div class="rempli"></div></div>
      <div class="don-chiffres">
        <span><b>4 000 000 F</b> réunis</span>
        <span>Objectif : <b>10 000 000 F</b></span>
      </div>
      <div class="don-autres">
        <p><strong>Pas envie ou pas les moyens de donner ?</strong><br>
        C'est parfaitement normal. Partager Coach BRVM autour de vous, signaler une erreur, proposer un sujet ou animer le forum aide tout autant la mission. Le soutien ne se mesure pas qu'en francs.</p>
      </div>
    </div>
  </div>

  {{-- FAQ --}}
  <div class="don-section" style="padding-top:0;">
    <div class="container" style="max-width:1080px;">
      <div class="don-trait"></div>
      <h2 class="don-h2">Questions <em>légitimes</em></h2>
      <div class="don-faq">
        <div class="don-qa">
          <h4>Un don me donne-t-il accès aux contenus payants ?</h4>
          <p>Non. Le don et les offres payantes sont totalement séparés. Donner soutient la mission, rien de plus — et c'est volontairement ainsi.</p>
        </div>
        <div class="don-qa">
          <h4>Puis-je donner une seule fois ?</h4>
          <p>Bien sûr. Un don unique, ponctuel ou régulier : vous restez libre, sans engagement ni reconduction automatique.</p>
        </div>
        <div class="don-qa">
          <h4>À quoi sert concrètement l'argent ?</h4>
          <p>Aux serveurs, aux données de marché, aux outils et à la production de contenus éducatifs. La barre de progression ci-dessus est mise à jour régulièrement.</p>
        </div>
        <div class="don-qa">
          <h4>Mon paiement est-il sécurisé ?</h4>
          <p>Oui. Paystack est l'un des fournisseurs de paiement les plus reconnus en Afrique de l'Ouest. Nous ne stockons aucune donnée bancaire — tout est traité par Paystack directement.</p>
        </div>
      </div>
    </div>
  </div>

  {{-- FOOTER LOCAL --}}
  <div class="don-footer-local">
    <div class="container">
      <p class="merci">Merci de croire en l'éducation financière.</p>
      <p>Coach BRVM · comprendre, analyser, progresser. Ensemble.</p>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  const montantHidden = document.getElementById('montantHidden');
  const montantLibre  = document.getElementById('montantLibre');
  const wrapLibre     = document.getElementById('wrapMontantLibre');
  const palierLibre   = document.getElementById('palier-libre');

  function choisir(el, montant) {
    document.querySelectorAll('.don-palier').forEach(p => p.classList.remove('actif'));
    el.classList.add('actif');

    if (montant === 0) {
      wrapLibre.style.display = 'block';
      montantHidden.value = montantLibre.value || '';
    } else {
      wrapLibre.style.display = 'none';
      montantHidden.value = montant;
      montantLibre.value = '';
    }
  }

  document.getElementById('donForm').addEventListener('submit', function (e) {
    const btn = document.getElementById('donBtn');
    const montant = parseInt(montantHidden.value);

    if (!montant || montant < 500) {
      e.preventDefault();
      alert('Veuillez choisir ou saisir un montant d\'au moins 500 FCFA.');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Redirection vers Paystack…';
  });
</script>
@endpush
