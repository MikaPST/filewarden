# Validation

## Final UI correction acceptance — 0.1.17-dev, 2026-11-22

Latest ZIP: **317 files / 4,296,190 bytes**, SHA-256 `d83119e346919b82e2071b833df3957cb9b66ae63ec09ad68e69df68c1e4b59a`. Its bytes match the current packaged sources and both installed WordPress fixtures; no rebuild followed final acceptance. The prior b5ff archive is preserved separately.

88 PHP tests / 192 assertions, eight JS tests, PHPCS, syntax (70 PHP / 30 JS), Composer validation/audit and 472 FR/EN source entries passed. Full WordPress integration/HTTP on 7.1.5 and a real same-version 6.8 upgrade check preserve documents/folders/audit, language, appearance, settings and protected bytes. Native WordPress Shortcode-block rendering passes on both. Chromium/Firefox EN/FR verify external full-width tabs, contextual controls with preserved values/drafts, exact translated palette/copy guidance, the independent compact code card, real/refused clipboard operations, recovery amber/contrast, mobile 375/320px and no-JS fallback. Prior corrective UI suites passed before the final card-height adjustment; affected paths were retested on the final exact archive. The full historical browser matrix was not rerun for these presentation changes.

Visual QA caught an inherited 420px card minimum and added actual compact-height/whitespace assertions. An isolated generic Firefox pageerror was not reproduced in instrumented and final full EN/FR reruns; its origin was not established and no error assertion was filtered. PHP server logs contain no warnings/fatals/parse errors. Identified test services stopped; fixtures retained. Local Windows/PHP 8.2.35/MariaDB 10.11.11 only; no owner-host acceptance, remote CI or accessibility/security certification. [Scope and results](corrections-finales-v0.1.17-dev.md), [structured proof and diagnostics](validation/v0.1.17-dev-final-corrections-local.json).

## Corrective archive acceptance — 0.1.17-dev, 2026-11-21 (historical)

The ten owner-approved corrections are implemented and locally validated. **88 PHP tests / 192 assertions**, eight JS tests, PHPCS, syntax (69 PHP / 29 JS files), Composer validation/audit and 471 current FR/EN source entries passed. The archive remains 0.1.17-dev, schema 6; no GitHub push, tag or release.

Actual initial-0.1.17 ZIP upgrades passed on WordPress 6.8 and 7.1.5 with MariaDB 10.11.11/PHP 8.2.35. Existing document bytes, references, policies, folder metadata and settings survived; the 6.8 fixture also checks an existing public name/palette and language choice. Full PHP integration and HTTP suites preserve the original document/security behavior. The grouped historical suite passed Chromium and Firefox on 7.1.5; the historical French suite passed both engines on 6.8. Targeted corrective scenarios passed both engines in FR/EN, including tabs/drafts, all ten colors, template capsules, inline controls, literal/native login URLs, CSRF/capability/unsafe URL refusals, real subfolder navigation, natural grid padding, keyboard/no-JS fallback, mobile and 44px touch controls. Fresh 7.1.5 installation and first-folder/document onboarding passed; reinstalling the final ZIP preserved that first document’s exact bytes.

The last changes were limited to public CSS cache URLs and coarse-pointer selector specificity. Their affected paths were retested on the final archive with PHP/HTTP and targeted Chromium/Firefox scenarios. The final ZIP was reinstalled on all three final fixtures and compared byte-for-byte with every packaged source. It contains **317 files / 4,294,924 bytes**, SHA-256 `b5ff1e7e4f50ce37837038555541be07a054905ff2d4371444d592594a21df49`. All 35 original icon/branding/illustration files from the initial 0.1.17 ZIP retain their contents (only SVG line endings normalized for comparison). The archive excludes local fixtures, tests, tools, development dependencies and credentials; no rebuild followed final acceptance.

No PHP warnings/fatal/parse/deprecation/notice errors were found in the four corrective server logs. Identified disposable web/database services were stopped; fixtures/screenshots remain under ignored `.tools/v17fix-dev/`. PHP 8.3/8.4, MySQL, Linux and remote CI were not run for this corrective archive. The local test storage does not validate the owner’s hosting or site-specific integrations. No full human screen-reader audit or absolute security claim. [Ten-correction matrix](corrections-v0.1.17-dev.md), [structured evidence and harness diagnostics](validation/v0.1.17-dev-corrections-local.json).

## Initial candidate acceptance — 0.1.17-dev, 2026-11-21 (historical)

