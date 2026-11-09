# Permissions proposal

- Public, authenticated and selected WordPress roles are content access predicates.
- Effective content ACL is the intersection of document and every ancestor folder policy.
- Administrator read override is an explicit capability (`filewarden_read_all_files`) assigned to administrators by default.
- Mutations are separately controlled by capabilities, such as `filewarden_upload_files`, `filewarden_edit_files`, `filewarden_delete_files`, `filewarden_manage_permissions`, `filewarden_manage_settings`, `filewarden_view_audit`.
- Frontend and admin UI visibility are not security controls: every API and binary endpoint must enforce authorization.
- Folder listing, search, versions, metadata, thumbnails, and preview all obey effective ACL.
- Privileged administrative operations require independent capabilities even if content read is permitted.

Initial mapping: administrators receive the capabilities above at activation; other roles receive none by default. Folder creation requires manage_permissions plus parent read access. Uploading with an explicit policy requires upload_files and manage_permissions plus parent read access. The minimal admin screen additionally requires manage_settings. Reading never implicitly grants mutation capabilities.

Folder/document policy editing is implemented in the administration UI; WordPress role assignment and delegated frontend uploads remain future scope. Multisite is excluded from the first version by owner decision. Every ancestor is reevaluated without permission caching. Missing ancestors or cycles deny access even with the read override.

## Editable policies (0.1.9)

New documents default to `inherit`: no additional file restriction, so the containing folder governs access dynamically. The existing complete ancestor intersection is preserved. At the virtual root there is no folder restriction, so inherited documents are public; the UI explicitly states this. Existing documents retain their stored policy.

A file may restrict its folder policy, never broaden it. All applicable predicates must be satisfied, including each selected-role predicate (there is no simple ranking of arbitrary role sets). Global-read administrators remain the explicit read-only override.

The backend editor requires `filewarden_manage_settings`, a valid admin nonce, and `filewarden_manage_permissions`; the service independently requires permission management and read access to the target. Editing does not require content-edit capability because it changes permissions only. Requested/completed policy changes are audited. Folder changes do not rewrite children; each fresh listing, preview and download reevaluates access without policy caching. Previously downloaded copies cannot be revoked.

## Workspace actions (0.1.10)

The table displays the file rule separately from the intersection of all applicable rules. A public file below a restricted folder receives an explicit “not public” warning. The editor repeats the warning when Public is selected; this is explanation, never an ACL override.

Move requires `filewarden_edit_files` and `filewarden_manage_permissions`; copy requires `filewarden_upload_files` and permission management. Both require read access to source and destination, and a fresh destination review. File rules are preserved; inherited rules follow the destination and may broaden access, which the confirmation explicitly explains. Trash/restore requires `filewarden_delete_files` and read access to the record’s current ancestry. The admin transport also requires `filewarden_manage_settings` and a valid nonce for every mutation. Global read alone never grants these operations. Trashed/purging files cannot be listed publicly, previewed or downloaded, even with global read.
