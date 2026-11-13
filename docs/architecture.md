# Architecture proposal

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
