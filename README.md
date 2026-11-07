# FileWarden

**Private Document Management for WordPress**

> **Status: 0.1.1-dev pre-alpha foundation. Not production-ready. Do not use confidential documents before deployment security validation.**

FileWarden is a free, open-source WordPress document-management plugin focused on private storage, inherited access control and modern publishing interfaces. Single-site only for the first version, confirmed by the owner.

## Implemented foundation

- Repeatable custom-table installation and separate administrator capabilities.
- Managed encrypted storage with HTTPS isolation checks, plus an explicit outside-webroot option.
- Nested folder creation and strict intersection of document and ancestor policies.
- Public, authenticated and role policies; explicit global read capability independent of write capabilities.
- PDF/JPEG/PNG upload with provenance, extension, MIME, signature/image and size checks.
- Logical URLs, authorized document details and attachment downloads; PDF byte ranges and HEAD responses.
- REST metadata/upload/folder endpoints, minimal administration, diagnostics, settings and audit retention.
- Unit tests, disposable WordPress/HTTP integration checks, CI and development ZIP packaging.

The testable publication workflow adds protected image and first-page PDF previews, local Media Library import, separate admin screens and a shortcode usable in Elementor. PDF preview requires a host that supports PDF rasterization with Imagick. Editing/replacing documents, versions, trash, categories/tags, search, visual layouts, frontend upload UI, Gutenberg, the native Elementor widget, statistics and translations remain future milestones.

## Proposed compatibility targets

WordPress 6.8+, PHP 8.2+ with `fileinfo`, MySQL 8.0+ or MariaDB 10.11+. These are targets, not yet verified compatibility claims. Use HTTPS. Read [the French preproduction setup guide](docs/setup.md) before installation. There is no public uploads-directory fallback.

## Development

### Préparer une release GitHub

Mets à jour le champ `Version` et la constante `FILEWARDEN_VERSION` dans `filewarden.php`. Après avoir poussé ce commit, pousse un tag de même version, par exemple `v0.1.1-dev`.

```sh
git tag -a v0.1.1-dev -m "FileWarden 0.1.1-dev"
git push origin v0.1.1-dev
```

Le workflow réutilise la CI, vérifie la concordance du tag et prépare une release en brouillon avec le ZIP installable et son empreinte SHA-256. Un suffixe comme `-dev` marque la release comme préversion. Vérifie les notes et le ZIP avant de publier le brouillon dans GitHub Releases.

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
