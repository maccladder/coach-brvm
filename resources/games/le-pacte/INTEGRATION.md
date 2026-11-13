# Le Pacte avec les caïmans de Yamoussoukro — intégration

Jeu en un seul fichier `index.html` + dossier `img/`. Aucune dépendance, aucun build.
Polices : Google Fonts (Alegreya, Alegreya SC, Caveat). Sons générés en Web Audio.

## Structure
- Partie gratuite : prologue, chapitre 1, chapitre 2 (jusqu'à l'écran « Fin de la partie gratuite »).
- Partie payante : chapitres 3 à 7 et les 5 fins. Prix prévu : 1 000 FCFA.
- Indices : 1 indice gratuit à vie par joueur, puis pack de 5 indices à 500 FCFA.

## Pont avec le site : `window.PacteHost`
Le jeu lit `window.PacteHost` au chargement. Il doit donc être défini AVANT le script du jeu.
Sans cet objet, le jeu tourne en mode test local (sauvegarde et indices en localStorage, déblocage gratuit).
Toutes les méthodes sont facultatives.

| Méthode | Retour | Rôle |
|---|---|---|
| `charger()` | `Promise<{point, etat} \| null>` | Sauvegarde du joueur connecté |
| `sauver(data \| null)` | — | Enregistre la sauvegarde (appelée à chaque début de chapitre) ; `null` = effacer (fin de partie) |
| `estDebloque()` | `Promise<boolean>` | Le joueur a-t-il payé la suite ? |
| `debloquer()` | `Promise<boolean>` | Lance le paiement de la suite ; `true` si payé |
| `indices()` | `Promise<number>` | Indices restants (1 offert à la création du joueur) |
| `utiliserIndice()` | `Promise<number>` | Consomme un indice côté serveur, renvoie le reste |
| `acheterIndices()` | `Promise<number>` | Lance l'achat du pack de 5 ; renvoie le nouveau solde (0 si échec) |
| `fin(cle, choix)` | — | Fin obtenue (`pacte`, `justice`, `argent`, `gardien`, `sacrifice`) et liste des choix, pour les statistiques |
| `lang` (propriété) | `'fr'` ou `'en'` | Langue par défaut si le joueur n'a encore rien choisi |
| `langue(code)` | — | Appelée quand le joueur change de langue dans le jeu (avant le rechargement) |
| `test` (propriété) | `boolean` | Vrai seulement en local : autorise les raccourcis `#test-…` et `#debug` |

`data.point` vaut `gare`, `villa`, `funerailles`, `chap3` … `chap7`. `data.etat` est un objet JSON (environ 5 à 10 Ko) à stocker tel quel.

Sécurité : le déblocage et le solde d'indices doivent être vérifiés côté serveur. Le fichier contient tout le jeu ;
pour protéger vraiment la partie payante, servir `index.html` et `img/` complets seulement aux comptes débloqués,
ou accepter le même niveau de protection qu'Akwaba.

## Raccourcis de test (à désactiver en production)
`#test-gare`, `#test-chap1` … `#test-chap7`, `#test-chap3fouille`, `#test-chap7gardien`, `#test-chap7sacrifice`, `#test-chap7argent`, `#test-maquis`, `#debug`.
Ils sont lus dans la constante `TEST`, à la fin du script.

## Constantes utiles dans le script
`PRIX_SUITE`, `PRIX_INDICES`, `PACK_INDICES`, `CLE_SAUVE`.

## Langues (français / anglais)
Le jeu est écrit en français. La version anglaise est un dictionnaire embarqué dans `index.html`
(`<script type="application/json" id="i18nEn">`, entre les repères `I18N:DEBUT` et `I18N:FIN`), suivi d'un petit moteur
qui remplace à l'écran chaque texte français par son équivalent anglais. Un texte absent du dictionnaire reste en français :
rien ne casse si on modifie un dialogue sans mettre le dictionnaire à jour.

- Choix de la langue, par ordre de priorité : `?lang=en` dans l'adresse du jeu, puis `localStorage['pacte-caimans-lang']`,
  puis `PacteHost.lang`, puis la langue du navigateur (`en…` donne l'anglais, tout le reste le français).
- Le bouton « English / Français » de l'écran titre enregistre le choix et recharge le jeu.
- La sauvegarde (`etat`) reste en français quelle que soit la langue : une partie commencée en français se reprend en anglais, et inversement.
- `?lang=audit` : le moteur tourne sans rien remplacer et écrit dans la console (`I18N-MISS|…`) les textes sans traduction.

## Images
Les images des scènes sont en `data-src` dans le HTML : elles ne sont demandées que quand la scène ou le gros plan s'ouvre,
puis le jeu précharge discrètement les deux scènes suivantes. Les JPEG sont progressifs. Aucun nom de fichier n'a changé.

## Téléphone
- Téléphone tenu debout (fenêtre du jeu en portrait, moins de 700 px de large) : l'écran titre a une mise en page verticale.
  Dès que la partie commence, la scène est couchée (rotation de 90°) et prend tout l'écran : le joueur tient son téléphone
  à l'horizontale, même si la rotation de l'écran est verrouillée. Classe `pivot` sur `<body>`.
- Pour désactiver ce mode : `PacteHost.pivot = false` (la scène reste alors droite et petite en portrait).
- L'iframe du jeu doit occuper toute la hauteur disponible sous la barre du site, en portrait comme en paysage.
- Petite scène (moins de 760 px de large) : l'objectif s'efface après 7 secondes, Menu et Son deviennent des icônes,
  « Fermer » devient une croix, l'inventaire tient sur une ligne.
