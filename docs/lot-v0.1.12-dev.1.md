# FileWarden 0.1.12-dev.1 — réception des dix évolutions originales

Date : 14 novembre 2026. Branche : `fix/ten-approved-evolutions`. Candidat correctif non publié ; main et la release 0.1.12-dev restent inchangés.

La référence est [le document fonctionnel et UI/UX fourni par le propriétaire](specifications/evolutions-v0.1.12-dev.md), conservé avec sa numérotation originale. Le précédent compte rendu avait repris un autre périmètre : ses résultats ne constituaient pas une preuve de conformité à ces dix demandes.

## Décisions reçues et périmètre

- ZIP contrôlé puis conservé comme archive téléchargeable ; aucune extraction automatique.
- Ajout de TXT, CSV et DOCX/XLSX/PPTX/ODT/ODS/ODP sans macros, avec contrôle du contenu, en conservant PDF/JPEG/PNG.
- Préservation du stockage, des URL permanentes, de l’intersection des droits, des fonctionnalités existantes et de la charte 1.1. Aucun changement de logo ni de palette.
- Pas d’installation sur le site réel ni de nouvelle release/tag dans cette intervention.

## Matrice de conformité

**Les dix évolutions sont implémentées et leurs tests passent dans WordPress jetable.** La réception technique du code runtime `84223dd` est documentée par la [matrice réussie](https://github.com/MikaPST/filewarden/actions/runs/43535617352). Ceci ne vaut pas recette de l’hébergement réel ni autorisation de release.

| Évolution originale | Fonctionnel et UI/UX développés | Sécurité et non-régression | Preuves ciblées | État |
|---|---|---|---|---|
| 01 — Compteur | Nombre de documents actifs autorisés à côté de Documents à classer, zéro explicite, badge Non publié conservé ; actualisation après opérations et dans la file d’import. | Exclusion de la corbeille et des documents non autorisés. | DocumentCollection.php, AdminWorkspace.php ; ten-evolutions-integration.php, ten-evolutions-browser.cjs, french-browser.cjs. | Implémentée — tests réussis |
| 02 — Déplacement | Destination spéciale sélectionnable, Classer plus tard, avertissement et confirmation de retrait de publication. | UUID/stockage conservés, politiques héritées retenues ; anciennes URL publiques refusées, accès de gestion avec nonce. | Lifecycle.php, AdminWorkspace.php ; tests d’intégration, HTTP et navigateur. | Implémentée — tests réussis |
| 03 — Boutons | Publication Deep Navy sans icône, permissions Slate Blue, suppression rouge ; composants existants conservés. | Aucune capacité ajoutée par un changement visuel. | admin.css et actions existantes ; couleurs calculées, absence d’icône et captures navigateur. | Implémentée — tests réussis |
| 04 — Ajout | Sélecteur ordinateur/médiathèque conservé, dépôt et sélection multiple, noms/tailles/états/erreurs par fichier ; formats validés ajoutés. | Contrôles côté serveur, provenance, taille, extension, MIME/contenu ; ZIP borné sans extraction, refus scripts/macros/traversées/bombes/liens. | FileValidator.php, upload-queue.js, DocumentService.php ; FileValidatorTest.php, HTTP, file mixte et dépôt navigateur. | Implémentée — tests réussis |
| 05 — Suppression/notifications | Dossier feuille supprimé avec récupération, messages contrôlés et précis ; quatre notifications avec icône/fermeture, succès temporaire 12 s et progression, erreurs persistantes. | Transaction, droits séparés, refus sous-dossiers, annulation sur panne ; focus/clavier et pause à la lecture. | FolderLifecycle.php, Notice.php, notices.js ; récupération d’origine, injection d’échec à la deuxième écriture, notifications-browser.cjs. | Implémentée — tests réussis |
| 06 — Création | Formulaire espacé, nom/parent/héritage explicites, action principale et actions du dossier courant. | Validation et droits du service existant ; pas de réécriture des autres politiques. | AdminWorkspace.php, AdminController.php ; création dans le parent sélectionné et suppression du dossier de test. | Implémentée — tests réussis |
| 07 — Corbeille | Vue globale sans arbre, nom/emplacement d’origine/date, recherche/filtre, restauration, suppression individuelle et vidage confirmés. | Origine préservée après suppression du dossier ; destination de remplacement explicite autorisée ; preuve signée, nonce, capacités, tombstones et conservation existante. | TrashConsole.php, TrashService.php, Lifecycle.php ; tests origine/restauration/purge/refus, HTTP et navigateur. | Implémentée — tests réussis |
| 08 — Publication | Affichage autorisé/Accès refusé, options et shortcode existants, copie avec retour, aperçu réel adjacent et borné. | Aucune donnée fictive ni contournement des droits ; vérification de la réponse d’aperçu courante. | Publisher.php, publisher.js, admin.css ; viewer-browser.cjs et ten-evolutions-browser.cjs. | Implémentée — tests réussis |
| 09 — Réglages | Cinq catégories réelles, navigation clavier sans rechargement, état de stockage/configuration et date de contrôle réels. | Aucune promesse « sécurisé » ; refus si stockage non vérifié ; validation complète avant sauvegarde, champs absents préservés. | SettingsConsole.php, settings.js ; sauvegardes partielles, limites invalides, navigation et mobile. | Implémentée — tests réussis |
| 10 — Audit | Terminal sombre à gauche/panneau à droite conservés, vues des mêmes événements, acteur WordPress/visiteur/système/ancien inconnu, noms conservés sous ACL, CSV/TXT/JSONL téléchargés. | Filtres appliqués partagés, toutes les lignes exportées, schéma 2/UTC ISO 8601, protection CSV, purge confirmée et traces conservées. | AuditService.php, AuditConsole.php, AuditHistory.php ; 1 005 événements, export vide, nonces/capacités et noms privés, affichage FR/EN. | Implémentée — tests réussis |

## Vérification de la véritable archive

Le workflow construit le ZIP avec l’allowlist runtime, installe successivement dans des environnements distincts les ZIP officiels 0.1.11-dev et 0.1.12-dev dont il contrôle les SHA-256, crée un document et des réglages non standard persistants puis remplace l’extension par le candidat. Les tests exécutent le plugin installé depuis cette archive, sans lien symbolique vers le dépôt. Ils comparent les champs existants, l’UUID, la politique, les dossiers, les clés de stockage et l’empreinte des octets, les valeurs des réglages et les anciennes lignes d’audit ; la migration est répétée pour vérifier son idempotence.

La migration vers le schéma 6 ajoute `trash_origin` aux documents et les instantanés d’acteur/type/nom/règles aux journaux. Elle ne supprime ni documents, ni dossiers, ni événements historiques. Les informations historiques inconnues restent inconnues.

La construction exclut `.git`, `.tools`, les tests, les documents personnels et les secrets. Les nouveaux scripts runtime et catalogues FR sont explicitement inclus.

## Résultats et limites

- **PHPUnit : 75 tests / 130 assertions réussis sur PHP 8.2, 8.3 et 8.4.** Huit tests JavaScript réussis ; syntaxe PHP, PHPCS et audit Composer réussis.
- **Quatre parcours de mise à jour réussis** : WordPress 6.8 et 7.1.4, depuis FileWarden 0.1.11-dev et 0.1.12-dev. Chaque parcours exécute les assertions de service/HTTP, Chromium et Firefox, puis les interfaces françaises des deux navigateurs. Les ZIP sont installés dans WordPress ; aucune substitution du numéro de version ne remplace ces essais.
- Navigateur : Chromium/Firefox, ordinateur/mobile, navigation clavier, simulation des délais et focus des notifications, téléchargement réel et erreurs réseau. Les vérifications d’accessibilité automatisées ne remplacent pas une recette humaine avec lecteur d’écran.
- Les tests tournent sur WordPress jetable/MySQL 8.0 et des documents synthétiques. Aucun test destructif n’est lancé sur le site utilisateur. L’hébergement réel, ses règles HTTPS/cache/proxy, les sauvegardes et une recette visuelle utilisateur restent à valider.
- Les formats ajoutés sont des pièces jointes sans aperçu intégré. Les limites de ZIP et le refus conservateur de contenu Office externe sont détaillés dans [la politique de sécurité](security.md). Ce contrôle structurel n’est pas un antivirus.

## Traçabilité des correctifs

- `e983710` : implémentation initiale des dix demandes originales, schéma 6 et installation du ZIP dans les tests.
- `6bcabe4` : correction du type SQL du chemin d’origine et contrôle des instantanés de règles.
- `6ef4082` : contrôle des imports non publiés et retours d’erreurs par fichier.
- `cd00fcb` : réception française et conservation du nonce propre à la récupération.
- `ebc93c9` : navigation de la corbeille, tests de panne transactionnelle et de dépôt mixte, refus Office renforcés.
- `32118d5` : noms visibles dans le journal simplifié et formats supplémentaires testés en HTTP.
- `75a57d1` : résultats historiques inconnus et formats de la médiathèque.
- `716f3c3` : exports complets en flux temporaire privé avant le téléchargement.
- `4ff0550` : notifications sous l’en-tête complet, revue de destination attendue avant saisie du nom.
- `84223dd` : signature/MIME ZIP explicites et conservation du paquet exact installé/testé.

## Preuves de réception

- [CI réussie du runtime 84223dd](https://github.com/MikaPST/filewarden/actions/runs/43535617352) : les huit jobs passent (trois PHP, JavaScript, quatre mises à jour WordPress).
- Notifications : succès encore visible à 10 s, disparition après 12 s hors interaction ; erreur persistante à 60 s, fermeture clavier et restitution du focus ; information/avertissement avec icônes. Les notifications sont replacées sous l’en-tête complet pour rester lisibles sur mobile.
- Compteur : zéro vérifié sur l’extension installée avant préparation des données, +3 documents, exclusion de la corbeille, diminution après classement ; compteur français visible dans les captures.
- ZIP/formats : les neuf nouveaux formats passent les vrais ajouts et téléchargements HTTP, le contrôle du MIME, l’identité des octets et le refus anonyme. Les fichiers hostiles passent les tests négatifs du validateur.
- Suppression : origine conservée, destination explicite, purge individuelle et vidage confirmés ; injection d’échec à la deuxième écriture démontrant l’annulation de récupération, et échec au deuxième lot d’export démontrant l’absence d’export partiel annoncé comme réussi.
- Audit : 1 005 lignes sans omission/duplication, trois vrais téléchargements, export vide, échec réseau, vues des mêmes IDs, acteurs WordPress/visiteur/système/ancien inconnu et absence de noms privés non autorisés.
- Captures des quatre environnements jointes à la CI : ordinateur/mobile, FR/EN, ajout/file mixte, création et confirmation de classement, publication, corbeille, réglages, audit. La disposition et les messages ont été examinés ; les derniers contrôles d’empaquetage conservent aussi l’archive exacte installée sous l’artefact « filewarden-tested-candidate ».
- Le document de référence est inchangé : SHA-256 9844ae634418e7e44350a6a8e4e1d5044338a921b6f3d7c563c7a43640935a73.

Les détails du contrôle du presse-papiers Chromium utilisent la [permission prévue par Playwright](https://playwright.dev/docs/api/class-browsercontext#browser-context-grant-permissions) sur l’origine de test ; la copie réelle est relue et comparée au shortcode. Le refus du presse-papiers est testé séparément. Aucun export d’audit n’utilise le presse-papiers.

## Livraison et réception sur site

Le candidat reste sur la branche corrective, sans merge dans main, tag, release ni installation sur le site utilisateur. Installer uniquement le ZIP correctif 0.1.12-dev.1 fourni pour cette recette, après sauvegarde de la base et du stockage séparé. Le nom proche du ZIP 0.1.12-dev déjà publié ne doit pas conduire à réinstaller l’ancien paquet. La recette de préproduction et l’autorisation de publication restent au propriétaire.

## Archive livrée et contrôle de branche

L’artefact « filewarden-tested-candidate » de la CI ci-dessus contient exactement le ZIP installé puis testé dans le parcours WordPress 6.8 depuis 0.1.12-dev. Il a été récupéré pour livraison, sans reconstruction locale.

- Fichier : `filewarden-0.1.12-dev.1.zip`.
- Version : `0.1.12-dev.1` ; schéma : `6` ; 274 fichiers runtime, 5 540 333 octets décompressés.
- SHA-256 : `8aaafc59892f605d7dfa2cc7d024963911fb40629569a82ea18cba704f4399f2`.
- Comparaison de chaque fichier décompressé à l’arbre Git canonique `84223dd` : identité octet par octet, incluant les nouveaux scripts/classes et les traductions. L’archivage Git de référence désactive la conversion CRLF de Windows pour comparer les véritables octets Git.
- Aucun `.git`, `.tools`, test, dépendance de développement ou secret dans l’archive. Les derniers changements de suivi/notes ne modifient aucun fichier runtime de ce paquet.
