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
- Adaptive secure local storage: prefer outside webroot; accept alternate only if sufficiently verified; otherwise fail closed.
- Protect previews, thumbnails, historical versions and temporary files.
- Server-side authorization for all listing, metadata and binary routes.
- Secure upload allowlist, MIME checks, limits, non-executable storage and CSRF defenses.
- Avoid leaking filesystem paths or unauthorized metadata.

## Distribution
- Public GitHub repository first; WordPress.org later, subject to name and guideline review.
- GPL-2.0-or-later; GitHub Actions tests and release ZIP packaging.
