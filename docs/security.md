# Security policy and design notes

FileWarden is pre-alpha and must not be deployed for confidential documents.

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

## Browser viewer

The 0.1.6 viewer uses locally bundled PDF.js 6.5.438 with evaluation disabled, XFA disabled, no scripting/annotation layer and canvas-rendered fonts. Each PDF/image request is independently authorized. Raw PDFs retain attachment disposition and a sandbox CSP; the trusted HTML viewer has a separate self-only CSP permitting its module, worker and WASM decoders. No document is transmitted to a remote viewer. Authorized users can save the bytes; this is not DRM. Maintain the pinned PDF.js distribution and its license notices with upstream security updates.
