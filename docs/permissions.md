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

New documents default to `inherit`: no additional file restriction, so the containing folder governs access dynamically. The existing complete ancestor intersection is preserved. The former virtual root is now a management-only recovery collection: inherited documents there are never published. Existing documents retain their stored policy.

A file may restrict its folder policy, never broaden it. All applicable predicates must be satisfied, including each selected-role predicate (there is no simple ranking of arbitrary role sets). Global-read administrators remain the explicit read-only override.

The backend editor requires `filewarden_manage_settings`, a valid admin nonce, and `filewarden_manage_permissions`; the service independently requires permission management and read access to the target. Editing does not require content-edit capability because it changes permissions only. Requested/completed policy changes are audited. Folder changes do not rewrite children; each fresh listing, preview and download reevaluates access without policy caching. Previously downloaded copies cannot be revoked.

## Workspace actions (0.1.10)

The table displays the file rule separately from the intersection of all applicable rules. A public file below a restricted folder receives an explicit “not public” warning. The editor repeats the warning when Public is selected; this is explanation, never an ACL override.

Move requires `filewarden_edit_files` and `filewarden_manage_permissions`; copy requires `filewarden_upload_files` and permission management. Both require read access to source and destination, and a fresh destination review. File rules are preserved; inherited rules follow the destination and may broaden access, which the confirmation explicitly explains. Trash/restore requires `filewarden_delete_files` and read access to the record’s current ancestry. The admin transport also requires `filewarden_manage_settings` and a valid nonce for every mutation. Global read alone never grants these operations. Trashed/purging files cannot be listed publicly, previewed or downloaded, even with global read.

## Navigation et récupération (lot suivant validé)

Les sous-dossiers peuvent hériter dynamiquement du parent. Toutes les règles des ancêtres restent cumulatives. Le frontend ne montre que les dossiers et fichiers autorisés ; la navigation et le fil d’Ariane restent dans le dossier publié.

La collection système « Documents à classer » (ancien emplacement racine, ID 0) n’est pas publiable. Les anciens liens frontend ne servent plus ces fichiers. La consultation de récupération requiert les capacités de réglages et de gestion des droits ; les liens binaires nécessitent aussi un nonce de gestion. Le reclassement dans un vrai dossier est obligatoire.

Une suppression de dossier ne peut viser qu’une feuille. Les documents sont récupérés sans effacer leurs octets, UUID ni état de corbeille. Les restrictions des anciens ancêtres sont conservées dans retained_rules et continuent à s’appliquer après reclassement ou copie. Les règles explicites du fichier restent modifiables, sans annuler cette protection. Les fichiers actifs récupérés ne sont pas automatiquement supprimés ; ceux déjà en corbeille restent soumis à leur rétention.

La purge manuelle d’audit requiert filewarden_manage_audit en plus de filewarden_view_audit, une confirmation et un nonce. Un périmètre signé et limité à 10 000 événements protège la confirmation ; les nouveaux événements et les traces de purge sont exclus.

## Correctif des dix évolutions (candidat 0.1.12-dev.1)

Le déplacement volontaire vers Documents à classer conserve UUID, stockage, règle du fichier et restrictions de ses anciens ancêtres. Il exige les droits de déplacement et de gestion des permissions, ainsi que l’accès de gestion à la récupération. Il retire le document des publications et des anciennes URL sans nonce de récupération. L’ajout direct dans cet espace exige aussi la capacité de réglages. La copie vers cet espace n’est pas proposée.

La corbeille globale filtre les documents selon les mêmes règles de contenu et exige la capacité de suppression. La suppression définitive exige en plus un périmètre revu correspondant à l’utilisateur, une confirmation explicite et, sur l’interface, le nonce d’administration. Si le dossier d’origine est supprimé, la restauration exige une destination existante lisible et la gestion des permissions ; les restrictions retenues ne sont pas retirées. La lecture globale ne confère aucune de ces capacités d’écriture.

Les noms historiques du journal restent des métadonnées protégées : contrôle de la ressource actuelle lorsqu’elle existe, ou des politiques sauvegardées lorsqu’elle a été supprimée. Une règle sauvegardée invalide ou une origine de récupération non autorisée masque le nom. Les règles internes ne sont pas exportées. Un ancien acteur nul ne devient jamais arbitrairement un visiteur ou une action système.
