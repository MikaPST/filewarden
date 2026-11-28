
<p align="center">
  <img
    src="assets/branding/filewarden-logo-512.png"
    alt="FileWarden Logo"
    width="160"
  />
</p>

<h1 align="center">FileWarden</h1>

<p align="center">
  <strong>Your files. Your control.</strong>
</p>

<p align="center">
  Secure document management for WordPress.
</p>

**Private Document Management for WordPress**

[Suivi court du projet et organisation des prochains lots](SUIVI.md)

> **Status: 0.1.19-alpha.1 — first Alpha, approved for GitHub publication after owner preproduction acceptance. Not a stable production release. Validate deployment security before using confidential documents.**

FileWarden is a free, open-source WordPress document-management plugin focused on private storage, inherited access control and modern publishing interfaces. Single-site only for the first version, confirmed by the owner.

## Première Alpha 0.1.19-alpha.1

La première Alpha regroupe les évolutions v18/v19 et leurs corrections validées en préproduction par le propriétaire : fiche document, descriptions de 400 caractères, recherche autorisée, doublons exacts avec copie indépendante à nom annoncé, sélection multiple, explications des accès par étapes, sauvegardes chiffrées, Diagnostic et personnalisation publique. Les contrôles GitHub Actions du commit et du tag conditionnent sa publication. [Notes de l’Alpha](docs/releases/v0.1.19-alpha.1.md).

Le schéma 9 et le runtime validé du dernier candidat v19 sont conservés ; ce passage harmonise l’identité Alpha et automatise la recette actuelle. Le rapport Plugin Check reçu pendant la préparation est suivi séparément dans [le prochain lot](docs/prochain-lot-beta.md), avant toute soumission à WordPress.org.

## Historique — candidat local 0.1.19-dev

Les 16 améliorations validées regroupent la recherche compacte et les tris du tableau, la sélection contextuelle, la date d’arrivée dans le dossier, les modales et le lecteur, les palettes Sombre/Terracotta, l’aide Popup Elementor, les réglages plus compacts et l’attribution Michaël PASTOR. Le menu WordPress s’appelle Bibliothèque. Voir [les notes du candidat](docs/releases/v0.1.19-dev.md) et [le périmètre](docs/lot-v0.1.19-dev.md).

La sauvegarde complète inclut tous les composants sans afficher de cases ; la sauvegarde personnalisée permet aussi un export des réglages et de l’apparence seuls, restaurable sur une destination non vide sans toucher aux documents ou dossiers. Les autres restaurations de bibliothèque exigent une destination vide. Le mot de passe de sauvegarde protège le même conteneur chiffré ; les anciens manifestes 1/2 restent lisibles, les nouveaux utilisent le manifeste 3. [Guide sauvegarde](docs/backup.md).

Schéma 9 additif (date d’arrivée du schéma 8, puis description facultative) : la date d’arrivée dans le dossier est distincte de la date d’import. Les anciennes dates inconnues restent inconnues. Les paramètres existants et les fichiers restent conservés à la mise à jour. Le ZIP local est destiné à la préproduction ; aucun tag ou publication GitHub v19 n’est autorisé par cette livraison.

Le lot documentaire ajoute la fiche privée, les descriptions (400 caractères), la recherche administrative nom/description et les doublons exacts à l’import, avec confirmation par fichier. Enregistrer explicitement les droits remplace les restrictions antérieures, en conservant les contraintes du dossier actuel. Voir les notes de la v19 pour les preuves locales et limites.

## Historique — candidat local 0.1.18-dev


Le candidat comprend le renommage, la sélection multiple, les tris/filtres et la recherche autorisée, les nouveaux pictogrammes, les contrastes AA/AAA et la sauvegarde/migration chiffrée. Les compléments ajoutent le Diagnostic dans les Réglages, la connexion Popup Elementor, la collecte IP facultative avec expiration, les filtres Action/Statut du journal et un bandeau Code court transversal. [Périmètre et limites](docs/lot-corrections-v0.1.18-dev.md). Schéma 7 additif ; archives de schéma 6 compatibles. La collecte IP reste désactivée par défaut.

Le ZIP local reste destiné à la préproduction. La dernière release publique est 0.1.17-dev ; aucune publication GitHub 0.1.18-dev n'est réalisée. Le Diagnostic est disponible dans **Réglages → Diagnostic**, en dernière position ; il ne répare pas les données. La sauvegarde nécessite Sodium ; mbstring n'est pas nécessaire pour vérifier la phrase. [Guide sauvegarde](docs/backup.md).

