# Architecture proposal

## Lot autorisé 0.1.19-dev

Voir [le périmètre des 16 points](lot-v0.1.19-dev.md) et [les notes du candidat](releases/v0.1.19-dev.md). Les contrôles d’accès et capacités restent inchangés. La migration additive de schéma 8 ajoute une date d’arrivée nullable ; aucune date historique inconnue n’est reconstruite. Le manifeste 3 inclut huit composants, avec import des anciens manifestes 1/2. Les paquets de configuration seule peuvent être appliqués sur une bibliothèque existante sans modifier documents, dossiers ni politiques ; une restauration de bibliothèque exige toujours une destination vide. Le chiffrement, les validations serveur, la confirmation, le verrou d’écriture et la reprise transactionnelle restent requis. Les mots de passe et clés du site source ne sont jamais exportés.


## Nouvelle passe UI et sauvegarde sélective — 0.1.18-dev

Lot validé par le propriétaire le 23 novembre 2026 : recherche/filtres homogènes Documents/Corbeille, cartes mobiles de corbeille, états publics vides centrés, destination lisible, suppressions multiples confirmées et modales d’action. Le Diagnostic différencie le module Sodium absent et ouvre l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration, sans conversion au clic du lien. Sauvegarde complète ou transfert simplifié, options authentifiées indépendantes et restauration privée à l’administration sans reprise des droits ; attribution globale des créateurs par défaut dans l’interface. Voir [le guide sauvegarde](backup.md) et [le périmètre de correction](lot-ui-v0.1.18-dev.md). Résultats de cette passe dans `docs/validation/v0.1.18-dev-ui-pass-local.json` ; les preuves des anciens ZIP restent historiques.


## Corrective candidate 0.1.18-dev

Publisher places one full-width Shortcode band directly after the external tabs; settings and preview start below it on the same row. The same output nodes and iframe are preserved across tab/context/device changes. publication-frame.js invokes only the known same-origin parent Elementor popup API after a user action on an explicitly configured numeric popup link; WordPress login remains the href fallback.

AuditEvents centralizes the fixed event/action/outcome mapping; AuditHistory translates the same validated action/status scopes into prepared conditions for display/export/purge. Unknown events stay undetermined. AuditIp centralizes opt-in capture, proxy trust and visibility/retention; schema 7 only adds audit network fields. Active legacy backup/repair barriers defer the additive migration without disabling recovery UI.

Diagnostics runs bounded capability-checked observations, returns whitelisted safe fields and never repairs data. DiagnosticConsole provides the nonce/capability-protected AJAX transport. The last Settings category is outside the settings form; diagnostic.js renders text-only results and downloads the transient redacted JSON report.

## Publication corrections (0.1.17-dev)

The final 22 November layout places the two tabs outside both cards. The right output column contains a compact Shortcode card above the same preview iframe. `publisher.js` toggles authorized list/grid or denied layout/login sections without disabling/resetting their controls; the code retains both selections. Both forms remain available without JavaScript. A native WordPress Shortcode block uses the existing shortcode registration, not a new FileWarden editor block. Retained-rule disclosures reuse the central recovery amber tone/surface; no permission or database service changes.

`Publisher` renders two progressively enhanced local tabs with an unchanged shared preview iframe. `AppearanceConsole`/`FrontendAppearance` keep the complete ten-color schema and four compact preset cards; client drafts remain local until a validated save. `PublicationLogin` owns the independent site-wide sign-in preference, native WordPress fallback, URL validation and settings capability. Its separate form uses `AdminController` nonce/feedback handling and the common writer gate/audit. No document schema or authorization service changes. `FrontendAppearance::stylesheet_url()` identifies the two allowlisted public CSS files by content for same-version ZIP replacement. Public functional icons are decorative current-color masks; document-type artwork remains unchanged.

## Legacy engine repair and administration identity (0.1.15-dev)

`DatabaseUpgrade` measures the three exact prefixed tables and offers an explicit, backup-confirmed MyISAM-to-InnoDB repair through SettingsConsole/AdminController. Installation specifies InnoDB; engine repair does not change schema version 6. Ordered 200-row fingerprints include full column/index definitions, table collation and next ID. A durable options checkpoint survives partial conversion and rejects data drift before retrying.

