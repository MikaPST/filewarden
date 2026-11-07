# Security policy and design notes

FileWarden is pre-alpha and must not be deployed for confidential documents.

## Design commitments

- Deny by default, fail closed on insecure storage.
- Never rely on `.htaccess` alone for Nginx or unknown hosting.
- Do not expose raw physical storage paths or media-library URLs.
- Validate upload extensions, MIME, size and content; disable execution in storage.
- Re-authorize every binary request and use appropriate private/no-store cache controls.
- Handle PDF range requests, active content, thumbnails and temporary files safely.
- Use capability checks and nonces for administrative mutations; a nonce is not authorization.
- Audit sensitive operations and minimize personal data retained.

Report vulnerabilities privately to the repository maintainers once a contact channel is published. Do not post exploitable reports as public issues.
