# Permissions proposal

- Public, authenticated and selected WordPress roles are content access predicates.
- Effective content ACL is the intersection of document and every ancestor folder policy.
- Administrator read override is an explicit capability (`filewarden_read_all_files`) assigned to administrators by default.
- Mutations are separately controlled by capabilities, such as `filewarden_upload_files`, `filewarden_edit_files`, `filewarden_delete_files`, `filewarden_manage_permissions`, `filewarden_manage_settings`, `filewarden_view_audit`.
- Frontend and admin UI visibility are not security controls: every API and binary endpoint must enforce authorization.
- Folder listing, search, versions, metadata, thumbnails, and preview all obey effective ACL.
- Privileged administrative operations require independent capabilities even if content read is permitted.

Detailed capability mapping, multisite behavior and role assignment require implementation review.