`Repository::lock()` acquires a reentrant common write gate before existing operation-specific locks; `unlock()` releases one matching acquisition. Audit writes/cleanup participate too, so engine repair drains active plugin writers before fingerprinting. The gate is connection-owned and the checkpoint blocks later writes after interruption. External SQL writers remain an operator responsibility. Current-schema reactivation preserves the checkpoint and leaves the repair interface accessible. ALTER TABLE has implicit commits; it is not a multi-table rollback transaction. See [MySQL implicit commits](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html) and [named locking functions](https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html).

`AdminBrand` shares the unchanged official header artwork; `AuditAppearance` provides presentation-only categories for simplified events. Neither changes access decisions, protected resource-name resolution, technical records or export schema 2.

During a pending repair or temporary lock contention, scheduled audit cleanup defers its batch without writing or throwing the known maintenance refusal through the WordPress cron dispatcher. Later callbacks still run; ordinary privileged writes remain blocked by the checkpoint. Administration previews load eagerly and coalesce rapid choices before navigation, without changing frontend lazy frames or server authorization.

WordPress PHP plugin with modular services: DocumentService, PermissionService, StorageService, AuditService. Admin interface may use WordPress React components; public interface should remain lightweight.

Data tables (subject to detailed schema review): documents, folders, versions, permissions, audit_logs, download_stats, displays. WordPress prefix is dynamic.

REST namespace proposal: `/wp-json/filewarden/v1` for metadata and mutations. Binary routes require browser-compatible WordPress authentication, support PDF range requests where appropriate, and enforce ACL per request.

Storage abstraction should support local private storage initially and allow S3/MinIO in a future release. Security verification is fail-closed, including when storage is moved or server configuration changes.

The 0.1.0-dev foundation implements Repository, PermissionService, StorageService, DocumentService and AuditService with admin, REST and content controllers. Three tables exist: folders, documents and audit_logs. Other proposed tables remain future scope.

ManagedStorage provisions the default wp-content directory and verifies HTTP isolation with a public control and a protected witness once per storage request. EncryptedStream applies Sodium secretstream with a distinct derived key per file. An explicit FILEWARDEN_STORAGE_DIR selects the existing outside-webroot mode. See setup.md for deployment and backup checks.

Single-site only initially. Folder and document policies can be edited by permission managers with read access to the target; moves/copies and recoverable trash use the Lifecycle service and separate mutation capabilities. Viewer renders trusted HTML with a local PDF.js worker and protected PDF/image fetches. PDF bytes keep attachment disposition; images may be displayed inline. The administrator opens this page in a native dialog, while shortcode links open it in a separate tab. Preview no longer requires Imagick.

## Folder references and access visibility (0.1.8)
New publication shortcodes use a permanent UUID v4 for each folder, excluding the management-only recovery collection. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric folder shortcodes remain compatible, except the former root publication which is now blocked. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Folder and file policies can be edited from the backend inspector.

## Document workspace and lifecycle (0.1.10)

AdminWorkspace renders task tabs, authorized folder navigation, explicit/effective permissions and contextual actions. All mutations use nonce-protected admin POSTs; navigation works without JavaScript. Schema 3 adds nullable `trashed_at` and a `purging` tombstone. Active repository queries and PermissionService exclude both states, including global-read users. Migration does not change existing policy or storage keys.

Moves preserve UUID and storage key. Copies create a new UUID/key and verify copied bytes against SHA-256. A destination review HMAC binds source identity, filename, location and source/destination policy chains; transfer rechecks it under the shared access-rules lock. Policy edits share that lock; document locks serialize moves, policy edits, trash, restore and purge, and folder locks prevent same-name collisions.

Trash retains bytes for 30 days by default (`filewarden_trash_days`, 1–3650). The existing hourly cleanup hook processes up to 25 expired records per run. Each purge sets a tombstone before removing bytes and then metadata; interrupted purges are retried and cannot be restored. The schedule depends on WordPress cron execution. Deactivation/uninstall does not remove documents.

## Lot navigation et interfaces