La dernière passe ajoute une recherche et des filtres homogènes dans Documents et la Corbeille, des états publics vides centrés, la mise à la corbeille multiple et des modales de confirmation. Les actions de sauvegarde sont espacées et adaptées au mobile. Les sauvegardes proposent un profil complet ou un transfert simplifié, avec six composants facultatifs. Sans reprise des droits source, les dossiers et documents restaurés restent privés et réservés à l’administration selon les exceptions explicites de lecture globale existantes. L’interface propose l’attribution globale au compte restaurateur ; les correspondances individuelles restent facultatives. Les anciens paquets restent lisibles. [Détail de la passe](docs/lot-ui-v0.1.18-dev.md).

Le Diagnostic distingue le module PHP Sodium natif absent et propose un accès à l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration. Le lien ne convertit rien : sauvegarde indépendante, maintenance et confirmations explicites sont requises. Cet assistant ne s’affiche que si une anomalie ou une reprise le nécessite.

## Nouveautés précédentes 0.1.17-dev

Ce lot ajoute la création de dossiers et l’import directement dans le dossier ouvert, un fil d’Ariane, une arborescence plus large et des boutons harmonisés. La corbeille retrouve les pictogrammes de fichiers, une recherche explicite, un compteur et le délai de conservation ; les notes de récupération deviennent des pastilles par fichier. Publication permet de personnaliser l’apparence publique commune au site : modèles FileWarden/Neutre/Nature/Personnalisé, dix couleurs, nom et logo PNG/JPEG, aperçu et enregistrement explicite. Ses deux onglets conservent les brouillons ; la connexion des visiteurs utilise WordPress par défaut ou une URL HTTP(S) personnalisée commune au site. Les vrais pictogrammes restent inchangés ; les cartes de grille ont une hauteur naturelle, des marges de 16 px, une identité centrée horizontalement et leurs actions en bas. Voir [les neuf axes initiaux](docs/lot-v0.1.17-dev.md), [les dix corrections validées](docs/corrections-v0.1.17-dev.md) et [les notes du candidat](docs/releases/v0.1.17-dev.md).

## Télécharger et installer

La dernière passe de recette place les onglets Publication au-dessus des cartes, adapte les options au mode Autorisé/Accès refusé, ajoute la carte Code court au-dessus de l’aperçu et harmonise la pastille d’information ambrée. « Palette de couleurs » et ses aides sont disponibles en FR/EN. Le code court s’utilise dans le bloc Code court de WordPress ou dans le widget Code court d’Elementor ; le bloc/widget spécifique à FileWarden reste une intégration future.

La version **0.1.17-dev** conserve les [dix évolutions originales](docs/specifications/evolutions-v0.1.12-dev.md), les langues FR/EN et l’option tous formats désactivée par défaut. La palette Nature utilise désormais le fond **#EDF3EE** ; une apparence déjà enregistrée reste conservée jusqu’à la sélection et l’enregistrement explicites d’une palette. La publication autorisée est conditionnée aux contrôles GitHub Actions ; les archives publiées sont disponibles dans les [releases GitHub](https://github.com/MikaPST/filewarden/releases), accompagnées de leur empreinte SHA-256.

L’archive de développement s’installe dans WordPress : **Extensions → Ajouter → Téléverser une extension**, puis remplacement de l’extension existante. Sauvegarder au préalable la base et le stockage séparé. Consulte le [journal des changements](CHANGELOG.md).

Les documents restent dans `wp-content/filewarden-storage`, séparés du code remplacé lors des mises à jour. Les dossiers affichés sont virtuels ; les fichiers physiques sont chiffrés et portent des noms opaques.

## Implemented foundation

- Repeatable custom-table installation and separate administrator capabilities.
- Managed encrypted storage with HTTPS isolation checks, plus an explicit outside-webroot option.
- Nested folder creation and strict intersection of document and ancestor policies.
- Public, authenticated and role policies; explicit global read capability independent of write capabilities.
- PDF/JPEG/PNG, TXT/CSV, macro-free DOCX/XLSX/PPTX/ODT/ODS/ODP and bounded ZIP inspection, with protected attachment downloads. ZIPs are preserved without automatic extraction; previews remain PDF/images only.
- Optional confirmed and audited all-format imports; content refused by the default validator is stored as an opaque attachment without inline preview. Size, provenance, inherited access, encryption and integrity checks remain enforced. WordPress Media upload restrictions are unchanged.
- Logical URLs, authorized document details and attachment downloads; PDF byte ranges and HEAD responses.
- REST metadata/upload/folder endpoints, tabbed administration with folder tree and contextual actions, diagnostics, settings and audit retention.
- Unit tests, disposable WordPress/HTTP integration checks, CI and development ZIP packaging.

