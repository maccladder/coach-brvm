<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Le Bon Prix CI — Compare et paie moins cher</title>
<meta name="description" content="Compare le prix des téléphones sur les sites marchands reconnus en Côte d'Ivoire et trouve le moins cher en 2 clics.">

<!-- Tom Select (liste déroulante filtrante) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>

<!-- Polices -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;700&family=Archivo+Black&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">

<style>
:root{
  --encre:#0B3D2E;        /* vert billet FCFA */
  --fond:#F2F7F4;         /* menthe très pâle */
  --papier:#FFFFFF;
  --accent:#FF7A1A;       /* orange marché : l'économie */
  --etiquette:#FFD84D;    /* jaune étiquette de prix */
  --gris:#5C6B64;
  --whatsapp:#25D366;
  --radius:14px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Archivo',system-ui,sans-serif;
  background:var(--fond);
  color:var(--encre);
  min-height:100vh;
}
.wrap{max-width:520px;margin:0 auto;padding:20px 16px 60px}

/* ---------- En-tête ---------- */
header{padding:18px 0 6px}
.marque{
  font-family:'Archivo Black',sans-serif;
  font-size:1.5rem;line-height:1.1;letter-spacing:-.02em;
}
.marque em{font-style:normal;color:var(--accent)}
.pitch{color:var(--gris);font-size:.95rem;margin-top:6px}
.verif{
  display:inline-flex;align-items:center;gap:6px;
  margin-top:12px;font-family:'IBM Plex Mono',monospace;
  font-size:.75rem;background:var(--papier);
  border:1px solid #DCE8E1;border-radius:99px;padding:5px 12px;
}
.verif .dot{width:7px;height:7px;border-radius:50%;background:var(--whatsapp)}

