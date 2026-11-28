# Security policy and design notes

## Lot autorisé 0.1.19-dev

Voir [le périmètre des 16 points](lot-v0.1.19-dev.md) et [les notes du candidat](releases/v0.1.19-dev.md). Les contrôles d’accès et capacités restent inchangés. La migration additive de schéma 8 ajoute une date d’arrivée nullable ; aucune date historique inconnue n’est reconstruite. Le manifeste 3 inclut huit composants, avec import des anciens manifestes 1/2. Les paquets de configuration seule peuvent être appliqués sur une bibliothèque existante sans modifier documents, dossiers ni politiques ; une restauration de bibliothèque exige toujours une destination vide. Le chiffrement, les validations serveur, la confirmation, le verrou d’écriture et la reprise transactionnelle restent requis. Les mots de passe et clés du site source ne sont jamais exportés.


## Nouvelle passe UI et sauvegarde sélective — 0.1.18-dev

Lot validé par le propriétaire le 23 novembre 2026 : recherche/filtres homogènes Documents/Corbeille, cartes mobiles de corbeille, états publics vides centrés, destination lisible, suppressions multiples confirmées et modales d’action. Le Diagnostic différencie le module Sodium absent et ouvre l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration, sans conversion au clic du lien. Sauvegarde complète ou transfert simplifié, options authentifiées indépendantes et restauration privée à l’administration sans reprise des droits ; attribution globale des créateurs par défaut dans l’interface. Voir [le guide sauvegarde](backup.md) et [le périmètre de correction](lot-ui-v0.1.18-dev.md). Résultats de cette passe dans `docs/validation/v0.1.18-dev-ui-pass-local.json` ; les preuves des anciens ZIP restent historiques.


## Corrective candidate 0.1.18-dev

Diagnostic reports use an explicit safe-field whitelist. Bounded in-memory Sodium/Argon2id checks and an exclusive random private storage witness do not modify library documents; the witness is removed in finally. Unknown measurements stay unknown. SQL metadata denial never implies transaction readiness. The diagnostic does not repair or certify a deployment.

Audit network collection is opt-in, off by default, limited to an explicit sensitive-event catalog. Retention defaults to 30 days and cannot exceed audit retention. Expired addresses are hidden immediately from details/exports and physically cleared by bounded cleanup. REMOTE_ADDR is authoritative unless FILEWARDEN_TRUSTED_PROXIES is explicitly defined by the server administrator as an array of trusted IP/CIDR ranges. In that case, a bounded valid X-Forwarded-For chain is walked from the right, discarding trusted hops; malformed headers fall back to REMOTE_ADDR. Never populate this constant from a request. CLI/cron and ordinary viewing/download events do not collect an address. An IP is not proof of identity.

Schema 7 adds only audit IP/expiry fields. Legacy encrypted job and database-repair barriers defer migration while keeping recovery routes available on schema 6. Backup manifests accept both schemas; restoration resets IP capture off and does not import source security secrets. Audit exports use schema 3. Legacy source actors with coincident numeric IDs are not resolved as current-site accounts.

Elementor popup mode accepts only a positive numeric template ID. The same-origin parent frame binds user click/keyboard actions directly to the known popup API; no arbitrary encoded action, cross-origin message or script is accepted. Missing Elementor Pro leaves the native WordPress sign-in fallback.

FileWarden is pre-alpha and must not be deployed for confidential documents.

## Explicit legacy database-engine repair (0.1.15-dev)

New tables explicitly require InnoDB. Existing MyISAM tables are never silently converted during an update: a settings-capability check, admin nonce and explicit backup/maintenance confirmation protect the repair action. A reentrant connection-level write gate drains plugin mutations and audit writers. Bounded content and schema/index/collation/next-ID fingerprints verify the exact three allowlisted tables; no document bytes, policies or identities are rewritten.

ALTER TABLE commits implicitly, so a multi-table conversion has no global rollback guarantee. A durable checkpoint blocks further plugin writes on an incomplete repair; retry compares original fingerprints before resuming. Reactivation with an existing schema-6 checkpoint preserves access to the repair screen without schema writes. External database writers must be paused separately. Unknown engines, missing metadata/tables or any integrity mismatch fail closed. The operator must hold a complete database/key/storage backup; FileWarden does not claim to have created or verified that backup. See the [repair procedure and validation scope](lot-v0.1.15-dev.md).

## Design commitments

- Deny by default, fail closed on insecure storage.
- Managed storage combines a positive public HTTPS control and a denied storage witness with authenticated Sodium encryption. Probes cover the configured content URL, so alternate domains and aliases still require host verification.
- Protect the independent WordPress database key and backup copies. A filesystem leak alone exposes encrypted bytes. Access to both the key and files permits decryption.
- Never rely on `.htaccess` alone for Nginx or unknown hosting.
- Do not expose raw physical storage paths or media-library URLs.
- Validate upload extensions, MIME, size and content; disable execution in storage.
- Re-authorize every binary request and use appropriate private/no-store cache controls.
- Handle PDF range requests, active content, thumbnails and temporary files safely.
- Use capability checks and nonces for administrative mutations; a nonce is not authorization.
- Audit sensitive operations and minimize personal data retained.

## Explicit all-format opt-in (0.1.16-dev)

The default remains the strict extension/content allowlist. Only a settings manager, through the existing nonce-protected transport, may opt in to all formats after explicit risk confirmation and saving. Enabling/disabling is recorded with the WordPress actor. This is a reduction of content restrictions, not malware detection or a claim that the accepted bytes are safe to open.

