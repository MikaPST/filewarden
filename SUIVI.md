# FileWarden — suivi court

Mis à jour le 9 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les seuls fichiers utiles au lot. Ce résumé ne remplace pas les spécifications ni les règles de sécurité.

## État actuel

- Dernier lot terminé : refonte du backend, préversion **0.1.10-dev**, code livré au commit `1579dc9`.
- Réalisé : onglets, arborescence, panneau contextuel, affichage des droits effectifs et avertissement Public sous dossier restreint ; déplacement, copie, corbeille et restauration.
- [Release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.10-dev) publiés ; ZIP téléchargé et empreinte SHA-256 vérifiée.
- Tests de ce lot : 48 tests PHP / 66 assertions, 8 tests JavaScript ; intégration WordPress 6.8 et version courante avec Chromium/Firefox réussie. Rendu ordinateur/mobile contrôlé.
- Preuves : [CI du code livré](https://github.com/MikaPST/filewarden/actions/runs/42587862914), [publication](https://github.com/MikaPST/filewarden/actions/runs/42588245303). Ces résultats concernent cette version, pas les modifications futures.
- Site utilisateur : installation du ZIP à effectuer par l’utilisateur ; réception sur son hébergement non confirmée. Le plugin reste une préversion.

## Lot en cours / prochaine action

- **Statut : attente de la liste regroupée des améliorations, corrections et optimisations de l’utilisateur.**
- À sa réception : préparer un compte rendu court du périmètre, du comportement attendu, des propositions visuelles utiles et des questions à trancher.
- Périmètre validé : aucun nouveau lot pour le moment.
- Autorisation de coder : en attente d’un accord explicite après revue du compte rendu.
- Vérifications automatisées regroupées : organisation à définir ensemble ensuite ; aucune nouvelle commande à développer maintenant.
- Publication du prochain lot : non autorisée à ce stade ; accord distinct requis.
- Blocage technique connu : aucun sur le lot livré.

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
