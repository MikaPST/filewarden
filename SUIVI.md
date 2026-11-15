# FileWarden — suivi court

Mis à jour le 15 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.16-dev en développement, GO global et autorisation conditionnelle de publication reçus le 15 novembre 2026.** Push, tag et release avec ZIP autorisés seulement après réussite de tous les contrôles. [Dix points de ce nouveau lot](docs/lot-v0.1.16-dev.md) ; les dix évolutions originales restent en non-régression. Aucun déploiement sur le site du propriétaire.
- Implémentés dans le candidat : réglages de langue FileWarden indépendants de WordPress (automatique site, FR/EN, repli anglais), opt-in tous formats confirmé et audité, pièces jointes opaques pour les contenus refusés par le validateur strict, contrôles de taille/droits/stockage inchangés ; schéma 6 conservé. En-tête, notice repliée, pastille hors zéro, transferts, boutons et états de focus, diagnostic de base compact intégrés.
- **Recette locale complète validée** sur le ZIP final : mises à jour depuis 0.1.15-dev vers 0.1.16-dev, WordPress 6.8 et 7.1.4, PHP 8.2.35/MariaDB 10.11.11, intégration, HTTP et Chromium/Firefox EN/FR. PHPUnit 76 tests/140 assertions, huit tests JS, syntaxe/PHPCS/Composer/secours Sodium et 393 entrées FR/EN réussis. Installation neuve InnoDB/défauts et absence de destination vérifiées. [Preuve locale](docs/validation/v0.1.16-dev-local.json).
- ZIP local : 301 fichiers, SHA-256 f35d8045bcb67c977c25bf94fb5fc662df1ada8e5d5e9eaf901562f4e2294d5c, identique aux sources et à cinq installations jetables. Les défauts préliminaires du banc et les dernières corrections CSS sont documentés. **Prochaine étape : push puis CI complète (15 jobs), tag/release seulement si réussite**, conformément à l’autorisation reçue. Banc `.tools/v16-dev/`, services locaux enregistrés ; aucun site utilisateur modifié.
- **Dernière version publiée : 0.1.15-dev**, tag cd9efd77db506e9067dff4296ed1f082238e078b, [release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.15-dev), [preuve](docs/validation/v0.1.15-dev-published.json). La réparation MyISAM reste volontaire après sauvegarde ; aucun changement de l’hébergement utilisateur effectué. Préversion de développement, sans certification de sécurité ni recette humaine complète au lecteur d’écran.

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
