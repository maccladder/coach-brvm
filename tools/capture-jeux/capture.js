'use strict';

const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '.env') });

const fs = require('fs');
const { execFile } = require('child_process');
const { chromium } = require('playwright');

// ═══════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════

// Cible la PRODUCTION : c'est le compte de prod qui a tous les jeux débloqués.
const BASE_URL = 'https://boursiv.com';

// Pour ne capturer qu'un seul jeu (test avant de lancer les six) :
// JEUX_A_CAPTURER = ['garba-master']
// Tableau vide = tous les jeux de la liste JEUX ci-dessous.
const JEUX_A_CAPTURER = ['abidjan-run-le-jeu-run-a-livoirienne'];

// IMPORTANT : `url` pointe vers la fiche marketplace (/marketplace/{slug}), pas directement
// vers la page de jeu. Raison : /mon-espace/mes-produits/{product}/play utilise le binding
// Eloquent implicite sur `id` (pas de slug custom sur MarketplaceProduct), donc l'URL réelle
// est .../play/42 et pas .../play/gri-gri-la-danse-des-perles — un ID qui peut changer.
// On part donc toujours de la fiche marketplace (routée par slug, stable) et on clique sur
// "Jouer maintenant" pour être redirigé vers la vraie page de jeu, quel que soit son ID.
//
// typeInputs possibles : 'clicker' | 'fleches' | 'viseur' | 'menu'
const JEUX = [
  {
    slug: 'gri-gri-la-danse-des-perles',
    url: `${BASE_URL}/marketplace/gri-gri-la-danse-des-perles`,
    dureeSecondes: 45,
    typeInputs: 'viseur',
  },
  {
    slug: 'garba-master',
    // La fiche marketplace redirige vers /jeu (jeu Phaser autonome, pas de page /play dédiée).
    url: `${BASE_URL}/marketplace/garba-master`,
    dureeSecondes: 45,
    typeInputs: 'clicker',
  },
  {
    slug: 'abidjan-run-le-jeu-run-a-livoirienne',
    url: `${BASE_URL}/marketplace/abidjan-run-le-jeu-run-a-livoirienne`,
    dureeSecondes: 45,
    typeInputs: 'fleches',
  },
  {
    slug: 'awale-master',
    url: `${BASE_URL}/marketplace/awale-master`,
    dureeSecondes: 45,
    typeInputs: 'viseur',
  },
  {
    slug: 'roi-du-cacao',
    url: `${BASE_URL}/marketplace/roi-du-cacao`,
    dureeSecondes: 45,
    typeInputs: 'clicker',
  },
  {
    slug: 'questions-pour-un-vrai-mogo',
    // Confirmé publié en prod (absent de la base locale de dev, donc pas dans marketplace_products
    // en local) : écran d'accueil à bouton unique "C'EST PARTI 🚀".
    url: `${BASE_URL}/marketplace/questions-pour-un-vrai-mogo`,
    dureeSecondes: 45,
    typeInputs: 'menu',
  },
];

const RUSHES_DIR = path.join(__dirname, 'rushes');
const VIEWPORT = { width: 1280, height: 720 };
const ATTENTE_ECRAN_ACCUEIL_MS = 3000;
const TIMEOUT_CHARGEMENT_MS = 20000;
// Nombre d'écrans à traverser en cliquant : ex. Abidjan Run = "Jouer maintenant" (fiche
// marketplace) → "JOUER !" (menu du jeu) → "JOUER !" (écran de sélection de personnage) = 3,
// +1 de marge de sécurité.
const MAX_CLICS_ENCHAINES = 4;

const BOUTONS_DEMARRAGE = [
  'Jouer maintenant', 'Accéder gratuitement', "C'est parti", 'Nouvelle partie', 'Démarrer', 'Commencer', 'Jouer', 'Rejouer', 'Start', 'Play',
];

// ═══════════════════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════════════════

