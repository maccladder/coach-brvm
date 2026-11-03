# Audit : intégration d'Akwaba sur Boursiv

Audit du 03/11/2026, mis à jour au fil des étapes.

## Avancement

| Étape | État |
|---|---|
| 0. Audit | ✅ validé |
| Sécurité S1 à S4 | ✅ commit `2ded392`, déployé |
| 1+2. Back-end | ✅ validé (`977b03e`) |
| Nouvelle version du jeu | ✅ `a79ed85` (aucun déblocage gratuit hors Boursiv) |
| 3. Page hôte | ✅ validée sur téléphone, déployée (`0db535f`) |
| Inscription → retour au jeu | ✅ `6339489`, déployé |
| 4. Admin | ✅ codé et testé, **en attente de validation** (non poussé) |
| 5+6. Sécurité + tests | à faire (tests back-end déjà en place) |
| 7. Déploiement | à faire |

### Décisions prises
- Jeu : `resources/games/akwaba.html`, versionné dans git (6,27 Mo, aucune ressource externe).
- Progression : table `game_progress` existante, avec le `product_id` du produit `akwaba` (unique (`user_id`, `product_id`) déjà présent). `data = {save, fins, max}`.
- Paiement : page Paystack hébergée, `channels = ['mobile_money']`, sans le checkout Mobile Money personnalisé.
- Produit : propriétaire et catégorie repris de Garba Master, `affiliate_eligible = false` (comme tes autres jeux).
- Indice gratuit : géré par le jeu seul.

