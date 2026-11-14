# FileWarden — suivi court

Mis à jour le 14 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.13-dev publiée comme préversion**, sur autorisation explicite du propriétaire le 14 novembre 2026 : [release, ZIP installable et SHA-256](https://github.com/MikaPST/filewarden/releases/tag/v0.1.13-dev). Tag au commit 7862027 ; le correctif des dix évolutions est intégré à main.
- Cette version distribue le candidat 0.1.12-dev.1 déjà validé sous un numéro distinct. Aucun changement fonctionnel supplémentaire ; schéma 6 conservé. Documents, permissions, réglages, stockage et historique préservés par les tests de mise à jour.
- [CI de préparation réussie](https://github.com/MikaPST/filewarden/actions/runs/43578019444) et [workflow de publication réussi](https://github.com/MikaPST/filewarden/actions/runs/43578350911) : 75 tests PHP / 130 assertions sur PHP 8.2–8.4, huit tests JavaScript, PHPCS, syntaxe et audit Composer ; quatre mises à jour WordPress 6.8/version courante depuis 0.1.11-dev et 0.1.12-dev, Chromium/Firefox, FR/EN.
- La release utilise exactement le ZIP installé et testé par le parcours WordPress. Archive publiée téléchargée, 274 fichiers runtime comparés au tag, empreinte SHA-256 vérifiée : 22f79af57e726eda6a167a66fc471245078016f8002771d36ead0f403196612c.
- Décisions : ZIP contrôlé et conservé sans extraction automatique ; TXT, CSV, DOCX/XLSX/PPTX/ODT/ODS/ODP sans macros, PDF/JPEG/PNG conservés. Contrôles et limites dans docs/security.md.
- Détails : [publication 0.1.13-dev](docs/lot-v0.1.13-dev.md), [notes](docs/releases/v0.1.13-dev.md), [matrice des dix évolutions et preuves historiques](docs/lot-v0.1.12-dev.1.md). Le cahier original reste inchangé.
- **0.1.14-dev poussée sur main le 14 novembre 2026**, sur demande explicite du propriétaire, au commit [58f6cfe](https://github.com/MikaPST/filewarden/commit/58f6cfee0963270897bea3ce7ef0fbba0b97478d). Six corrections intégrées ; petit dossier plein de 80 px et Rail compact validés. [Périmètre et réception corrective](docs/corrections-v0.1.14-dev.md), [notes](docs/releases/v0.1.14-dev.md).
- Notifications isolées des classes génériques WordPress, superposées en haut à droite sous la barre d’administration, hors des conteneurs susceptibles de les masquer et dans la couche du dialogue de prévisualisation ouvert. Aucun déplacement du contenu. Les durées, progression, pauses, surplus, traduction, secours sans JavaScript et retours serveur vérifiés sont conservés.
- Noms complets de documents/dossiers avec retour à la ligne, accueil avec dossier plein Navy/turquoise de 80 px, Hiérarchie guidée légèrement plus compacte et bouton de création espacé. Réglages : rail vertical, descriptifs sous les titres à droite, changement local sans perte de saisie, registre de catégories extensible.
- Suppression : le propriétaire confirme que l’échec survient après la confirmation finale. Suppression vide réelle réussie sur le candidat initial jetable ; cause propre au site non établie. Cas de masquage par une règle générique WordPress reproduit ; ce n’est pas une preuve de la cause du site utilisateur. Vérification alternative des moteurs via métadonnées des trois tables si information_schema est indisponible ; refus conservé si les transactions ne sont pas vérifiables. Messages explicites de réussite/récupération et de refus persistant. Attendre le code result demandé pour affiner le diagnostic propre au site, sans bloquer les essais locaux indépendants.
- Ressources d’administration identifiées par leur contenu dans les URL afin que le remplacement du même numéro de développement charge les fichiers corrigés. Schéma 6 et paramètres existants conservés ; aucune migration ou réinitialisation ajoutée.
- Réception corrective locale réussie : 75 tests PHP/130 assertions, huit tests JS, syntaxe PHP/JS, PHPCS, audit Composer et secours Sodium. WordPress 6.8 et 7.1.4 jetables : mise à jour 0.1.13-dev → candidat initial 0.1.14-dev → ZIP corrigé, intégration/HTTP et parcours complets Chromium/Firefox EN/FR réussis ; dix évolutions conservées, six corrections vérifiées. [Journal local historique](docs/validation/v0.1.14-dev-corrections-local.txt), [matrice et réserves](docs/corrections-v0.1.14-dev.md). La cause propre au site de l’échec de suppression reste non établie ; aucune recette d’hébergement revendiquée.
- **CI GitHub du code 58f6cfe entièrement réussie : dix jobs**, [exécution 43616653528](https://github.com/MikaPST/filewarden/actions/runs/43616653528). PHP 8.2–8.4, huit tests JS, MySQL 8.0, six mises à jour depuis 0.1.11/12/13-dev vers WordPress 6.8/7.1.4, HTTP, Chromium/Firefox EN/FR. [Preuve structurée](docs/validation/v0.1.14-dev-ci.json). [ZIP réellement installé et testé conservé dans Actions](https://github.com/MikaPST/filewarden/actions/runs/43616653528/artifacts/14122811563). Le suivi documentaire est poussé ensuite sans changement runtime.
- Candidat initial conservé dans build/candidates-initial/filewarden-0.1.14-dev-initial.zip (SHA-256 b5ebe433bd93b5f0cc0bfacbee18a3b4e446eb09db8ea5d5f9f9075334ec79d5). [Preuves historiques initiales](docs/lot-v0.1.14-dev.md) inchangées. ZIP local corrigé build/filewarden-0.1.14-dev.zip : 279 fichiers identiques aux sources locales et aux deux installations finales, SHA-256 a89e68643c064308413464198285a4c71937d36c95b0c4ccf6efd1c94993961e. Captures finales FR et notes conservées. Code poussé au commit 58f6cfe ; archive locale distincte de l’artefact construit et testé par la CI Linux.
- **Push du code et CI terminés ; aucun tag, release ou déploiement utilisateur autorisé ou effectué pour 0.1.14-dev.** L’accord « Pousse la sur github stp » a été exécuté sur main. Attendre un accord distinct pour le tag et la release. Ne pas relancer les suites destructives complètes sur leurs anciennes données de test. Quatre serveurs WordPress locaux et base jetable arrêtés après consolidation des preuves ; archives et fixtures synthétiques conservées.
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
