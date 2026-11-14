# Prochain lot UI/UX — décisions et propositions

Décisions de référence du lot 0.1.14-dev, dont le développement puis les six corrections ont été autorisés le 14 novembre 2026. [Preuves du candidat initial](lot-v0.1.14-dev.md), [état de la réception corrective](corrections-v0.1.14-dev.md). Push du code explicitement autorisé le même jour après présentation du candidat ; tag et publication de release en attente d’un accord distinct.

## Accueil Documents — validé le 14 novembre 2026

- Lorsqu’aucun dossier n’est sélectionné, conserver l’arborescence uniquement dans le menu latéral et présenter un accueil central léger.
- Pictogramme de dossier centré au-dessus du titre **Ouvrez un dossier**. Après recette, le propriétaire demande de réduire le dessin initial de 160 px et de le rendre plus stylisé/plein. Le feu vert du lot correctif valide la proposition de **80 × 80 px**, dossier plein Navy avec accent turquoise et espacement inférieur de 16 px ; proportions préservées et adaptation mobile. Il s’agit d’un pictogramme d’interface, sans modification du logo officiel.
- Texte exact : **Sélectionnez un dossier dans le menu latéral pour consulter ses documents ou créer un nouveau dossier.**
- Bouton **Créer un dossier** sous le texte, dans la palette FileWarden.
- Cet accueil ne porte pas les mentions Dossier actuel ou Non publié. Le statut Non publié reste attaché à Documents à classer.
- Le contenu d’un dossier sélectionné et celui de Documents à classer conservent leur fonctionnement et leurs contrôles d’accès.
- Si aucun dossier ordinaire n’existe, adapter le titre en Créez votre premier dossier.

## Arborescence — Hiérarchie guidée validée le 14 novembre 2026

Le propriétaire retient explicitement la deuxième variante **Hiérarchie guidée** pour le prochain lot :

- Repères verticaux discrets pour suivre la filiation et indentation régulière des sous-dossiers.
- Dossier sélectionné en Deep Navy avec texte et pictogramme blancs ; ancêtres mis en valeur pour faciliter le repérage.
- Chevrons de dépliage séparés de l’action de sélection du dossier.
- Correction après recette : lignes simples légèrement plus compactes (40 px minimum sur pointeur fin, 44 px sur pointeur tactile), marges verticales réduites ; hauteur libre pour les noms sur plusieurs lignes.
- Bouton secondaire **Créer un dossier** en bas du panneau, conformément à la variante retenue ; pictogramme et texte séparés par **10 px**, alignement vertical centré, pictogramme de 18–20 px et cible tactile d’au moins 44 px.

Conserver le compteur autorisé et le statut de Documents à classer, les états de sélection, le dépliage et la navigation. Aucun compteur supplémentaire, contrôle de permissions ni comportement de création nouveau ne découle de cette amélioration visuelle. L’accueil et l’arborescence sont intégrés au candidat local 0.1.14-dev.

## Notifications globales — validées le 14 novembre 2026

Ajouter une véritable refonte visuelle et fonctionnelle des notifications à toute l’administration FileWarden. [Spécification complète et critères de réception](specifications/notifications-globales.md).

- Composant réutilisable commun à toutes les opérations ; quatre types avec pictogramme, titre, description éventuelle et fermeture manuelle.
- Cartes flottantes en haut à droite, pile sans chevauchement, adaptations mobiles et animations discrètes.
- Réglages → Général → Notifications : durée de 5, 10, 15, 20 ou 30 secondes, **10 secondes par défaut**, conservée après actualisation et mise à jour.
- Chaque carte temporaire porte une barre régressive synchronisée au temps restant. Pause au survol, au focus et lorsque la page est inactive ; reprise sans remise à zéro.
- Les erreurs critiques et demandes d’intervention peuvent rester persistantes, sans barre temporelle trompeuse.
- Permissions, confirmation serveur, confidentialité, FR/EN, accessibilité et tests de non-régression conservés.

L’aperçu et son intégration sont validés, avec trois cartes visibles et accès aux notifications supplémentaires conservées. Cette demande remplace la durée fixe de 12 secondes de 0.1.13-dev et est intégrée au candidat local 0.1.14-dev. Le choix de 5 secondes essayé dans la maquette ne change pas le défaut validé de 10 secondes.

La correction demandée après recette place les cartes en **superposition non modale**, hors du flux et des conteneurs de la page, sous la barre WordPress. Les avis FileWarden sont isolés des classes génériques d’avis WordPress et restent au-dessus du dialogue de prévisualisation lorsqu’il est ouvert. Aucun déplacement de la page ni vol de focus.

## Réglages — Rail compact validé puis autorisé le 14 novembre 2026

- Navigation verticale conservée et rendue plus compacte, avec pictogrammes et libellés ; catégorie sélectionnée Navy/texte blanc.
- **Descriptif sous le titre de la catégorie dans le panneau de droite**, et non dans le menu.
- Changement de panneau local sans rechargement complet ni perte des champs saisis ; clavier et petits écrans pris en charge.
- Métadonnées des cinq catégories centralisées et extensibles ; les champs, contrôles de droits et sauvegardes restent ceux des réglages existants.
