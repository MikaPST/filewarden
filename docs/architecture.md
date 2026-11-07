# Architecture proposal

WordPress PHP plugin with modular services: DocumentService, PermissionService, StorageService, AuditService. Admin interface may use WordPress React components; public interface should remain lightweight.

Data tables (subject to detailed schema review): documents, folders, versions, permissions, audit_logs, download_stats, displays. WordPress prefix is dynamic.

REST namespace proposal: `/wp-json/filewarden/v1` for metadata and mutations. Binary routes require browser-compatible WordPress authentication, support PDF range requests where appropriate, and enforce ACL per request.

Storage abstraction should support local private storage initially and allow S3/MinIO in a future release. Security verification is fail-closed, including when storage is moved or server configuration changes.

The 0.1.0-dev foundation implements Repository, PermissionService, StorageService, DocumentService and AuditService with admin, REST and content controllers. Three tables exist: folders, documents and audit_logs. Other proposed tables remain future scope.

ManagedStorage provisions the default wp-content directory and verifies HTTP isolation with a public control and a protected witness once per storage request. EncryptedStream applies Sodium secretstream with a distinct derived key per file. An explicit FILEWARDEN_STORAGE_DIR selects the existing outside-webroot mode. See setup.md for deployment and backup checks.

Single-site only initially. Folder creation and uploads assign immutable policies in this milestone; policy editing, moves and lifecycle mutations follow after foundation acceptance. Binary delivery currently supports attachment download; inline preview is not implemented.