### Étapes 1+2 : ce qui a été fait
- **Migrations** : `game_credits`, `game_credit_ledger` (`payment_reference` unique) et le produit `akwaba` (1 000 F, **créé en brouillon** ; à publier depuis l'admin quand la page hôte sera en ligne. La migration ne fait rien si le produit existe).
- **Modèles** : `GameCredit`, `GameCreditLedger`.
- **`App\Services\AkwabaService`** : état, déblocage (achat payé ou attribution admin active), progression (fins fusionnées, `max` pour les stats), consommation d'indice par UPDATE conditionnel `balance > 0`, ajout d'indices.
- **`App\Services\Payments\PaystackAkwabaHintsService`** : références `AKW-H-{user}-{ms}-{rand}`. Il relit la transaction chez Paystack (le corps du webhook n'est pas utilisé), contrôle statut `success`, 500 XOF, `metadata.game = akwaba`, `metadata.product = hints5` et le joueur, puis crédite +5. Une référence déjà dans le ledger ne crédite rien.
- **`AkwabaController`** et routes `/jeux/akwaba/{fichier,state,progress,hint/use,buy,paiement/retour}`. `full` crée un achat marketplace standard (`MP-…`, montant = prix du produit en base). Le webhook existant le valide.
- **Webhook** : nouvelle branche `AKW-H-`.
- **Garde-fou supplémentaire** : sans déblocage, `progress` refuse un chapitre au-delà de 2 ou une fin (403). Ça évite de fausser les stats de l'admin.
- **Tests** : `tests/Feature/AkwabaTest.php`, 33 cas, tous verts. Suite complète : 305 passent ; les 9 échecs Breeze (`PasswordReset`, `Profile`, `Example`) existaient déjà avant ce chantier.

### Limites connues du back-end
- Les packs d'indices ne passent pas par la table `payments`, donc la commande `payments:reconcile-paystack` ne les rattrape pas. Ils sont validés par le webhook **et** par le retour navigateur. Si les deux échouent, l'admin crédite à la main (étape 4).
- `full` est un paiement marketplace normal : un achat abandonné déclenche la même relance par e-mail que les autres produits.
- Deux envois de progression simultanés du même joueur : le dernier écrit gagne. Le pont envoie après 1 s sans nouveau message, donc c'est sans effet en pratique.
### Étape 3 : ce qui a été fait
- **`GET /jeux/akwaba`** (publique, sans compte) : vue `resources/views/akwaba-play.blade.php`, iframe plein écran **sans sandbox** (`allow="autoplay; fullscreen"`), barre Boursiv, plein écran (API Fullscreen, ou pseudo plein écran CSS sur iPhone). `noindex` tant que le produit est en brouillon.
- **Pont postMessage** conforme au contrat : `ready` → `state` ; `progress` envoyé 1 s après le dernier changement (si connecté) ; `buy` : invité → `/jeux/akwaba/connexion` (retour au jeu après connexion, formulaire ou Google), connecté → `/buy` puis redirection **de la page parente** vers Paystack ; `useHint` → `hintResult` avec le même `id`. Chaque message est vérifié : `event.source`, `event.origin`, `akwaba === 1`.
- **Retour `?paid=1`** : « Paiement en cours de validation… », `/state` interrogé toutes les 2 s pendant 30 s, `state` renvoyé au jeu à chaque changement. Le produit attendu est mémorisé (sessionStorage) avant le départ vers Paystack, pour savoir quand c'est validé. Au bout de 30 s, un message rassurant + lien WhatsApp.
- **URLs relatives** pour l'API : la session suit, que l'on arrive par `boursiv.com` ou `www.boursiv.com`.
- **`/jeux/akwaba/fichier?v={hash}`** : hash SHA-256 du contenu (16 caractères), recalculé seulement quand la date ou la taille du fichier change. Cache long `immutable` uniquement sous la bonne version ; sans version ou avec une ancienne, `no-cache`. Un nouveau fichier = une nouvelle URL.
- **Marketplace** : fiche Akwaba = « Gratuit pour commencer · Histoire complète 1 000 FCFA » + « Jouer gratuitement » → `/jeux/akwaba`, sans formulaire d'achat ni bouton de connexion pour acheter. Carte de `/jeux` et de la marketplace : même mention + bouton « Jouer ». « Mes produits » → `/jeux/akwaba`.
- **Aucun téléchargement** : `my.products.play` et `my.products.download` redirigent vers `/jeux/akwaba` ; `game-html` et le téléchargement des fichiers produit renvoient 404 pour Akwaba, même si un fichier était ajouté par erreur depuis l'admin.
- **Produit toujours en brouillon** : la fiche et la carte restent invisibles. La page `/jeux/akwaba` est accessible pour ton test sur téléphone.
- **Vérifié dans Chrome** (390 × 844, sans interface) : le jeu s'affiche, l'iframe charge la version hachée, `/state` répond en invité, aucune erreur JavaScript.
- **Tests** : `AkwabaTest` passe à 41 cas (page, cache, connexion, fiche, carte, routes bloquées). Suite complète : 313 passent ; toujours les 9 échecs Breeze qui existaient avant.

### Étape 4 : ce qui a été fait
- **`/admin/akwaba`** (`admin.code` + `stagiaire.log`), lien « 🌙 Akwaba » dans le menu admin.
  - Joueurs : nom, e-mail, débloqué (payé / offert / non), solde d'indices, chapitre max, fins, dernière activité. Recherche par nom ou e-mail, 25 par page, tri par dernière activité.
  - Statistiques : entonnoir Prologue → 1 → 2 → 🔒 Cadenas → 3 … 7, déblocages payés et offerts, conversion au cadenas (payés ÷ arrivés au cadenas), packs vendus, CA (montants réellement encaissés : déblocages + packs), ventes à 7 et 30 jours, fins découvertes, indices ajoutés à la main et utilisés.
  - Historique des indices complet : 30 par page, filtrable par joueur (`?user=`), par nom, e-mail ou référence, et par type (achat / utilisé / admin).
- **Gestes manuels** (« Gérer » sur un joueur), chacun avec une confirmation et un motif obligatoire :
  - débloquer = attribution marketplace (`admin_grants`, même e-mail de notification que la page Attributions) ; retirer = révocation de l'attribution. Un achat Paystack payé ne se retire pas ;
  - ajouter de 1 à 100 indices (5 par défaut), ligne `admin` dans l'historique ;
  - rattrapage d'un pack : référence `AKW-H-…` revérifiée chez Paystack (même contrôle que le webhook), +5 au joueur qui a payé, une seule fois. Si la référence est déjà dans l'historique, rien n'est fait.
- **Auteur enregistré** : le code admin est partagé, donc on enregistre le compte connecté s'il y en a un (« Nom (#id, code admin) »), sinon « Code admin (sans compte connecté) » ou « Code stagiaire… ». Nouvelles colonnes `admin_user_id` et `admin_label` dans `game_credit_ledger` (migration `2026_11_03_000004`). Pour les attributions : `granted_by` / `revoked_by` = compte connecté, et le libellé est ajouté au motif.
- **Fiche utilisateur admin** : encadré Akwaba (histoire achetée / offerte / non, solde, chapitre max, fins) + lien vers `/admin/akwaba?user=…`.
- **Tests** : `AkwabaAdminTest`, 17 cas (accès refusé sans code admin, liste, recherche, chiffres de l'entonnoir et du CA, historique filtré, motif obligatoire, attribution et retrait, achat Paystack non retirable, auteur enregistré, rattrapage valide, idempotent ou refusé). Suite complète : 332 passent, toujours les 9 échecs Breeze anciens.
- **Non vérifié visuellement** : la page admin n'a pas été ouverte dans un navigateur (il faut le code admin). Son rendu est couvert par les tests.

### Limites et points ouverts (étape 4)
- Les **stagiaires** (`admin.code` les laisse passer) peuvent eux aussi débloquer et ajouter des indices. C'est tracé (« code stagiaire » + journal `stagiaire.log`), mais tu peux préférer réserver ces gestes à `is_admin`.
- Les paiements WhatsApp n'entrent pas dans le CA : seul le motif est noté. Un champ « montant encaissé » pourrait être ajouté si tu veux les compter.
- Les invités ne sont pas dans l'entonnoir (aucune sauvegarde serveur). Le cadenas ne compte que les joueurs connectés qui ont cliqué « Continuer l'aventure », ceux qui ont l'accès, et ceux qui ont dépassé le chapitre 2.

### Limites et points ouverts (étape 3)
- **Inscription** : la connexion (formulaire et Google) ramène au jeu, mais **la création de compte ramène au tableau de bord** (`RegisteredUserController` ne lit pas l'URL de retour). Un nouveau joueur doit donc revenir sur `/jeux/akwaba` à la main. Correctif proposé (1 ligne, effet global) : `redirect()->intended(route('dashboard'))` à l'inscription.
- Après connexion, la progression locale (par ex. chapitre 2) n'est envoyée au serveur qu'au prochain changement de chapitre. Sans effet pratique : le joueur est au cadenas, il change de chapitre juste après l'achat.

---

## 0. Bloquant immédiat : les fichiers du jeu sont absents

- `storage/app/games/` **n'existe pas** dans le projet. `akwaba.html` et `pont-exemple.html` sont introuvables.
- Ce dossier ne passerait de toute façon **pas par git** : `storage/app/.gitignore` ignore tout (`*`) sauf `private/` et `public/`. Un `git pull` sur le VPS ne livrerait donc jamais le jeu.
- Le précédent existe déjà : `resources/games/prince-de-yop.html` (130 Ko) est versionné dans git et servi par Laravel.

→ Voir la **question Q1**.

---

## 1. Ce qui existe et se réutilise

| Brique | Où | Réutilisation pour Akwaba |
|---|---|---|
| Produit marketplace + achats | `marketplace_products`, `marketplace_purchases` (unique user+produit, `status=paid`) | Le déblocage `full` = un achat marketplace normal du produit `akwaba` (1 000 F). |
| Attribution manuelle | `admin_grants` (polymorphique), `AdminGrantController` (type `marketplace`), `$product->isGrantedTo($user)` | Fonctionne sans changement : un grant sur le produit Akwaba = déblocage. |
| Test d'accès standard | `purchasedProducts()->wherePivot('status','paid')` ou `isGrantedTo()` (copié dans ~8 contrôleurs) | Même règle pour `unlocked`. |
| Paiement marketplace Paystack | `MarketplacePaymentController::buyPaystack` → référence `MP-…`, ligne `payments` (purpose `marketplace`, montant attendu), webhook → `PaystackMarketplaceService` (idempotent, `lockForUpdate`, contrôle montant + XOF, e-mail admin, notif vendeur, affiliation) | **On garde la référence `MP-`** pour `full` : webhook, callback, réconciliation (`payments:reconcile-paystack`) et e-mails marchent tels quels. Seule différence : un `callback_url` qui ramène à `/jeux/akwaba?paid=1`. |
| Webhook | `PaystackWebhookController` : HMAC SHA512 + `hash_equals`, aiguillage par préfixe de référence, répond toujours 200 | Ajouter une branche `AKW-H-` (packs d'indices). |
| Checkout Mobile Money perso | `PaystackMobileMoneyCheckout` + `app/Checkout/Providers/*Payable` (un provider par type, drapeaux `CHECKOUT_MM_*`) | Architecture prête : un `AkwabaPayable` suffirait. **Attention** : `marketplace` est activé par défaut dans `config/checkout.php` → `buyPaystack` redirigerait `full` vers ce checkout. Voir **Q2**. |
| Progression | Table `game_progress` **déjà créée** (Prince de Yop) : `user_id`, `product_id`, `data` JSON, unique (user, produit) | **Je propose de la réutiliser** (voir §3) au lieu d'en créer une seconde du même nom, ce qui est impossible. |
| Page hôte + pont postMessage | `resources/views/prince-de-yop-play.blade.php` : iframe plein écran, pseudo plein écran iPhone, vérif `event.source`, `postMessage(…, location.origin)`, file d'envoi, CSRF en en-tête | Modèle direct pour `/jeux/akwaba`. |
| Admin | middleware `admin.code` (session `is_admin`/`is_stagiaire`), `layouts.admin`, page `admin/garba-master` (stats d'un jeu), `admin/users/show` | Nouvelle page `admin/akwaba` sur le modèle de Garba Master + bloc sur la fiche utilisateur. |
| Pipeline d'upload | `MarketplaceProductAdminController::sanitizeGameHtml` → stockage dans `game_html` (limite 10 Mo) | **Contourné** : Akwaba est servi depuis le fichier, le produit n'a pas de `game_html`. |

## 2. Ce qui manque

1. Le produit `akwaba` en base (type `game`, 1 000 F, publié) : migration de données idempotente (`firstOrCreate` sur le slug), sans seeder.
2. Le solde d'indices : tables `game_credits` + `game_credit_ledger`.
3. Un contrôleur `AkwabaController` (page, fichier, state, progress, hint/use, buy, retour de paiement).
4. Un service `AkwabaHintsService` (crédit idempotent d'un pack, consommation atomique).
5. Le cas particulier Akwaba dans la marketplace : sa fiche affiche aujourd'hui un bouton d'achat classique et « Jouer » exige l'achat. Il faut « Jouer gratuitement » → `/jeux/akwaba` et la mention « Gratuit jusqu'au chapitre 2 · Histoire complète 1 000 FCFA ». `MarketplaceMyProductsController::play` doit rediriger vers `/jeux/akwaba` (comme `garba-master` vers `/jeu`).
6. Admin : page Akwaba (tableau de bord + ajout d'indices) et bloc sur la fiche utilisateur.
7. Tests.

Note sur `/jeux` : cette URL fait aujourd'hui une redirection 301 vers `/marketplace?type=game`. `/jeux/akwaba` est une route distincte, sans conflit. Akwaba apparaîtra dans `/jeux` dès que le produit est publié.

## 3. Plan précis

### Migrations
- `2026_11_03_000001_create_game_credits_table` : `user_id` (FK cascade), `game_slug`, `kind` (`hints`), `balance` unsigned int défaut 0, timestamps, unique (`user_id`, `game_slug`, `kind`).
- `2026_11_03_000002_create_game_credit_ledger_table` : `user_id`, `game_slug`, `kind`, `delta` (int), `reason` (`purchase` | `use` | `admin` | `free`), `payment_reference` nullable **unique**, `amount` (XOF, nullable), `admin_note` nullable, timestamps.
- `2026_11_03_000003_create_akwaba_product` : insère le produit s'il n'existe pas (slug `akwaba`, type `game`, prix 1000, statut `published`, vendeur = compte admin existant). Ne touche à rien s'il existe déjà.

**Progression : réutilisation de `game_progress`** avec `product_id` = Akwaba et `data = {"save": "3", "fins": ["oubli"], "max": "3"}`. `max` garde le chapitre le plus loin jamais atteint, pour les stats (le joueur peut revenir en arrière). Même contrat d'API que demandé ; la seule différence est le stockage par `product_id` au lieu de `game_slug`.

### Routes (`routes/web.php`, web + CSRF)
| Méthode | URL | Accès | Rôle |
|---|---|---|---|
| GET | `/jeux/akwaba` | public | page hôte |
| GET | `/jeux/akwaba/fichier` | public | `response()->file()`, `text/html`, `Cache-Control: public, max-age=…`, `X-Frame-Options: SAMEORIGIN` |
| GET | `/jeux/akwaba/state` | public, throttle 60/min | `{logged, unlocked, hints, progress}` |
| POST | `/jeux/akwaba/progress` | auth, throttle 30/min | upsert + union des fins |
| POST | `/jeux/akwaba/hint/use` | auth, throttle 30/min | décrément atomique |
| POST | `/jeux/akwaba/buy` | auth, throttle 6/min | renvoie l'URL de paiement |
| GET | `/jeux/akwaba/paiement/retour` | public | vérifie la transaction (filet si le webhook tarde) puis redirige vers `/jeux/akwaba?paid=1` |

`/jeux/akwaba/fichier` doit être déclarée avant toute route générique éventuelle.

### Paiements
- **`full`** : flux marketplace existant, référence `MP-…`, montant = `products.price` lu en base (1 000), `callback_url` = retour Akwaba. Refus (409) si déjà possédé ou attribué. Le webhook existant crée l'achat.
- **`hints5`** : référence `AKW-H-{user}-{ms}-{rand}`, montant 500 en constante serveur, `metadata = {user_id, game:'akwaba', product:'hints5'}`. Webhook → branche `AKW-H-` → `AkwabaHintsService::creditFromPaystack($data)` qui :
  1. revérifie via `PaystackService::verify($reference)` (statut `success`, montant 500 × 100, devise `XOF`) ;
  2. dans une transaction, insère la ligne de ledger `+5` avec `payment_reference` (**unique** : une référence rejouée lève une violation d'unicité, donc aucun double crédit) et incrémente `balance`.
  Le retour navigateur appelle le même service : premier arrivé, premier servi.
- Pas de remise d'affiliation sur les indices. Sur `full`, la remise passe par le service existant, calculé côté serveur.

### Indices
- **1 indice gratuit par partie** : c'est le jeu qui le gère côté client. Le serveur ne voit passer que les `useHint` payants. Je n'ajoute pas de crédit gratuit côté serveur. À confirmer en **Q4**.
- **Décrément atomique** : `UPDATE game_credits SET balance = balance - 1 WHERE user_id=? AND game_slug='akwaba' AND kind='hints' AND balance > 0`, puis contrôle du nombre de lignes modifiées, puis ligne de ledger `−1`, le tout dans une transaction. **Important : la base est SQLite, et `lockForUpdate()` n'y fait rien** (SQLite ignore `FOR UPDATE`). L'UPDATE conditionnel est atomique par nature, donc le solde ne peut jamais devenir négatif, même avec 10 requêtes en parallèle.
- Le nonce `id` est renvoyé tel quel dans la réponse. Il n'est pas stocké (le jeu ne renvoie pas deux fois le même nonce).

### Page hôte
`resources/views/akwaba-play.blade.php`, copiée de la page Prince de Yop (barre, plein écran, astuce iPhone), avec iframe **sans sandbox** (même origine, le jeu a besoin de `localStorage` et de l'audio), `allow="autoplay; fullscreen"`. Pont conforme au contrat : `ready` → `state`, `progress` envoyé après 1 s sans nouveau message, `buy` (invité → `/login?redirect=…`), `useHint` → `hintResult`, scrutation de `/state` toutes les 2 s pendant 30 s après `?paid=1`. Chaque message est vérifié (`event.source === iframe.contentWindow`, `event.origin === location.origin` et `data.akwaba === 1`).

Le retour après connexion vers `/jeux/akwaba` passe par `url.intended` (à vérifier sur le login Google).

### Admin (`admin.code`)
- `GET /admin/akwaba` : tableau de bord (joueurs par chapitre max atteint, nombre arrivés au chapitre 2 = cadenas, déblocages payés et attribués, packs vendus, CA Akwaba = achats `full` + ledger `purchase`), recherche d'un utilisateur, ledger récent.
- `POST /admin/akwaba/users/{user}/hints` : ajout manuel (+N, motif obligatoire, ligne de ledger `admin`).
- Déblocage manuel : le bouton redirige vers la page Grants existante (déjà testée, avec révocation) au lieu de dupliquer la logique.
- Bloc « Akwaba » sur `admin/users/show` : déblocage, solde, progression, 10 dernières lignes du ledger.

### Tests (`tests/Feature/AkwabaTest.php`, Paystack simulé avec `Http`/Guzzle mock comme dans `PaystackPaymentsTest`)
Webhook valide, signature invalide, montant falsifié, devise falsifiée, référence rejouée ; `hint/use` à 0 et en rafale ; `buy full` déjà possédé ; `buy` invité (401) ; `state` invité / connecté / attribué par grant ; fusion de la progression (les fins ne disparaissent jamais, chapitre invalide refusé) ; isolement entre utilisateurs.

---

## 4. Risques de sécurité trouvés dans le code actuel

| # | Gravité | Où | Problème | Correctif proposé |
|---|---|---|---|---|
| S1 | **Critique** | `GameScoreController::verifyPremiumUnlock` (`POST /games/{product}/verify-premium`) | Accepte **n'importe quelle** référence Paystack `success` : il ne contrôle ni le montant, ni la devise, ni `metadata.user_id`, ni le produit, ni la feature. Exemples : un joueur paie « turbo » (500 F) et réclame « magnat » (2 500 F) avec la même référence ; ou il réutilise la référence d'un don de 100 F ou d'un achat de cauris pour débloquer un premium. | Déléguer à `PaystackGamePremiumService::handleGamePremium` (qui contrôle déjà montant, devise et métadonnées) et exiger `metadata.user_id === auth()->id()`. |
| S2 | Haute | `GameScoreController::premiumCallback` | Pas de contrôle de montant, ni de `metadata.user_id` / `metadata.product_id`. La feature vaut `premium_chars` par défaut si les métadonnées n'en ont pas : la référence de n'importe quel autre paiement réussi débloque le premium d'Abidjan Run. | Même délégation au service. |
| S3 | **Critique** | `routes/web.php` (~l. 1057) : `/admin/payouts/*` | Protégé par `auth` seulement (commentaire « + ton middleware admin » jamais fait), et le contrôleur ne vérifie rien. **N'importe quel utilisateur connecté peut approuver, refuser ou marquer payée une demande de virement vendeur.** | Ajouter `admin.code`. |
| S4 | Haute (déjà connue) | `/admin/users`, `/admin/users/{user}` | Sans `admin.code` (voir la mémoire du projet, décision en attente). Le bloc Akwaba prévu sur la fiche utilisateur y serait visible publiquement. | À corriger avant l'étape 4, sinon je mets le bloc Akwaba uniquement sur `/admin/akwaba`. |
| S5 | Moyenne | `lockForUpdate()` sur SQLite (Prince de Yop, marketplace, wallet…) | Sans effet sur SQLite. Les transactions sont « deferred » : deux requêtes concurrentes peuvent lire le même état avant d'écrire. En pratique, ça donne surtout des erreurs `database is locked` plutôt que des doubles crédits, grâce aux contraintes d'unicité. | Pour Akwaba : UPDATE conditionnel + unicité. Pour le reste : hors périmètre, à signaler. |
| S6 | Faible | Webhook marketplace | Se fie au corps signé sans revérifier via l'API. C'est acceptable, puisque la signature HMAC est contrôlée. | Akwaba revérifie quand même, comme demandé. |
| S7 | Faible | `/paystack-inline-mock.js` | `postMessage(..., "*")` | Hors périmètre. |
| — | Info | En-têtes | Aucune CSP n'est posée par l'application aujourd'hui. Il n'y a donc rien à assouplir pour le jeu (scripts inline, `data:`, Web Audio). Je n'ajoute pas de CSP globale ; sur `/fichier` je pose seulement `X-Frame-Options: SAMEORIGIN` et `X-Content-Type-Options: nosniff`. | — |

Je propose de corriger S1, S2 et S3 dans un commit séparé pendant l'étape 5. Ce sont de petits correctifs, mais ils touchent d'autres jeux, donc j'attends ton accord (**Q5**).

## 5. Limite connue (non corrigée maintenant)

Le jeu entier (chapitres 3 à 7 compris) est dans un seul fichier HTML public. Quelqu'un qui sait utiliser la console peut forcer l'ouverture des chapitres côté client. Les paiements, le solde d'indices et la progression serveur restent protégés.

**Proposition v2 (non codée)** : découper le jeu en deux. Le fichier public contient le moteur + prologue + chapitres 1-2. Les chapitres 3-7 et les fins (texte, énigmes, images) vont dans un second paquet JSON servi par `GET /jeux/akwaba/contenu/{chapitre}`, réservé aux utilisateurs qui ont débloqué l'histoire (contrôle d'accès + `Cache-Control: private`). Le jeu charge ce paquet après réception de `state.unlocked = true`. Les **réponses des énigmes** et les **indices** sont vérifiés et servis par le serveur (`POST /jeux/akwaba/indice` renvoie le texte de l'indice après décrément), pour qu'ils ne soient jamais dans le client. Il faut modifier le jeu, donc c'est une étape à part.

## 6. Questions avant de coder

- **Q1 : fichier du jeu.** (a) Je le place dans `resources/games/akwaba.html` et je le versionne (comme Prince de Yop ; +6 Mo dans l'historique git à chaque mise à jour du jeu) ; ou (b) il reste dans `storage/app/games/` hors git, et tu le copies à la main sur le VPS (`scp`) à chaque version. **Je recommande (a)** pour la simplicité du déploiement. Dans les deux cas, copie d'abord `akwaba.html` et `pont-exemple.html` dans le projet : ils n'y sont pas actuellement.
- **Q2 : checkout Mobile Money perso.** `config/checkout.php` l'active par défaut pour `marketplace`. Sur le VPS, `CHECKOUT_MM_MARKETPLACE` est-il à `false` (« en pause ») ? Pour Akwaba, je propose d'utiliser **uniquement la page Paystack hébergée** (canal Mobile Money), sans passer par le checkout perso, et de prévoir un `AkwabaPayable` plus tard.
- **Q3 : vendeur du produit.** Le produit `akwaba` doit-il appartenir à ton compte admin (pas de part vendeur) ? Est-il éligible à l'affiliation (`affiliate_eligible`) ?
- **Q4 : indice gratuit.** Je confirme que l'indice gratuit par partie reste géré par le jeu seul, sans trace côté serveur ?
- **Q5 : corriger S1, S2 et S3** dans le même chantier (commit séparé) ?
- **Q6 : S4 (`/admin/users` ouvert).** Je le corrige maintenant, ou je mets le bloc Akwaba uniquement sur `/admin/akwaba` ?

---

## 7. Plan de test manuel (à dérouler après livraison)

1. Navigation privée, sans compte : `/jeux/akwaba` → jouer prologue, chapitres 1 et 2 → le cadenas apparaît.
2. « Continuer l'aventure » → redirection vers la connexion → retour sur `/jeux/akwaba`.
3. Re-cliquer → page Paystack (mode test, Mobile Money) → payer 1 000 F → retour `?paid=1` → « Paiement en cours de validation… » → le jeu passe tout seul au chapitre 3.
4. Utiliser l'indice gratuit → la boutique s'ouvre → « Acheter sur Boursiv » → payer 500 F → « Indice (5) » → consommer un indice → « Indice (4) ».
5. Se connecter avec le même compte sur un autre téléphone → la progression et les fins reviennent, déblocage actif, « Indice (4) ».
6. Admin `/admin/akwaba` : le joueur apparaît au bon chapitre, CA = 1 500 F, ledger `+5` puis `−1`. Ajouter 3 indices à la main → « Indice (7) » après rechargement.
7. Rejouer le webhook depuis le tableau de bord Paystack → aucun double crédit.