/* ---------- Sélecteur ---------- */
.selecteur{margin-top:22px}
.selecteur label{font-weight:700;font-size:.9rem;display:block;margin-bottom:8px}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px}
.chip{
  font-family:'IBM Plex Mono',monospace;font-size:.78rem;
  padding:7px 13px;border-radius:99px;cursor:pointer;
  border:1.5px solid var(--encre);background:var(--papier);color:var(--encre);
}
.chip.actif{background:var(--encre);color:#fff}
.ts-wrapper .ts-control{
  border:2px solid var(--encre);border-radius:var(--radius);
  padding:13px 14px;font-size:1rem;background:var(--papier);
  box-shadow:0 2px 0 var(--encre);
}
.ts-dropdown{border-radius:var(--radius);border:2px solid var(--encre);font-size:.95rem}
.ts-dropdown .option{padding:10px 12px;display:flex;align-items:center;gap:10px}
.ts-dropdown .active{background:var(--fond);color:var(--encre)}
.mini{
  width:34px;height:34px;border-radius:8px;flex:none;
  background:var(--fond);display:flex;align-items:center;justify-content:center;
  font-family:'IBM Plex Mono',monospace;font-size:.65rem;font-weight:600;
  overflow:hidden;border:1px solid #DCE8E1;
}
.mini img{width:100%;height:100%;object-fit:cover}

/* ---------- Ticket de caisse (signature) ---------- */
#resultat{margin-top:26px;display:none}
.ticket{
  background:var(--papier);
  padding:26px 22px 30px;
  position:relative;
  filter:drop-shadow(0 6px 14px rgba(11,61,46,.14));
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
.ticket h2{
  font-size:1.05rem;font-weight:700;line-height:1.3;
  padding-bottom:14px;border-bottom:2px dashed #D7E4DC;
}
.gagnant{margin-top:16px}
.gagnant .site{
  font-family:'IBM Plex Mono',monospace;font-size:.75rem;
  text-transform:uppercase;letter-spacing:.08em;color:var(--gris);
}
.gagnant .prix{
  font-family:'Archivo Black',sans-serif;
  font-size:2.3rem;letter-spacing:-.02em;line-height:1.05;margin-top:2px;
}
.gagnant .prix small{font-size:1rem;font-family:'IBM Plex Mono',monospace;font-weight:400}

.badge-eco{
  display:inline-block;margin-top:10px;
  background:var(--etiquette);
  font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:.85rem;
  padding:6px 12px;border-radius:6px;
  transform:rotate(-1.5deg);
  border:1.5px solid var(--encre);
  box-shadow:2px 2px 0 var(--encre);
}
.badge-eco[hidden]{display:none}

.btn-offre{
  display:block;text-align:center;text-decoration:none;
  background:var(--accent);color:#fff;font-weight:700;font-size:1.05rem;
  padding:15px;border-radius:var(--radius);margin-top:18px;
  box-shadow:0 3px 0 #C55708;
  transition:transform .1s;
}
.btn-offre:active{transform:translateY(2px);box-shadow:none}

.autres{margin-top:20px;border-top:2px dashed #D7E4DC;padding-top:14px}
.autres .titre{
  font-family:'IBM Plex Mono',monospace;font-size:.72rem;
  text-transform:uppercase;letter-spacing:.08em;color:var(--gris);margin-bottom:8px;
}
.ligne{
  display:flex;justify-content:space-between;align-items:baseline;
  font-family:'IBM Plex Mono',monospace;font-size:.92rem;padding:6px 0;
}
.ligne .p{text-decoration:line-through;color:#9AAaa2;color:#96A69E}
.ligne .diff{font-size:.72rem;color:var(--accent);margin-left:8px}

.btn-wa{
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:22px;text-decoration:none;
  background:var(--whatsapp);color:#fff;font-weight:700;
  padding:13px;border-radius:var(--radius);font-size:.98rem;
  box-shadow:0 3px 0 #128C4B;
}
.btn-wa:active{transform:translateY(2px);box-shadow:none}
.btn-wa svg{width:20px;height:20px;fill:#fff}

.note{
  margin-top:14px;text-align:center;color:var(--gris);
  font-size:.78rem;font-family:'IBM Plex Mono',monospace;
}

footer{margin-top:40px;text-align:center;color:var(--gris);font-size:.78rem}

@media (prefers-reduced-motion:no-preference){
  #resultat.montre .ticket{animation:sortie .35s ease-out}
  @keyframes sortie{from{transform:translateY(14px);opacity:0}to{transform:none;opacity:1}}
}
</style>
</head>
<body>
<div class="wrap">

  <header>
    <div class="marque">Le Bon Prix <em>CI</em></div>
    <p class="pitch">Le même produit, le même plat — jamais au même prix. On a comparé pour toi.</p>
    <span class="verif"><span class="dot"></span> Prix vérifiés&nbsp;: <span id="date-verif">—</span></span>
  </header>

  <div class="selecteur">
    <label for="produit">Que veux-tu comparer&nbsp;?</label>
    <div class="chips" id="chips"></div>
    <select id="produit" placeholder="Tape 2-3 lettres… ex : A15, garba, riz"></select>
  </div>

  <div id="resultat">
    <div class="ticket">
      <h2 id="r-nom"></h2>

      <div class="gagnant">
        <div class="site">Meilleur prix — <span id="r-site"></span></div>
        <div class="prix"><span id="r-prix"></span> <small>FCFA</small></div>
        <div class="badge-eco" id="r-eco" hidden></div>
      </div>

      <a class="btn-offre" id="r-lien" href="#" target="_blank" rel="noopener sponsored">Voir l'offre →</a>

      <div class="autres" id="r-autres" hidden>
        <div class="titre">Ailleurs, le même modèle</div>
        <div id="r-lignes"></div>
      </div>
    </div>

    <a class="btn-wa" id="r-wa" href="#" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8.9-.3.2-.5 0a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.4-1.7c-.1-.2 0-.4.1-.5l.4-.4.2-.4a.5.5 0 0 0 0-.4c0-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4 15.6 15.6 0 0 0 1.5.6 3.6 3.6 0 0 0 1.7.1 2.8 2.8 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>
      Partager le bon plan
    </a>

    <p class="note">Lien marchand officiel. Le prix peut évoluer sur le site du vendeur.</p>
  </div>

  <footer>Comparateur indépendant — Abidjan, Côte d'Ivoire</footer>
</div>

<script>
/* =====================================================================
   DONNÉES
   En production : n8n génère produits.json chaque nuit sur ton serveur.
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

const fmt = n => n.toLocaleString("fr-FR").replace(/\u202f|\u00a0/g," ");

function initiales(nom){
  return nom.split(/[\s—-]+/).filter(Boolean).slice(0,2).map(m=>m[0]).join("").toUpperCase();
}

function afficherDate(iso){
  const d = new Date(iso);
  const opts = {day:"numeric",month:"long",hour:"2-digit",minute:"2-digit"};
  document.getElementById("date-verif").textContent =
    isNaN(d) ? "cette nuit" : d.toLocaleDateString("fr-FR",opts);
}

function demarrer(data){
  afficherDate(data.generated_at);
  const parId = Object.fromEntries(data.produits.map(p=>[p.id,p]));
  const categories = [...new Set(data.produits.map(p=>p.categorie||"Autres"))];

  const enOptions = liste => liste.map(p=>({
    value:p.id, text:p.nom, image:p.image, optgroup:p.categorie||"Autres"
  }));

  const select = new TomSelect("#produit",{
    options: enOptions(data.produits),
    optgroups: categories.map(c=>({value:c, label:c})),
    maxItems:1,
    render:{
      option:(d,esc)=>`<div class="option"><span class="mini">${
        d.image?`<img src="${esc(d.image)}" alt="" loading="lazy">`:esc(initiales(d.text))
      }</span>${esc(d.text)}</div>`,
      optgroup_header:(d,esc)=>`<div class="optgroup-header" style="font-family:'IBM Plex Mono',monospace;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gris);padding:8px 12px 4px">${esc(d.label)}</div>`
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

  function montrer(p){
    const offres = [...p.offres].sort((a,b)=>a.prix-b.prix);
    const top = offres[0], autres = offres.slice(1);

    document.getElementById("r-nom").textContent = p.nom;
    document.getElementById("r-site").textContent = top.site;
    document.getElementById("r-prix").textContent = fmt(top.prix);
    document.getElementById("r-lien").href = top.lien;

    const eco = document.getElementById("r-eco");
    if(autres.length){
      const gain = autres[autres.length-1].prix - top.prix;
      eco.hidden = gain <= 0;
      eco.textContent = `Tu économises ${fmt(gain)} F`;
    } else eco.hidden = true;

    const bloc = document.getElementById("r-autres");
    const lignes = document.getElementById("r-lignes");
    lignes.innerHTML = "";
    bloc.hidden = autres.length === 0;
    for(const o of autres){
      const div = document.createElement("div");
      div.className = "ligne";
      div.innerHTML = `<span>${o.site}</span>
        <span><span class="p">${fmt(o.prix)} F</span><span class="diff">+${fmt(o.prix-top.prix)}</span></span>`;
      lignes.appendChild(div);
    }

    const msg = `📱 ${p.nom}\n💰 Meilleur prix trouvé : ${fmt(top.prix)} FCFA sur ${top.site}\n👉 ${location.href.split("#")[0]}#${p.id}`;
    document.getElementById("r-wa").href = "https://wa.me/?text="+encodeURIComponent(msg);

    const res = document.getElementById("resultat");
    res.style.display = "block";
    res.classList.remove("montre"); void res.offsetWidth; res.classList.add("montre");
    history.replaceState(null,"","#"+p.id);
  }

  // Lien profond : comparateur.html#samsung-a15-128
  const hash = location.hash.slice(1);
  if(hash && parId[hash]) select.setValue(hash);
}

/* Charge produits.json (généré par n8n) ; sinon, données d'exemple */
fetch("{{ route('comparateur.data') }}",{cache:"no-store"})
  .then(r => r.ok ? r.json() : Promise.reject())
  .then(demarrer)
  .catch(()=>demarrer(DONNEES_EXEMPLE));
</script>
</body>
</html>
