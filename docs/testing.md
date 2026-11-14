# Validation

## 0.1.15-dev — final corrective local candidate

[Corrective local evidence](validation/v0.1.15-dev-corrections-local.json): fresh complete 6.8 acceptance passed; fresh 7.1.4 upgrade/integration and targeted rapid-preview/French/320px checks passed in both browsers. The final cleanup follow-up defers known maintenance/busy lock refusals, preserving audit records and allowing later WordPress cron callbacks; both installed ZIPs passed this targeted check. PHPUnit remains 75 tests/130 assertions, JS eight tests, PHPCS/syntax pass. The final local archive has 290 verified files, SHA-256 57bafc38e30ca435bd0c226096b0d7da9476c28c20cef5c31657d1da442e0e17. Final GitHub matrix and publication remain pending at this record; earlier evidence below refers to its own candidate.

## 0.1.15-dev — initial CI failure and corrective acceptance

[Initial CI 43639555618](https://github.com/MikaPST/filewarden/actions/runs/43639555618), commit f7db9f4, passed PHP 8.2.35/8.3.36/8.4.27 (75 tests/130 assertions each), JS, and real MySQL 8.0.46 database/upgrade integration. Browser jobs failed: five at French repair width 320px in Firefox/Linux, one at a stale publication preview after rapid changes and two at restoration navigation synchronization. No release/tag was created. [Preserved failure summary](validation/v0.1.15-dev-ci-initial.json).

The corrective candidate constrains timestamps and repair buttons, makes only administration previews eager and coalesces rapid form changes before frame navigation. Tests retain the original assertions, await actual preview/restore navigation, exercise rapid final-choice rendering and preserve overflow diagnostics/screenshots. A new complete WordPress 6.8 ZIP-upgrade/integration/HTTP/Chromium/Firefox EN/FR run and five-cycle rapid-preview stress in both browsers passed locally. A new independent 7.1.4 fixture and the corrected CI matrix are pending at this stage. Earlier local evidence below describes the first Windows candidate and remains unchanged.

## 0.1.15-dev — local acceptance, 2026-11-14

All nine approved changes and historical non-regression suites passed locally: PHP 8.2.35, MariaDB 10.11.11, actual ZIP upgrades from 0.1.14-dev with three MyISAM tables on fresh WordPress 6.8 and 7.1.4. InnoDB repair remained explicit and preserved existing data. Integration, HTTP, Chromium/Firefox EN/FR, 75 PHP tests/130 assertions, eight JavaScript tests, syntax, PHPCS, Composer validation/audit and WordPress Sodium fallback passed. New failure-injection, concurrent-gate, checkpoint/reactivation and French 320px/375px repair checks passed. See [structured local evidence](validation/v0.1.15-dev-local.json) and [the nine-point matrix and repair procedure](lot-v0.1.15-dev.md).

The final local ZIP has 290 files, verified against sources and both installed final archives; SHA-256 a8f0c1d0f9b3285473c079bc5884aab9c29d979a0572a731080f7cf96a636364. It differs from the full application-acceptance ZIP only in the WordPress release-note text and Lucide attribution inventory; all application bytes match. The CI/release archive will be independently built and tested on Linux, then verified against its tag.

Preliminary browser failures were test-harness issues: cross-context simulated-clock leakage, an unbounded audit-navigation wait and an assumed initial folded-menu state. The notification suite now owns a separate browser context with a fixed clock; navigation is explicit/bounded and menu checks use actual state. Final complete runs passed without suppressing assertions. PHP 8.3/8.4 and MySQL 8.0 remain pending GitHub CI at this stage; target-host deployment/security acceptance and full human screen-reader review are not claimed.

## Published 0.1.14-dev — 2026-11-14

Tag v0.1.14-dev points to c51f6e5, with the same runtime as validated code 58f6cfe. [Main CI](https://github.com/MikaPST/filewarden/actions/runs/43623671639) and [release workflow](https://github.com/MikaPST/filewarden/actions/runs/43623671643) succeeded. The first release attempt failed only at Firefox clock.pauseAt (Cannot fast-forward to the past); requested failed-job rerun succeeded on attempt 2 without runtime changes. Preserve this trace and stabilize the test clock separately.

The public release ZIP was downloaded: 279 runtime files match the tag byte-for-byte, version/schema and release notes verified. Its SHA-256 is 9a887e3bca9dc7e9ab9ec0505fb6c3eb4efac9c714934f8500a4c3b7fb91023b, confirmed against the attached checksum and GitHub asset digest. [Published evidence](validation/v0.1.14-dev-published.json). Hosting-specific deletion refusal remains open for 0.1.15-dev diagnosis; no deployment or human screen-reader acceptance claimed.

## GitHub CI 0.1.14-dev — 2026-11-14

Code commit [58f6cfe](https://github.com/MikaPST/filewarden/commit/58f6cfee0963270897bea3ce7ef0fbba0b97478d) was pushed to main with owner approval. [CI run 43616653528](https://github.com/MikaPST/filewarden/actions/runs/43616653528) completed successfully: ten jobs, PHP 8.2–8.4 (75 tests / 130 assertions each), eight JavaScript tests, six actual ZIP upgrades from 0.1.11/12/13-dev across WordPress 6.8 and 7.1.4, MySQL 8.0, integration/HTTP and Chromium/Firefox EN/FR. The [exact tested ZIP](https://github.com/MikaPST/filewarden/actions/runs/43616653528/artifacts/14122811563) is preserved as an Actions artifact. See [structured evidence](validation/v0.1.14-dev-ci.json). Documentation tracking follows without runtime changes; no tag, release or user-hosting deployment was authorized or performed. Historical local evidence below is retained unchanged in scope.

## Corrective acceptance 0.1.14-dev — 2026-11-14

The corrected ZIP (a89e68643c064308413464198285a4c71937d36c95b0c4ccf6efd1c94993961e, 279 runtime files) passed PHPUnit (75 tests / 130 assertions), eight JavaScript tests, syntax/PHPCS, Composer validation/audit and WordPress Sodium fallback locally. Fresh WordPress 6.8 and 7.1.4 installations on PHP 8.2.35/MariaDB 10.11.11 passed actual ZIP updates from 0.1.13-dev through the initial 0.1.14-dev candidate to the corrected same-version ZIP, integration/HTTP and Chromium/Firefox EN/FR. All runtime bytes match both final installations.

The six corrections include actual floating visibility, native-dialog stacking, generic WP notice-hiding compatibility, contained long names, compact tree and welcome icon, extensible settings without lost inputs, and successful/refused transactional deletion with preserved recovery. New failure-injection fixtures are test-only and excluded from the ZIP. Hosting-specific deletion failure remains unconfirmed; no CI, PHP 8.3/8.4, MySQL 8.0 or complete human screen-reader acceptance is claimed for this local batch. See [matrix and limits](corrections-v0.1.14-dev.md) and [sanitized evidence](validation/v0.1.14-dev-corrections-local.txt). Initial candidate evidence remains separate and unchanged.

## Lot 0.1.14-dev — réception locale du 14 novembre 2026

Les trois améliorations UI/UX et leurs non-régressions ont été testées sur le ZIP local installé dans WordPress 6.8 et 7.1.4 jetables, PHP 8.2.35/MariaDB 10.11.11, Chromium/Firefox EN/FR. Mise à jour réelle depuis 0.1.13-dev sans perte ; 75 tests PHP/130 assertions et huit tests JavaScript réussis, syntaxe/PHPCS/Composer et secours Sodium vérifiés. [Matrice, archive, résultats et limites](lot-v0.1.14-dev.md), [sorties finales](validation/v0.1.14-dev-local.txt). Aucun push ni CI GitHub exécuté pour ce candidat à cette étape ; publication soumise à l’accord explicite du propriétaire.

## Lot 0.1.12-dev — 2026-11-13

- [CI finale du code b6968e4](https://github.com/MikaPST/filewarden/actions/runs/43488511977) : tous les jobs réussis.
- PHP 8.2–8.4 : 50 tests unitaires, 71 assertions ; syntaxe, PHPCS, audit Composer et génération du ZIP réussis. 8 tests JavaScript réussis.
- WordPress 6.8 et version courante (7.1.4 lors de cette exécution), MySQL 8.0 : migrations, HTTP, ACL et parcours Chromium/Firefox réussis dans des installations jetables.
- Nouveaux contrôles : migration du journal 4 vers 5 sans perte, login WordPress mémorisé, rapprochement historique, filtre hostile, accès à l’audit sans droit de purge ni noms de documents privés, exports CSV/TXT/JSONL de 1 005 événements et exports sans résultat, refus des nonces/capacités et retour d’erreur navigateur.
- Captures ordinateur/mobile inspectées ; ZIP local inspecté (265 entrées runtime), version et schéma vérifiés, outils de développement exclus, licence des pictogrammes incluse.
- Les parcours attendent explicitement la navigation du panneau avant de manipuler ses formulaires et conservent des captures/diagnostics en cas d’échec. Les échecs intermédiaires restent consultables dans les exécutions précédentes de la branche de validation.
- [CI sur main](https://github.com/MikaPST/filewarden/actions/runs/43489443735) et [validation de release](https://github.com/MikaPST/filewarden/actions/runs/43489504775) également réussies ; préversion v0.1.12-dev publiée. ZIP publié téléchargé et inspecté, empreinte vérifiée contre le fichier `.sha256` et les métadonnées GitHub : `9d83b8116631ff1fa32047a52ea8a3c9ae5c62399f14c9f228f94931232b324d`. Réception sur l’hébergement utilisateur toujours distincte.


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

CI also installs pinned Playwright 1.65.0 as a test-only tool and runs `tests/viewer-browser.cjs` in Chromium and Firefox against the disposable WordPress instances. It creates a synthetic two-page PDF, submits it through the real JavaScript form, verifies rendered pixels/page navigation/zoom, new-tab viewing, modal close/focus, an image and anonymous denial. It never targets the shared preproduction site.

`php tools/build.php` creates a development ZIP with a fixed runtime allowlist. It includes no `.git`, `.tools`, `vendor`, tests, credentials or server configuration. Runtime classes use the plugin's own small PSR-4 loader.
