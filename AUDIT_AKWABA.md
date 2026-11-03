# Akwaba sur Boursiv : état du chantier

Jeu **Akwaba : le mystère de la lagune Ébrié**. Gratuit jusqu'au chapitre 2, histoire complète 1 000 FCFA (accès à vie), packs de 5 indices à 500 FCFA.
Audit initial du 03/11/2026, document tenu à jour à chaque étape.

## 1. Avancement

| Étape | Commit | État |
|---|---|---|
| 0. Audit | — | ✅ validé |
| Sécurité S1 à S4 (premium des jeux, /admin/users, /admin/payouts) | `2ded392` | ✅ déployé |
| 1+2. Back-end | `ebb94e1`, `977b03e` | ✅ déployé |
| Nouvelle version du jeu | `a79ed85` | ✅ déployé |
| 3. Page hôte + marketplace | `0db535f` | ✅ validé sur téléphone, déployé |
| Inscription → retour au jeu | `6339489` | ✅ déployé |
| 4. Admin | `98ab565` | ✅ validé, **non poussé** |
| 4bis. Code admin seul, montant encaissé, compteur invités | `bded026` | **non poussé, à valider** |
| 5+6. Sécurité : codes admin/stagiaire hors du dépôt | `5434841` | **non poussé, à valider : prépare le `.env` avant de déployer (§ 5)** |
| 5+6. Durcissement Akwaba | `c31ba4f` | **non poussé, à valider** |
| 7. Déploiement + publication du produit | — | à faire |

## 2. Ce qui est en place

### Joueur
- **`/jeux/akwaba`**, publique : tout le monde joue au prologue et aux chapitres 1-2 sans compte. Le jeu (`resources/games/akwaba.html`, 6,27 Mo, versionné dans git) tourne dans une iframe même origine sans sandbox, servie par `/jeux/akwaba/fichier?v={hash du contenu}`.
- **Pont postMessage** (contrat « à plat », `akwaba: 1`) : `ready` → `state` ; `progress` envoyé au serveur 1 s après le dernier changement ; `buy` (invité → connexion puis retour au jeu, connecté → page Paystack) ; `useHint` → `hintResult`. Chaque message est vérifié : `event.source`, `event.origin`, `akwaba === 1`.
- **Retour de paiement** `?paid=1` : « Paiement en cours de validation… », `/state` interrogé toutes les 2 s pendant 30 s, puis message + lien WhatsApp si rien n'est confirmé.
- **Marketplace** : la fiche et les cartes affichent « Gratuit pour commencer · Histoire complète 1 000 FCFA » + « Jouer » → `/jeux/akwaba`. Aucun achat standard, aucun téléchargement, aucun `game_html`.
- **Connexion et inscription** ramènent au jeu (`redirect()->intended()`).

