# Capture vidéo automatique des jeux Boursiv

Script Node.js + Playwright qui se connecte à **boursiv.com (production)**, ouvre
chaque jeu, joue quelques instants avec des inputs scriptés, et enregistre une
vidéo par jeu — pour produire des rushes de promo.

Ce dossier est hors du déploiement Laravel : pur outillage local.

## Installation

```bash
cd tools/capture-jeux
npm install
npx playwright install chromium
```

## Configuration

```bash
cp .env.example .env
```

Puis renseigne dans `.env` :

```
BOURSIV_EMAIL=ton-email@exemple.com
BOURSIV_PASSWORD=ton-mot-de-passe
```

Utilise un compte de production qui a déjà débloqué/acheté tous les jeux.

## Lancer un seul jeu (test)

Avant de lancer les six jeux, valide login + capture sur un seul en éditant
`JEUX_A_CAPTURER` en tête de `capture.js` :

```js
const JEUX_A_CAPTURER = ['garba-master'];
```

Tableau vide (`[]`) = tous les jeux de la liste `JEUX`.

## Lancement

```bash
node capture.js
```

Une fenêtre Chromium s'ouvre (mode headed), se connecte, puis ouvre chaque jeu
l'un après l'autre. Le script ne s'arrête jamais en cours de route : si un jeu
plante, ne charge pas, ou affiche un écran de déverrouillage/paiement, c'est
loggé clairement dans la console et le script passe au jeu suivant.

Les vidéos sont écrites dans `rushes/` :
- `<slug>.webm` pendant la capture (format natif Playwright)
- `promo-<slug>.mp4` après conversion H.264 (si `ffmpeg` est disponible dans le PATH)

Si `ffmpeg` n'est pas installé, les `.webm` sont conservés et le script te le
signale en fin d'exécution.

## Ajuster durées / inputs / BASE_URL

Tout se configure en tête de `capture.js` :

- `BASE_URL` — cible la production par défaut (`https://boursiv.com`). Ne
  change ça que si tu veux exceptionnellement capturer contre le local.
- `JEUX` — un objet par jeu : `slug`, `url`, `dureeSecondes` (défaut 45),
  `typeInputs`.
- `typeInputs` disponibles :
  - `clicker` — clics rapides au centre + clics occasionnels sur les boutons visibles
  - `fleches` — flèches directionnelles aléatoires + espace
  - `viseur` — clics à positions aléatoires dans la surface de jeu (canvas/iframe)
  - `menu` — pas d'interaction, laisse tourner l'intro

## Diagnostiquer un échec de login

Si le script s'arrête avec « toujours sur /login après soumission », regarde
`login-debug.png` (généré automatiquement dans ce dossier juste après la
tentative de connexion) : il montre l'état exact de la page — message
d'identifiants invalides, throttle Laravel (5 tentatives max), etc.

## Notes

- Le login se fait une seule fois ; la session (storageState Playwright) est
  réutilisée pour ouvrir chaque jeu dans un contexte de navigateur séparé (un
  contexte par jeu = une vidéo par jeu).
- Le jeu `awale-master` utilise l'URL fournie manuellement
  (`/marketplace/awale-master`) — ce jeu n'existe pas encore dans la base de
  données du projet au moment de l'écriture de ce script, donc l'URL n'a pas
  pu être vérifiée dans le code. Si une page `/play` dédiée existe entre-temps,
  ajuste l'URL dans `capture.js`.
- Le jeu `questions-pour-un-vrai-mogo` n'a pas non plus de produit publié en
  base au moment de l'écriture — si la page n'existe pas encore en prod, le
  script le loggera en échec et passera au jeu suivant sans bloquer les autres.
