{{-- resources/views/comparateur.blade.php --}}
@extends('layouts.app')

@section('title', 'Comparateur de prix — Boursiv')

@push('meta')
<meta name="description" content="Compare le prix des téléphones et des repas sur les sites marchands reconnus en Côte d'Ivoire et trouve le moins cher en 2 clics.">
<meta property="og:title" content="Comparateur de prix — Boursiv">
<meta property="og:description" content="Le même produit, jamais au même prix. Compare et économise sur tes téléphones et tes repas.">
@endpush

@push('styles')
<!-- Tom Select (liste déroulante filtrante) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.min.css">

<style>
:root{
  /* Alias sur la charte Boursiv — le badge d'économie et le CTA gardent
     des couleurs vives dédiées pour rester très visibles dans le ticket. */
  --encre:      var(--cb-ink);
  --fond:       var(--cb-paper);
  --papier:     var(--cb-card);
  --accent:     #FF7A1A;   /* orange marché : signal fort de l'économie réalisée */
  --etiquette:  #FFD84D;   /* jaune étiquette de prix */
  --gris:       var(--cb-muted);
  --whatsapp:   #25D366;
  --radius:14px;
}
.comparateur-page, .comparateur-page *{box-sizing:border-box}
.comparateur-page{
  font-family:'DM Sans',system-ui,sans-serif;
  color:var(--encre);
}
.comparateur-page .wrap{max-width:520px;margin:0 auto;padding:32px 16px 60px}

/* ---------- En-tête ---------- */
.comparateur-page .cmp-header{padding:0 0 6px}
.comparateur-page .marque{
  font-family:'Playfair Display',serif;
  font-size:1.7rem;font-weight:900;line-height:1.1;letter-spacing:-.01em;
}
.comparateur-page .marque em{font-style:normal;color:var(--accent)}
.comparateur-page .pitch{color:var(--gris);font-size:.95rem;margin-top:8px;font-weight:300}
.comparateur-page .verif{
  display:inline-flex;align-items:center;gap:6px;
  margin-top:14px;font-family:'Syne',sans-serif;
  font-size:.875rem;font-weight:600;letter-spacing:.04em;background:var(--papier);
  border:1px solid var(--cb-border);border-radius:99px;padding:6px 13px;
}
.comparateur-page .verif .dot{width:7px;height:7px;border-radius:50%;background:var(--whatsapp);flex:none}

/* ---------- Sélecteur ---------- */
.comparateur-page .selecteur{margin-top:26px}
.comparateur-page .selecteur label{
  font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;
  letter-spacing:.02em;display:block;margin-bottom:10px;
}
.comparateur-page .chips{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px}
.comparateur-page .chip{
  font-family:'Syne',sans-serif;font-size:.875rem;font-weight:600;
  padding:7px 14px;border-radius:99px;cursor:pointer;
  border:1.5px solid var(--encre);background:var(--papier);color:var(--encre);
  transition:all .2s;
}
.comparateur-page .chip.actif{background:var(--encre);color:var(--fond);border-color:var(--encre)}
.comparateur-page .ts-wrapper{width:100%;max-width:100%}
.comparateur-page .ts-wrapper .ts-control{
  border:2px solid var(--encre);border-radius:var(--radius);
  padding:13px 14px;font-size:1rem;background:var(--papier);
  box-shadow:0 2px 0 var(--encre);width:100%;max-width:100%;
}
.comparateur-page .ts-dropdown{border-radius:var(--radius);border:2px solid var(--encre);font-size:.95rem;max-width:100%}
.comparateur-page .ts-dropdown .option{padding:12px;display:flex;align-items:center;gap:10px;min-height:44px}
.comparateur-page .ts-dropdown .active{background:var(--fond);color:var(--encre)}
.comparateur-page .ts-dropdown mark{background:var(--etiquette);color:inherit;padding:0;margin:0;border-radius:2px}
.comparateur-page .mini{
  width:34px;height:34px;border-radius:8px;flex:none;
  background:var(--fond);display:flex;align-items:center;justify-content:center;
  font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;
  overflow:hidden;border:1px solid var(--cb-border);
}
.comparateur-page .mini img{width:100%;height:100%;object-fit:cover}

