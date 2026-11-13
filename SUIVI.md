# FileWarden — suivi court

Mis à jour le 13 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les seuls fichiers utiles au lot. Ce résumé ne remplace pas les spécifications ni les règles de sécurité.

## État actuel

- **Lot 0.1.12-dev terminé, validé et publié le 13 novembre 2026.**
- Accord utilisateur du 13 novembre 2026 : développer les dix évolutions du [cahier des charges](docs/specifications/evolutions-v0.1.12-dev.md), tester, puis publier la release et le ZIP si les tests passent. L’installation sur son site n’est pas incluse.
- Le lot conserve la navigation, l’héritage, la récupération, la suppression des dossiers et les interfaces déjà livrés en 0.1.11-dev ; il complète l’audit, les acteurs WordPress, les filtres et les téléchargements CSV/TXT/JSONL. Voir [le compte rendu](docs/lot-v0.1.12-dev.md) et [les notes de version](docs/releases/v0.1.12-dev.md).
- Acteurs : ID + instantané du login WordPress ; aucun e-mail ajouté. Schéma 5, migration sans suppression des journaux ou documents. Les noms des ressources restent soumis aux droits de lecture du contenu.
- Charte 1.1 intégrée ; couleurs et pictogrammes d’action approuvés, licence des icônes incluse dans le ZIP.
- [Validation finale du code b6968e4](https://github.com/MikaPST/filewarden/actions/runs/43488511977) réussie : PHP 8.2–8.4, 50 tests PHP / 71 assertions, 8 tests JavaScript, PHPCS et audit Composer ; WordPress 6.8 et version courante, MySQL 8.0, HTTP, Chromium/Firefox. Exports filtrés sur 1 005 lignes, exports vides, refus de permissions/nonces, migrations et absence de fuite des noms privés vérifiés.
- Captures ordinateur/mobile contrôlées ; ZIP inspecté : 265 fichiers runtime, version 0.1.12-dev et schéma 5, aucun outil de développement. 238 traductions françaises compilées. Les captures de CI utilisent WordPress en anglais.
- Les essais intermédiaires ont corrigé un compte de test qui avait conservé une capacité globale et des attentes de navigation des panneaux ; les assertions n’ont pas été retirées. Le blocage PHPUnit local a été résolu par l’accès autorisé aux fichiers.
- Publication : [release et ZIP v0.1.12-dev](https://github.com/MikaPST/filewarden/releases/tag/v0.1.12-dev), tag au commit `4f8a671`. Notes françaises publiées et empreinte SHA-256 jointe.
- Preuves supplémentaires réussies : [CI sur main](https://github.com/MikaPST/filewarden/actions/runs/43489443735) et [workflow de release](https://github.com/MikaPST/filewarden/actions/runs/43489504775), incluant la matrice complète avant publication.
- ZIP publié téléchargé puis inspecté : 265 entrées runtime, version 0.1.12-dev, schéma 5 et licence des icônes présents ; aucun outil de développement. Empreinte vérifiée contre le fichier publié et les métadonnées GitHub : `9d83b8116631ff1fa32047a52ea8a3c9ae5c62399f14c9f228f94931232b324d`.
- Prochaine étape : l’utilisateur peut installer le ZIP via WordPress en remplaçant l’extension, puis réaliser la réception sur son hébergement. Aucun développement restant ni blocage connu sur ce lot après les vérifications décrites.
- Hébergement utilisateur : installation et réception non effectuées par cette intervention. Le plugin reste une préversion ; l’acceptation du stockage et des accès sur l’hébergement réel reste nécessaire.

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