Both local uploads and readable local Media Library imports use the same service. Files that pass the original validator preserve their existing MIME/preview behavior. Other content is stored byte-identically with `application/octet-stream`: attachment disposition, nosniff, sandbox CSP and no inline preview remain enforced independently of the current setting. Arbitrary source MIME/extension never grants a preview type. Files are never executed or extracted to document paths. The media picker can select additional existing formats but does not change WordPress's own upload rules or protect the original media copy.

Size limits, names/provenance/path checks, inherited ACL, recovery nonces, opaque storage keys, encryption and storage verification stay mandatory. A persisted opt-in is rechecked after acquiring the common writer gate; changing the setting uses that gate too. No client-provided flag authorizes an unrestricted import. Disabling restores validation for subsequent imports without deleting or rewriting existing attachments, whose access rules remain enforced. Existing documents are not automatically published by changing this option.

Plugin language and translated confirmation text never affect authorization, stored policies, audit event codes, resource snapshots or export schema 2. Language identifiers are a fixed catalog registry, not file paths from a request; the translation-file override is restricted to the `filewarden` domain.

Report vulnerabilities privately to the repository maintainers once a contact channel is published. Do not post exploitable reports as public issues.

## Public appearance and visitor sign-in (0.1.17-dev)

Presentation settings do not participate in document authorization. Public colors are limited to ten validated hexadecimal tokens with server-side contrast checks; names are plain text and custom logos retain the independent PNG/JPEG media checks. File-type artwork is never recolored. Public stylesheets use content digests so replacing a development archive of the same version does not keep the previous cached layout.

The site-wide visitor sign-in link defaults to `wp_login_url()` with WordPress filters and the existing validated local return page. A user with the separate settings capability may explicitly save an absolute HTTP(S) URL instead. The custom URL is used literally; FileWarden does not append return arguments, fetch the URL, implement authentication or grant access based on this setting. Reject unsupported schemes, relative/protocol-relative URLs, credentials, control characters and oversized input; malformed stored data falls back to WordPress. Save through the existing capability/nonce/maintenance-lock/audit flow. Anonymous request parameters cannot override the saved destination. Draft changes affect only the same-origin preview, and all output URLs remain escaped.

## Corrective candidate: additional document formats

0.1.12-dev.1 accepts UTF-8 TXT/CSV, macro-free DOCX/XLSX/PPTX/ODT/ODS/ODP, and ZIP as protected attachments. Files are checked before persistence using their sanitized extension, actual content and server-derived type; submitted browser MIME is never trusted. CSV formulas, scripts, macro/ActiveX/embedded executable parts and external Office relationships/content are refused conservatively. Existing PDF/image validation and protected viewer behavior are preserved; new formats have no inline preview. This is structural content validation, not antivirus or an absolute guarantee against vulnerabilities in third-party document readers.

ZIPs remain byte-identical downloadable documents. They are inspected through bounded streams and private anonymous temporary files, never extracted to their member paths. Limits: 1,000 entries, 100 MiB total expanded content, 50 MiB per member and 100:1 expansion ratio; nested Office containers share the same budget. Nested ZIPs, encrypted members, symlinks, traversal/absolute/encoded paths, case-insensitive duplicate names, unsupported member types, executables and active content are rejected. Each stream must match its declared length and CRC. Invalid XML and DTD/entities are refused; XML parsing disables external resolution. A missing ZIP/XML extension or an unverifiable private inspection directory blocks affected imports. Upload and PHP server limits still apply.

The chosen limits deliberately reject some otherwise readable Office/ZIP files, including encrypted packages and documents containing external relationships. No import silently strips content or unpacks an archive into documents.

Manual trash disposal binds confirmation to the exact authorized selection and current policies. It verifies storage before marking records for deletion. An interrupted disposal leaves non-readable tombstones for the existing idempotent cleanup job. Database and document-storage backups remain a separate operator responsibility.

Archive validation also requires a ZIP signature and an allowlisted detected MIME, so executable/self-extracting prefixes are refused. Audit downloads are fully generated in a verified private anonymous temporary stream before successful attachment headers are emitted. Any query/encoding failure closes and removes partial output. A response length detects interrupted transfers; an audit export never becomes a public temporary download URL.

## Browser viewer

The 0.1.6 viewer uses locally bundled PDF.js 6.5.438 with evaluation disabled, XFA disabled, no scripting/annotation layer and canvas-rendered fonts. Each PDF/image request is independently authorized. Raw PDFs retain attachment disposition and a sandbox CSP; the trusted HTML viewer has a separate self-only CSP permitting its module, worker and WASM decoders. No document is transmitted to a remote viewer. Authorized users can save the bytes; this is not DRM. Maintain the pinned PDF.js distribution and its license notices with upstream security updates.

## Document lot 0.1.19-dev

Descriptions remain private document metadata, not executable HTML or a public search field. Server validation rejects invalid UTF-8/overlength values; backup validation also rejects noncanonical markup, unexpected types and overlength values before activation. Archive memory budgets include descriptions. JSON review responses and REST errors are private/no-store; native fallback review is user-scoped, short-lived and read-checked again. A hidden duplicate is neither named nor reported as a match. A stored equal hash is insufficient: original bytes must match, with bounded private buffers. User/session-bound confirmation is invalidated by candidate rule changes. The display does not independently calculate permissions.

Local native encrypted-stream tests isolate only the hosting HTTPS protection probe. They do not certify deployment isolation or PHP 8.5/LiteSpeed compatibility. Run the preproduction Diagnostic and human acceptance after installing the candidate.
