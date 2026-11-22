# FileWarden — suivi court

Mis à jour le 21 novembre 2026. Lire ce fichier à la reprise, puis vérifier l’état Git et les fichiers utiles au lot.

## État actuel

- **0.1.17-dev validée localement le 21 novembre 2026**, ZIP livré pour installation manuelle en préproduction. Aucun push, tag ou release GitHub. [Notes](docs/releases/v0.1.17-dev.md), [preuves](docs/validation/v0.1.17-dev-local.json).

- **0.1.16-dev publiée comme préversion le 20 novembre 2026**, tag `v0.1.16-dev`, commit `ed43ab88a2317a9c8dde19496ac02e58efb08d28`. [Release et ZIP](https://github.com/MikaPST/filewarden/releases/tag/v0.1.16-dev). Publication autorisée par le propriétaire après tests ; aucun déploiement sur son site.
- [Les dix améliorations du lot](docs/lot-v0.1.16-dev.md) sont implémentées, avec non-régression des dix évolutions originales. Langue globale FileWarden (automatique site, FR/EN, repli anglais), opt-in tous formats confirmé et audité, en-tête/notice/transferts/boutons/pastille/focus/santé de base. Tous formats désactivé par défaut ; contenus normalement refusés servis en pièces jointes opaques sans aperçu actif, droits/taille/stockage conservés. Schéma 6.
- **CI complète 15/15 réussie** sur le commit du tag : [run 44777904156](https://github.com/MikaPST/filewarden/actions/runs/44777904156), [preuve](docs/validation/v0.1.16-dev-ci.json). PHP 8.2.36, 8.3.37, 8.4.28, MySQL 8.0.46, WordPress 6.8, 7.1.5. Dix mises à jour réelles depuis 0.1.11/12/13/14/15-dev ; PHPUnit 76 tests/140 assertions sur chaque PHP, huit tests JS, 393 entrées FR/EN, installation neuve, intégration/HTTP et Chromium/Firefox EN/FR validés. Recette locale complète antérieure sur 6.8/7.1.4 conservée.
- **ZIP public vérifié** : 301 fichiers, SHA-256 `8c0054772640788ea0328bbf6275de108cf8cf56a6f5818ace92860ee4d49e1b`. Promotion de l’archive exacte installée/testée par la CI réussie ; octets identiques à cet artifact et aux blobs du tag. Checksum joint, empreintes GitHub et notes vérifiés. [Publication](docs/validation/v0.1.16-dev-publication.json), [vérification publique](docs/validation/v0.1.16-dev-published.json).
- Le workflow de publication distinct n’est **pas** déclaré réussi : installation WP-CLI indisponible sur certains runners avant les nouveaux tests, puis installateurs de navigateurs figés. Trois tentatives conservées et workflow annulé ; promotion du candidat de la CI 15/15 après contrôle frais des statuts, du tag, des digests et des fichiers joints. [Incident initial](docs/validation/v0.1.16-dev-release-ci-initial.json), [historique et méthode](docs/validation/v0.1.16-dev-publication.json). L’installation des outils CI reste un point de fiabilité pour les prochaines publications.
- Échec initial du banc Firefox corrigé sans retrait d’assertion ni changement runtime ; [diagnostic](docs/validation/v0.1.16-dev-ci-initial.json), [recette du correctif](docs/validation/v0.1.16-dev-browser-sync-local.json). Réparation MyISAM toujours volontaire après sauvegarde. Aucun site utilisateur ou base réelle modifié, aucune certification de sécurité ou revue humaine complète au lecteur d’écran revendiquée. Services jetables locaux arrêtés après vérification d’identité ; fixtures et preuves conservées.

## Organisation convenue

1. L’utilisateur transmet ses demandes regroupées par fonctionnalité.
2. L’assistant reformule les demandes, les décisions et les critères de réussite ; comportement et maquette éventuelle sont validés ensemble.
3. Le code commence seulement après l’accord explicite de l’utilisateur sur le lot.
4. Les contrôles sont ciblés puis regroupés selon l’organisation convenue ; seuls les résultats utiles et les erreurs pertinentes sont rapportés. Les contrôles de sécurité restent obligatoires.
5. Une seule release et un ZIP par lot validé, après les tests et un accord explicite de publication. Un push de code ou de documentation ne vaut pas accord de release ; ne pas pousser de tag avant cet accord.
6. Comptes rendus courts : réalisé, résultats, problèmes et décisions attendues. Mettre à jour ce fichier à chaque validation, fin de lot ou interruption significative ; remplacer l’état périmé au lieu d’accumuler un journal. Garder les détails historiques dans CHANGELOG.md et les commits.

## Décisions durables

- WordPress classique uniquement ; stockage persistant dans `wp-content/filewarden-storage`, séparé du plugin `wp-content/plugins/filewarden`.
- Droits : intersection du fichier et de tous ses dossiers parents. Public ne contourne jamais un dossier restreint. La lecture globale administrateur ne donne pas de droits de modification.
- Déplacement : UUID conservé. Copie : document et stockage indépendants. Corbeille : 30 jours par défaut, configurables.
- Déploiement par ZIP depuis WordPress ; ne pas réutiliser les anciens scripts FTP. Aucun test destructif sur les documents du site réel.
- Ne conserver ici aucun identifiant secret, mot de passe ou document personnel.

## Candidat initial validé — 0.1.17-dev (historique)

**GO reçu le 20 novembre 2026.** Les neuf axes sont implémentés et la recette finale du ZIP est validée sur des WordPress locaux isolés. Livraison uniquement sous `filewarden-0.1.17-dev.zip` pour installation manuelle par le propriétaire. Aucun push, tag ou release GitHub autorisé pour ce candidat.

1. Boutons secondaires identiques aux exports, indépendants du profil WordPress.
2. Logo officiel 56/44 px et FILEWARDEN 20/18 px sur une même ligne, titre dessous.
3. Arborescence élargie, plus compacte, cibles tactiles conservées.
4. Fil d’Ariane des parents autorisés.
5. Formulaires contextuels sous la recherche, destination documentaire fixée au dossier ouvert.
6. Navigation Documents/Corbeille, création racine/premier dossier intégrée avec droits explicites.
7. Pastille d’information par fichier conservant des règles antérieures, sans modification d’ACL.
8. Corbeille avec icônes de types, recherche/filtre combinés, compteur/réinitialisation et conservation.
9. Apparence publique commune au site : modèles FileWarden/Neutre/Nature, couleurs et contraste, nom/logo PNG/JPEG, aperçu et enregistrement explicite ; vrais pictogrammes et identité de grille centrée.

Limites retenues du logo : médiathèque WordPress native, PNG/JPEG local de 2 Mo/6 000 px maximum, autorisations média distinctes ; refus des contenus actifs même en mode tous formats. Nom public limité à 64 caractères. Schéma 6 inchangé.

Contrôles finaux validés : 82 tests PHP/167 assertions, huit tests JS, PHPCS/syntaxe, 453 entrées FR/EN, Composer validation/audit ; mises à jour réelles depuis le ZIP 0.1.16-dev, intégration et nouveaux parcours Chromium/Firefox EN/FR. L’installation neuve vérifie InnoDB et les réglages par défaut. Non-régression historique intégrale, HTTP et nouveaux parcours Chromium/Firefox FR/EN validés sur WordPress 6.8 et 7.1.5. Installation neuve, premier dossier, annulation modifiée et premier import contextuel validés sur 7.1.5. ZIP de 305 fichiers, 4 283 227 octets, identique aux trois installations et au code ; SHA-256 `7d5d0f3e676f21820da40172f1a2df1b441758841de3b99727f554fb75335c7a`. Les 34 assets d’origine conservent leur contenu (fins de ligne SVG normalisées pour la comparaison). PHP 8.3/8.4, MySQL et CI distante non exécutés pour ce candidat ; aucun site utilisateur modifié. Services locaux jetables arrêtés après vérification d’identité ; fixtures et captures conservées. Voir la preuve structurée pour les ajustements du banc de tests et les limites.

Références : [lot détaillé](docs/lot-v0.1.17-dev.md), [notes du candidat](docs/releases/v0.1.17-dev.md), [droits](docs/permissions.md), [sécurité](docs/security.md), [déploiement](docs/deployment.md).

## Lot correctif 0.1.17-dev — GO du 21 novembre 2026

Les dix corrections du [cahier validé](docs/corrections-v0.1.17-dev.md) sont terminées et validées localement. Unités **88 tests/192 assertions**, PHPCS, syntaxe (69 PHP/29 JS), huit tests JS, 471 entrées FR/EN et audit Composer validés. Mises à jour réelles du ZIP initial, intégration et HTTP sur WordPress 6.8 et 7.1.5 ; parcours ciblés Chromium/Firefox FR/EN, non-régression regroupée et installation neuve/premier import validés. Styles publics identifiés par contenu ; cibles tactiles de 44 px vérifiées. [Preuve exacte, séquence des retests et limites](docs/validation/v0.1.17-dev-corrections-local.json).

ZIP corrigé livré localement : `build/filewarden-0.1.17-dev.zip`, **317 fichiers / 4 294 924 octets**, SHA-256 `b5ff1e7e4f50ce37837038555541be07a054905ff2d4371444d592594a21df49`, identique aux sources et aux trois installations finales. Les 35 visuels d’origine sont préservés ; candidat initial conservé dans build/candidate-v0.1.17-dev-initial. Version et schéma 6 inchangés. Aucun push, tag ou release GitHub autorisé pour ce lot. Services locaux jetables arrêtés après contrôle d’identité, captures/fixtures conservées. Étape suivante : recette manuelle du ZIP corrigé sur la préproduction par le propriétaire.


## Dernières corrections — GO du 22 novembre 2026

Les dernières corrections sont terminées : onglets Publication hors cartes, options contextuelles avec choix/brouillons conservés, Palette de couleurs et aide, carte Code court compacte au-dessus de l’aperçu avec texte WordPress/Elementor, pastille ambrée/charte, FR/EN. Unités 88/192, huit tests JS, PHPCS/syntaxe, audit Composer et 472 entrées FR/EN validés. WordPress 7.1.5 : mise à jour réelle du précédent ZIP et intégration/HTTP complets ; 6.8 : toutes les lignes documents/dossiers/audit, options et octets de la fixture préservés lors de la mise à jour. Bloc Code court WordPress natif et parcours Chromium/Firefox FR/EN, mobile 375/320px et sans JS validés. Dernière hauteur héritée supprimée après contrôle visuel puis retestée. [Périmètre et résultats](docs/corrections-finales-v0.1.17-dev.md), [preuve et limites/diagnostics](docs/validation/v0.1.17-dev-final-corrections-local.json).

ZIP local final `build/filewarden-0.1.17-dev.zip` : **317 fichiers / 4 296 190 octets**, SHA-256 `d83119e346919b82e2071b833df3957cb9b66ae63ec09ad68e69df68c1e4b59a`. Identique aux sources empaquetées et aux deux installations finales ; 46 assets d’origine identiques au précédent ZIP. Archive précédente b5ff conservée dans build/candidate-v0.1.17-dev-corrected-2026-11-21. Version et schéma 6 inchangés. Aucun push/tag/release GitHub. Services de test identifiés arrêtés, fixtures/captures conservées. Prochaine étape : installation et recette manuelle par le propriétaire en préproduction.


## Ajustement Nature — 22 novembre 2026

Demande explicite : fond du modèle Nature #EDF3EE. Une seule modification runtime (FrontendAppearance.php), charte actualisée ; apparences enregistrées préservées, sélectionner Nature puis enregistrer pour appliquer la nouvelle palette. Unités 88/192 et PHPCS validés ; champ et fond calculé de l’aperçu réel vérifiés dans Chromium, validation des contrastes active. ZIP local comparé aux sources et à l’installation : 317 fichiers, 4296192 octets, SHA-256 `ac4c40ff75f78ea9599b8850aa729e5cfe0c26c177735e64b59cf769a4a38dde`. Ancien ZIP d831 préservé dans build/candidate-v0.1.17-dev-final-before-nature. Aucun push/tag/release. Indicateur de lisibilité validé pour la prochaine version 0.1.18-dev, non implémenté. Preuve : docs/validation/v0.1.17-dev-nature-local.json.

## Publication 0.1.17-dev — autorisation du 22 novembre 2026

Le propriétaire autorise explicitement le push, le tag et la prerelease avec ZIP, après validation des tests. Préparation du commit consolidé et de la CI distante : PHP 8.2/8.3/8.4, MySQL 8.0, installation neuve, mises à jour depuis 0.1.11-dev à 0.1.16-dev sur WordPress 6.8/latest, HTTP et navigateurs EN/FR. Les résultats distants et la publication restent à vérifier ; aucune réussite distante n’est présumée. Les preuves locales historiques ci-dessus restent conservées. Archive de release : candidat exact installé et testé par la CI, avec empreinte jointe.

## Prochaine version — 0.1.18-dev

Première évolution validée le 22 novembre 2026 : encart Lisibilité des textes, score des huit contrastes contrôlés, niveaux AA/AAA/À améliorer/À compléter et détails repliables avec rapports réels. Mise à jour dynamique, FR/EN, clavier/mobile, validation serveur et apparences enregistrées préservées. Le score concerne les contrastes de texte, sans certification globale d’accessibilité. Démonstration approuvée ; [spécification et critères de recette](docs/lot-v0.1.18-dev.md). Phase de collecte/préparation du lot : pas de développement, changement du numéro runtime ou nouvelle archive à ce stade. Publication GitHub distincte, soumise à autorisation explicite.

Demande complémentaire : démonstration interactive du renommage document/dossier à lien et droits constants, sélection multiple pour classer/déplacer/restaurer, tris/filtres nom/date/taille/type et recherche globale limitée aux documents autorisés. Présentation avant développement ; les historiques de versions et d’activité sont explicitement reportés.