/* ---------- Exemples & guide ---------- */
.comparateur-page .exemples{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:14px}
.comparateur-page .exemples-label{
  font-family:'Syne',sans-serif;font-size:.875rem;font-weight:700;color:var(--gris);
}
.comparateur-page .exemple-btn{
  font-family:'DM Sans',system-ui,sans-serif;font-size:.875rem;font-weight:500;
  padding:6px 12px;border-radius:99px;cursor:pointer;
  border:1.5px dashed var(--cb-border);background:transparent;color:var(--encre);
  transition:all .2s;
}
.comparateur-page .exemple-btn:hover,
.comparateur-page .exemple-btn:active{border-color:var(--accent);color:var(--accent)}
.comparateur-page .guide-note{
  margin-top:10px;color:var(--gris);font-size:.875rem;line-height:1.4;
}

/* ---------- Ticket de caisse (signature) ---------- */
.comparateur-page #resultat{margin-top:26px;display:none}
.comparateur-page .ticket{
  background:var(--papier);
  padding:26px 22px 30px;
  position:relative;
  filter:drop-shadow(0 6px 14px rgba(15,92,67,.14));
  /* bords dentelés haut et bas */
  --dent:12px;
  clip-path:polygon(
    0 var(--dent),4% 0,8% var(--dent),12% 0,16% var(--dent),20% 0,24% var(--dent),28% 0,
    32% var(--dent),36% 0,40% var(--dent),44% 0,48% var(--dent),52% 0,56% var(--dent),60% 0,
    64% var(--dent),68% 0,72% var(--dent),76% 0,80% var(--dent),84% 0,88% var(--dent),92% 0,
    96% var(--dent),100% 0,
    100% calc(100% - var(--dent)),96% 100%,92% calc(100% - var(--dent)),88% 100%,
    84% calc(100% - var(--dent)),80% 100%,76% calc(100% - var(--dent)),72% 100%,
    68% calc(100% - var(--dent)),64% 100%,60% calc(100% - var(--dent)),56% 100%,
    52% calc(100% - var(--dent)),48% 100%,44% calc(100% - var(--dent)),40% 100%,
    36% calc(100% - var(--dent)),32% 100%,28% calc(100% - var(--dent)),24% 100%,
    20% calc(100% - var(--dent)),16% 100%,12% calc(100% - var(--dent)),8% 100%,
    4% calc(100% - var(--dent)),0 100%
  );
}
.comparateur-page .ticket h2{
  font-family:'Syne',sans-serif;
  font-size:1.05rem;font-weight:700;line-height:1.3;
  padding-bottom:14px;border-bottom:2px dashed var(--cb-border);
}
.comparateur-page .gagnant{margin-top:16px}
.comparateur-page .badge-top{
  display:inline-flex;align-items:center;gap:5px;margin-bottom:8px;
  background:var(--encre);color:var(--fond);
  font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;
  letter-spacing:.02em;padding:5px 12px;border-radius:99px;
}
.comparateur-page .badge-top[hidden]{display:none}
.comparateur-page .gagnant .site{
  font-family:'Syne',sans-serif;font-size:.875rem;font-weight:600;
  text-transform:uppercase;letter-spacing:.08em;color:var(--gris);
}
.comparateur-page .gagnant .prix{
  font-family:'Playfair Display',serif;font-weight:900;
  font-size:2.3rem;letter-spacing:-.02em;line-height:1.05;margin-top:4px;
  word-break:break-word;
}
.comparateur-page .gagnant .prix small{font-size:1rem;font-family:'Syne',sans-serif;font-weight:600}

.comparateur-page .badge-eco{
  display:inline-block;margin-top:10px;
  background:var(--etiquette);
  font-family:'Syne',sans-serif;font-weight:700;font-size:.875rem;
  padding:6px 12px;border-radius:6px;
  transform:rotate(-1.5deg);
  border:1.5px solid var(--encre);
  box-shadow:2px 2px 0 var(--encre);
}
.comparateur-page .badge-eco[hidden]{display:none}

.comparateur-page .btn-offre{
  display:block;text-align:center;text-decoration:none;
  background:var(--accent);color:#fff;
  font-family:'Syne',sans-serif;font-weight:800;font-size:1.02rem;letter-spacing:.01em;
  padding:15px;border-radius:var(--radius);margin-top:18px;
  box-shadow:0 3px 0 #C55708;
  transition:transform .1s;
}
.comparateur-page .btn-offre:active{transform:translateY(2px);box-shadow:none}

