# FileWarden — suivi court

Mis à jour le 20 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.16-dev publiée comme préversion le 20 novembre 2026**, tag `v0.1.16-dev`, commit `ed43ab88a2317a9c8dde19496ac02e58efb08d28`. [Release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.16-dev). Publication autorisée par le propriétaire après tests ; aucun déploiement sur son site.
- [Les dix améliorations du lot](docs/lot-v0.1.16-dev.md) sont implémentées, avec non-régression des dix évolutions originales. Langue globale FileWarden (automatique site, FR/EN, repli anglais), opt-in tous formats confirmé et audité, en-tête/notice/transferts/boutons/pastille/focus/santé de base. Tous formats désactivé par défaut ; contenus normalement refusés servis en pièces jointes opaques sans aperçu actif, droits/taille/stockage conservés. Schéma 6.
- **CI complète 15/15 réussie** sur le commit du tag : [run 44777904156](https://github.com/MikaPST/filewarden/actions/runs/44777904156), [preuve](docs/validation/v0.1.16-dev-ci.json). PHP 8.2.36, 8.3.37, 8.4.28, MySQL 8.0.46, WordPress 6.8, 7.1.5. Dix mises à jour réelles depuis 0.1.11/12/13/14/15-dev ; PHPUnit 76 tests/140 assertions sur chaque PHP, huit tests JS, 393 entrées FR/EN, installation neuve, intégration/HTTP et Chromium/Firefox EN/FR validés. Recette locale complète antérieure sur 6.8/7.1.4 conservée.
- **ZIP public vérifié** : 301 fichiers, SHA-256 `8c0054772640788ea0328bbf6275de108cf8cf56a6f5818ace92860ee4d49e1b`. Promotion de l’archive exacte installée/testée par la CI réussie ; octets identiques à cet artifact et aux blobs du tag. Checksum joint, empreintes GitHub et notes vérifiés. [Publication](docs/validation/v0.1.16-dev-publication.json), [vérification publique](docs/validation/v0.1.16-dev-published.json).
- Le workflow de publication distinct n’est **pas** déclaré réussi : installation WP-CLI indisponible sur certains runners avant les nouveaux tests, puis installateurs de navigateurs figés. Trois tentatives conservées et workflow annulé ; promotion du candidat de la CI 15/15 après contrôle frais des statuts, du tag, des digests et des fichiers joints. [Incident initial](docs/validation/v0.1.16-dev-release-ci-initial.json), [historique et méthode](docs/validation/v0.1.16-dev-publication.json). L’installation des outils CI reste un point de fiabilité pour les prochaines publications.
- Échec initial du banc Firefox corrigé sans retrait d’assertion ni changement runtime ; [diagnostic](docs/validation/v0.1.16-dev-ci-initial.json), [recette du correctif](docs/validation/v0.1.16-dev-browser-sync-local.json). Réparation MyISAM toujours volontaire après sauvegarde. Aucun site utilisateur ou base réelle modifié, aucune certification de sécurité ou revue humaine complète au lecteur d’écran revendiquée. Services jetables locaux arrêtés après vérification d’identité ; fixtures et preuves conservées.

## Organisation convenue

1. L’utilisateur transmet ses demandes regroupées par fonctionnalité.
2. L’assistant reformule les demandes, les décisions et les critères de réussite ; comportement et maquette éventuelle sont validés ensemble.
3. Le code commence seulement après l’accord explicite de l’utilisateur sur le lot.
4. Les contrôles sont ciblés puis regroupés selon l’organisation convenue ; seuls les résultats utiles et les erreurs pertinentes sont rapportés. Les contrôles de sécurité restent obligatoires.
5. Une seule release et un ZIP par lot validé, après les tests et un accord explicite de publication. Un push de code ou de documentation ne vaut pas accord de release ; ne pas pousser de tag avant cet accord.
6. Comptes rendus courts : réalisé, résultats, problèmes et décisions attendues. Mettre à jour ce fichier à chaque validation, fin de lot ou interruption significative ; remplacer l’état périmé au lieu d’accumuler un journal. Garder les détails historiques dans CHANGELOG.md et les commits.

## Décisions durables

- WordPress classique uniquement ; stockage persistant dans `wp-content/filewarden-storage`, séparé du plugin `wp-content/plugins/filewarden`.
- Droits : intersection du fichier et de tous ses dossiers parents. Public ne contourne jamais un dossier restreint. La lecture globale administrateur ne donne pas de droits de modification.
- Déplacement : UUID conservé. Copie : document et stockage indépendants. Corbeille : 30 jours par défaut, configurables.
- Déploiement par ZIP depuis WordPress ; ne pas réutiliser les anciens scripts FTP. Aucun test destructif sur les documents du site réel.
- Ne conserver ici aucun identifiant secret, mot de passe ou document personnel.

## Prochain lot — 0.1.17-dev

Demandes en cours de collecte, sans GO de développement. 1) Boutons Rechercher, Choisir dans la médiathèque et Ouvrir le journal aux couleurs des exports (normal TXT/JSONL, survol CSV). 2) Logo à la taille 0.1.15-dev et FILEWARDEN agrandi : proposition 56/44 px et nom 20/18 px soumise à validation visuelle. 3) Panneau Dossiers plus large et lignes de l’arborescence plus compactes ; aucune dimension précise validée. [Décisions et critères](docs/lot-v0.1.17-dev.md). Demande distincte de la 0.1.16-dev publiée ; aucun développement 0.1.17-dev engagé.

Références : [spécifications](docs/specifications/requirements.md), [droits](docs/permissions.md), [sécurité](docs/security.md), [feuille de route](docs/roadmap.md), [déploiement](docs/deployment.md).
