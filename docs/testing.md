# Validation

## Local results — 2026-11-07

- PHP 8.2.35 with Sodium: **45 unit tests, 57 assertions passed**, including a multi-frame encryption round trip and rejection of altered, truncated, appended and wrong-key ciphertext.
- PHP syntax checks: passed for plugin, services, test scripts and ZIP builder.
- PHP_CodeSniffer / WordPress: passed with the documented PSR-4 and narrowly scoped I/O exceptions.
- Composer manifest/lock validation: passed; dependency audit reported no known vulnerability advisories.
- Development ZIP: generated from the explicit runtime allowlist and inspected.
- WordPress/MySQL integration and HTTP tests: **provided but not executed locally**; no configured WordPress/MySQL test server was available. Hosted preproduction checks remain pending.
- Managed mode still requires a hosted acceptance check. Verify a successful upload and download, direct HTTP denial, and that removal of the HTTP protection blocks storage operations. Use disposable documents.

## Unit and coding checks

Run `node --test tests/admin.test.cjs` with Node.js 22+ for the upload-interface regression tests. These execute the shipped JavaScript with DOM/transport doubles, including the WordPress hidden `action` field that shadows the native form property. The routing test fails against 0.1.4-dev and passes after the 0.1.5-dev fix. These tests complement HTTP integration; they are not an end-to-end browser test.

Run `composer install --no-interaction --no-plugins`, `composer lint`, and `composer test` with PHP 8.2+. Development dependencies are pinned in `composer.lock`. `fileinfo` is required at runtime; tests require PHPUnit's normal PHP extensions.

Coverage includes inherited policy intersections, missing parents/cycles, explicit read override, malformed policies, storage isolation, traversal, partial-write cleanup, size checks, and valid/invalid PDF ranges.

## Disposable integration tests

`.github/workflows/ci.yml` provisions MySQL 8.0 and clean WordPress installations with a nondefault table prefix. It runs `tests/integration.php` through WP-CLI, then starts a PHP server and runs `tests/http-smoke.py`.

These tests create users and documents. **Never run them against production or a shared preproduction database.** They require `FILEWARDEN_TESTING=1` and an explicit `FILEWARDEN_FIXTURES` output filename. Test passwords are disposable fixtures, not deployment credentials.

Coverage: repeat migrations, real WordPress capabilities, REST filtering, immediate ancestor-policy changes, no write escalation from read override, audit expiry, cookie authentication, public/private downloads, HEAD, ranges, CSRF refusal, valid/invalid uploads and duplicates.

The workflow definition is not proof of a passing CI run. Record actual results before accepting the milestone. Hosted Apache/Nginx aliases, cache rules, PHP upload limits and storage ACLs also require validation using [setup.md](setup.md).

## Packaging

`php tools/build.php` creates a development ZIP with a fixed runtime allowlist. It includes no `.git`, `.tools`, `vendor`, tests, credentials or server configuration. Runtime classes use the plugin's own small PSR-4 loader.
