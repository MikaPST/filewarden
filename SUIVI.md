# FileWarden — suivi court

Mis à jour le 14 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.13-dev publiée comme préversion**, sur autorisation explicite du propriétaire le 14 novembre 2026 : [release, ZIP installable et SHA-256](https://github.com/MikaPST/filewarden/releases/tag/v0.1.13-dev). Tag au commit 7862027 ; le correctif des dix évolutions est intégré à main.
- Cette version distribue le candidat 0.1.12-dev.1 déjà validé sous un numéro distinct. Aucun changement fonctionnel supplémentaire ; schéma 6 conservé. Documents, permissions, réglages, stockage et historique préservés par les tests de mise à jour.
- [CI de préparation réussie](https://github.com/MikaPST/filewarden/actions/runs/43578019444) et [workflow de publication réussi](https://github.com/MikaPST/filewarden/actions/runs/43578350911) : 75 tests PHP / 130 assertions sur PHP 8.2–8.4, huit tests JavaScript, PHPCS, syntaxe et audit Composer ; quatre mises à jour WordPress 6.8/version courante depuis 0.1.11-dev et 0.1.12-dev, Chromium/Firefox, FR/EN.
- La release utilise exactement le ZIP installé et testé par le parcours WordPress. Archive publiée téléchargée, 274 fichiers runtime comparés au tag, empreinte SHA-256 vérifiée : 22f79af57e726eda6a167a66fc471245078016f8002771d36ead0f403196612c.
- Décisions : ZIP contrôlé et conservé sans extraction automatique ; TXT, CSV, DOCX/XLSX/PPTX/ODT/ODS/ODP sans macros, PDF/JPEG/PNG conservés. Contrôles et limites dans docs/security.md.
- Détails : [publication 0.1.13-dev](docs/lot-v0.1.13-dev.md), [notes](docs/releases/v0.1.13-dev.md), [matrice des dix évolutions et preuves historiques](docs/lot-v0.1.12-dev.1.md). Le cahier original reste inchangé.
- Nouvelle demande : accueil Documents allégé lorsqu’aucun dossier n’est sélectionné. Proposition UI/UX présentée séparément ; **aucune implémentation de cette nouvelle demande dans 0.1.13-dev**. Attendre la validation du propriétaire avant ce nouveau lot.
- L’hébergement utilisateur n’est pas modifié ; sa recette de préproduction reste à effectuer. Préversion uniquement, sans certification de sécurité ni recette humaine complète au lecteur d’écran. Aucun autre tag ni autre release autorisé.

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
