# FileWarden

**Private Document Management for WordPress**

> **Status: 0.1.8-dev pre-alpha foundation. Not production-ready. Do not use confidential documents before deployment security validation.**

FileWarden is a free, open-source WordPress document-management plugin focused on private storage, inherited access control and modern publishing interfaces. Single-site only for the first version, confirmed by the owner.

## Télécharger et installer

Ouvre [GitHub Releases](https://github.com/MikaPST/filewarden/releases) et télécharge **filewarden-0.1.8-dev.zip** dans les fichiers joints. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**. La préversion reste destinée aux tests. Consulte le [journal des changements](CHANGELOG.md).

Les documents restent dans `wp-content/filewarden-storage`, séparés du code remplacé lors des mises à jour. Les dossiers affichés sont virtuels ; les fichiers physiques sont chiffrés et portent des noms opaques.

## Implemented foundation

- Repeatable custom-table installation and separate administrator capabilities.
- Managed encrypted storage with HTTPS isolation checks, plus an explicit outside-webroot option.
- Nested folder creation and strict intersection of document and ancestor policies.
- Public, authenticated and role policies; explicit global read capability independent of write capabilities.
- PDF/JPEG/PNG upload with provenance, extension, MIME, signature/image and size checks.
- Logical URLs, authorized document details and attachment downloads; PDF byte ranges and HEAD responses.
- REST metadata/upload/folder endpoints, minimal administration, diagnostics, settings and audit retention.
- Unit tests, disposable WordPress/HTTP integration checks, CI and development ZIP packaging.

The publication library offers folder-wide authorized search, detailed list/grid layouts, file-type icons, date/size metadata and separate preview/download actions. Use `[filewarden folder="1" view="grid"]` for a grid by default (list otherwise). Search is by filename within that folder, with 12 visible results per page; nested folders are not searched. The French frontend labels are bundled. Icons for Office and other formats are prepared, but uploads remain PDF/JPEG/PNG only.

The testable publication workflow adds protected image and multipage PDF previews, local Media Library import, separate admin screens and a shortcode usable in Elementor. The bundled PDF.js viewer renders PDFs in the browser without Imagick. Administrator previews open in a dialog; publication links open the full viewer in a new tab. Editing/replacing documents, versions, trash, categories/tags, frontend upload UI, Gutenberg, the native Elementor widget, statistics and translations remain future milestones.

## Proposed compatibility targets

WordPress 6.8+, PHP 8.2+ with `fileinfo`, MySQL 8.0+ or MariaDB 10.11+. These are targets, not yet verified compatibility claims. Use HTTPS. Read [the French preproduction setup guide](docs/setup.md) before installation. There is no public uploads-directory fallback.

## Development

### Préparer une release GitHub

Mets à jour le champ `Version` et la constante `FILEWARDEN_VERSION` dans `filewarden.php`. Après avoir poussé ce commit, pousse un tag de même version, par exemple `v0.1.8-dev`.

```sh
git tag -a v0.1.8-dev -m "FileWarden 0.1.8-dev"
git push origin v0.1.8-dev
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
New publication shortcodes use a permanent UUID v4 for each folder, including the virtual root. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric shortcodes remain compatible. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Policy editing remains a separate milestone.

## Emplacement de l’extension et mises à jour

Le code de FileWarden se trouve dans `wp-content/plugins/filewarden/` ; les documents chiffrés sont conservés séparément dans `wp-content/filewarden-storage/`. Pour les mises à jour, utiliser le ZIP de release depuis l’administration WordPress. Consulter la [procédure de déploiement](docs/deployment.md) pour l’arborescence, les sauvegardes et le nettoyage des anciens transferts manuels.
