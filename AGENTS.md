# AGENTS.md — FileWarden

## Mission and status
FileWarden is a **free, open-source WordPress document-management plugin** licensed **GPL-2.0-or-later**. Prioritize secure private document storage, strict inherited access control, reliable auditability, accessibility, and maintainability.

**Current status: development preview. Read SUIVI.md for the current implementation, test evidence, pending work and release approval. Never describe the plugin as production-ready or safe for confidential files without deployment acceptance. Single-site WordPress only.**

## Source of truth and workflow
Read `SUIVI.md` first for the current batch, remaining work, evidence and owner approvals. Check Git state before relying on that snapshot. The owner requires a consolidated request summary and explicit agreement before coding each new batch, plus separate explicit approval before publishing its release/tag. Keep this short status file current; do not reconstruct completed work from the entire chat history when the recorded evidence suffices.

Before changing code, read:
1. `README.md`
2. `docs/specifications/requirements.md`
3. `docs/architecture.md`
4. `docs/permissions.md`
5. `docs/security.md`
6. `docs/roadmap.md`
7. `docs/branding.md`

Follow documented decisions. If requirements conflict or are incomplete, explain the trade-off and ask for a decision rather than silently redefining behavior. Do not create features beyond the agreed milestone without approval. Keep specifications and documentation aligned with implementation. Communicate with the project owner **in French**; write code identifiers and developer-facing technical comments in clear English.

## Confirmed functional rules
- Nested folders, categories, tags, and strict inherited access policies.
- Public, authenticated-user, and WordPress-role access; ordinary users receive the **intersection** of the policies of the document and all ancestor folders.
- WordPress administrators have global **content read access**, implemented using an explicit capability assigned to administrators by default. This does **not** bypass separate edit, delete, permission-management, or settings capabilities.
- For V1, viewing/previewing/downloading share one `read` permission. A preview is not DRM and cannot prevent an authorized user from saving content.
- Permanent logical document URLs, with authorization enforced on **every** request; never expose physical storage paths.
- Document details open first, with preview and download actions. Preview supports PDF and images, in configurable modal or dedicated-page layouts.
- Trash retention: **30 days** by default; automatic cleanup, configurable.
- Same-name upload: warn and offer rename or a new version; version replacement requires edit authorization.
- Version history is optional; when enabled, retain up to **10 versions** by default, configurable.
- Maximum upload size: **50 MB** by default, configurable and bounded by server limits.
- Audit log retention: **365 days** by default, configurable.
- Native shortcode, Gutenberg and Elementor integrations; admin and authorized frontend uploads; grid/list/explorer displays.
- French and English localization.
- Multiple-file ZIP download and S3/MinIO are **future scope**, not V1.

## Security invariants (non-negotiable)
1. **Fail closed**: deny access, uploads, or storage operations if authorization or storage protection cannot be established.
2. Default to managed encrypted storage under wp-content with verified anonymous HTTPS denial and a positive public control. Preserve explicit outside-webroot storage. Never rely on `.htaccess` alone. Fail closed on inconclusive verification and keep the encryption key separate from stored documents.
3. Centralize access decisions in one authorization service. Check authorization server-side for metadata, listings, download, preview, thumbnails, versions, search, and all mutations. Never trust hidden UI controls.
4. Validate authentication and WordPress capabilities on every privileged operation. Use nonces for CSRF protection where appropriate; **nonces are not authorization**.
5. Use WordPress database APIs with prepared statements; sanitize inputs, validate schemas, and escape outputs at the appropriate context. Apply least privilege.
6. Allowlist file types using extension and server-side MIME checks; validate sizes; generate opaque storage keys; avoid executing uploaded content. Handle temporary files and errors safely. Treat SVG/HTML and active content as unsafe unless explicitly reviewed.
7. For private binary responses, use appropriate cache headers and secure content-disposition/MIME handling. Implement HTTP Range support for PDFs only with correct authorization and boundary validation.
8. Prevent leaks through direct URLs, public thumbnails, backups, logs, REST responses, error messages, and caches.
9. Log sensitive actions and access denials without unnecessarily collecting personal data; respect retention settings.
10. Make trash/version cleanup idempotent and safe against races, interrupted operations, and deletion of active content.
11. Never commit credentials, tokens, real personal documents, environment secrets, or production data.
12. Do not claim absolute security or compatibility that has not been tested.