### Paiements (page Paystack hébergée, Mobile Money seulement)
- **Histoire complète** = achat marketplace standard du produit `akwaba` (référence `MP-…`, montant = prix en base). Le webhook existant valide l'achat, avec la réconciliation, les e-mails et les attributions admin.
- **Pack de 5 indices** = référence `AKW-H-…`, 500 XOF fixés côté serveur. Le webhook et le retour navigateur **relisent la transaction chez Paystack** (le corps du webhook n'est pas utilisé). Ils contrôlent : statut `success`, montant exact en sous-unités (50 000), XOF, `metadata.game = akwaba`, `metadata.product = hints5`, joueur existant. Ensuite +5 dans `game_credits` et une ligne dans `game_credit_ledger` (`payment_reference` **unique** = pas de double crédit).
- **Indices consommés** par un `UPDATE … WHERE balance > 0` (atomique sous SQLite) : le solde ne passe jamais sous zéro.
- **Progression** dans `game_progress` (produit `akwaba`) : `{save, fins, max}`, fins fusionnées sans jamais en retirer. Sans déblocage, un chapitre au-delà de 2 ou une fin est refusé (403).

### Admin (`/admin/akwaba`, lien « 🌙 Akwaba » dans le menu)
- **Consultation** (code admin ou stagiaire) :
  - joueurs (débloqué payé / offert / non, indices, chapitre max, fins, dernière activité), recherche et pagination ;
  - historique des indices filtrable ;
  - encadré Akwaba sur la fiche utilisateur.
- **Statistiques** :
  - entonnoir des **connectés** : Prologue → 1 → 2 → 🔒 Cadenas → 3 … 7 ;
  - entonnoir des **invités** : sessions anonymes qui ont lancé le jeu → arrivées au cadenas ;
  - conversion connectés, et conversion globale = payés ÷ (cadenas invités + connectés) ;
  - packs vendus, **CA Paystack / CA manuel / total**, sur 7 jours, 30 jours et au total.
- **Actions, code admin seul** (403 au code stagiaire), avec confirmation et motif obligatoire :
  - débloquer ou retirer (attribution marketplace existante ; un achat Paystack ne se retire pas) ;
  - ajouter 1 à 100 indices ;
  - montant encaissé facultatif (FCFA) sur ces deux actions ;
  - rattraper un pack `AKW-H-…` (revérifié chez Paystack, idempotent).
- **Auteur enregistré** : compte connecté (« Nom (#id, code admin) »), sinon « Code admin (sans compte connecté) ».
- **Compteur anonyme des invités** : `POST /jeux/akwaba/event` (`play_start` au premier `ready`, `lock_reached` à `save='3'`). Table `akwaba_events` : type, HMAC de l'id de session, date. Une ligne par session et par type, ni IP ni donnée personnelle.

## 3. Revue de sécurité (étape 5+6)

Relecture avec un regard d'attaquant : routes publiques, webhook, rattrapage, admin, page hôte, en-têtes, limites.

### Corrigé

| # | Gravité | Problème | Correctif |
|---|---|---|---|
| A1 | **Critique** | Le dépôt GitHub est **public** et le **code admin était écrit en dur** dans `AdminController`. N'importe qui pouvait ouvrir le panel admin : débloquer Akwaba, s'ajouter des indices, et tout le reste de l'admin. Le **code stagiaire** avait une valeur par défaut publique, et `env()` appelé hors de la config renvoie `null` quand la config est en cache : en prod, c'était donc presque sûrement le code public. | `5434841` : codes lus dans `.env` via `config/services.php` (`ADMIN_ACCESS_CODE`, `STAGIAIRE_CODE`), sans valeur par défaut (vide = accès fermé), `hash_equals`, session régénérée à la connexion, `throttle:5,1` sur les deux formulaires. **Les deux anciens codes sont publics : il faut en choisir de nouveaux.** |
| A2 | Moyenne | Une personne avec le code stagiaire pouvait débloquer, ajouter des indices et rattraper des packs. | `bded026` : réservé au code admin (403 + message). |
| A3 | Moyenne | Page hôte sans CSP ni protection contre l'encadrement : un autre site pouvait afficher `/jeux/akwaba` dans un cadre (clickjacking). | `c31ba4f` : CSP stricte avec **nonce** renouvelé à chaque affichage (un seul script autorisé, `connect-src`/`frame-src 'self'`, `frame-ancestors 'self'`, `object-src 'none'`, `base-uri 'none'`), `X-Frame-Options`, `nosniff`, `Referrer-Policy`. Le fichier du jeu refuse aussi d'être encadré par un autre site. Vérifié dans Chrome : aucune violation de CSP, le jeu s'affiche, le pont fonctionne. |
| A4 | Moyenne | `/jeux/akwaba/paiement/retour` est public et déclenche un appel à l'API Paystack pour n'importe quelle référence, sans limite : on pouvait épuiser le quota Paystack du compte. | Référence au format attendu seulement (sinon aucun appel), `throttle:20,1`. |
| A5 | Faible | Montant d'un pack comparé après arrondi : 499,51 F passait pour 500. | Comparaison exacte en sous-unités. |
| A6 | Faible | `/state` limité à 60/min **par IP** pour les invités. Sur les réseaux mobiles ivoiriens, beaucoup de joueurs partagent une IP (CGNAT) : des joueurs légitimes pouvaient être bloqués. | Limite par compte ou par session (60/min), plafond par IP large (600/min). Même logique pour le compteur d'invités (10/min par session, 300/min par IP). |
| A7 | Faible | Confirmations admin construites en injectant le nom du joueur dans du JavaScript (`addslashes`) : un nom avec un saut de ligne cassait la page. | Texte dans `data-confirm`, échappé par Blade, lu par un petit script. |
| A8 | Faible | Deux premières sauvegardes de progression simultanées : erreur 500 sur la contrainte unique. | Exception d'unicité tolérée. |

### Vérifié, sans problème
- **Montants** et produits uniquement côté serveur. Le client n'envoie que `full` ou `hints5`, et un montant envoyé par le navigateur est ignoré (testé).
- **Webhook** : signature HMAC SHA512 + `hash_equals` ; les packs revérifiés chez Paystack ; l'idempotence par référence est testée (webhook rejoué, webhook + retour, rattrapage admin).
- **Indices** : décrément conditionnel, solde jamais négatif (testé en rafale).
- **Isolation** : chaque route ne lit et n'écrit que les données du joueur connecté. Le retour de paiement crédite le joueur des métadonnées Paystack, pas la personne qui ouvre l'URL (testé).
- **CSRF** sur toutes les routes POST (en-tête `X-CSRF-TOKEN`) ; routes admin derrière `admin.code` (testé pour invité, compte connecté et stagiaire).
- **Admin** : tout l'affichage passe par `{{ }}` (pas de XSS stocké via nom, motif ou référence) ; référence de rattrapage validée par une regex.
- **Redirections** : `/jeux/akwaba/connexion` fixe l'URL de retour côté serveur, pas de redirection ouverte.
- **Cache du jeu** : long seulement sous l'URL qui contient le hash du contenu, `no-cache` sinon.

### Limites restantes (non corrigées, à connaître)
1. **Tout le jeu est dans un seul fichier public.** Quelqu'un qui sait utiliser la console peut ouvrir les chapitres 3 à 7 côté client. Les paiements, les indices et la progression restent protégés côté serveur. Solution v2 au § 6.
2. **Le jeu est de confiance totale** : il tourne sur le même domaine que Boursiv, sans sandbox. Il peut donc techniquement lire le DOM parent, le jeton CSRF et appeler l'API au nom du joueur. C'est sans risque tant que le fichier vient de toi. **Toute nouvelle version du jeu doit être traitée comme du code** (même source, relue). Pour aller plus loin : servir le jeu depuis un sous-domaine dédié, ce qui suppose de modifier la liste d'origines du jeu.
3. **Pas de CSP restrictive dans le jeu lui-même** (seulement `frame-ancestors`) : une règle trop stricte risquerait de le casser, et je ne peux pas relire le fichier. À ajouter avec l'auteur du jeu si besoin.
4. **N'importe qui peut payer un pack pour le compte d'un autre** via la popup Paystack publique, avec des métadonnées choisies. Il paie bien 500 F : c'est un cadeau, pas une fraude.
5. **Le compteur d'invités peut être gonflé** par un script qui ouvre des sessions en boucle (limité à 300/min par IP). Ce ne sont que des statistiques. Un invité qui se connecte puis paie peut aussi compter à la fois comme invité et comme connecté au cadenas : la conversion globale est donc légèrement sous-estimée.
6. **Packs d'indices** : ils ne passent pas par `payments:reconcile-paystack`. Le filet de sécurité, c'est le webhook, le retour navigateur et le rattrapage admin.
7. **Code admin partagé** : on ne sait pas quel admin agit, sauf s'il est connecté à son compte. Une vraie authentification admin par compte (rôle sur `users`) serait la suite logique.
8. **Hors Akwaba, mêmes motifs ailleurs** (non traités ici) :
   - le service marketplace compare aussi les montants après arrondi ;
   - `/paystack-inline-mock.js` poste avec `"*"`.

## 4. Tests automatisés
- `tests/Feature/AkwabaTest.php` : page, fichier, cache, CSP/nonce, encadrement, state, progression, indices, achat, webhook, retour, rattrapage via retour, limites, compteur invités, marketplace.
- `tests/Feature/AkwabaAdminTest.php` : accès, stagiaire en lecture seule, liste, recherche, entonnoirs, CA Paystack/manuel, historique, attribution et retrait, ajout d'indices, montant encaissé, rattrapage.
- `tests/Feature/AdminPanelLoginTest.php` : codes lus dans `.env`, anciens codes refusés, accès fermé sans code, limite de tentatives.
- `tests/Feature/SecurityGamePremiumAndAdminRoutesTest.php` et `tests/Feature/Auth/RegistrationTest.php`.
- Suite complète : **353 passent**. Les 9 échecs `PasswordReset`, `Profile` et `Example` existaient avant ce chantier (la page `/` redirige).

## 5. Déploiement des commits non poussés

**Avant le `git pull`**, choisis deux **nouveaux** codes (les anciens sont publics sur GitHub) et ajoute-les au `.env` du VPS, sinon le panel admin restera fermé :

```bash
# sur le VPS, dans /var/www/coach-brvm/.env
ADMIN_ACCESS_CODE=<nouveau code long et aléatoire>
STAGIAIRE_CODE=<autre nouveau code>
```

Pense aussi à les ajouter à ton `.env` local, sinon l'admin local sera fermé. Ensuite :

```bash
git push origin main            # en local
cd /var/www/coach-brvm && git pull
php artisan migrate --force     # amount_received (admin_grants), akwaba_events, admin du ledger
php artisan config:cache && php artisan route:cache && php artisan view:clear
systemctl reload php8.3-fpm
```

Après le déploiement :
- reconnecte-toi à l'admin avec le nouveau code : les sessions admin ouvertes avec l'ancien code restent valides jusqu'à leur expiration. Pour les couper tout de suite, vide la table `sessions` (tout le monde est déconnecté) ;
- publie le produit `akwaba` depuis l'admin marketplace quand tu es prêt (il est encore en brouillon).

## 6. Proposition v2 (non codée) : chapitres servis par le serveur
Découper le jeu : le fichier public contient le moteur, le prologue et les chapitres 1-2. Les chapitres 3 à 7 et les fins (textes, énigmes, images) deviennent des paquets servis par `GET /jeux/akwaba/contenu/{chapitre}`, réservés aux joueurs qui ont débloqué l'histoire (`Cache-Control: private`). Le jeu les charge à la réception de `state.unlocked = true`. Les réponses des énigmes et le texte des indices sont vérifiés ou servis par le serveur (`POST /jeux/akwaba/indice` renvoie l'indice après décrément) : ils ne sont plus dans le client. Cela demande de modifier le jeu.

## 7. Plan de test manuel (sur téléphone, après déploiement)

**Invité et compteur**
1. Navigation privée : `/jeux/akwaba` → « Nouvelle partie » → jouer jusqu'au cadenas du chapitre 3.
2. Admin → Akwaba : « Ont lancé le jeu » et « Arrivés au cadenas » ont pris +1 (entonnoir des invités).

**Déblocage payé**
3. « Continuer l'aventure » → connexion (ou création de compte) → retour automatique sur le jeu.
4. Re-cliquer → page Paystack, Mobile Money → payer 1 000 F → retour « Paiement en cours de validation… » → le jeu passe tout seul au chapitre 3.
5. Admin : le joueur est « Oui · payé », CA Paystack +1 000 F.

**Indices**
6. Utiliser l'indice gratuit → « Acheter sur Boursiv » → payer 500 F → « Indice (5) » → en utiliser un → « Indice (4) ». L'historique admin montre +5 (Paystack) puis −1.

**Autre téléphone**
7. Se connecter avec le même compte → progression, fins et « Indice (4) » reviennent.

**Gestes admin** (avec le **nouveau** code admin, connecté à ton compte)
8. Sur un autre compte de test : débloquer avec le motif « test WhatsApp » et 1 000 F encaissés → le jeu de ce compte s'ouvre au chapitre 3 ; CA manuel +1 000 F ; ton nom apparaît comme auteur.
9. Ajouter 3 indices (motif obligatoire, 0 F) → « Indice (3) » après rechargement.
10. Retirer le déblocage → le cadenas revient ; le CA manuel retombe.
11. Rattrapage : coller la référence `AKW-H-…` du pack de l'étape 6 → « déjà créditée, rien à faire ».

**Code stagiaire**
12. Se connecter avec le nouveau code stagiaire → `/admin/akwaba` est consultable, sans formulaires d'action ; un POST forcé renvoie 403.
13. Les anciens codes admin et stagiaire sont refusés.

**Sécurité visible**
14. Outils du navigateur → onglet Réseau sur `/jeux/akwaba` : en-tête `Content-Security-Policy` présent, aucune erreur CSP dans la console.
