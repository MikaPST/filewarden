# FileWarden — suivi court

Mis à jour le 14 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **Correction des dix évolutions originales en cours**, autorisée explicitement par le propriétaire. Le compte rendu de 0.1.12-dev avait utilisé un périmètre reconstruit et annoncé à tort une conformité complète. Le cahier de référence est maintenant la copie exacte du document fonctionnel/UIUX fourni : [les dix évolutions](docs/specifications/evolutions-v0.1.12-dev.md).
- Branche isolée : **fix/ten-approved-evolutions** ; version candidate **0.1.12-dev.1**, schéma 6. Main, tag et release 0.1.12-dev restent inchangés. Aucun déploiement sur l’hébergement utilisateur dans ce correctif.
- Décisions confirmées : ZIP contrôlé puis conservé comme document téléchargeable, sans extraction automatique ; TXT, CSV et DOCX/XLSX/PPTX/ODT/ODS/ODP sans macros ajoutés à PDF/JPEG/PNG. Les restrictions d’inspection sont documentées dans docs/security.md.
- Fonctionnalités développées : compteur, retrait de publication confirmé, imports multiples/dépôt, notifications accessibles, création modernisée, corbeille globale et purge confirmée, modes de publication, cinq catégories de réglages et état mesuré, audit avec types d’acteurs/instantanés et JSONL versionné. Conserver les fonctions existantes et les restrictions héritées.
- Vérifications locales actuelles : **72 tests PHP / 125 assertions** ; PHP 8.2–8.4, JavaScript, PHPCS et construction du ZIP réussissent en CI sur les premiers correctifs. La mise à jour réelle du ZIP 0.1.11-dev vers le candidat préserve les UUID, politiques, dossiers, clés et octets. Les contrôles HTTP passent.
- **Réception navigateur complète encore en cours.** Les essais ont détecté puis corrigé le type d’écriture du chemin d’origine et les onglets manquants de la corbeille ; les tests restent présents. Des scénarios supplémentaires couvrent les annulations transactionnelles, le dépôt mixte, la purge et les écrans français. Ne pas déclarer le lot terminé avant leur réussite et l’examen des captures.
- Détails et matrice : [réception corrective](docs/lot-v0.1.12-dev.1.md). Les preuves historiques de 0.1.12-dev démontraient l’ancien lot, pas les dix évolutions originales.
- L’installation et la recette de l’hébergement réel ne sont pas réalisées. Préversion uniquement ; aucune garantie de sécurité absolue. Une publication officielle exige un accord distinct explicite.

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
