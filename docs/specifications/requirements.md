# FileWarden — Requirements baseline v1.0

## Mission
A fully free, open-source WordPress document-management plugin. No paid tier or artificial limits.

## Document management
- Nested folders, categories and tags; grid, list and explorer displays.
- Modern administration, drag-and-drop, authorized front-end uploads.
- Document details with preview and download. Modal or dedicated page, configurable.
- Permanent logical URLs, re-authorized on every request.
- PDF/image previews only in v1; do not serve executable or untrusted active formats inline.
- Optional version history; configurable cap, default 10 versions.
- Duplicate-name warning: rename, create a version if authorized, or cancel.
- Trash retention default 30 days, configurable.
- Per-file upload limit default 50 MB, subject to server limits.
- Audit retention default 365 days, configurable; download statistics.
- Manual import from Shared Files. ZIP bulk download postponed.

## Publishing
- Visual display presets with color, typography, icon and dimension controls.
- Shortcode, native Gutenberg block and Elementor widget.
- French and English from first stable release.

## Security
- Public, authenticated or selected-role policies, with strict parent inheritance.
- WordPress administrator has global document-read override, explicitly modeled as a dedicated capability; write/configuration actions require separate capabilities.
- Single content-read permission for preview and download in v1.
- Default managed storage in `wp-content/filewarden-storage`, provisioned on first use. Require Sodium authenticated encryption and successful anonymous HTTPS checks. A public control must return the exact random witness while a matching witness in storage must be denied. Repeat checks for storage requests and fail closed on ambiguous responses.
- Store a random site key separately from the encrypted files in the WordPress database. Include it in protected backups and restores. Never replace a missing key when encrypted files already exist.
- Keep the explicitly configured outside-webroot mode available. Changing modes requires a separate migration. Authorized downloads may use a private temporary stream outside public roots to preserve PDF byte ranges.
- Protect previews, thumbnails, historical versions and temporary files.
- Server-side authorization for all listing, metadata and binary routes.
- Secure upload allowlist, MIME checks, limits, non-executable storage and CSRF defenses.
- Avoid leaking filesystem paths or unauthorized metadata.

## Distribution
- Public GitHub repository first; WordPress.org later, subject to name and guideline review.
- GPL-2.0-or-later; GitHub Actions tests and release ZIP packaging.

## Frontend library (implemented)
The shortcode supports list/grid layouts, a filename search limited to its published folder, authorized pagination (12 documents), local file-type SVG icons, date/size and separate preview/download actions. Search and pagination filter inherited permissions before rendering. The corrective 0.1.12-dev.1 candidate extends uploads to TXT/CSV, macro-free DOCX/XLSX/PPTX/ODT/ODS/ODP and inspected ZIPs without automatic extraction. Previews remain PDF/JPEG/PNG only. See security.md for inspection limits and evolutions-v0.1.12-dev.md for the original ten requests.

## Folder references and access visibility (0.1.8)
New publication shortcodes use a permanent UUID v4 for each folder, including the virtual root. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric shortcodes remain compatible. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Policy editing remains a separate milestone.

## Editable access policies (0.1.9)
Folder and document access rules can be changed after creation by an authorized permission manager. New files default to dynamic folder inheritance; individual restrictions narrow access. Existing file policies remain unchanged. Public file policies cannot open restricted parent folders; existing ancestor checks remain enforced. The admin UI explains these rules and confirms saved changes.

## Approved backend workspace (0.1.10)
- Separate tabs for documents, adding documents, folder creation and trash; retain folder context across tabs.
- Authorized collapsible folder tree, searchable file table with icons, own/effective rules, preview/download and contextual actions.
- Right-side action inspector for rights, move/copy review and trash/restore confirmation.
- Explicit warning: choosing Public cannot override a restricted folder. All ancestor rules remain intersected.
- Review destination access before moving/copying; preserve explicit rules and move URLs, generate independent copy identity/storage, refuse active name collisions.
- Trash hides files immediately from every read surface; restore preserves rules and refuses name collisions; scheduled permanent cleanup after configurable retention (default 30 days).

## Lot validé le 9 novembre 2026 — navigation et administration

- Dossiers imbriqués navigables avec fil d’Ariane borné, héritage dynamique et intersection des droits ; recherche limitée au dossier courant.
- Racine remplacée par Documents à classer, non publiable. Anciens fichiers conservés mais retirés des publications jusqu’au reclassement.
- Suppression confirmée des dossiers feuilles : récupération des fichiers et conservation des restrictions héritées ; blocage si sous-dossiers.
- Publication dynamique : liste/grille, choix compact/carte centrée/bandeau éditorial pour accès refusé, copie du code et aperçu. Carte centrée par défaut ; aucun nom privé dans l’erreur.
- Réglages harmonisés et diagnostic visible ; audit en deux colonnes, filtres événement/date, exports CSV/TXT, purge irréversible confirmée, sans import.
- Aucun tag ni release sans validation distincte du lot testé.
