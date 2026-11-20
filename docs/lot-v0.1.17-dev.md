# FileWarden 0.1.17-dev — demandes en cours de collecte

Première demande reçue le 20 novembre 2026, pendant la finalisation de la 0.1.16-dev. Ce document prépare le lot suivant ; son développement n’a pas encore été lancé.

## 1. Couleurs des boutons secondaires identiques aux exports du journal

Le propriétaire remplace son choix précédent de turquoise plein par le style déjà utilisé pour les exports CSV/TXT/JSONL :

- **État normal** : même fond clair légèrement teinté, même texte/pictogramme Navy et même bordure que TXT et JSONL dans la capture fournie.
- **Survol** : mêmes fond, bordure et texte/pictogramme que CSV survolé dans cette capture.
- Réutiliser les styles et jetons des exports existants plutôt qu’une approximation des couleurs de la capture. Conserver les pictogrammes appropriés à chaque action et le focus clavier accessible de FileWarden.

### Boutons concernés

L’inventaire actuel de `fw-button-accent` comprend les trois composants suivants :

| Bouton | Emplacement | Référence actuelle |
|---|---|---|
| Rechercher | Documents, recherche dans le dossier | `includes/AdminWorkspace.php` |
| Choisir dans la médiathèque | Ajouter des documents, source Médiathèque | `includes/AdminController.php` |
| Ouvrir le journal d’audit | Réglages → Journal d’audit | `includes/SettingsConsole.php` |

Style partagé actuel : `assets/admin-controls.css`. Appliquer le changement à toutes les occurrences de ces composants ; les fonctions de recherche, sélection et navigation restent identiques.

### Critères de réception

1. Les trois boutons reprennent les couleurs calculées des exports à l’état normal et au survol, y compris texte, pictogramme et bordure.
2. Le thème de couleur du profil WordPress ne modifie pas ces états.
3. Navigation clavier, focus Navy, état désactivé, libellés FR/EN et petites résolutions restent utilisables.
4. Recherche, sélection dans la médiathèque, ouverture du journal et téléchargements d’export conservent leur fonctionnement.

## 2. En-tête : logo plus grand et nom rééquilibré — proposition à valider

Le propriétaire trouve le logo 0.1.16-dev trop petit et demande de retrouver la taille de la 0.1.15-dev, en augmentant aussi FILEWARDEN pour rééquilibrer l’identité visuelle. Un visuel est demandé avant validation.

Proposition de départ :

- Logo officiel à **56 × 56 px** sur ordinateur et **44 × 44 px** sur mobile, conformément à la 0.1.15-dev ; asset et proportions inchangés.
- FILEWARDEN à **20 px** sur ordinateur et **18 px** sur mobile, au-dessus du titre.
- Titre de page conservé à **27 px** sur ordinateur et **23 px** sur mobile.
- Logo à gauche du bloc nom + titre, avec espacement et alignement équilibrés ; navigation existante conservée.

Cette proposition reste en attente de validation du visuel ; ne pas la traiter comme une décision définitive ni commencer son intégration.

## 3. Arborescence : davantage de largeur, lignes plus compactes

Demande du propriétaire : le panneau Dossiers est trop étroit ; l’élargir pour rendre les libellés et l’arborescence plus confortables. Réduire légèrement la hauteur et l’espacement vertical des lignes afin de voir davantage de dossiers simultanément.

- Conserver le design de hiérarchie guidée, les niveaux, les guides verticaux et les pictogrammes.
- Prévoir une largeur mieux adaptée à l’espace disponible ; aucune largeur précise n’a encore été validée.
- Réduire raisonnablement les espacements/paddings des lignes, sans rogner les cibles tactiles ni la navigation clavier.
- Garder le retour à la ligne des noms longs, sans débordement, et préserver l’espace utile pour la liste des documents et l’inspecteur.
- Vérifier grands/petits écrans, dossiers imbriqués et longs libellés ; le comportement d’ouverture, la sélection et les permissions restent inchangés.

Les autres demandes du lot seront ajoutées au fil de leur validation. Aucun changement de code ni publication 0.1.17-dev n’est engagé par cette collecte.
