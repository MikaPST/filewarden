# Refonte globale des notifications — prochain lot

Exigences et aperçu visuel validés par le propriétaire le 14 novembre 2026 pour intégration au prochain lot. Cette spécification ne décrit pas une fonctionnalité déjà livrée dans 0.1.13-dev.

## Périmètre commun

Un composant réutilisable pour toutes les pages d’administration FileWarden : création, modification, déplacement et suppression de documents/dossiers ; téléversement et publication ; droits d’accès ; restauration ; réglages ; export et purge d’audit ; erreurs techniques, refus d’accès et indisponibilités. Recenser chaque point d’entrée existant pour éviter les messages oubliés ou les doublons. Le fonctionnement des opérations, leurs permissions et leurs confirmations restent conservés.

Le composant harmonise les notifications propres à FileWarden, sans transformer les avis d’autres extensions ou du cœur WordPress. Les erreurs de champ restent associées à leur champ ; le retour global complète ces informations sans les remplacer.

## Quatre types et présentation

| Type | Traitement visuel | Usage |
|---|---|---|
| Succès | Vert, icône de validation | Opération effectivement confirmée |
| Information | Bleu, icône d’information | État ou progression d’une opération |
| Avertissement | Orange, icône d’attention | Situation à examiner ou action nécessaire |
| Erreur | Rouge, icône d’erreur | Refus ou échec expliqué sans détail confidentiel |

Chaque carte comprend une icône, un titre court explicite, une description si utile, une fermeture manuelle × et, seulement si elle est temporaire, une barre de progression régressive en bas sur toute sa largeur. Les couleurs sont des couleurs fonctionnelles compatibles avec la charte FileWarden, avec contraste lisible et sens également porté par le texte et l’icône.

Cartes flottantes en haut à droite de l’administration FileWarden, sous la barre WordPress, coins légèrement arrondis, ombre légère, icône à gauche, texte au centre et fermeture à droite. Apparition/disparition discrètes, respect de la préférence de réduction des animations. Prévoir leur emplacement sans couvrir les commandes essentielles ; sur petit écran, adapter leur largeur et leur placement pour préserver les actions et la lecture.

## Réglage obligatoire

Dans **Réglages → Général → Notifications**, ajouter **Durée d’affichage automatique** avec les seules valeurs **5, 10, 15, 20, 30 secondes**. **Défaut : 10 secondes.**

Texte d’aide exact : « Définissez la durée pendant laquelle les notifications restent visibles avant de disparaître automatiquement. »

Enregistrer ce paramètre dans les options WordPress avec autorisation de réglages, nonce et validation stricte côté serveur de la liste autorisée. Le conserver après actualisation et mise à jour. Sauvegarder ce champ ne réinitialise aucune autre catégorie ; un formulaire ne contenant pas ce champ conserve sa valeur existante.

Cette nouvelle demande remplace la durée fixe de 12 secondes de 0.1.13-dev et autorise explicitement le choix 5 secondes, malgré l’ancien minimum de 10 secondes. Ne pas réécrire les preuves historiques pour les faire correspondre à cette nouvelle règle.

## Temps et progression

- Barre initialement pleine à 100 %, puis diminution fluide de 100 à 0 correspondant au temps restant effectif.
- À 0, fermeture avec animation discrète ; pas de disparition anticipée ni de pause déconnectée de la barre.
- Pause au survol et lorsque la carte ou un contrôle interne reçoit le focus clavier.
- Reprise au temps restant, sans remise à zéro ; les motifs de pause se combinent. Quitter le survol ne relance pas une carte encore au focus, par exemple.
- Pause également lorsque l’onglet est masqué ou la fenêtre inactive ; au retour, conserver le temps restant, sans rattrapage brutal du temps passé hors lecture.
- La fermeture manuelle reste immédiate. Utiliser une horloge monotone ; limiter la mise à jour des annonces accessibles et éviter de faire lire chaque changement de pourcentage.
- Chaque notification conserve son propre temps. Proposition pour le changement de réglage : appliquer la nouvelle durée aux notifications suivantes, sans faire sauter la progression des cartes déjà affichées.

Les erreurs critiques et messages exigeant une intervention restent jusqu’à fermeture manuelle ou résolution réelle de la situation. **Aucune barre de décompte sur une notification persistante.** Le type seul ne doit pas imposer arbitrairement une fermeture : distinguer importance et caractère temporaire/persistant.

## Notifications multiples

Empiler sans chevauchement, garder les nouvelles notifications repérables, fermer une carte sans modifier les temps des autres et éviter la saturation. Ne perdre ni une erreur ni un message nécessitant une action.

