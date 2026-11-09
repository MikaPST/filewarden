# Changelog

## 0.1.10-dev

- Task tabs, collapsible folder tree, searchable document table and contextual rights/action inspector.
- Distinguish explicit file rules from effective access; explain public-file restrictions under private folders.
- Confirmed move/copy with destination-policy review, stable move URLs and independent verified copy storage.
- Recoverable trash, duplicate-safe restoration and retry-safe scheduled cleanup (30 days, configurable).
- Repeatable schema 3 migration preserving existing documents and permissions; French workspace translations.
- WordPress/browser regression tests for transfers, trash, restoration, public warnings and responsive navigation.

## 0.1.9-dev

- Edit folder/file access policies after creation, with permission checks, CSRF protection and audit events.
- New documents default to dynamic folder inheritance; existing policies remain unchanged.
- Explain that file and folder restrictions apply together; public files cannot bypass private folders.
- Test policy editing, immediate revocation, anonymous filtering and viewer/download authorization.

## 0.1.8-dev

- Permanent folder UUIDs in generated shortcodes and public navigation, with automatic repeatable schema upgrade and legacy numeric shortcode compatibility.
- Backend shows folder/file policies, inherited rules and document icons.
- Migration, UUID access denial, shortcode and browser regression tests.

## 0.1.7-dev

- Frontend document library: detailed list/grid, responsive cards, filename search with ACL-filtered pagination, date/size and preview/download buttons.
- Ten original folded-page SVG file-type icons, local French frontend translations and automatic shortcode frame height.
- Publication builder offers a default view; existing shortcodes remain compatible.
- Office icons are prepared; accepted upload formats remain PDF/JPEG/PNG.

## 0.1.6-dev — 2026-11-07

- Replace host-dependent PDF rasterization with bundled Mozilla PDF.js 6.5.438: all pages, page navigation, fit-width and zoom, without sending documents to third parties.
- Add a full-size administrator preview dialog with Escape/close and focus restoration; preserve new-tab links without JavaScript and from shortcode publications.
- Add a responsive image viewer with zoom and download/new-tab actions.
- Keep authorization on both viewer and binary requests, private cache headers and attachment-only raw PDFs. No PDF scripts, XFA forms or interactive annotations are executed by the viewer.
- Add Chromium/Firefox tests for actual browser upload, PDF rendering, navigation, zoom, dialog focus, image rendering and denied anonymous access.
- PDF/JPEG/PNG remain the accepted upload types. Office documents and other formats are not added by this release.

## 0.1.5-dev — 2026-11-07

- Fix direct browser uploads returning HTTP 404: the hidden WordPress `action` input shadowed `form.action`. Read the form's HTML action attribute to obtain the upload URL.
- Merge computer upload and Media Library import into one form with a source selector, one destination, one access policy and one submit button. Inactive source fields are excluded from the request.
- Preserve Media Library preselection and server-side capability, nonce, MIME and storage checks. Direct uploads remain private and do not create public Media Library attachments.
- Add dependency-free JavaScript regression tests for routing, source switching, duplicate submission, progress and failed responses. Extend WordPress HTTP checks for the unified form and invalid/unauthorized requests.

## 0.1.4-dev — 2026-11-07

- Fix encrypted uploads larger than 64 KiB when WordPress supplies Sodium compatibility functions without the native `TAG_MESSAGE` constant. The encrypted format is unchanged.
- Verify stored bytes and the database destination before confirming an upload; distinguish storage, database and audit failures.
- Use named folder selectors, a document table, preview/download links and a named destination confirmation.
- Show upload progress followed by a separate encryption/verification stage. Report unconfirmed HTTP/network responses without claiming success.
- Explain virtual folders and persistent encrypted storage outside the plugin directory.
- Add a regression check against WordPress's actual Sodium fallback, including frame boundaries and corrupted ciphertext.
- Publish visible GitHub releases with an installable ZIP and SHA-256 after CI succeeds; development versions remain prereleases.

Target-host check: synthetic PDF uploads of approximately 100 KB, 1 MB and 5 MB failed before the encryption correction and succeeded afterwards, with destination and byte verification. Test documents were removed. This is not full production/security acceptance.

## Earlier development foundation

- Added default encrypted shared-hosting storage with anonymous HTTPS verification and retained the explicit outside-webroot mode.

- Implemented 0.1.0-dev single-site foundation: schema/capabilities, private local storage, inherited policies, protected upload/download, minimal administration and audit retention.
- Added unit tests, disposable WordPress/HTTP integration workflow, dependency lockfile and runtime-only development ZIP builder.
- Added preproduction instructions. Full V1 features and target-host acceptance remain pending.
