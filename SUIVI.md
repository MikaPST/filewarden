# FileWarden — suivi court

Mis à jour le 15 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.15-dev publiée le 15 novembre 2026**, après autorisation conditionnelle du propriétaire et réussite complète des tests : [release, notes et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.15-dev). Tag annoté fixé au commit **cd9efd77db506e9067dff4296ed1f082238e078b** ; le suivi documentaire ultérieur ne déplace pas ce tag.
- Les **neuf demandes sont intégrées** : réparation MyISAM/InnoDB contrôlée, PNG d’accueil seul, logo officiel, pictogrammes des sources, audit simplifié coloré, purge harmonisée, onglets compacts soulignés, bandeau d’état synthétique et icône de dossier sécurisé **en contour**. [Cahier validé](docs/prochain-lot-v0.1.15-dev.md), [matrice et procédure](docs/lot-v0.1.15-dev.md).
- **CI finale entièrement réussie** : [main, 12 jobs](https://github.com/MikaPST/filewarden/actions/runs/43641557456) ; [tag et publication, 13 jobs](https://github.com/MikaPST/filewarden/actions/runs/43687379324). PHP 8.2–8.4 : 75 tests/130 assertions chacun ; huit tests JavaScript ; syntaxe, PHPCS, audit Composer ; huit parcours de mise à jour depuis 0.1.11/12/13/14-dev sur WordPress 6.8 et 7.1.4/MySQL 8.0.46, intégration/HTTP et Chromium/Firefox EN/FR. Réparation MyISAM et refus de sécurité vérifiés. [Preuves CI](docs/validation/v0.1.15-dev-ci.json), [publication](docs/validation/v0.1.15-dev-release-ci.json).
- **ZIP public téléchargé et contrôlé** : 290 fichiers identiques au tag, version et schéma 6, PNG original, notes et checksum vérifiés. SHA-256 **db4a3d80811ef6b8bed1fee58a6bd4178f37f1899c38b18531a0810b0a842fa0**. Le workflow publie l’archive exacte installée par son job WordPress 6.8 depuis 0.1.14-dev. [Preuve publiée](docs/validation/v0.1.15-dev-published.json).
- La première CI a révélé un débordement Firefox/Linux à 320 px, un aperçu périmé après changements rapides et des attentes de navigation à corriger. Ces échecs et leurs corrections sont conservés séparément ; les matrices finales sont réussies. [Historique](docs/validation/v0.1.15-dev-ci-initial.json), [recette corrective locale](docs/validation/v0.1.15-dev-corrections-local.json), [tests](docs/testing.md).
- **Aucune installation ni migration sur le site du propriétaire.** Ses trois tables sont MyISAM : après sauvegarde complète (base, stockage, clé), installer le ZIP puis ouvrir **Réglages → Stockage et sécurité → Vérifier et réparer les tables de la base**, confirmer la maintenance et contrôler la suppression d’un dossier feuille. Aucune conversion silencieuse à la mise à jour ; une réparation incomplète conserve son point de reprise et bloque les écritures jusqu’à reprise vérifiée. Schéma 6 et identités/droits/documents conservés.
- Préversion de développement uniquement ; réception sur l’hébergement et recette humaine complète au lecteur d’écran restent distinctes. Le lot est terminé ; attendre les prochaines demandes validées avant un nouveau développement.

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

Références : [spécifications](docs/specifications/requirements.md), [droits](docs/permissions.md), [sécurité](docs/security.md), [feuille de route](docs/roadmap.md), [déploiement](docs/deployment.md).