The publication library offers folder-wide authorized search, detailed list/grid layouts, file-type icons, date/size metadata and separate preview/download actions. Use `[filewarden folder="1" view="grid"]` for a grid by default (list otherwise). Search is by filename within that folder, with 12 visible results per page; nested folders are not searched. French and English catalogs are bundled; FileWarden’s global language setting also applies to its publication and viewer without changing WordPress or another plugin. Office, text, CSV and ZIP attachments use the validated upload formats listed above.

The testable publication workflow adds protected image and multipage PDF previews, local Media Library import, separate admin screens and a shortcode usable in Elementor. The bundled PDF.js viewer renders PDFs in the browser without Imagick. Administrator previews open in a dialog; publication links open the full viewer in a new tab. Folder/file policy editing, confirmed move/copy and recoverable trash are available. Replacing document content, versions, categories/tags, frontend upload UI, Gutenberg, the native Elementor widget and statistics remain future milestones.

## Proposed compatibility targets

WordPress 6.8+, PHP 8.2+ with `fileinfo`, MySQL 8.0+ or MariaDB 10.11+. These are targets, not yet verified compatibility claims. Use HTTPS. Read [the French preproduction setup guide](docs/setup.md) before installation. There is no public uploads-directory fallback.

## Development

### Préparer une release GitHub

Mets à jour le champ `Version` et la constante `FILEWARDEN_VERSION` dans `filewarden.php`. Après avoir poussé ce commit, pousse un tag de même version, par exemple `v0.1.10-dev`.

```sh
git tag -a v0.1.10-dev -m "FileWarden 0.1.10-dev"
git push origin v0.1.10-dev
```

Le workflow réutilise la CI, vérifie la concordance du tag et publie une release visible avec le ZIP installable et son empreinte SHA-256 seulement si tous les tests réussissent. Un suffixe comme `-dev` marque la release comme préversion. Pousser un tag autorise sa publication.

Pour installer FileWarden, télécharge le ZIP nommé `filewarden-<version>.zip` dans les fichiers joints de la release. Dans WordPress, ouvre **Extensions → Ajouter → Téléverser une extension** et sélectionne ce ZIP. Les archives automatiques **Source code** de GitHub contiennent le dépôt de développement.

```sh
composer install --no-interaction --no-plugins
composer lint
composer test
composer audit
php tools/build.php
```

Runtime code has no Composer dependency. The development ZIP excludes development tools. PHPCS uses WordPress standards with PSR-4 filenames and narrowly documented exceptions for private byte streams, custom tables and read-only navigation. See [test instructions](docs/testing.md). A CI definition is not proof of a passing CI run.

See [requirements](docs/specifications/requirements.md), [architecture](docs/architecture.md), [permissions](docs/permissions.md), [security](docs/security.md) and [roadmap](docs/roadmap.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## Folder references and access visibility (0.1.8)
New publication shortcodes use a permanent UUID v4 for each folder, excluding the management-only recovery collection. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric folder shortcodes remain compatible, except the former root publication which is now blocked. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Folder and file policies can be edited from the backend inspector.

## Emplacement de l’extension et mises à jour

Le code de FileWarden se trouve dans `wp-content/plugins/filewarden/` ; les documents chiffrés sont conservés séparément dans `wp-content/filewarden-storage/`. Pour les mises à jour, utiliser le ZIP de release depuis l’administration WordPress. Consulter la [procédure de déploiement](docs/deployment.md) pour l’arborescence, les sauvegardes et le nettoyage des anciens transferts manuels.


## Local candidate 0.1.18-dev

This candidate adds direct document/folder rename, reviewed atomic multi-document moves/classification/restoration, combined authorized search/sorting/filters, the approved file-type artwork, an eight-pair AA/AAA text-contrast indicator and encrypted full-library backup/migration. Existing document links, strict inherited policies and schema 6 are retained. Version and activity histories remain deferred.

Backup and migration live in Settings, after Storage and security. Archives use Argon2id and Sodium secretstream; keep the phrase separately. Restoration requires an empty library, explicit role/creator mappings and transactional FileWarden plus WordPress options/posts/postmeta tables. It never imports accounts, credentials, source keys or storage paths. The all-format exception and custom sign-in URL reset to safe defaults. Default managed destination storage re-encrypts with its own key; the explicit outside-webroot backend retains its existing storage format.

Initial limits: one-hour private jobs, 10,000 rows per plugin table, 4 MiB manifest, 256 MiB per file, and the destination PHP upload limit. Files are processed one at a time with bounded binary buffers. These are enforced limits, not a large-volume benchmark. See docs/backup.md and the local release notes. No 0.1.18 GitHub release/tag is published by this local delivery.
