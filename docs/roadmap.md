# Roadmap

## Lot autorisé 0.1.19-dev

Voir [le périmètre des 16 points](lot-v0.1.19-dev.md) et [les notes du candidat](releases/v0.1.19-dev.md). Les contrôles d’accès et capacités restent inchangés. La migration additive de schéma 8 ajoute une date d’arrivée nullable ; aucune date historique inconnue n’est reconstruite. Le manifeste 3 inclut huit composants, avec import des anciens manifestes 1/2. Les paquets de configuration seule peuvent être appliqués sur une bibliothèque existante sans modifier documents, dossiers ni politiques ; une restauration de bibliothèque exige toujours une destination vide. Le chiffrement, les validations serveur, la confirmation, le verrou d’écriture et la reprise transactionnelle restent requis. Les mots de passe et clés du site source ne sont jamais exportés.


## Nouvelle passe UI et sauvegarde sélective — 0.1.18-dev

Lot validé par le propriétaire le 23 novembre 2026 : recherche/filtres homogènes Documents/Corbeille, cartes mobiles de corbeille, états publics vides centrés, destination lisible, suppressions multiples confirmées et modales d’action. Le Diagnostic différencie le module Sodium absent et ouvre l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration, sans conversion au clic du lien. Sauvegarde complète ou transfert simplifié, options authentifiées indépendantes et restauration privée à l’administration sans reprise des droits ; attribution globale des créateurs par défaut dans l’interface. Voir [le guide sauvegarde](backup.md) et [le périmètre de correction](lot-ui-v0.1.18-dev.md). Résultats de cette passe dans `docs/validation/v0.1.18-dev-ui-pass-local.json` ; les preuves des anciens ZIP restent historiques.


## Local candidate — 0.1.18-dev

Owner development GO received on 22 November 2026. All seven approved axes are implemented and locally validated: text-contrast score, rename, bulk actions, sorting/filtering, authorized global search, supplied artwork and encrypted backup/migration. ZIP ready for manual preproduction acceptance; no GitHub publication authorized. See SUIVI.md, lot-v0.1.18-dev.md, releases/v0.1.18-dev.md and validation/v0.1.18-dev-local.json. Histories remain deferred. Subsequent Elementor and IP-logging requests are recorded separately in prochain-lot.md.

## Published development preview — 0.1.17-dev

Contextual creation, improved document/trash navigation and shared public appearance are released with the final Publication/Shortcode/visitor-login refinements and Nature #EDF3EE. Source CI passed 17/17 checks and tag/release CI 18/18; the public ZIP matches the installed/tested artifact and annotated tag. See SUIVI.md and docs/validation/v0.1.17-dev-published.json for evidence and deployment limits. Schema 6 is retained.

- **0.1.0 Secure foundations:** plugin bootstrap, migrations, verified private storage, ACL, protected upload/download, automated tests.
- **0.2.0 Documents:** folders, document lifecycle, versions, trash and auditing.
- **0.3.0 Interface:** admin manager, document detail and previews.
- **0.4.0 Integrations:** shortcode builder, Gutenberg and Elementor.
- **0.9.0 Beta:** security review, compatibility tests, translations, documentation.
- **1.0.0 Stable:** release after security and functionality acceptance criteria are met.

No target release date has been committed.

## Current progress

The 0.1.0-dev foundation is implemented for single-site WordPress: schema/capabilities, private-storage diagnostics, inherited ACL, PDF/image upload, protected downloads, basic administration, audit retention, unit tests, integration workflow and development ZIP packaging.

**0.1.0 acceptance is pending:** run the WordPress/HTTP integration suite and validate private storage, aliases, caching, permissions and upload limits on the target preproduction host. Unit tests alone do not establish milestone acceptance. Selected features from later milestones are implemented incrementally below; the milestones are not yet complete.

## Frontend library (implemented)
The shortcode supports list/grid layouts, a filename search limited to its published folder, authorized pagination (12 documents), local file-type SVG icons, date/size and separate preview/download actions. Search and pagination filter inherited permissions before rendering. PDF/JPEG/PNG remain the accepted upload formats.

## Folder references and access visibility (0.1.8)
New publication shortcodes use a permanent UUID v4 for each folder, including the virtual root. Schema 2 automatically backfills existing folders without changing internal IDs, parent relationships, policies or files; numeric shortcodes remain compatible. UUIDs reduce predictability but never replace authorization. The backend shows each explicit policy, expandable inherited folder rules, a global-read override explanation and file-type icons. Policy editing was added in 0.1.9.

## Editable access policies (0.1.9)
Folder and document access rules can be changed after creation by an authorized permission manager. New files default to dynamic folder inheritance; individual restrictions narrow access. Existing file policies remain unchanged. Public file policies cannot open restricted parent folders; existing ancestor checks remain enforced. The admin UI explains these rules and confirms saved changes.

## Administration workspace and lifecycle (0.1.10)
Task tabs, authorized folder tree, contextual inspector, explicit/effective policy warnings, confirmed moves/copies, trash and restore are implemented. Trash cleanup is configurable (30 days by default). Content replacement/version history, categories/tags and native editor integrations remain pending. Bulk actions are implemented in the local 0.1.18-dev candidate.

## Lot en développement : navigation et interfaces

Voir docs/lot-navigation-interfaces.md et SUIVI.md pour le périmètre validé, les vérifications et l’état réel. La publication est distincte de la validation du code.


## Approved 0.1.16-dev scope

The ten administration/language/import-policy refinements are consolidated in lot-v0.1.16-dev.md. This extends the existing supported strict formats (PDF/JPEG/PNG, TXT/CSV, macro-free Office and bounded ZIP) and does not imply that the earlier PDF-only milestone remains the current allowlist. The optional confirmed all-format mode serves normally refused content as opaque attachments only. Plugin-only FR/EN/site-language selection is implemented; additional languages require reviewed catalogs and acceptance. Schema 6 and prior public interfaces are retained. See SUIVI.md for current release/test evidence.


## Candidat local 0.1.18-dev

Les sept axes du lot-v0.1.18-dev.md sont développés. La recette locale du ZIP couvre le renommage, la sélection atomique, les filtres/recherche autorisée, les pictogrammes, les contrastes et la migration chiffrée. Voir SUIVI.md et docs/releases/v0.1.18-dev.md pour les preuves et limites ; cela ne vaut pas publication ni validation de déploiement. Historiques de versions/activité toujours reportés.