**Présentation retenue après validation de l’aperçu :** trois cartes visibles, plus récentes en premier, et un accès explicite aux cartes supplémentaires conservées. Les cartes non visibles ne consomment pas leur temps de lecture. Afficher distinctement l’existence d’erreurs persistantes masquées ; ne jamais les effacer pour libérer une place. L’accès à la liste complète doit rester utilisable au clavier et sur mobile.

## Fiabilité, confidentialité et accessibilité

- Aucun succès avant confirmation effective du serveur et de l’état réellement obtenu. Un démarrage d’action peut recevoir une information « Préparation… », pas un succès anticipé.
- Pour un export, distinguer fichier reçu et téléchargement demandé au navigateur de l’enregistrement effectif sur le disque, que le plugin ne peut pas garantir. Conserver les véritables téléchargements CSV/TXT/JSONL.
- Messages FR/EN via les traductions WordPress ; données affichées comme texte sûr, sans injection HTML ni divulgation de chemins, erreurs SQL, secrets ou métadonnées non autorisées.
- Annonces polies pour les retours ordinaires, annonces adaptées aux erreurs urgentes. Ne pas voler le focus à l’apparition ; gérer proprement le focus après fermeture d’un contrôle actif.
- Navigation clavier, libellé explicite de fermeture, contraste des quatre types, réduction des mouvements et disposition responsive.
- Respect des protections serveur, des nonces, des autorisations, du stockage et de l’audit ; aucun mécanisme de notification ne constitue une autorisation.
- En l’absence de JavaScript, conserver un retour accessible et persistant. Une erreur réseau ou une réponse expirée ne doit jamais devenir un succès visuel.

## Critères et essais de réception

1. Inventaire puis réception des opérations de chaque page FileWarden, incluant documents, dossiers, publication, droits, corbeille, réglages et audit.
2. Quatre types visuellement distincts avec icône, titre, description éventuelle et fermeture manuelle.
3. Paramètre présent dans Général/Notifications ; seules les cinq valeurs autorisées sont acceptées. Réglage absent : 10 secondes.
4. Vérifier chaque durée avec une horloge contrôlée : 100 % au départ, 50 % à mi-parcours, 0 % à l’expiration puis fermeture. La préférence de réduction des mouvements ne change pas le temps restant.
5. Pause survol, focus, combinaison des deux, changement d’onglet/fenêtre ; reprise sans remise à zéro ni disparition immédiate au retour.
6. Notifications persistantes toujours conservées, sans barre temporelle trompeuse ; fermeture manuelle et résolution contrôlée.
7. Plusieurs notifications avec temps indépendants, ajout/fermeture pendant une pause, ordre cohérent, surplus accessible et absence de chevauchement.
8. Conservation du paramètre après actualisation et mise à jour ; sauvegarde partielle sans perte des autres valeurs ; refus des valeurs forgées et des demandes non autorisées ou sans nonce.
9. Vérifier les messages sur réussite, refus serveur, erreur réseau et réponse expirée ; aucun succès simulé par la seule fin d’un transfert.
10. Vérifier clavier, focus, annonces accessibles, FR/EN, petits écrans et réduction des mouvements ; compléter les contrôles automatisés par une recette humaine au lecteur d’écran.
11. Rejouer les tests fonctionnels et de sécurité existants. Inspecter le ZIP réellement installé avant de déclarer cette évolution livrée.

## Écart avec la version actuelle et plan du lot

0.1.13-dev dispose déjà de Notice.php/notices.js, de quatre classes de messages, de fermeture et d’une barre pour les succès temporaires. Le délai reste fixé à 12 secondes et l’apparence est celle d’avis intégrés à la page : cela ne satisfait pas cette refonte.

Lots techniques : (1) modèle de message commun et option validée sans perte de réglages ; (2) cartes, pile et horloge par notification ; (3) raccordement de tous les points d’entrée et suppression des retours doublonnés ; (4) réception de toutes les opérations, accessibilité, sécurité, langues, mobile et mise à jour du ZIP. Développement autorisé et intégré au candidat local 0.1.14-dev ; voir [le candidat initial](../lot-v0.1.14-dev.md) et [la réception corrective](../corrections-v0.1.14-dev.md). Après recette, le placement demandé est explicitement une superposition non modale sous la barre WordPress, sans modifier la largeur du contenu. Les classes FileWarden sont indépendantes des avis génériques WordPress ; la région flottante reste visible dans la couche du dialogue de prévisualisation. Aucun push ou publication sans l’accord distinct du propriétaire.