All nine approved axes and the historical non-regression components passed on the actual local ZIP, without a GitHub push/tag/release. Independent WordPress **6.8** and **7.1.5** installations upgraded the published **0.1.16-dev** archive, preserving document bytes, UUIDs, folder relationships, access policies, settings and storage keys. Schema remains 6.

Environment: Windows, PHP **8.2.35**, MariaDB **10.11.11**, PHPUnit **10.5.66**, Node **24.22.3**, Playwright **1.65.0**, Chromium **158.0.8127.18**, Firefox **159.0**. PHPUnit: **82 tests/167 assertions**; JavaScript: **eight tests**; syntax: **66 PHP/28 JavaScript files**; PHPCS, Composer validation/audit and **453** complete FR/EN source entries/placeholders/plurals/MO passed. No dependency advisories were reported at audit time.

Installed-ZIP coverage includes the original ten evolutions; real MyISAM/InnoDB failure/repair/recovery; actual HTTP imports, strict/permissive/strict transitions, protected bytes, PDF ranges, authorization, CSRF and CSV/TXT/JSONL downloads; contextual creation and fixed upload destinations; breadcrumbs, per-file retained-rule disclosures, combined trash filters/icons/retention; public palettes/name/logo, isolated draft preview, server save/reset, nonce CSP and exact grid-identity centering. Chromium/Firefox EN/FR passed on both WordPress versions. A separate fresh 7.1.5 installation passed InnoDB/safe defaults, first-root explicit rights, inherited child creation, dirty-cancel confirmation, contextual upload and mobile acceptance. Valid PNG/JPEG logos and MIME/extension/content/size/dimension/origin/path/media-capability refusals were exercised; all-format document permission never relaxed logo restrictions.

Historical harness expectations were adapted to the approved interface: open the folded integration disclosure, new row/button/header dimensions and colors, current asset version, native media-library tab, explicit dirty-form confirmation, and unique synthetic filenames passed between scenarios. Earlier incomplete fixture runs were not declared successful. On WordPress 6.8, the initial Chromium components and resumed correction/language components passed separately, followed by full Firefox and FR acceptance; no functional/security assertion was waived. Detailed evidence: [local JSON](validation/v0.1.17-dev-local.json), [nine-axis matrix](lot-v0.1.17-dev.md).

The **305-file**, **4,283,227-byte** ZIP matches every source and installed runtime file on all three final fixtures. Original logos/illustration and file-type artwork remain unchanged; SVG comparison normalizes line endings. SHA-256: `7d5d0f3e676f21820da40172f1a2df1b441758841de3b99727f554fb75335c7a`. Packaging excludes tests, tools, development dependencies, credentials and local fixtures.

No PHP warnings/fatal/parse errors were found in the three final server logs. Screenshots and disposable fixture logs remain under ignored `.tools/v17-dev/`. PHP 8.3/8.4, MySQL 8.0, Linux and remote GitHub CI were not run for this local candidate. The CI definition includes the new browser/security checks and the 0.1.16 upgrade path, but is not proof of an executed run. No target-host deployment, production document access, full human screen-reader review or blanket compatibility/security certification is claimed.


## Published 0.1.16-dev — 2026-11-20

