# FileWarden 0.1.15-dev — lot validé

Le propriétaire a donné le GO global le 14 novembre 2026 et autorisé par avance le push, le tag et la préversion avec ZIP **uniquement après réussite des tests**. Son dernier choix remplace les essais de pictogramme plein : **dossier sécurisé en contour** dans le menu WordPress. Le cahier des dix évolutions originales reste applicable en non-régression.

## Conformité du lot

| Point | Changement | Code et couverture locale puis CI validée |
|---|---|---|
| 01 | Création explicite InnoDB ; diagnostic des anciennes tables ; réparation MyISAM volontaire après sauvegarde, contrôle d’intégrité et reprise après interruption | `Installer`, `DatabaseUpgrade`, `Repository`, `SettingsConsole` ; intégration réelle MyISAM et parcours POST navigateur |
| 02 | PNG fourni au-dessus d’« Ouvrez un dossier », 80 × 80 px, proportions et transparence originales ; PNG seul importé | `assets/illustrations/folder-welcome.png`, `admin.css`, allowlist du ZIP ; hash de l’original et ressource HTTP |
| 03 | Logo officiel original à gauche des titres Documents, Publication, Réglages et Journal | `AdminBrand`, PNG officiel 512 ; contrôles de chargement, captures ordinateur/mobile |
| 04 | Ordinateur portable et bibliothèque de fichiers dans les sélecteurs de source | Lucide `laptop-minimal` / `library-big`, `AdminController` ; contrôles radio et imports existants conservés |
| 05 | Catégories, pictogrammes et repères colorés uniquement dans le journal simplifié | `AuditAppearance`, `AuditConsole` ; vue technique, noms autorisés, filtres et formats d’export conservés |
| 06 | Préparation et confirmation de purge avec corbeille Lucide blanche, bouton Danger Red et espacement cohérent | `AuditConsole`, `admin.css` ; parcours préparer/revoir/confirmer et refus de sécurité |
| 07 | Onglets compacts soulignés avec pictogrammes, sans titre visible « Navigation FileWarden » | `AdminWorkspace`, `admin.css` ; mêmes liens et capacités, disposition mobile en deux colonnes |
| 08 | Bandeau synthétique d’état, date serveur et détails dépliables ; alertes importantes visibles repliées | `SettingsConsole`, `admin.css` ; hauteur normale, clavier, stockage bloqué et configuration limitée |
| 09 | Dossier sécurisé **en contour** dans le menu WordPress | Lucide `folder-lock` masqué avec la couleur native des états WordPress ; menu déplié/replié |

Les SVG Lucide sont conservés localement avec leur licence, sans bibliothèque distante à l’exécution. Le dossier d’accueil est une illustration distincte du logo officiel. Le schéma reste **6** : aucune colonne, politique, URL ou clé documentaire n’est remplacée.

## Réparation des tables existantes

La mise à jour ne convertit pas silencieusement les tables d’un site. Après installation, ouvrir **Réglages → Stockage et sécurité**. Si les trois tables sont en MyISAM, une action dédiée propose de les vérifier et de les convertir en InnoDB.

Avant de confirmer : sauvegarder la base complète, la clé de chiffrement et le stockage documentaire ; suspendre les écritures externes et prévoir temps/espace disque. Le compte SQL doit autoriser la conversion. La sauvegarde reste sous la responsabilité de l’opérateur ; le plugin ne prétend pas en avoir créé une.

La réparation exige la capacité de réglages, un nonce valide et une confirmation explicite de sauvegarde/maintenance. Elle cible uniquement les trois tables FileWarden du préfixe courant. Un verrou de connexion commun draine et sérialise les écritures du plugin, y compris téléversements, journal et purges. Les opérations ordinaires réutilisent ce verrou de façon réentrante ; elles peuvent retourner « occupé » après cinq secondes de contention. Les écritures SQL d’autres logiciels ou opérateurs doivent être suspendues séparément.

Le contrôle parcourt les lignes par lots de 200 et compare empreintes des données, définitions de colonnes, index, collation et prochain identifiant. Les octets stockés ne sont ni déplacés ni réécrits. Un point de reprise privé conserve uniquement des compteurs et empreintes, sans contenu documentaire. La demande et le succès confirmé sont audités séparément.

