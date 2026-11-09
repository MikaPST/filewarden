# Lot proposé — navigation et cohérence des interfaces

Statut : périmètre complet, ajouts, modalités de suppression et conséquences pour les anciens fichiers explicitement validés par l’utilisateur. Développement autorisé le 9 novembre 2026 ; publication non autorisée. Les maquettes ne modifient pas le plugin.

## Périmètre demandé

- Dossiers : remplacer le symbole actuel par une véritable icône de dossier en contour ; supprimer « Racine » de l’usage courant.
- Panneau d’action : afficher l’icône du type à côté du nom du fichier ; raccourcir les explications de droits.
- Navigation frontend : sous-dossiers accessibles, parcours descendant et retour par fil d’Ariane ; masquer entièrement les dossiers/fichiers non autorisés.
- Héritage : nouveaux sous-dossiers et fichiers héritent dynamiquement de leur parent ; restrictions supplémentaires possibles ; intersection avec tous les ancêtres conservée.
- Accès refusé : encart soigné, Carte centrée validée par défaut ; choix personnalisable par publication entre Compact, Carte centrée et Bandeau éditorial demandé. Visiteur anonyme invité à se connecter ; compte connecté sans droits informé sans promesse d’accès ni nouveau bouton de connexion. Aucun nom, contenu ou nombre de documents privés exposé.
- Publication : style cohérent avec Documents ; sélection du dossier ; boutons joints Liste/Grille avec icônes et libellés accessibles ; code court complet dynamique ; copie avec confirmation réelle ou message d’échec ; aperçu dynamique encadré avec états de chargement/erreur.
- Réglages : regroupement visuel des paramètres, diagnostic de stockage toujours visible et état explicite (validé, bloqué ou non vérifié).
- Audit : terminal lisible à gauche, filtres à droite ; types d’événements, plage de dates/heures avec fuseau affiché, pagination ; exports CSV/TXT respectant les filtres et les droits.

## Décisions validées

1. Sans Racine : accueil présentant les dossiers créés par l’utilisateur ; dossier obligatoire pour les nouveaux fichiers. Les dossiers de premier niveau restent créables via « Aucun dossier parent ». Conserver les anciens fichiers sans dossier dans une vue administrative transitoire « Documents à classer », sans perte de fichiers ; conserver les règles propres aux fichiers, tout en appliquant un blocage de publication à cette collection. La demande précisée par l’utilisateur remplace la compatibilité publique initialement proposée : cette collection spéciale sera non publiable. Les anciennes publications de la racine et les accès frontend directs aux fichiers à classer seront bloqués jusqu’à leur reclassement. Les identifiants sont conservés, pas l’accès public.
2. Nouveau dossier de premier niveau : droits explicites, présélection « Utilisateurs connectés » conservée ; nouveau sous-dossier : « Hériter du dossier parent » par défaut. Ne pas convertir silencieusement les politiques existantes.
3. Recherche frontend limitée au dossier actuellement ouvert pour ce lot ; navigation bornée au dossier publié et à ses descendants autorisés. Le fil d’Ariane ne révèle pas les parents extérieurs au dossier publié.
4. Texte commun : « Les règles les plus restrictives s’appliquent : celles du fichier et de son dossier. Un fichier “Public” reste privé si son dossier est restreint. » Aide de l’option : « Hériter : suivre automatiquement les droits du dossier. »
5. Accès restreint : Carte centrée par défaut, sélection graphique Compact / Carte centrée / Bandeau éditorial dans Publication. Enregistrer le choix par publication avec le code court et synchroniser l’aperçu ; proposer un aperçu de l’état inaccessible sans changer les droits. Connexion via WordPress hors du cadre de publication, puis retour à la page d’origine. Pour une adresse invalide ou un accès impossible, message neutre sans dévoiler l’existence d’un dossier privé ; incident technique distinct sans détail sensible.
6. Audit : exports CSV/TXT inclus. Ne pas importer de journaux dans le journal actif (traçabilité ambiguë). Purge manuelle demandée et incluse, en complément de la rétention automatique : autorisation administrative distincte de la lecture, périmètre explicite, nombre d’événements à supprimer, confirmation de l’irréversibilité et trace de purge conservée. Proposer l’export avant suppression.

## Ajout : collection de récupération et suppression de dossier

- « Documents à classer » est une collection système permanente, visuellement distincte (icône et badge « Non publié »), réservée à la gestion autorisée. Elle n’est ni publiable, ni supprimable comme un dossier ordinaire, ni soumise à la rétention de la corbeille.
- Ajout demandé : supprimer un dossier depuis son menu d’actions. Décision validée : si le dossier contient des fichiers, confirmation puis récupération dans « Documents à classer », sans effacement des fichiers ni de leurs UUID. Les documents déjà en corbeille doivent également conserver leur état et leur rattachement de récupération.
- Décision validée pour les sous-dossiers : bloquer la suppression tant qu’ils existent, puis traiter d’abord leur contenu et les supprimer explicitement ; pas de suppression récursive implicite.
- Préserver la restriction héritée pertinente lors de la récupération, pour que le reclassement ultérieur ne rende pas involontairement publics les fichiers auparavant privés. Le reclassement montre les droits résultants avant confirmation.
- Récupération et suppression doivent être cohérentes : en cas d’échec, aucun dossier supprimé ne laisse de documents orphelins ou exposés. La suppression du dossier et la purge des logs sont des actions distinctes, autorisées et tracées.

## Critères de validation proposés

- Un visiteur d’un dossier public voit les fichiers publics et les sous-dossiers autorisés, jamais les noms ou compteurs des éléments restreints.
- Un compte autorisé descend dans un sous-dossier, voit les fichiers permis et revient au dossier publié. Les liens directs, aperçus et téléchargements appliquent les mêmes règles.
- Modifier un parent actualise l’héritage ; aucune règle enfant ne contourne un parent restreint ; les politiques et références existantes sont préservées.
- Le choix dossier/Liste/Grille actualise code court et aperçu ensemble ; « Copié » n’apparaît qu’après une copie réussie.
- L’aperçu d’administration indique qu’il utilise les droits du compte connecté et ne représente pas nécessairement la vue d’un visiteur.
- Les filtres d’audit et les exports retournent le même périmètre ; un utilisateur sans droit d’audit n’obtient aucun journal ; les cellules CSV sont protégées contre l’interprétation de formules.
- Interfaces lisibles sur ordinateur/mobile, utilisables au clavier ; retours explicites en cas de refus, chargement ou erreur.

## Étape suivante

Périmètre complet validé, notamment la récupération des fichiers lors de la suppression d’un dossier, le blocage si sous-dossiers et le blocage des anciens accès aux fichiers à classer. Développement en cours après accord explicite. Organisation des vérifications regroupées et publication à convenir séparément.
