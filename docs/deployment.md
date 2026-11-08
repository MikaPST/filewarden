# Déploiement et organisation des fichiers

## Organisation attendue

```text
wp-content/
├── plugins/
│   └── filewarden/           # Code de l’extension, remplacé lors des mises à jour
│       ├── filewarden.php
│       ├── includes/
│       ├── assets/
│       └── languages/
└── filewarden-storage/       # Documents chiffrés persistants (mode standard)
```

Ne pas créer `wp-content/filewarden` pour le code : WordPress charge les extensions depuis `wp-content/plugins`. Les dossiers documentaires affichés dans FileWarden sont virtuels et enregistrés dans la base de données ; ils ne correspondent pas à des sous-dossiers physiques nommés dans le stockage.

## Procédure standard de mise à jour

1. Télécharger `filewarden-<version>.zip` dans les fichiers joints de la release GitHub, et non l’archive « Source code ».
2. Dans WordPress, ouvrir **Extensions → Ajouter une extension → Téléverser une extension**.
3. Choisir le ZIP et confirmer le remplacement de FileWarden. Ne pas désinstaller l’extension pour effectuer une mise à jour.
4. Vérifier la version, les diagnostics du stockage, la liste des documents et l’aperçu d’un document de test. Vérifier également le refus d’accès avec un compte non autorisé.

Le ZIP généré par `tools/build.php` contient un unique dossier `filewarden/`. Il ne contient ni documents, ni configuration WordPress, ni outils de déploiement. WordPress se charge de son extraction dans le dossier des extensions. Aucune modification de `wp-config.php` n’est nécessaire pour une mise à jour standard.

WordPress peut utiliser ses propres dossiers `upgrade` et `upgrade-temp-backup` : ne pas les confondre avec les anciens restes des interventions manuelles FileWarden.

## Sauvegardes et interventions manuelles

La procédure standard remplace les anciens scripts ponctuels FTPS de développement. Ne plus déposer de copies PHP, de dossiers `fw-stage-*`, `filewarden-stage-*`, `fw-backup-*` ou `filewarden-fix-backup-*` à la racine de `wp-content`.

Conserver les sauvegardes hors de la racine publique du site ou sur le poste de maintenance. Une sauvegarde complète des documents nécessite le stockage chiffré **et** la base WordPress contenant sa clé ; une copie du code du plugin ne constitue pas une sauvegarde des documents.

Pour nettoyer des restes historiques : inventorier les chemins exacts, confirmer qu’ils ne sont pas utilisés, archiver leur contenu hors du site, vérifier chaque fichier, puis supprimer uniquement les copies inventoriées. Ne jamais effectuer une suppression générale sur le préfixe `filewarden*` : elle pourrait englober le stockage des documents.

Tout outil de diagnostic exceptionnel doit être limité dans le temps, protégé par un secret et supprimé après l’intervention, y compris en cas d’échec. Aucune sauvegarde de code n’est conservée dans un emplacement public par cette procédure.
