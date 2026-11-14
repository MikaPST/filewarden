# FileWarden — suivi court

Mis à jour le 14 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **Candidat correctif final vérifié localement** : [preuves](docs/validation/v0.1.15-dev-corrections-local.json), 290 fichiers identiques aux sources et aux deux ZIP installés, SHA-256 57bafc38e30ca435bd0c226096b0d7da9476c28c20cef5c31657d1da442e0e17. Recette complète 6.8 EN/FR réussie ; installation/intégration 7.1.4 et vérifications ciblées aperçu rapide/FR/320px réussies. Le nettoyage cron se reporte en cas de maintenance/occupation, sans toucher aux données ni interrompre les callbacks WordPress suivants ; contrôle sur les deux versions réussi. La CI de ce dernier ajustement doit être entièrement réussie avant tag/release. Les preuves des candidats précédents restent historiques.

- **CI initiale f7db9f4 non validée, aucune release ni tag créé** : [exécution 43639555618](https://github.com/MikaPST/filewarden/actions/runs/43639555618), quatre jobs réussis (PHP 8.2–8.4, JS), huit parcours navigateur échoués. Causes : débordement de réparation à 320 px sous Firefox/Linux, aperçu lors de changements successifs et synchronisation de la confirmation de restauration. [Échecs et corrections](docs/validation/v0.1.15-dev-ci-initial.json). Correctif : contraintes de largeur/date/bouton, aperçu immédiat regroupant les choix rapides et attentes explicites de navigation/POST. Nouvelle recette complète WordPress 6.8 EN/FR réussie ; stress d’aperçu Chromium/Firefox réussi. Vérification indépendante 7.1.4 en cours ; relancer la CI avant toute publication.

- **Lot 0.1.15-dev : GO global reçu le 14 novembre 2026**, avec autorisation explicite anticipée de push, tag et release **conditionnée à la réussite des tests**. Dernier choix : dossier sécurisé **en contour** pour le menu WordPress ; les essais de dossier plein sont abandonnés.
- Les neuf demandes sont développées : réparation MyISAM/InnoDB contrôlée, PNG d’accueil seul, logo officiel, sources ordinateur/bibliothèque, audit simplifié coloré, purge harmonisée, onglets soulignés, bandeau d’état synthétique et icône de menu. [Cahier validé](docs/prochain-lot-v0.1.15-dev.md), [matrice et sécurité de réparation](docs/lot-v0.1.15-dev.md), [notes](docs/releases/v0.1.15-dev.md).
- Schéma **6** conservé. Nouvelles tables explicitement InnoDB ; anciennes tables MyISAM réparables volontairement dans Stockage et sécurité après sauvegarde. Pas de conversion silencieuse à la mise à jour. Point de reprise conservant les écritures bloquées en cas de conversion incomplète ; aucune réinitialisation de données ou déplacement des fichiers.
- **Recette locale entièrement réussie** : 75 tests PHP / 130 assertions, huit tests JavaScript, syntaxe, PHPCS, audit Composer et secours Sodium ; mises à jour depuis 0.1.14-dev en MyISAM, intégration/HTTP et Chromium/Firefox EN/FR sur WordPress 6.8 et 7.1.4 jetables, MariaDB 10.11.11/PHP 8.2.35. Réparation française à 375/320 px, reprise, refus de divergence, réactivation et verrou concurrent vérifiés. [Preuve locale](docs/validation/v0.1.15-dev-local.json). ZIP final : 290 fichiers identiques aux sources et aux deux installations, SHA-256 a8f0c1d0f9b3285473c079bc5884aab9c29d979a0572a731080f7cf96a636364. CI et publication à effectuer ; ne pas les annoncer réussies avant preuve.
- Banc navigateur corrigé : attente Firefox bornée, navigation technique explicite et menu replié vérifié à partir de son état réel ; horloge fixe des notifications isolée dans son propre contexte pour ne pas figer les minuteurs WordPress des suites suivantes. Les échecs préliminaires restent distincts de la recette finale.
- **0.1.14-dev publiée** sur autorisation : [release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.14-dev), tag c51f6e5 ; SHA-256 public 9a887e3bca9dc7e9ab9ec0505fb6c3eb4efac9c714934f8500a4c3b7fb91023b. [Preuve publiée](docs/validation/v0.1.14-dev-published.json). Le détail historique est conservé dans les docs de validation.
- Le site du propriétaire utilise bien trois tables MyISAM, d’après ses définitions complètes. **Aucune migration ni modification de son hébergement exécutée.** La réparation et la suppression devront y être vérifiées après sauvegarde et installation. Préversion uniquement ; aucune certification de sécurité ni recette humaine complète au lecteur d’écran.

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
