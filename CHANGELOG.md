# Changelog

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