## Technical approach
- Target compatibility (not yet verified): WordPress **6.8+**, PHP **8.2+**, MySQL **8.0+** / MariaDB **10.11+**. Recommend HTTPS and currently supported PHP releases.
- Modular PHP services for documents, folders, permissions, storage, audit, and REST/HTTP controllers.
- Use a versioned REST namespace `filewarden/v1` for metadata and mutations; browser-compatible secure routes for binary content.
- Use the WordPress table prefix dynamically; migrations must be repeatable and safe.
- Use WordPress coding standards, PHP_CodeSniffer, PHPUnit, and existing GitHub Actions; add focused tests for each security-sensitive change.
- Prefer WordPress APIs and dependencies already in the repository. Avoid adding a framework or dependency without explaining its benefit and maintenance/security cost.
- Ship release ZIPs with runtime dependencies and built assets, excluding development-only files and secrets.
- Keep translations ready with the `filewarden` text domain.
- Do not delete user documents on plugin deactivation or uninstall without a separately designed, explicit policy.

## Branding & UI Guidelines

Before implementing or modifying any visual component, AI coding agents and contributors MUST read `docs/branding.md`.

This document is the authoritative source for FileWarden's visual identity and design guidelines.

### Brand Identity

- Follow the official FileWarden brand identity.
- Use centralized design tokens defined in the branding guidelines.
- Do not introduce arbitrary or unapproved brand colors.
- Ask for maintainer approval before changing the official brand identity, logo, colors or typography.

### Official Brand Assets

- Use the approved logo assets located in `assets/branding/`.
- Use the approved favicon and application icons located in `assets/favicon/`.
- Never recreate, recolor, distort or modify the official logo without approval.
- Preserve the original logo proportions, colors and transparency.
- Prefer SVG assets for scalable interfaces when a validated vector version is available.
- Use appropriately sized PNG assets when raster images are required.
- Do not introduce duplicate or unofficial logo variants.
- Optimize image assets without compromising visual quality.

### Interface Design & Accessibility

- Support accessible light and dark themes where implemented.
- Respect WCAG 2.2 AA accessibility requirements where applicable.
- Use semantic design tokens instead of hardcoded colors.
- Maintain consistent spacing, typography and component styling.
- Ensure responsive layouts and keyboard accessibility.
- Avoid overriding global WordPress styles.
- Follow WordPress administration interface conventions.

### Frontend Customization

- Keep frontend document displays customizable to match the host WordPress site's branding.
- Do not impose FileWarden's official colors or visual identity on frontend document displays.
- Maintain separation between internal FileWarden branding and user-configurable frontend styles.

### Branding Governance

- Consult `docs/branding.md` before introducing new visual assets or UI patterns.
- Reuse existing approved assets whenever possible.
- Any modification to the official brand identity requires maintainer approval.
- Keep branding documentation consistent with implemented assets.

## How to work on a task
1. Inspect relevant code, docs, and tests before editing.
2. State a small implementation plan and identify security implications.
3. Make focused changes; preserve existing functionality and public interfaces.
4. Add or update automated tests, including negative authorization cases.
5. Run applicable checks (PHP syntax, PHPCS, PHPUnit, frontend build, and security checks) when available. Report commands and actual outcomes; distinguish unavailable checks from passing checks.
6. Summarize files changed, decisions made, remaining risks, and suggested next step.
7. Ask before destructive changes, dependency overhauls, release publication, or changing agreed requirements.

## First milestone: v0.1.0 secure foundation
Prioritize activation/bootstrap, schema migrations, storage diagnostics, safe upload/download proof of concept, inherited ACL enforcement, audit events, and security-focused tests. Do **not** build the full visual file manager or announce a stable release before the secure foundation passes its acceptance criteria.
