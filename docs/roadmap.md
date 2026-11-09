# Roadmap

- **0.1.0 Secure foundations:** plugin bootstrap, migrations, verified private storage, ACL, protected upload/download, automated tests.
- **0.2.0 Documents:** folders, document lifecycle, versions, trash and auditing.
- **0.3.0 Interface:** admin manager, document detail and previews.
- **0.4.0 Integrations:** shortcode builder, Gutenberg and Elementor.
- **0.9.0 Beta:** security review, compatibility tests, translations, documentation.
- **1.0.0 Stable:** release after security and functionality acceptance criteria are met.

No target release date has been committed.

## Current progress

The 0.1.0-dev foundation is implemented for single-site WordPress: schema/capabilities, private-storage diagnostics, inherited ACL, PDF/image upload, protected downloads, basic administration, audit retention, unit tests, integration workflow and development ZIP packaging.

**0.1.0 acceptance is pending:** run the WordPress/HTTP integration suite and validate private storage, aliases, caching, permissions and upload limits on the target preproduction host. Unit tests alone do not establish milestone acceptance. Selected features from later milestones are implemented incrementally below; the milestones are not yet complete.

## Frontend library (implemented)
The shortcode supports list/grid layouts, a filename search limited to its published folder, authorized pagination (12 documents), local file-type SVG icons, date/size and separate preview/download actions. Search and pagination filter inherited permissions before rendering. PDF/JPEG/PNG remain the accepted upload formats.

## Folder references and access visibility (0.1.8)
New publication shortcodes use a permanent UUID v4 for each folder, including the virtual root. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric shortcodes remain compatible. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Policy editing was added in 0.1.9.

## Editable access policies (0.1.9)
Folder and document access rules can be changed after creation by an authorized permission manager. New files default to dynamic folder inheritance; individual restrictions narrow access. Existing file policies remain unchanged. Public file policies cannot open restricted parent folders; existing ancestor checks remain enforced. The admin UI explains these rules and confirms saved changes.

## Administration workspace and lifecycle (0.1.10)
Task tabs, authorized folder tree, contextual inspector, explicit/effective policy warnings, confirmed moves/copies, trash and restore are implemented. Trash cleanup is configurable (30 days by default). Content replacement/version history, categories/tags, bulk actions and native editor integrations remain pending.

## Lot en développement : navigation et interfaces

Voir docs/lot-navigation-interfaces.md et SUIVI.md pour le périmètre validé, les vérifications et l’état réel. La publication est distincte de la validation du code.
