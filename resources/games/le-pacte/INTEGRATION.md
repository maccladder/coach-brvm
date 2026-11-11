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

`data.point` vaut `gare`, `villa`, `funerailles`, `chap3` … `chap7`. `data.etat` est un objet JSON (environ 5 à 10 Ko) à stocker tel quel.

Sécurité : le déblocage et le solde d'indices doivent être vérifiés côté serveur. Le fichier contient tout le jeu ;
pour protéger vraiment la partie payante, servir `index.html` et `img/` complets seulement aux comptes débloqués,
ou accepter le même niveau de protection qu'Akwaba.

## Raccourcis de test (à désactiver en production)
`#test-gare`, `#test-chap1` … `#test-chap7`, `#test-chap3fouille`, `#test-chap7gardien`, `#test-chap7sacrifice`, `#test-chap7argent`, `#test-maquis`, `#debug`.
Ils sont lus dans la constante `TEST`, à la fin du script.

## Constantes utiles dans le script
`PRIX_SUITE`, `PRIX_INDICES`, `PACK_INDICES`, `CLE_SAUVE`.