.comparateur-page .autres{margin-top:20px;border-top:2px dashed var(--cb-border);padding-top:14px}
.comparateur-page .autres .titre{
  font-family:'Syne',sans-serif;font-size:.875rem;font-weight:600;
  text-transform:uppercase;letter-spacing:.08em;color:var(--gris);margin-bottom:8px;
}
.comparateur-page .ligne{
  display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;
  gap:2px 10px;
  font-family:'Syne',sans-serif;font-size:.9rem;padding:6px 0;
}
.comparateur-page .ligne .site-o{word-break:break-word}
.comparateur-page .ligne .valeurs{display:flex;flex-wrap:wrap;gap:2px 8px;justify-content:flex-end}
.comparateur-page .ligne .p{text-decoration:line-through;color:var(--gris);white-space:nowrap}
.comparateur-page .ligne .diff{font-size:.875rem;color:#D9291C;font-weight:700;white-space:nowrap}

/* ---------- Historique de prix ---------- */
.comparateur-page .historique{margin-top:20px;border-top:2px dashed var(--cb-border);padding-top:14px}
.comparateur-page .historique[hidden]{display:none}
.comparateur-page .histo-comp{
  font-family:'Syne',sans-serif;font-size:.9rem;line-height:1.5;margin-bottom:6px;
}
.comparateur-page .histo-var{font-weight:700;white-space:nowrap}
.comparateur-page .histo-var.baisse{color:#1F9254}
.comparateur-page .histo-var.hausse{color:#D9291C}
.comparateur-page .histo-var.stable{color:var(--gris)}
.comparateur-page .histo-min{font-family:'Syne',sans-serif;font-size:.875rem;color:var(--gris)}

.comparateur-page .btn-wa{
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:22px;text-decoration:none;
  background:var(--whatsapp);color:#fff;font-family:'Syne',sans-serif;font-weight:700;
  padding:13px;border-radius:var(--radius);font-size:.98rem;
  box-shadow:0 3px 0 #128C4B;
}
.comparateur-page .btn-wa:active{transform:translateY(2px);box-shadow:none}
.comparateur-page .btn-wa svg{width:20px;height:20px;fill:#fff}

.comparateur-page .note{
  margin-top:14px;text-align:center;color:var(--gris);
  font-size:.875rem;font-family:'Syne',sans-serif;
}

.comparateur-page .cmp-footer{margin-top:40px;text-align:center;color:var(--gris);font-size:.875rem}

@media (prefers-reduced-motion:no-preference){
  .comparateur-page #resultat.montre .ticket{animation:sortie .35s ease-out}
  @keyframes sortie{from{transform:translateY(14px);opacity:0}to{transform:none;opacity:1}}
}

/* ---------- Mobile first : garde-fous 360-400px ---------- */
html, body{overflow-x:hidden}
.comparateur-page{overflow-x:hidden}
.comparateur-page .wrap{width:100%}
.comparateur-page img{max-width:100%}
@media (max-width:400px){
  .comparateur-page .wrap{padding:24px 14px 48px}
  .comparateur-page .gagnant .prix{font-size:1.9rem}
  .comparateur-page .ticket{padding:22px 16px 26px}
}
</style>
@endpush

@section('content')
<div class="comparateur-page">
<div class="wrap">

  <div class="cmp-header">
    <div class="marque">Comparateur <em>Boursiv</em></div>
    <p class="pitch">Le même produit, le même plat — jamais au même prix. On a comparé pour toi.</p>
    <span class="verif"><span class="dot"></span> Prix vérifiés&nbsp;: <span id="date-verif">—</span></span>
  </div>

  <div class="selecteur">
    <label for="produit">Que veux-tu comparer&nbsp;?</label>
    <div class="chips" id="chips"></div>
    <select id="produit" placeholder="Tape 2-3 lettres… ex : A15, garba, riz"></select>

    <div class="exemples" id="exemples">
      <span class="exemples-label">Essaie&nbsp;:</span>
      <button type="button" class="exemple-btn" data-q="iPhone 13">iPhone 13</button>
      <button type="button" class="exemple-btn" data-q="Spaghetti">Spaghetti</button>
      <button type="button" class="exemple-btn" data-q="Infinix Hot">Infinix Hot</button>
      <button type="button" class="exemple-btn" data-q="KFC">KFC</button>
    </div>
    <p class="guide-note">Choisis une catégorie, tape un produit, on t'affiche le meilleur prix vérifié cette nuit.</p>
  </div>

  <div id="resultat">
    <div class="ticket">
      <h2 id="r-nom"></h2>

      <div class="gagnant">
        <div class="badge-top" id="r-badge-top" hidden>🏆 Meilleur prix trouvé</div>
        <div class="site">Meilleur prix — <span id="r-site"></span></div>
        <div class="prix"><span id="r-prix"></span> <small>FCFA</small></div>
        <div class="badge-eco" id="r-eco" hidden></div>
      </div>

      <a class="btn-offre" id="r-lien" href="#" target="_blank" rel="noopener sponsored">Voir l'offre →</a>

      <div class="autres" id="r-autres" hidden>
        <div class="titre">Ailleurs, le même modèle</div>
        <div id="r-lignes"></div>
      </div>

      <div class="historique" id="r-historique" hidden>
        <div class="histo-comp" id="r-histo-comp"></div>
        <div class="histo-min" id="r-histo-min"></div>
      </div>
    </div>

    <a class="btn-wa" id="r-wa" href="#" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8.9-.3.2-.5 0a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.4-1.7c-.1-.2 0-.4.1-.5l.4-.4.2-.4a.5.5 0 0 0 0-.4c0-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4 15.6 15.6 0 0 0 1.5.6 3.6 3.6 0 0 0 1.7.1 2.8 2.8 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>
      Partager le bon plan
    </a>

    <p class="note">Lien marchand officiel. Le prix peut évoluer sur le site du vendeur.</p>
  </div>

  <div class="cmp-footer">Comparateur Boursiv — Abidjan, Côte d'Ivoire</div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>
<script>
/* =====================================================================
   DONNÉES
   En production : n8n génère produits.json chaque nuit sur le serveur.
   Les données ci-dessous servent d'exemple + de secours hors ligne.
   Format d'une offre : {site, prix, lien}
   Ajoute ?aff_id=TON_ID sur les liens Jumia (programme d'affiliation).
===================================================================== */
const DONNEES_EXEMPLE = {
  generated_at: "2026-07-17T02:00:00Z",
  produits: [
    /* ---- Téléphones ---- */
    {id:"samsung-a15-128", nom:"Samsung Galaxy A15 — 128 Go", categorie:"Téléphones", image:"",
     offres:[{site:"Jumia CI",prix:84900,lien:"https://www.jumia.ci/"},
             {site:"Yaatoo",prix:96500,lien:"https://www.yaatoo.ci/"},
             {site:"Boutique Plateau",prix:92000,lien:"#"}]},
    {id:"iphone-13-128", nom:"iPhone 13 — 128 Go", categorie:"Téléphones", image:"",
     offres:[{site:"Jumia CI",prix:329000,lien:"https://www.jumia.ci/"},
             {site:"Boutique Marcory",prix:355000,lien:"#"}]},
    {id:"redmi-13c-128", nom:"Xiaomi Redmi 13C — 128 Go", categorie:"Téléphones", image:"",
     offres:[{site:"Yaatoo",prix:62900,lien:"https://www.yaatoo.ci/"},
             {site:"Jumia CI",prix:64500,lien:"https://www.jumia.ci/"},
             {site:"Boutique Adjamé",prix:70000,lien:"#"}]},
    {id:"tecno-spark-20", nom:"Tecno Spark 20 — 256 Go", categorie:"Téléphones", image:"",
     offres:[{site:"Jumia CI",prix:78500,lien:"https://www.jumia.ci/"},
             {site:"Yaatoo",prix:81900,lien:"https://www.yaatoo.ci/"}]},
    {id:"infinix-hot-40-128", nom:"Infinix Hot 40 — 128 Go", categorie:"Téléphones", image:"",
     offres:[{site:"Jumia CI",prix:74900,lien:"https://www.jumia.ci/"}],
     historique:[{site:"Jumia CI",prix:79900,date:"2026-07-08"},
                 {site:"Jumia CI",prix:79900,date:"2026-07-15"},
                 {site:"Jumia CI",prix:76500,date:"2026-07-22"},
                 {site:"Jumia CI",prix:74900,date:"2026-07-29"},
                 {site:"Jumia CI",prix:74900,date:"2026-08-04"}]},
    /* ---- Restos : même plat, même resto, plateformes différentes ---- */
    {id:"garba-choco-yop", nom:"Garba complet — Chez Choco (Yopougon)", categorie:"Restos", image:"",
     offres:[{site:"En direct (retrait)",prix:2000,lien:"#"},
             {site:"Glovo",prix:2800,lien:"https://glovoapp.com/ci/"},
             {site:"Yango Deli",prix:2600,lien:"#"}]},
    {id:"poulet-braise-marcory", nom:"Poulet braisé + attiéké — Espace Braise (Marcory)", categorie:"Restos", image:"",
     offres:[{site:"En direct (retrait)",prix:5000,lien:"#"},
             {site:"Glovo",prix:6200,lien:"https://glovoapp.com/ci/"}]},
    {id:"burger-norima-cocody", nom:"Burger classique — Norima (Cocody)", categorie:"Restos", image:"",
     offres:[{site:"Glovo",prix:4900,lien:"https://glovoapp.com/ci/"},
             {site:"Yango Deli",prix:4500,lien:"#"},
             {site:"En direct (retrait)",prix:4000,lien:"#"}]},
    /* ---- Courses ---- */
    {id:"riz-parfume-25kg", nom:"Riz parfumé 25 kg — Uncle Sam", categorie:"Courses", image:"",
     offres:[{site:"Jumia CI",prix:16500,lien:"https://www.jumia.ci/"},
             {site:"Carrefour (via Glovo)",prix:17900,lien:"https://glovoapp.com/ci/"},
             {site:"Boutique de quartier",prix:17000,lien:"#"}]},
    {id:"huile-dinor-5l", nom:"Huile Dinor — bidon 5 L", categorie:"Courses", image:"",
     offres:[{site:"Carrefour (via Glovo)",prix:9800,lien:"https://glovoapp.com/ci/"},
             {site:"Jumia CI",prix:10500,lien:"https://www.jumia.ci/"}]}
  ]
};

const fmt = n => n.toLocaleString("fr-FR").replace(/ | /g," ");

function initiales(nom){
  return nom.split(/[\s—-]+/).filter(Boolean).slice(0,2).map(m=>m[0]).join("").toUpperCase();
}

function afficherDate(iso){
  const d = new Date(iso);
  const opts = {day:"numeric",month:"long",hour:"2-digit",minute:"2-digit"};
  document.getElementById("date-verif").textContent =
    isNaN(d) ? "cette nuit" : d.toLocaleDateString("fr-FR",opts);
}

/* Surligne la sous-chaîne recherchée dans un texte déjà échappé (HTML-safe),
   sans toucher au reste du texte : pas d'espace ni de padding injecté. */
function surligner(texteEchappe, requete){
  const q = (requete||"").trim();
  if(!q) return texteEchappe;
  const echappeRegex = q.replace(/[.*+?^${}()|[\]\\]/g,"\\$&");
  const re = new RegExp(echappeRegex,"ig");
  return texteEchappe.replace(re, m => `<mark>${m}</mark>`);
}

function joursDepuis(dateStr){
  const jours = Math.round((Date.now() - new Date(dateStr).getTime()) / 86400000);
  return Math.max(1,jours);
}

function demarrer(data){
  afficherDate(data.generated_at);
  const parId = Object.fromEntries(data.produits.map(p=>[p.id,p]));
  const categories = [...new Set(data.produits.map(p=>p.categorie||"Autres"))];

  const enOptions = liste => liste.map(p=>({
    value:p.id, text:p.nom, image:p.image, optgroup:p.categorie||"Autres"
  }));

  let requeteActuelle = "";

  const select = new TomSelect("#produit",{
    options: enOptions(data.produits),
    optgroups: categories.map(c=>({value:c, label:c})),
    maxItems:1,
    highlight:false,
    onType:(str)=>{ requeteActuelle = str; },
    render:{
      option:(d,esc)=>`<div class="option"><span class="mini">${
        d.image?`<img src="${esc(d.image)}" alt="" loading="lazy">`:esc(initiales(d.text))
      }</span>${surligner(esc(d.text),requeteActuelle)}</div>`,
      optgroup_header:(d,esc)=>`<div class="optgroup-header" style="font-family:'Syne',sans-serif;font-size:.875rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gris);padding:8px 12px 4px">${esc(d.label)}</div>`
    },
    onChange:(id)=>{ if(id) montrer(parId[id]); }
  });

  /* Puces de filtrage par catégorie */
  const chips = document.getElementById("chips");
  const filtres = ["Tout", ...categories];
  filtres.forEach((cat,i)=>{
    const b = document.createElement("button");
    b.type = "button"; b.className = "chip"+(i===0?" actif":""); b.textContent = cat;
    b.onclick = ()=>{
      chips.querySelectorAll(".chip").forEach(c=>c.classList.remove("actif"));
      b.classList.add("actif");
      const sous = cat==="Tout" ? data.produits : data.produits.filter(p=>(p.categorie||"Autres")===cat);
      select.clear(true); select.clearOptions();
      select.addOptions(enOptions(sous));
      document.getElementById("resultat").style.display="none";
      select.open();
    };
    chips.appendChild(b);
  });

  /* Exemples cliquables sous la barre de recherche */
  document.querySelectorAll("#exemples .exemple-btn").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      chips.querySelectorAll(".chip").forEach(c=>c.classList.remove("actif"));
      chips.querySelector(".chip")?.classList.add("actif");
      select.clear(true); select.clearOptions();
      select.addOptions(enOptions(data.produits));
      document.getElementById("resultat").style.display="none";
      const q = btn.dataset.q;
      requeteActuelle = q;
      select.setTextboxValue(q);
      select.refreshOptions();
      select.open();
      select.focus();
    });
  });

  function montrer(p){
    const offres = [...p.offres].sort((a,b)=>a.prix-b.prix);
    const top = offres[0], autres = offres.slice(1);

    document.getElementById("r-nom").textContent = p.nom;
    document.getElementById("r-site").textContent = top.site;
    document.getElementById("r-prix").textContent = fmt(top.prix);
    document.getElementById("r-lien").href = top.lien;

    document.getElementById("r-badge-top").hidden = autres.length === 0;

    const eco = document.getElementById("r-eco");
    if(autres.length){
      const gain = autres[autres.length-1].prix - top.prix;
      eco.hidden = gain <= 0;
      eco.textContent = `Tu économises ${fmt(gain)} FCFA aujourd'hui`;
    } else eco.hidden = true;

    const bloc = document.getElementById("r-autres");
    const lignes = document.getElementById("r-lignes");
    lignes.innerHTML = "";
    bloc.hidden = autres.length === 0;
    for(const o of autres){
      const div = document.createElement("div");
      div.className = "ligne";
      div.innerHTML = `<span class="site-o">${o.site}</span>
        <span class="valeurs"><span class="p">${fmt(o.prix)} FCFA</span><span class="diff">+${fmt(o.prix-top.prix)} FCFA de plus</span></span>`;
      lignes.appendChild(div);
    }

    /* Historique de prix (produits à offre unique) */
    const histoBloc = document.getElementById("r-historique");
    const historique = (p.offres.length === 1 && Array.isArray(p.historique))
      ? [...p.historique].filter(h=>h && typeof h.prix==="number" && h.date).sort((a,b)=>new Date(a.date)-new Date(b.date))
      : [];
    if(historique.length >= 2){
      const cutoff = new Date(); cutoff.setDate(cutoff.getDate()-7);
      let reference = null;
      for(const h of historique){ if(new Date(h.date) <= cutoff) reference = h; }
      if(!reference) reference = historique[0];

      const variation = reference.prix > 0 ? ((top.prix - reference.prix) / reference.prix) * 100 : 0;
      const classeVar = variation < 0 ? "baisse" : (variation > 0 ? "hausse" : "stable");
      const fleche = variation < 0 ? "▼" : (variation > 0 ? "▲" : "→");

      document.getElementById("r-histo-comp").innerHTML =
        `Il y a ${joursDepuis(reference.date)} j : ${fmt(reference.prix)} FCFA → aujourd'hui ${fmt(top.prix)} FCFA `+
        `<span class="histo-var ${classeVar}">${fleche} ${Math.abs(variation).toFixed(1)}%</span>`;

      const min30 = Math.min(...historique.map(h=>h.prix));
      document.getElementById("r-histo-min").textContent = `Meilleur prix observé sur 30 jours : ${fmt(min30)} FCFA`;

      histoBloc.hidden = false;
    } else {
      histoBloc.hidden = true;
    }

    const msg = `📱 ${p.nom}\n💰 Meilleur prix trouvé : ${fmt(top.prix)} FCFA sur ${top.site}\n👉 ${location.href.split("#")[0]}#${p.id}`;
    document.getElementById("r-wa").href = "https://wa.me/?text="+encodeURIComponent(msg);

    const res = document.getElementById("resultat");
    res.style.display = "block";
    res.classList.remove("montre"); void res.offsetWidth; res.classList.add("montre");
    history.replaceState(null,"","#"+p.id);
  }

  // Lien profond : comparateur#samsung-a15-128
  const hash = location.hash.slice(1);
  if(hash && parId[hash]) select.setValue(hash);
}

/* Charge produits.json (généré par n8n) ; sinon, données d'exemple */
fetch("{{ route('comparateur.data') }}",{cache:"no-store"})
  .then(r => r.ok ? r.json() : Promise.reject())
  .then(demarrer)
  .catch(()=>demarrer(DONNEES_EXEMPLE));
</script>
@endpush
