# FileWarden 0.1.12-dev.1 — réception des dix évolutions originales

Date : 14 novembre 2026. Branche : `fix/ten-approved-evolutions`. Candidat correctif non publié ; main et la release 0.1.12-dev restent inchangés.

La référence est [le document fonctionnel et UI/UX fourni par le propriétaire](specifications/evolutions-v0.1.12-dev.md), conservé avec sa numérotation originale. Le précédent compte rendu avait repris un autre périmètre : ses résultats ne constituaient pas une preuve de conformité à ces dix demandes.

## Décisions reçues et périmètre

- ZIP contrôlé puis conservé comme archive téléchargeable ; aucune extraction automatique.
- Ajout de TXT, CSV et DOCX/XLSX/PPTX/ODT/ODS/ODP sans macros, avec contrôle du contenu, en conservant PDF/JPEG/PNG.
- Préservation du stockage, des URL permanentes, de l’intersection des droits, des fonctionnalités existantes et de la charte 1.1. Aucun changement de logo ni de palette.
- Pas d’installation sur le site réel ni de nouvelle release/tag dans cette intervention.

## Matrice de conformité

**Réception en cours : les statuts définitifs seront établis après la matrice WordPress/navigateurs et l’examen des captures.** « Développée » n’équivaut pas à « validée ».

| Évolution originale | Fonctionnel et UI/UX développés | Sécurité et non-régression | Preuves ciblées | État |
|---|---|---|---|---|
| 01 — Compteur | Nombre de documents actifs autorisés à côté de Documents à classer, zéro explicite, badge Non publié conservé ; actualisation après opérations et dans la file d’import. | Exclusion de la corbeille et des documents non autorisés. | DocumentCollection.php, AdminWorkspace.php ; ten-evolutions-integration.php, ten-evolutions-browser.cjs, french-browser.cjs. | Développée, réception en cours |
| 02 — Déplacement | Destination spéciale sélectionnable, Classer plus tard, avertissement et confirmation de retrait de publication. | UUID/stockage conservés, politiques héritées retenues ; anciennes URL publiques refusées, accès de gestion avec nonce. | Lifecycle.php, AdminWorkspace.php ; tests d’intégration, HTTP et navigateur. | Développée, réception en cours |
| 03 — Boutons | Publication Deep Navy sans icône, permissions Slate Blue, suppression rouge ; composants existants conservés. | Aucune capacité ajoutée par un changement visuel. | admin.css et actions existantes ; couleurs calculées, absence d’icône et captures navigateur. | Développée, réception en cours |
| 04 — Ajout | Sélecteur ordinateur/médiathèque conservé, dépôt et sélection multiple, noms/tailles/états/erreurs par fichier ; formats validés ajoutés. | Contrôles côté serveur, provenance, taille, extension, MIME/contenu ; ZIP borné sans extraction, refus scripts/macros/traversées/bombes/liens. | FileValidator.php, upload-queue.js, DocumentService.php ; FileValidatorTest.php, HTTP, file mixte et dépôt navigateur. | Développée, réception en cours |
| 05 — Suppression/notifications | Dossier feuille supprimé avec récupération, messages contrôlés et précis ; quatre notifications avec icône/fermeture, succès temporaire 12 s et progression, erreurs persistantes. | Transaction, droits séparés, refus sous-dossiers, annulation sur panne ; focus/clavier et pause à la lecture. | FolderLifecycle.php, Notice.php, notices.js ; récupération d’origine, injection d’échec à la deuxième écriture, notifications-browser.cjs. | Développée, réception en cours |
| 06 — Création | Formulaire espacé, nom/parent/héritage explicites, action principale et actions du dossier courant. | Validation et droits du service existant ; pas de réécriture des autres politiques. | AdminWorkspace.php, AdminController.php ; création dans le parent sélectionné et suppression du dossier de test. | Développée, réception en cours |
| 07 — Corbeille | Vue globale sans arbre, nom/emplacement d’origine/date, recherche/filtre, restauration, suppression individuelle et vidage confirmés. | Origine préservée après suppression du dossier ; destination de remplacement explicite autorisée ; preuve signée, nonce, capacités, tombstones et conservation existante. | TrashConsole.php, TrashService.php, Lifecycle.php ; tests origine/restauration/purge/refus, HTTP et navigateur. | Développée, réception en cours |
| 08 — Publication | Affichage autorisé/Accès refusé, options et shortcode existants, copie avec retour, aperçu réel adjacent et borné. | Aucune donnée fictive ni contournement des droits ; vérification de la réponse d’aperçu courante. | Publisher.php, publisher.js, admin.css ; viewer-browser.cjs et ten-evolutions-browser.cjs. | Développée, réception en cours |
| 09 — Réglages | Cinq catégories réelles, navigation clavier sans rechargement, état de stockage/configuration et date de contrôle réels. | Aucune promesse « sécurisé » ; refus si stockage non vérifié ; validation complète avant sauvegarde, champs absents préservés. | SettingsConsole.php, settings.js ; sauvegardes partielles, limites invalides, navigation et mobile. | Développée, réception en cours |
| 10 — Audit | Terminal sombre à gauche/panneau à droite conservés, vues des mêmes événements, acteur WordPress/visiteur/système/ancien inconnu, noms conservés sous ACL, CSV/TXT/JSONL téléchargés. | Filtres appliqués partagés, toutes les lignes exportées, schéma 2/UTC ISO 8601, protection CSV, purge confirmée et traces conservées. | AuditService.php, AuditConsole.php, AuditHistory.php ; 1 005 événements, export vide, nonces/capacités et noms privés, affichage FR/EN. | Développée, réception en cours |

