# Permissions proposal

## Lot autorisé 0.1.19-dev

Voir [le périmètre des 16 points](lot-v0.1.19-dev.md) et [les notes du candidat](releases/v0.1.19-dev.md). Les contrôles d’accès et capacités restent inchangés. La migration additive de schéma 8 ajoute une date d’arrivée nullable ; aucune date historique inconnue n’est reconstruite. Le manifeste 3 inclut huit composants, avec import des anciens manifestes 1/2. Les paquets de configuration seule peuvent être appliqués sur une bibliothèque existante sans modifier documents, dossiers ni politiques ; une restauration de bibliothèque exige toujours une destination vide. Le chiffrement, les validations serveur, la confirmation, le verrou d’écriture et la reprise transactionnelle restent requis. Les mots de passe et clés du site source ne sont jamais exportés.


## Nouvelle passe UI et sauvegarde sélective — 0.1.18-dev

Lot validé par le propriétaire le 23 novembre 2026 : recherche/filtres homogènes Documents/Corbeille, cartes mobiles de corbeille, états publics vides centrés, destination lisible, suppressions multiples confirmées et modales d’action. Le Diagnostic différencie le module Sodium absent et ouvre l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration, sans conversion au clic du lien. Sauvegarde complète ou transfert simplifié, options authentifiées indépendantes et restauration privée à l’administration sans reprise des droits ; attribution globale des créateurs par défaut dans l’interface. Voir [le guide sauvegarde](backup.md) et [le périmètre de correction](lot-ui-v0.1.18-dev.md). Résultats de cette passe dans `docs/validation/v0.1.18-dev-ui-pass-local.json` ; les preuves des anciens ZIP restent historiques.


## Diagnostic, audit network data and visitor sign-in (0.1.18-dev)

Diagnostic execution and its transient report require filewarden_manage_settings and a fresh AJAX nonce, independently checked by the service. No repair is performed. IP collection can be enabled only by a settings manager; audit IP details and exports require existing audit authorization and resource-name checks. Export and purge share validated action/status/date/actor scopes; an empty selection never becomes all events. The additive schema migration does not regrant a previously revoked audit-management capability.

Popup Elementor settings use the existing settings capability/nonce and strict positive template ID validation. The public click invokes only the parent's same-origin Elementor popup API; the ordinary WordPress login link is the fallback. No visitor is granted a capability.

## Legacy database repair (0.1.15-dev)

The explicit engine-repair transport requires `filewarden_manage_settings`, the existing admin nonce and an explicit backup/maintenance confirmation; the repair service independently checks the settings capability. It targets only the three current-prefix FileWarden tables. Global document read never grants this operation. Missing SQL ALTER permissions or unverifiable metadata do not bypass transaction safety. An incomplete repair checkpoint blocks plugin mutations, including audit writes, until a verified retry; external SQL writers must be paused separately. No user or role is granted a new capability by this release.

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


## Advanced formats and plugin language (0.1.16-dev)

Both settings require the existing FileWarden settings capability and the existing administrative CSRF nonce; upload or global-read privileges do not grant settings privileges. Enabling all formats additionally requires an explicit risk confirmation and is audited with the WordPress actor. The server reads the stored setting; request flags cannot enable it. The mode never widens document/folder ACLs, changes publication state, grants WordPress upload permissions or enables executable/HTML/SVG previews. Import/download authorization remains mandatory. Language affects labels only, never permissions, resource identities or machine export keys.

## Explicit file-rule replacement — document lot 0.1.19-dev

Saving a document policy intentionally clears its retained_rules in the same SQL update, transaction and audit boundary. No extra checkbox is required. Moving/reclassifying alone retains those restrictions. The live folder/ancestor intersection and management-only unfiled collection still apply. A failed update or audit rolls both changes back. PermissionService::rules is shared by authorization and explanations; malformed policies, missing ancestry and cycles fail closed, including global readers. Unknown role slugs remain restrictive predicates, not public fallbacks.

Description writes require edit-files and manage-permissions capabilities plus readable active content and the shared writer/access locks. Administration transports additionally require settings access and valid CSRF nonces. Descriptions, duplicate names/locations and sheets are never disclosed before current read authorization. Exact-duplicate checks ignore unreadable and trashed candidates; confirmations recheck content and current rules.