Code [ed43ab8](https://github.com/MikaPST/filewarden/commit/ed43ab88a2317a9c8dde19496ac02e58efb08d28) passed the complete [source CI](https://github.com/MikaPST/filewarden/actions/runs/44777904156): all 15 jobs succeeded. Recorded versions: PHP 8.2.36, 8.3.37, 8.4.28, MySQL 8.0.46, WordPress 6.8, 7.1.5. All ten upgrade combinations, fresh installation, integration/HTTP and Chromium/Firefox EN/FR passed; PHPUnit 76 tests/140 assertions per PHP, eight JS tests, 393 current translation entries, syntax/PHPCS and dependency audits passed. [Structured CI proof](validation/v0.1.16-dev-ci.json).

The separate tag-publication workflow did not pass: some runners could not install WP-CLI before tests; browser installers also stalled. Its three attempts and cancellation are retained. Publication promoted the exact installed/tested candidate from the successful complete source CI after rechecking every job, the immutable remote tag and each runtime blob. Assets were uploaded to a draft, their sizes/digests verified, then published and downloaded again. No application test assertion was waived. Do not describe the duplicate publication workflow as 16/16 green. [Initial infrastructure failure](validation/v0.1.16-dev-release-ci-initial.json), [promotion and history](validation/v0.1.16-dev-publication.json).

The public ZIP contains 301 files, SHA-256 `8c0054772640788ea0328bbf6275de108cf8cf56a6f5818ace92860ee4d49e1b`, identical to the successful source-CI artifact and runtime tag contents. Checksum, GitHub digests, release notes, version/schema, illustration and catalogs were verified. [Public verification](validation/v0.1.16-dev-published.json). No target-host installation or full human screen-reader acceptance is claimed.


## Local acceptance — 0.1.16-dev, 2026-11-15

Follow-up on 2026-11-20: the first remote CI finished with 14/15 jobs passing. One Firefox language scenario timed out waiting for global network silence, after its upgrade, integration, HTTP and historical browser assertions had passed. [Initial failure evidence](validation/v0.1.16-dev-ci-initial.json). The test helper now waits only for page loading and native WordPress `users/me` responses, so unrelated background traffic cannot hold the suite open. Submit clicks retain their explicit navigation wait without a competing implicit wait. All functional/security assertions and the zero JavaScript-error assertion remain. Runtime files are unchanged; [focused local verification](validation/v0.1.16-dev-browser-sync-local.json) passed in both browsers on WordPress 6.8/7.1.4. The subsequent complete source CI passed all 15 jobs; see the published evidence above.

The final 301-file ZIP passed fresh complete upgrade/integration/HTTP/Chromium/Firefox EN/FR acceptance on WordPress 6.8 and 7.1.4, PHP 8.2.35/MariaDB 10.11.11. Both runs upgraded the actual public 0.1.15-dev ZIP and preserved documents, policies, identities and settings. Fresh-install defaults/InnoDB and absent destinations were separately exercised. PHPUnit: 76 tests/140 assertions; JavaScript: eight tests; syntax, PHPCS, Composer validation/audit, WordPress Sodium fallback and 393 current FR/EN catalog entries passed. [Structured evidence](validation/v0.1.16-dev-local.json), [ten-point matrix](lot-v0.1.16-dev.md).

Initial Firefox failures were reproduced as native WordPress users/me requests aborted by rapid test navigation. Tests now await native request completion and keep the zero-error assertion. Export surface specificity and narrow-column badge alignment were corrected and tested on the final ZIP. Catalog extraction uses official WP-CLI; check `python3 tools/translation-catalogs.py --check` for coverage/placeholder/plural/MO integrity. `tests/fresh-install.php` requires empty disposable plugin tables; never run it against a shared site. CI additionally covers PHP 8.3/8.4, MySQL 8.0 and ten upgrade combinations; its results remain separate from this local record. No target-host deployment or full human screen-reader acceptance is claimed.


## Published 0.1.15-dev — 2026-11-15

The final annotated tag points to **cd9efd77db506e9067dff4296ed1f082238e078b**. [Main CI, 12 successful jobs](https://github.com/MikaPST/filewarden/actions/runs/43641557456) and [release workflow, 13 successful jobs](https://github.com/MikaPST/filewarden/actions/runs/43687379324) both passed on attempt 1. This supersedes pending statuses in the historical candidate records below.

Actual CI coverage: PHP 8.2.35/8.3.36/8.4.27 (75 tests/130 assertions each), eight JavaScript tests, syntax/PHPCS/Composer audit; eight actual ZIP upgrade paths from 0.1.11/12/13/14-dev across WordPress 6.8 and 7.1.4, MySQL 8.0.46, HTTP/security integration and Chromium/Firefox EN/FR including French repair at 375/320px. Initial failed candidates and subsequent corrections remain recorded separately. [Main evidence](validation/v0.1.15-dev-ci.json), [release jobs and tested artifact](validation/v0.1.15-dev-release-ci.json).

The [public release](https://github.com/MikaPST/filewarden/releases/tag/v0.1.15-dev) ZIP was downloaded and verified: all 290 files match the tagged Git blobs byte-for-byte; exact runtime allowlist, version, schema 6, original PNG and release notes match. SHA-256 **db4a3d80811ef6b8bed1fee58a6bd4178f37f1899c38b18531a0810b0a842fa0** agrees with the attached checksum and GitHub asset digest. The release job consumed the same workflow’s installed-and-tested WordPress 6.8 upgrade artifact, without rebuilding. [Public archive evidence](validation/v0.1.15-dev-published.json).

No installation or database conversion was performed on the owner's site. Existing MyISAM tables require backup and explicit repair through Settings/Storage and security; no silent conversion is claimed. Target-host acceptance and full human screen-reader review remain separate.


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