async function login(browser) {
  const context = await browser.newContext({ viewport: VIEWPORT });
  const page = await context.newPage();

  console.log('[capture] Connexion à boursiv.com ...');
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });

  // Sélecteurs alignés sur resources/views/auth/login.blade.php :
  // <input type="email" name="email" id="email">, <input type="password" name="password" id="password">
  await page.fill('input[name="email"]', process.env.BOURSIV_EMAIL);
  await page.fill('input[name="password"]', process.env.BOURSIV_PASSWORD);

  await Promise.all([
    page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 15000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForLoadState('networkidle').catch(() => {});

  // Pause + capture d'écran de diagnostic : si le login échoue, on voit exactement
  // ce que la page affiche (erreur de validation, throttle, etc.).
  await page.waitForTimeout(1500);
  const debugScreenshotPath = path.join(__dirname, 'login-debug.png');
  await page.screenshot({ path: debugScreenshotPath, fullPage: true }).catch(() => {});

  if (new URL(page.url()).pathname.startsWith('/login')) {
    console.error(`[capture] Capture de diagnostic enregistrée → ${debugScreenshotPath}`);
    throw new Error('toujours sur /login après soumission — identifiants invalides ou formulaire de connexion modifié (voir login-debug.png)');
  }

  console.log('[capture] Connecté avec succès.');
  const storageState = await context.storageState();
  await context.close();
  return storageState;
}

// ═══════════════════════════════════════════════════════
// DÉTECTION ÉCRAN DE DÉVERROUILLAGE / PAIEMENT
// ═══════════════════════════════════════════════════════

// La page wrapper (my-products-play.blade.php / gri-gri-play.blade.php) affiche un toast
// "🎮 Partie terminée" en position:fixed;z-index:200 dès qu'un score est posté par l'iframe
// (y compris score=0 au premier chargement, ou à chaque mort du joueur). Il couvre
// physiquement le bas de l'écran de jeu — dont le bouton "JOUER !" de l'écran de sélection
// de personnage d'Abidjan Run — bloquant tout clic à cet endroit (l'élément intercepte
// réellement les événements, ce n'est pas contournable par sélecteur).
// Un simple classList.remove('show') ne suffit PAS : le wrapper garde une référence JS
// (`const toast = document.getElementById('score-toast')`) et rappelle showToast() →
// classList.add('show') à chaque nouveau score posté par l'iframe, ce qui peut le refaire
// réapparaître entre notre dismiss et notre clic suivant. On retire donc l'élément du DOM
// (`.remove()`) : une fois détaché, les appels classList.add() suivants du wrapper restent
// inoffensifs (élément non affiché = jamais peint, jamais interceptable), sans jamais
// pouvoir revenir bloquer un clic.
async function dismissScoreToast(page) {
  try {
    await page.evaluate(() => {
      const toast = document.getElementById('score-toast');
      if (toast) toast.remove();
    });
  } catch {
    // page/contexte fermé ou navigation en cours, tant pis
  }
}

async function ecranDeverrouillageDetecte(page) {
  try {
    if (page.frames().some((f) => /paystack/i.test(f.url()))) return true;
  } catch {
    // ignore
  }
  try {
    // Bouton "Débloquer" (.cb-btn-outline) de la fiche marketplace pour un jeu payant non acheté.
    if (await page.getByText('Débloquer', { exact: false }).first().isVisible()) return true;
  } catch {
    // ignore
  }
  return false;
}

// ═══════════════════════════════════════════════════════
// SURFACE DE JEU (canvas / iframe)
// ═══════════════════════════════════════════════════════

// Retourne { locator, trouvee } — trouvee=false signifie qu'on est retombé sur un
// locator générique ('body') faute d'avoir trouvé le vrai canvas/iframe du jeu.
async function attendreSurfaceDeJeu(page, jeu) {
  if (jeu.slug === 'garba-master') {
    // L'écran d'accueil (#accueil-screen, bouton "✦ Nouvelle partie") est en DOM pur ;
    // le canvas Phaser (#jeu canvas) n'est créé qu'après clic sur ce bouton. On ne bloque
    // donc que 8s ici (au lieu des 20s génériques) : si le canvas n'apparaît pas, on est
    // probablement encore sur l'écran d'accueil, ce qui est normal avant le clic sur le
    // bouton de démarrage — la fonction est rappelée après ce clic pour re-détecter.
    try {
      await page.waitForSelector('#jeu canvas', { timeout: 8000 });
      return { locator: page.locator('#jeu canvas'), trouvee: true };
    } catch {
      return { locator: page.locator('body'), trouvee: false };
    }
  }

  const aUneIframe = await page.locator('#game-frame').count().then((n) => n > 0).catch(() => false);
  if (!aUneIframe) {
    return { locator: page.locator('body'), trouvee: false };
  }

  const frame = page.frameLocator('#game-frame');
  const canvas = frame.locator('canvas').first();
  try {
    await canvas.waitFor({ timeout: TIMEOUT_CHARGEMENT_MS });
    return { locator: canvas, trouvee: true };
  } catch {
    console.log(`[capture] ${jeu.slug} : canvas introuvable dans l'iframe, on interagit avec l'iframe entière.`);
    return { locator: frame.locator('body'), trouvee: false };
  }
}

// ═══════════════════════════════════════════════════════
// BOUTON DE DÉMARRAGE
// ═══════════════════════════════════════════════════════

// On essaie TOUS les éléments correspondants, pas juste le premier : Abidjan Run a deux
// boutons "JOUER !" — celui du menu (cliquable) et #char-go dans l'écran de sélection de
// personnage, qui a opacity:0 + pointer-events:none tant que l'écran n'est pas ouvert.
// Playwright considère `:visible` uniquement via display/visibility/bounding-box — PAS
// opacity — donc #char-go passe le filtre "button:visible" alors qu'il est invisible et
// bloqué aux clics (pointer-events:none sur son parent). Comme il est placé AVANT le vrai
// bouton dans le DOM, .first() le sélectionnait toujours et le clic échouait en boucle sans
// jamais essayer le second candidat. On boucle donc sur tous les matches jusqu'à un clic
// qui réussit réellement.
async function essayerClicBouton(scope, texte) {
  const boutons = scope.locator('button:visible, a:visible').filter({ hasText: texte });
  const total = await boutons.count();
  if (total === 0) {
    throw new Error(`aucun élément visible pour le texte "${texte}"`);
  }

  let derniereErreur = null;
  for (let i = 0; i < total; i++) {
    try {
      await boutons.nth(i).click({ timeout: 1500 });
      return;
    } catch (err) {
      derniereErreur = err;
    }
  }
  throw derniereErreur;
}

// Screenshot + dump du HTML de l'iframe de jeu, pour diagnostiquer sans deviner quand
// aucun bouton connu n'est trouvé (le game_html réel en prod peut différer de la copie
// inspectée en base locale de dev).
async function sauvegarderDiagnostic(page, slug) {
  try {
    const captureP = path.join(__dirname, `debug-${slug}.png`);
    await page.screenshot({ path: captureP });
    console.log(`[capture] Diagnostic : capture d'écran → ${captureP}`);
  } catch {
    // pas grave
  }

  try {
    const iframeEl = await page.locator('#game-frame').first().elementHandle({ timeout: 1000 });
    const frame = iframeEl ? await iframeEl.contentFrame() : null;
    if (frame) {
      const html = await frame.content();
      const htmlPath = path.join(__dirname, `debug-${slug}-iframe.html`);
      fs.writeFileSync(htmlPath, html, 'utf-8');
      console.log(`[capture] Diagnostic : HTML de l'iframe → ${htmlPath}`);
    }
  } catch {
    // pas grave — pas d'iframe, ou contenu inaccessible
  }
}

// Retourne true si un bouton a été cliqué (page ou iframe), false si aucun trouvé.
async function cliquerBoutonDemarrage(page, slug) {
  for (const texte of BOUTONS_DEMARRAGE) {
    try {
      await essayerClicBouton(page, texte);
      console.log(`[capture] Bouton de démarrage cliqué : "${texte}"`);
      return true;
    } catch {
      // bouton absent sur la page, on essaie le suivant
    }
  }

  const aUneIframe = await page.locator('#game-frame').count().then((n) => n > 0).catch(() => false);
  if (aUneIframe) {
    const frame = page.frameLocator('#game-frame');
    for (const texte of BOUTONS_DEMARRAGE) {
      try {
        await essayerClicBouton(frame, texte);
        console.log(`[capture] Bouton de démarrage cliqué dans l'iframe : "${texte}"`);
        return true;
      } catch {
        // bouton absent dans l'iframe, on essaie le suivant
      }
    }
  }

  console.log('[capture] Aucun bouton de démarrage trouvé sur cet écran.');
  await sauvegarderDiagnostic(page, slug);
  return false;
}

// ═══════════════════════════════════════════════════════
// REPRISE APRÈS UN GAME OVER EN COURS D'ENREGISTREMENT
// ═══════════════════════════════════════════════════════

// Ces jeux sont des runners/arcades qui finissent tôt ou tard par une partie perdue en
// pleine capture ("GAME OVER", écran de fin) — sans reprise, le reste des 45s ne montre
// plus qu'un écran figé. On vérifie donc à intervalles réguliers si un de ces textes de
// relance est GENUINEMENT cliquable, et si oui on reclique à travers les écrans (Rejouer →
// sélection perso → Jouer, etc.) comme au démarrage.
//
// IMPORTANT : on utilise click({trial:true}) et non un simple count() sur ':visible'. Sur
// Abidjan Run, l'écran "GAME OVER" (#gs2) contient un bouton "🔄 REJOUER" — vu comme
// ':visible' par Playwright même quand #gs2 a la classe "hidden" (opacity:0 seulement, pas
// display:none). Un count() aurait donc détecté un faux "écran de fin" en permanence, même
// en plein écran de sélection de personnage. click({trial:true}) vérifie la clicabilité
// réelle (opacity, pointer-events, éléments qui recouvrent...) sans effectuer le clic.
const TEXTES_RELANCE = ['Rejouer', 'Nouvelle partie'];

async function detecterEcranDeFin(page) {
  try {
    const aUneIframe = await page.locator('#game-frame').count().then((n) => n > 0).catch(() => false);
    const scope = aUneIframe ? page.frameLocator('#game-frame') : page;
    for (const texte of TEXTES_RELANCE) {
      const boutons = scope.locator('button:visible, a:visible').filter({ hasText: texte });
      const total = await boutons.count().catch(() => 0);
      for (let i = 0; i < total; i++) {
        const cliquable = await boutons.nth(i).click({ trial: true, timeout: 300 }).then(() => true).catch(() => false);
        if (cliquable) return true;
      }
    }
  } catch {
    // ignore
  }
  return false;
}

async function essayerReprendrePartieEnCoursSiFinie(page, jeu) {
  if (!(await detecterEcranDeFin(page))) return;

  console.log(`[capture] ${jeu.slug} : écran de fin de partie détecté pendant l'enregistrement, relance en cours...`);
  for (let tentative = 0; tentative < MAX_CLICS_ENCHAINES; tentative++) {
    await dismissScoreToast(page);
    const clique = await cliquerBoutonDemarrage(page, jeu.slug);
    await page.waitForTimeout(700);
    if (!clique) break;
  }
}

// ═══════════════════════════════════════════════════════
// INPUTS SCRIPTÉS
// ═══════════════════════════════════════════════════════

async function boiteDeLaSurface(page, surface) {
  try {
    const box = await surface.boundingBox();
    if (box && box.width > 0 && box.height > 0) return box;
  } catch {
    // pas de bounding box (ex: locator body sur un canvas plein écran) → on retombe sur le viewport
  }
  const vp = page.viewportSize() || VIEWPORT;
  return { x: 0, y: 0, width: vp.width, height: vp.height };
}

async function inputsClicker(page, surface, fin, jeu) {
  const box = await boiteDeLaSurface(page, surface);
  const cx = box.x + box.width / 2;
  const cy = box.y + box.height / 2;

  // On ne clique QUE dans la surface du jeu (centre + positions aléatoires alentour), jamais
  // sur des boutons du reste de la page : la barre de contrôle du wrapper (my-products-play.
  // blade.php) contient Plein écran / Classement (nouvel onglet) / ← Mes produits (quitte le
  // jeu !) — un clic aléatoire dessus casserait la capture en cours.
  while (Date.now() < fin) {
    await essayerReprendrePartieEnCoursSiFinie(page, jeu);
    const surCentre = Math.random() < 0.6;
    const x = surCentre ? cx : box.x + Math.random() * box.width;
    const y = surCentre ? cy : box.y + Math.random() * box.height;
    await page.mouse.click(x, y).catch(() => {});
    await page.waitForTimeout(200 + Math.random() * 250);
  }
}

async function inputsFleches(page, surface, fin, jeu) {
  try {
    await surface.click({ timeout: 1000 });
  } catch {
    // pas cliquable, on tente quand même les touches sur la page
  }

  // Abidjan Run n'écoute QUE ArrowUp (sauter) et ArrowDown (glisser) — pas de changement de
  // voie, ArrowLeft/ArrowRight/Space n'y font rien. D'après la config du jeu (ODEFS), les
  // obstacles au sol à sauter (voitures, motos, police, vendeurs, animaux...) sont ~3x plus
  // fréquents que les obstacles aériens à éviter en glissant (fils/corde/branches), d'où ce
  // ratio de touches.
  const touches = ['ArrowUp', 'ArrowUp', 'ArrowUp', 'ArrowDown'];
  while (Date.now() < fin) {
    await essayerReprendrePartieEnCoursSiFinie(page, jeu);
    const touche = touches[Math.floor(Math.random() * touches.length)];
    await page.keyboard.press(touche).catch(() => {});
    await page.waitForTimeout(250 + Math.random() * 300);
  }
}

async function inputsViseur(page, surface, fin, jeu) {
  const box = await boiteDeLaSurface(page, surface);

  while (Date.now() < fin) {
    await essayerReprendrePartieEnCoursSiFinie(page, jeu);
    const x = box.x + Math.random() * box.width;
    const y = box.y + Math.random() * box.height;
    await page.mouse.click(x, y).catch(() => {});
    await page.waitForTimeout(300 + Math.random() * 400);
  }
}

async function inputsMenu(page, fin) {
  while (Date.now() < fin) {
    await page.mouse.move(100 + Math.random() * 400, 100 + Math.random() * 300).catch(() => {});
    await page.waitForTimeout(1000 + Math.random() * 1000);
  }
}

async function executerInputs(page, surface, jeu) {
  const fin = Date.now() + jeu.dureeSecondes * 1000;
  console.log(`[capture] Inputs "${jeu.typeInputs}" pendant ${jeu.dureeSecondes}s ...`);
  // Au cas où le toast de score réapparaîtrait juste avant qu'on commence à jouer.
  await dismissScoreToast(page);

  switch (jeu.typeInputs) {
    case 'clicker':
      return inputsClicker(page, surface, fin, jeu);
    case 'fleches':
      return inputsFleches(page, surface, fin, jeu);
    case 'viseur':
      return inputsViseur(page, surface, fin, jeu);
    case 'menu':
      return inputsMenu(page, fin);
    default:
      console.log(`[capture] typeInputs "${jeu.typeInputs}" inconnu, on laisse tourner sans interaction.`);
      return page.waitForTimeout(Math.max(0, fin - Date.now()));
  }
}

// ═══════════════════════════════════════════════════════
// CAPTURE D'UN JEU
// ═══════════════════════════════════════════════════════

async function capturerJeu(browser, storageState, jeu) {
  const context = await browser.newContext({
    viewport: VIEWPORT,
    storageState,
    recordVideo: { dir: RUSHES_DIR, size: VIEWPORT },
  });
  const page = await context.newPage();

  let erreurDeroulement = null;
  try {
    await page.goto(jeu.url, { waitUntil: 'domcontentloaded', timeout: TIMEOUT_CHARGEMENT_MS });

    if (await ecranDeverrouillageDetecte(page)) {
      throw new Error('écran de déverrouillage détecté');
    }

    await page.waitForTimeout(ATTENTE_ECRAN_ACCUEIL_MS);

    if (await ecranDeverrouillageDetecte(page)) {
      throw new Error('écran de déverrouillage détecté');
    }

    // On part toujours de la fiche marketplace (/marketplace/{slug}), qui n'a ni canvas ni
    // iframe de jeu — il faut donc cliquer à travers un ou plusieurs écrans avant d'atteindre le
    // vrai gameplay : "Jouer maintenant" (fiche) → écran d'accueil du jeu ("JOUER", "Nouvelle
    // partie", ...) → parfois un écran intermédiaire (ex. sélection de personnage sur Abidjan
    // Run, avec son propre bouton "JOUER !"). IMPORTANT : on ne peut PAS s'arrêter dès qu'un
    // canvas/iframe est détecté ("trouvee"), car plusieurs jeux (Abidjan Run, GRI-GRI) ont un
    // <canvas> présent dès le chargement de la page, alors que le menu tourne dedans ou par-
    // dessus — on boucle donc tant qu'un bouton connu est trouvé et cliqué, jusqu'à épuisement
    // ou jusqu'à MAX_CLICS_ENCHAINES, et on re-détecte la surface après CHAQUE clic.
    let surface = page.locator('body');
    let trouvee = false;
    for (let tentative = 0; tentative < MAX_CLICS_ENCHAINES; tentative++) {
      if (await ecranDeverrouillageDetecte(page)) {
        throw new Error('écran de déverrouillage détecté');
      }

      // Le toast de score de la page wrapper peut apparaître dès le chargement (score=0)
      // et couvrir physiquement des boutons du jeu (ex. "JOUER !" sur Abidjan Run) — il
      // intercepte réellement les clics, aucun sélecteur ne peut contourner ça.
      await dismissScoreToast(page);

      const aClique = await cliquerBoutonDemarrage(page, jeu.slug);

      await page.waitForLoadState('domcontentloaded').catch(() => {});
      await page.waitForTimeout(1000);
      ({ locator: surface, trouvee } = await attendreSurfaceDeJeu(page, jeu));

      if (!aClique) break;
    }

    if (!trouvee) {
      console.log(`[capture] ${jeu.slug} : surface de jeu (canvas/iframe) introuvable, on interagit avec la page telle quelle.`);
    }

    await executerInputs(page, surface, jeu);
  } catch (err) {
    erreurDeroulement = err;
  }

  const video = page.video();
  await context.close();

  let videoPathFinal = null;
  if (video) {
    try {
      const videoPathBrut = await video.path();
      videoPathFinal = path.join(RUSHES_DIR, `${jeu.slug}.webm`);
      fs.renameSync(videoPathBrut, videoPathFinal);
    } catch {
      // vidéo non finalisée, tant pis — on remonte quand même l'erreur d'origine ci-dessous
    }
  }

  if (erreurDeroulement) {
    const err = new Error(erreurDeroulement.message);
    err.videoPath = videoPathFinal;
    throw err;
  }

  return videoPathFinal;
}

// ═══════════════════════════════════════════════════════
// CONVERSION MP4 (ffmpeg, optionnel)
// ═══════════════════════════════════════════════════════

function ffmpegDisponible() {
  return new Promise((resolve) => {
    execFile('ffmpeg', ['-version'], (err) => resolve(!err));
  });
}

function convertirUnFichier(webmPath) {
  return new Promise((resolve, reject) => {
    const slug = path.basename(webmPath, '.webm');
    const mp4Path = path.join(path.dirname(webmPath), `promo-${slug}.mp4`);
    execFile('ffmpeg', [
      '-y',
      '-i', webmPath,
      '-c:v', 'libx264',
      '-preset', 'medium',
      '-crf', '20',
      '-pix_fmt', 'yuv420p',
      '-c:a', 'aac',
      mp4Path,
    ], (err) => {
      if (err) reject(err);
      else resolve(mp4Path);
    });
  });
}

async function convertirEnMp4(videoPaths) {
  const chemins = videoPaths.filter(Boolean);
  if (!chemins.length) return;

  console.log('\n[capture] Conversion en .mp4 (H.264) ...');
  if (!(await ffmpegDisponible())) {
    console.log('[capture] ffmpeg introuvable dans le PATH — les .webm sont conservés tels quels dans rushes/.');
    console.log('[capture] Installe ffmpeg (https://ffmpeg.org/) puis relance ce script pour obtenir les .mp4.');
    return;
  }

  for (const webmPath of chemins) {
    try {
      const mp4Path = await convertirUnFichier(webmPath);
      console.log(`[capture] ✔ ${path.basename(mp4Path)}`);
    } catch (err) {
      console.error(`[capture] ✘ Échec conversion ${path.basename(webmPath)} : ${err.message}`);
    }
  }
}

// ═══════════════════════════════════════════════════════
// MAIN
// ═══════════════════════════════════════════════════════

async function main() {
  if (!process.env.BOURSIV_EMAIL || !process.env.BOURSIV_PASSWORD) {
    console.error('[capture] BOURSIV_EMAIL / BOURSIV_PASSWORD manquants — copie .env.example vers .env et remplis tes identifiants.');
    process.exit(1);
  }

  fs.mkdirSync(RUSHES_DIR, { recursive: true });

  const jeux = JEUX_A_CAPTURER.length
    ? JEUX.filter((j) => JEUX_A_CAPTURER.includes(j.slug))
    : JEUX;

  if (!jeux.length) {
    console.error('[capture] JEUX_A_CAPTURER ne correspond à aucun slug connu dans JEUX.');
    process.exit(1);
  }

  console.log(`[capture] ${jeux.length} jeu(x) à capturer : ${jeux.map((j) => j.slug).join(', ')}`);

  const browser = await chromium.launch({ headless: false });

  let storageState;
  try {
    storageState = await login(browser);
  } catch (err) {
    console.error(`[capture] Échec du login : ${err.message}`);
    await browser.close();
    process.exit(1);
  }

  const resultats = [];
  for (const jeu of jeux) {
    console.log(`\n[capture] ── ${jeu.slug} ──`);
    try {
      const videoPath = await capturerJeu(browser, storageState, jeu);
      resultats.push({ slug: jeu.slug, ok: true, videoPath });
      console.log(`[capture] ${jeu.slug} : vidéo enregistrée → ${videoPath}`);
    } catch (err) {
      resultats.push({ slug: jeu.slug, ok: false, erreur: err.message, videoPath: err.videoPath });
      console.error(`[capture] ${jeu.slug} : ÉCHEC — ${err.message}`);
      if (err.videoPath) {
        console.error(`[capture] (une vidéo partielle a quand même été enregistrée → ${err.videoPath})`);
      }
    }
  }

  await browser.close();

  console.log('\n[capture] ── Résumé ──');
  for (const r of resultats) {
    console.log(r.ok ? `  ✔ ${r.slug}` : `  ✘ ${r.slug} — ${r.erreur}`);
  }

  await convertirEnMp4(resultats.map((r) => r.videoPath));
}

main().catch((err) => {
  console.error('[capture] Erreur fatale inattendue :', err);
  process.exit(1);
});
