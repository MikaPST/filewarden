# FileWarden

**Private Document Management for WordPress**

> **Status: 0.1.0-dev pre-alpha foundation. Not production-ready. Do not use confidential documents before deployment security validation.**

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

This is **not the complete V1**. Editing/replacing documents, versions, trash, categories/tags, previews, search, visual layouts, frontend upload UI, Gutenberg, Elementor, shortcodes, statistics and translations remain future milestones. PDFs are downloaded as attachments and are not sanitized for active PDF features.

## Proposed compatibility targets

WordPress 6.8+, PHP 8.2+ with `fileinfo`, MySQL 8.0+ or MariaDB 10.11+. These are targets, not yet verified compatibility claims. Use HTTPS. Read [the French preproduction setup guide](docs/setup.md) before installation. There is no public uploads-directory fallback.

## Development

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