`ALTER TABLE` valide implicitement chaque table : une conversion partielle ne peut pas être annulée par un `ROLLBACK` global. En cas d’échec après création du point de reprise, celui-ci reste présent et bloque les écritures FileWarden. Après vérification de la sauvegarde et résolution du problème d’hébergement, relancer la même action : elle vérifie l’intégrité, conserve les tables déjà converties et reprend les autres. Une divergence de données bloque la reprise ; faire intervenir l’hébergeur, sans supprimer le point de reprise ni réinitialiser les tables. Les moteurs autres que MyISAM/InnoDB et les métadonnées non vérifiables ne déclenchent aucune conversion.

La suppression de dossier conserve son contrôle transactionnel, ses capacités, son verrou des droits, son refus des sous-dossiers et son rollback. Après réparation, un dossier feuille peut être supprimé ; ses documents restent récupérables dans « Documents à classer » avec leurs UUID, stockage et restrictions héritées.

## Validation

**Lot publié le 15 novembre 2026** : [release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.15-dev), tag annoté au commit cd9efd77db506e9067dff4296ed1f082238e078b. [CI main : 12/12 jobs réussis](https://github.com/MikaPST/filewarden/actions/runs/43641557456), [workflow du tag : 13/13 réussis, publication comprise](https://github.com/MikaPST/filewarden/actions/runs/43687379324), tous deux à la première tentative. PHP 8.2–8.4, WordPress 6.8/7.1.4 et MySQL 8.0.46 vérifiés. [Preuve CI](validation/v0.1.15-dev-ci.json), [preuve du workflow de publication](validation/v0.1.15-dev-release-ci.json).

Le ZIP public téléchargé contient 290 fichiers identiques au tag. Version, schéma 6, PNG original, notes de release, fichier SHA-256 et empreinte GitHub vérifiés. SHA-256 : **db4a3d80811ef6b8bed1fee58a6bd4178f37f1899c38b18531a0810b0a842fa0**. [Preuve de l’archive publiée](validation/v0.1.15-dev-published.json). Les paragraphes suivants conservent l’historique des candidats et des corrections.


La première CI sur f7db9f4 a détecté des écarts non reproduits dans la recette Windows : débordement Firefox/Linux à 320 px et attentes d’aperçu/restauration. **Aucun tag ni release publié à ce stade.** [Trace et correctif](validation/v0.1.15-dev-ci-initial.json). Le candidat correctif borne le bouton et la date, regroupe les changements rapides d’aperçu et attend les véritables navigations/POST ; la nouvelle recette complète 6.8 et le stress d’aperçu passent localement. Les contrôles GitHub finaux ont ensuite tous réussi avant publication le 15 novembre 2026.

**Recette locale réussie** sur WordPress 6.8 et 7.1.4, PHP 8.2.35/MariaDB 10.11.11, Chromium/Firefox EN/FR : mises à jour réelles depuis 0.1.14-dev en MyISAM, intégration/HTTP, non-régression des dix évolutions originales, 75 tests PHP/130 assertions, huit tests JavaScript, syntaxe, PHPCS, audit Composer et secours Sodium. Les tests couvrent aussi la reprise après conversion partielle, le refus d’une divergence de données, la réactivation avec point de reprise et le verrou depuis une connexion indépendante. Le formulaire de réparation français est testé à 375 et 320 px. [Preuve locale et empreinte du ZIP](validation/v0.1.15-dev-local.json).

Les fichiers `tests/v15-integration.php`, `tests/v15-browser.cjs` et `tests/v15-french-browser.cjs` complètent les suites historiques ; ils ne sont jamais inclus dans le ZIP. Les erreurs préliminaires du banc navigateur et leur correction sont consignées dans la preuve, distinctes des parcours finaux réussis. Ce constat local est historique ; la CI et la publication finales sont désormais réussies, avec les preuves référencées dans cette section.

La matrice GitHub ajoute la mise à jour depuis 0.1.14-dev aux parcours depuis 0.1.11/12/13-dev, sur WordPress 6.8 et la version courante, MySQL 8.0, Chromium/Firefox FR/EN, PHP 8.2–8.4. La release consomme l’archive exacte installée par le job WordPress 6.8 depuis 0.1.14-dev, après réussite de tous les jobs.

La recette sur l’hébergement du propriétaire reste distincte : aucune migration distante, installation ou modification du site n’est exécutée dans ce lot. Préversion de développement ; aucune garantie absolue de sécurité ni recette humaine complète au lecteur d’écran.