## Vérification de la véritable archive

Le workflow construit le ZIP avec l’allowlist runtime, installe le ZIP officiel 0.1.11-dev dont il contrôle le SHA-256, crée un document persistant puis remplace l’extension par le candidat. Les tests exécutent le plugin installé depuis cette archive, sans lien symbolique vers le dépôt. Ils comparent les champs existants, l’UUID, la politique, les dossiers, les clés de stockage et l’empreinte des octets ; la migration est répétée pour vérifier son idempotence.

La migration vers le schéma 6 ajoute `trash_origin` aux documents et les instantanés d’acteur/type/nom/règles aux journaux. Elle ne supprime ni documents, ni dossiers, ni événements historiques. Les informations historiques inconnues restent inconnues.

La construction exclut `.git`, `.tools`, les tests, les documents personnels et les secrets. Les nouveaux scripts runtime et catalogues FR sont explicitement inclus.

## Résultats et limites

- PHPUnit local : 72 tests / 125 assertions réussis (PHP 8.2). Matrice PHP 8.2–8.4, JavaScript et contrôles de code réussis sur les premiers correctifs.
- Mise à jour ZIP, assertions WordPress et contrôles HTTP réussis sur les correctifs intermédiaires. La matrice finale et la réception navigateur FR/EN sont encore en cours ; aucun succès global n’est revendiqué ici à ce stade.
- Navigateur : Chromium/Firefox, ordinateur/mobile, navigation clavier, simulation des délais et focus des notifications, téléchargement réel et erreurs réseau. Les vérifications d’accessibilité automatisées ne remplacent pas une recette humaine avec lecteur d’écran.
- Les tests tournent sur WordPress jetable/MySQL 8.0 et des documents synthétiques. Aucun test destructif n’est lancé sur le site utilisateur. L’hébergement réel, ses règles HTTPS/cache/proxy, les sauvegardes et une recette visuelle utilisateur restent à valider.
- Les formats ajoutés sont des pièces jointes sans aperçu intégré. Les limites de ZIP et le refus conservateur de contenu Office externe sont détaillés dans [la politique de sécurité](security.md). Ce contrôle structurel n’est pas un antivirus.

## Traçabilité des correctifs

- `e983710` : implémentation initiale des dix demandes originales, schéma 6 et installation du ZIP dans les tests.
- `6bcabe4` : correction du type SQL du chemin d’origine et contrôle des instantanés de règles.
- `6ef4082` : contrôle des imports non publiés et retours d’erreurs par fichier.
- `cd00fcb` : réception française et conservation du nonce propre à la récupération.
- `ebc93c9` : navigation de la corbeille, tests de panne transactionnelle et de dépôt mixte, refus Office renforcés.

Le lot ne doit être déclaré terminé qu’avec le lien de la CI finale et les résultats effectivement observés, ajoutés ci-dessous à l’issue de la réception.
