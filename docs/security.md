# Security policy and design notes

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

Report vulnerabilities privately to the repository maintainers once a contact channel is published. Do not post exploitable reports as public issues.

## Corrective candidate: additional document formats

0.1.12-dev.1 accepts UTF-8 TXT/CSV, macro-free DOCX/XLSX/PPTX/ODT/ODS/ODP, and ZIP as protected attachments. Files are checked before persistence using their sanitized extension, actual content and server-derived type; submitted browser MIME is never trusted. CSV formulas, scripts, macro/ActiveX/embedded executable parts and external Office relationships/content are refused conservatively. Existing PDF/image validation and protected viewer behavior are preserved; new formats have no inline preview. This is structural content validation, not antivirus or an absolute guarantee against vulnerabilities in third-party document readers.

ZIPs remain byte-identical downloadable documents. They are inspected through bounded streams and private anonymous temporary files, never extracted to their member paths. Limits: 1,000 entries, 100 MiB total expanded content, 50 MiB per member and 100:1 expansion ratio; nested Office containers share the same budget. Nested ZIPs, encrypted members, symlinks, traversal/absolute/encoded paths, case-insensitive duplicate names, unsupported member types, executables and active content are rejected. Each stream must match its declared length and CRC. Invalid XML and DTD/entities are refused; XML parsing disables external resolution. A missing ZIP/XML extension or an unverifiable private inspection directory blocks affected imports. Upload and PHP server limits still apply.

The chosen limits deliberately reject some otherwise readable Office/ZIP files, including encrypted packages and documents containing external relationships. No import silently strips content or unpacks an archive into documents.

Manual trash disposal binds confirmation to the exact authorized selection and current policies. It verifies storage before marking records for deletion. An interrupted disposal leaves non-readable tombstones for the existing idempotent cleanup job. Database and document-storage backups remain a separate operator responsibility.

Archive validation also requires a ZIP signature and an allowlisted detected MIME, so executable/self-extracting prefixes are refused. Audit downloads are fully generated in a verified private anonymous temporary stream before successful attachment headers are emitted. Any query/encoding failure closes and removes partial output. A response length detects interrupted transfers; an audit export never becomes a public temporary download URL.

## Browser viewer

The 0.1.6 viewer uses locally bundled PDF.js 6.5.438 with evaluation disabled, XFA disabled, no scripting/annotation layer and canvas-rendered fonts. Each PDF/image request is independently authorized. Raw PDFs retain attachment disposition and a sandbox CSP; the trusted HTML viewer has a separate self-only CSP permitting its module, worker and WASM decoders. No document is transmitted to a remote viewer. Authorized users can save the bytes; this is not DRM. Maintain the pinned PDF.js distribution and its license notices with upstream security updates.