Schéma 4 : colonne documents.retained_rules nullable pour les restrictions conservées après suppression du dossier. Migration répétable, sans déplacement physique des fichiers ni changement des UUID. FolderLifecycle récupère et supprime dans une transaction InnoDB vérifiée ; verrou global des droits puis verrou du dossier. Les transferts, changements de droits, création et corbeille partagent le verrou des droits. Les uploads verrouillent puis revérifient le dossier.

FolderNavigation borne les chemins publiés et filtre les sous-dossiers. Publisher produit le code court et l’aperçu dynamiques sans modifier les droits. AuditHistory porte filtres, pagination et purge ; AuditConsole contrôle les accès, nonces et exports CSV/TXT sans fichier temporaire public.

## Audit log updates (0.1.12-dev)

Schema 5 adds `actor_login` to audit records as a point-in-time WordPress `user_login` snapshot alongside `actor_id`. It contains no email address. Existing records remain intact; the new field defaults to an empty string, and authorized audit views may resolve older IDs against the current WordPress account when available. The audit console supports simplified and technical views and actor filtering. CSV, tab-separated TXT, and JSONL downloads share the same filtered snapshot and retain technical IDs; JSONL writes one object per line. Exports stream in bounded batches and never create a public temporary file.

## Original ten-evolution correction (0.1.12-dev.1 candidate)

Schema 6 adds nullable `documents.trash_origin` and audit `actor_type`, `resource_type`, `resource_name`, `resource_rules`. Migration is repeatable and preserves prior records; missing historic values are not reconstructed as facts. AuditConsole shows current or saved names only after authorization, exports schema 2/UTC ISO 8601, and keeps unknown outcomes explicit.

DocumentCollection filters recovery/trash rows through PermissionService before counting or displaying. FolderLifecycle snapshots the logical original path and transactionally retains ancestry; moving into recovery also retains the prior policy chain. TrashConsole is global without a tree. TrashService binds a user-specific review to exact UUIDs, deletion timestamps and effective policies under the access-rules lock, then serializes each disposal using document locks and existing retryable tombstones.

FileValidator bounds ZIP/Office member streams, rejects active content and validates private anonymous temporary inspection paths. It does not extract members to their archive paths. upload-queue.js posts each selected/dropped file through the existing independently authorized transport; accepted files are not retried when another file is rejected. Media Library selection uses the same format allowlist. SettingsConsole validates provided fields before saving and does not reset absent categories. Progressive notices/settings/publication enhancements preserve the existing PHP forms and controls.


## Import and language policies (0.1.16-dev)

ImportPolicy preserves FileValidator as the strict default. Only an exact persisted opt-in allows validation failures to become application/octet-stream; DocumentService rechecks it under the common writer gate before saving. The fresh option read is prepared and does not flush unrelated option caches. SettingsConsole validates all provided fields, requires explicit enable confirmation, serializes policy changes through that gate and records the WordPress actor. Binary downloads retain attachment/nosniff/sandbox headers; viewers refuse opaque content.

Language selects a bundled catalog for the filewarden domain only. Its automatic mode reads the site's WPLANG setting, not the request/user locale, and maps supported language families through a registry. Both MO catalogs are bundled so explicit English overrides a French WordPress profile. Server-rendered HTML and localized JavaScript share the same choice. See languages.md for catalog extraction and future additions. No global locale mutation or new schema is needed.

## Document sheet and exact imports (schema 9)

DocumentDescription validates UTF-8/plain text and counts at most 400 Unicode code points without mbstring. DocumentSheet renders authorized administration-only metadata and the authoritative PermissionService::rules chain. LibraryQuery checks authorization before matching names/descriptions. Repository policy updates clear retained_rules only for explicit document policy saves, under an audited transaction.

ContentDuplicates uses the indexed size/SHA-256 pair to scan active candidates in bounded batches, checks authorization, opens private StorageService streams (including managed decryption) and compares 64 KiB blocks plus EOF. Its session/user/content/name/destination/current-rule-bound proof is reviewed again under the shared access-rules writer gate. DuplicateContent contains only authorized review metadata. The queue keeps originals in the browser and resends only the selected file on confirmation; no public cleartext staging or cross-file acknowledgement exists. Importing anyway creates independent identity/storage, never deduplication aliases.
