# FileWarden — suivi court

Mis à jour le 28 novembre 2026. Vérifier l’état Git après lecture.

## Première Alpha — publiée et vérifiée

Le propriétaire a confirmé que le dernier candidat fonctionne en préproduction et valide la proposition de première Alpha GitHub : version `0.1.19-alpha.1`, tag `v0.1.19-alpha.1`, release marquée préversion avec ZIP installable et SHA-256. Cet accord autorise commit/push/tag/publication après contrôles ; ne pas redemander son GO. Aucune soumission à WordPress.org demandée.

Publication achevée : [FileWarden 0.1.19-alpha.1](https://github.com/MikaPST/filewarden/releases/tag/v0.1.19-alpha.1), préversion publique, tag annoté au commit `45a67ac6831d40474b8e158fffcc57cffb19c783`. Le ZIP installable publié contient 376 fichiers / 4 466 915 octets ; SHA-256 `216fbe577a67ff687abaa03cdc8cbcb4e269e6f685cbcf6052a0ffa76d3f575b`. Téléchargement réel vérifié : chaque membre correspond au blob Git du tag et l’empreinte jointe est correcte. Les 71 SVG validés sont conservés.

Les évolutions v18/v19 ont été regroupées depuis la dernière release publique v0.1.17-dev. Le dernier ZIP dev validé reste préservé. Préparation Alpha : métadonnées/catalogues, recette actuelle d’import/doublon et sources de pagination exportables. Une correction CSS supplémentaire, issue de la recette Linux, fait passer les commandes du lecteur PDF à la ligne sous 480 px pour éviter un débordement lié aux polices de plateforme ; revalidée localement dans Chromium/Firefox FR/EN, ordinateur/mobile. Aucune autre modification fonctionnelle pendant cette préparation.

Contrôles [main : 19/19](https://github.com/MikaPST/filewarden/actions/runs/46153157490), puis [tag et publication : 20/20](https://github.com/MikaPST/filewarden/actions/runs/46153612848), tous réussis. PHP 8.2/8.3/8.4, WordPress 6.8/latest, mises à jour depuis v0.1.11 à v0.1.17, installation neuve, HTTP/ACL/CSRF, parcours actuels EN/FR Chromium/Firefox et restauration chiffrée indépendante. Recette locale : 122 tests / 436 assertions et huit tests JS, restauration avec descriptions/octets vérifiés ; 12 fichiers de même contenu contrôlés sans altération de l’original. Les quatre serveurs HTTP locaux et la base jetable sont arrêtés, preuves préservées. Voir `docs/validation/v0.1.19-alpha.1.json`.

Pendant cette préparation, rapport Plugin Check reçu : deux erreurs de readme, un avertissement i18n et sept SQL. Première lecture : fragments fixes ou déjà préparés, sans injection démontrée. Réserver le traitement systématique au [prochain lot](docs/prochain-lot-beta.md), comme demandé ; ne pas désactiver les contrôles ni assimiler un avertissement à une faille prouvée. Les résultats des candidats ci-dessous sont historiques.

## Correctif doublons et finitions — ZIP terminé pour préproduction

GO explicite reçu pour les quatre points, corrigés et validés : même nom à l’import, étapes Comprendre l’accès, fermeture des modales homogène, destination Copier/Déplacer plus lisible et espacée. ZIP courant : `build/filewarden-0.1.19-dev.zip`, 376 fichiers / 4467535 octets, SHA-256 `bc0ade58cf36d515c11b983b03c70deee1047334bada792d3757fcdbb616cfab`. Schéma 9 inchangé. ZIP précédent préservé sous `build/filewarden-0.1.19-dev-before-same-name-fix.zip` (f715e9ea92f2c068d48588fcd9b3d9ba0528d193a661c3fad2775a9144d25c66).

122 tests / 436 assertions, 8 tests JS, PHPCS sans erreur/avertissement, syntaxe de 66 PHP runtime et 17 JS/MJS, 809 entrées FR/EN. Chromium/Firefox FR/EN, ordinateur/mobile, exactement le même nom depuis ordinateur et Média dans un dossier à trois rôles. Course de nom revérifiée ; original intact, 14 fichiers de même contenu vérifiés avec des noms distincts. Étapes réelles sur trois dossiers/conditions conservées, X de styles calculés identiques, label 14px et espaces mesurés. ZIP identique aux sources et installations 7.1.5/6.8 ; 71 SVG et modules sensibles inchangés. Services locaux arrêtés, fixtures conservées. [Preuves et limites](docs/validation/v0.1.19-dev-same-name-local.json). Aucun commit/tag/publication GitHub.

Les résultats des lots ci-dessous restent historiques.

## Compléments documentaires v19 — ZIP terminé pour préproduction

Lot autorisé le 28 novembre, implémenté et validé localement. ZIP courant : `build/filewarden-0.1.19-dev.zip`, 375 fichiers / 4 463 834 octets, SHA-256 `f715e9ea92f2c068d48588fcd9b3d9ba0528d193a661c3fad2775a9144d25c66`. Schéma 9 additif ; manifeste 3. Archives antérieures conservées. Le ZIP est identique aux sources et aux installations WordPress 7.1.5 / 6.8. Les 71 SVG officiels sont inchangés. [Preuves et limites](docs/validation/v0.1.19-dev-document-local.json).

119 tests unitaires / 429 assertions, 8 tests JS, PHPCS sans erreur/avertissement, syntaxe de 65 fichiers PHP runtime et 17 JS/MJS, 797 traductions FR/EN. Services HTTP et base de recette arrêtés, fichiers de fixtures conservés. Parcours réels dans Chromium/Firefox, FR/EN, ordinateur/mobile ; droits/CSRF/rollback, doublons sur flux chiffrés, descriptions et recherches autorisées, migration 8→9/reprise différée et restaurations chiffrées entre installations (manifestes 1/2/3). Aucun commit/tag/publication GitHub ni passage en Alpha. Les résultats des lots ci-dessous restent historiques.

Voir [le lot documentaire](docs/lot-document-v0.1.19-dev.md). Les quatre fonctionnalités choisies sont validées : doublons (taille/SHA-256/comparaison exacte, résultats autorisés, avertissement limité à l’import, choix de conserver une copie), fiche centrée (nom/menu Informations, œil pour aperçu direct), explication de l’accès effectif (détail repliable, synthèse dans la modale de droits) et description facultative/recherche. Limite finale de description : 400 caractères, compteur 0 / 400, fiche et import, recherche nom + description soumise aux droits. Également validé : remplacer les restrictions antérieures lors d’un enregistrement explicite des droits, sans case supplémentaire, avec disparition de la pastille après succès. GO consolidé reçu le 28 novembre 2026 : développement, tests locaux et ZIP autorisés. Implémentation et recette terminées, ZIP documentaire identifié ci-dessus. Send réservé à Alpha/Bêta ; pas de publication autorisée. Le ZIP du lot précédent décrit ci-dessous reste préservé.

## Corrections de préproduction v19 — ZIP corrigé livré

Corrections terminées et validées localement. ZIP courant : `build/filewarden-0.1.19-dev.zip` (371 fichiers, 4450339 octets), SHA-256 `69eece3320980f186987272984d0e6ebcfdeee32a9bd5389cbee7ecac3b19085`. Identique aux sources et aux deux installations locales. Preuves : `docs/validation/v0.1.19-dev-ui-corrections-local.json`.

117 tests / 422 assertions, 8 tests JS, 109 fichiers PHP et 17 JS runtime syntaxiquement valides, PHPCS sans erreur ni avertissement, 765 entrées FR/EN. Géométrie/tri/modale/CSRF/palette/marges validés dans Chromium et Firefox, français et anglais ; contrôle natif WordPress 6.8 complémentaire. Trois documents de fixture : identités, métadonnées, droits et contenus inchangés. 71 SVG officiels identiques ; code de sauvegarde, schéma et permissions inchangé. Les deux serveurs HTTP et la base de recette sont arrêtés, fixtures conservées. Aucune publication GitHub.

GO reçu pour le lot [corrections v19](docs/lot-corrections-v0.1.19-dev.md) : tableau en liste/chevron unique/retrait Type/colonnes, Annuler centré, lien filewarden.org, icônes et pastille sombre, marges des trois styles d’accès refusé. Tests locaux et nouveau ZIP demandés. Pas de publication GitHub. ZIP antérieur préservé dans build/filewarden-0.1.19-dev-before-ui-corrections.zip (SHA-256 4c64e193b5bcc5705bce0364c5db09dcc729d8d453e1df44b84d5576f645ec36). Les résultats et SHA de la livraison initiale ci-dessous restent historiques.

## Lot 0.1.19-dev — candidat local prêt pour préproduction

Les 16 points validés sont implémentés. ZIP : `build/filewarden-0.1.19-dev.zip` (371 fichiers, 4 449 422 octets), SHA-256 `4c64e193b5bcc5705bce0364c5db09dcc729d8d453e1df44b84d5576f645ec36`. Installé et comparé octet par octet aux sources et aux deux WordPress locaux 6.8 / 7.1.5. Aucune publication GitHub, aucun commit, tag ou changement vers Alpha effectué pour ce lot.

Recette locale : PHP 8.2.35, MariaDB 10.11.11 ; 117 tests PHPUnit / 422 assertions, 8 tests JS, PHPCS sans erreur ni avertissement, syntaxe de 107 fichiers PHP et 17 fichiers JS runtime, 766 entrées FR/EN. Composer valide, audit sans avis ni dépendance abandonnée. Tests HTTP d’ACL/CSRF/MIME/ranges, interfaces EN/FR et lecteur compact dans Chromium/Firefox ; régressions historiques exécutées par modules après adaptation des anciens sélecteurs. Mise à jour réelle v18 → v19 et reprise d’une tâche v18 avant migration différée vérifiées.

Sauvegardes : 252 combinaisons valides de composants, trois restaurations chiffrées de configuration sur bibliothèque remplie, restauration réelle sur une seconde installation avec chiffrement de destination, anciens manifestes v1/v2 et nouveau v3, refus mot de passe/paquet altéré/bibliothèque remplie, rollback d’activation et reprise, 18 manifestes hostiles refusés. 71 SVG officiels identiques au ZIP v18 préservé ; extension inconnue → file. Preuves et limites : `docs/validation/v0.1.19-dev-local.json`.

Schéma SQL 8, manifeste 3. Les dates d’arrivée historiques inconnues restent « — ». PHP 8.5/hébergement réel, ouverture Elementor Pro réelle et validation humaine complète d’accessibilité restent à vérifier en préproduction. Les mentions d’attente de GO dans le détail historique ci-dessous sont obsolètes : le GO global a autorisé l’ensemble et ce lot est désormais livré localement.

GO global reçu le 27 novembre 2026 pour les 16 points ci-dessous, tests locaux et livraison ZIP. Les mentions historiques « attendre GO » ci-dessous sont levées par cet accord. Aucune publication GitHub/tag autorisée. ZIP v18 préservé dans build/filewarden-0.1.18-dev-before-v19.zip (SHA-256 a2a910ad84e7e6dd133acbb8dce42a22792c14a9e105baf1642228de370017b2).

Le propriétaire valide la préparation sous 0.1.19-dev, avec une éventuelle première 0.1.19-alpha.1 après corrections, tests et recette de préproduction. Il transmet les demandes une par une ; attendre son GO consolidé avant développement. Aucun accord de publication pour ce nouveau lot.

**Correction 1 — menu contextuel des documents** : lors du défilement avec le menu « … » ouvert, sa largeur augmente progressivement. Capture de préproduction fournie le 27 novembre. Lecture du code : le repositionnement passe le menu en position fixe et recalcule sa gauche à partir de sa largeur mesurée, tout en conservant la contrainte droite du CSS ; mécanisme cohérent avec l’étirement cumulatif observé. Pas de reproduction navigateur nouvelle ni de correction runtime à ce stade. Attendu : menu compact de largeur stable, ancrage cohérent au bouton, maintien dans le viewport, fonctionnement identique après plusieurs défilements dans les deux sens et redimensionnements. Prévoir tests ordinateur/mobile et clavier. [Lot en préparation](docs/lot-v0.1.19-dev.md).

**Évolution 2 — sélection intégrée au tableau** : maquette interactive validée par le propriétaire. Case de sélection des documents visibles dans l’en-tête de première colonne, état intermédiaire pour sélection partielle ; suppression du bandeau permanent. Barre compacte uniquement dès une sélection, compteur, déplacer/classer, mise à la corbeille et désélection. Destination dans une modale, actions navy et corbeille rouge, adaptation mobile. Attendre le GO global avant implémentation.

**Évolution 3 — recherche compacte et tri du tableau** : démo ordinateur/mobile entièrement validée. Périmètre intégré à droite du champ ; Rechercher puis Filtres sur une ligne ordinateur, adaptation mobile. Filtres repliables et compteur actif ; suppression de Trier par, tri ascendant/descendant dans les en-têtes Document/Taille/Ajouté le. Nouvelle colonne date et heure d’arrivée dans le dossier entre Taille et Règle du fichier, actualisée lors des déplacements, date d’import conservée et reprise des anciennes données à définir sans dates inventées. Voir le lot pour les détails. Aucun changement runtime avant GO global.

**Correction 4 — infobulle Renommer** : ajouter « Renommer » sur le bouton crayon des documents, comme Aperçu/Télécharger, même style et comportement au survol/focus, nom accessible et traduction FR/EN. Aucun texte permanent supplémentaire dans le tableau. À implémenter après GO global.

**Correction 5 — droits du document en modale** : remplacer le panneau latéral « Modifier les droits » d’un fichier par une modale centrée cohérente avec les autres actions, sans rétrécir le tableau. Conserver tous les champs et explications, contrôles serveur et autorisations ; fermeture/Échap/focus et mobile. Attendre GO global.

**Correction 6 — finition modale Renommer** : bouton fermer carré avec croix centrée, suppression du double encadrement navy du champ et bordure homogène. Focus clavier discret mais visible, comportements de sélection/validation conservés. Vérifier styles hérités et mobile. Attendre GO global.

**Évolution 7 — aperçu compact** : maquette ordinateur/mobile validée. Barre compacte logo/nom/métadonnées, navigation page/total, zoom moins/plus et ajustements, ouvrir/télécharger avec infobulles ; document centré sur fond neutre, suppression du compteur de page redondant et adaptation mobile. Préserver sécurité, formats existants, identité et traductions. Attendre GO global.

**Évolution 8 — titre et fil d’Ariane du dossier** : maquette validée. Retirer le bouton textuel Renommer ; crayon seul sans fond/contour à droite du titre, infobulle et modale, accessibilité clavier/tactile préservée. Fil d’Ariane au-dessus à la place de DOSSIER ACTUEL, fond léger, icône dossier, chevrons, parents cliquables et courant navy/gras ; adaptation chemins longs/mobile. Attendre GO global.

**Évolution 9 — réglage popup Elementor** : accès direct à la liste des fenêtres modales du site via URL d’administration dynamique, nouvel onglet, en remplacement de la documentation ; aide sur le numéro du code court. Saisie ID ou `[elementor-template id="1252"]`, extraction/affichage et stockage de l’ID seul, validation serveur stricte sans exécution du code court. Approche validée ; conserver droits, prérequis et secours WordPress, traductions FR/EN. Attendre GO global.

**Évolution 10 — langue compacte** : proposition validée, sélecteur Général réduit vers 40–44 px et choix Automatique — Langue du site / FR — Français / EN — English, sans drapeaux. Conserver pictogramme de traduction, accessibilité, sélecteur natif et logique des langues existante. Attendre GO global.

**Ajustement 11 — titre Préférences** : demande de rendre l’en-tête du menu latéral plus visible. Proposition navy 16–18 px semi-gras, espacement normal, marges et séparation discrète, hiérarchie de page et mobile préservés, FR/EN. Attendre GO global.

**Ajustement 12 — espacement sous les titres** : réduire le vide entre Publication et ses onglets ; harmoniser l’espace titre/navigation ou titre/contenu sur les pages FileWarden, ordinateur/mobile. Vérifier les marges cumulées, préserver logo/nom/titres et leurs proportions. Attendre GO global.

**Évolution 13 — palettes supplémentaires** : FileWarden sombre (navy profond, turquoise) et Terracotta (crème, terre cuite) validées via démo ; dix couleurs de chaque palette consignées dans le lot. Ajouter aux palettes publiques en conservant identité personnalisée et icônes officielles. Contrastes texte préliminaires calculés ; vérifier tous les états à l’intégration, sans affirmer une conformité globale. Attendre GO global.

**Évolution 14 — sauvegarde plus explicite et configuration seule** : ensemble validé. Complète sans cases avec résumé ; Transfert simplifié devient Sauvegarde personnalisée avec options regroupées et dépendances. Documents/arborescence deviennent sélectionnables, export réglages/apparence seuls possible ; configuration seule restaurable sur destination non vide avec confirmation sans toucher bibliothèque, exigence vide conservée pour restauration de bibliothèque. Phrase secrète devient Mot de passe de la sauvegarde partout FR/EN, chiffrement/validations et anciens imports préservés. Voir lot pour périmètre et tests. Attendre GO global.

**Ajustement 15 — menu Bibliothèque** : libellé Bibliothèque au singulier validé pour le sous-menu WordPress auparavant Documents (Library en anglais). Conserver titre Bibliothèque de documents, onglet interne Documents, routes/identifiants et droits. Attendre GO global.

**Ajustement 16 — auteur et liens** : Michaël PASTOR remplace MikaPST dans l’attribution ; Author URI fourni explicitement : https://michael-pastor.fr. Site de l’extension retenu : https://filewarden.org, à vérifier prêt avant finalisation. Aucun identifiant GitHub changé ni site externe modifié. Attendre GO global.

## 0.1.18-dev — passe UI et sauvegarde sélective validée, ZIP prêt pour recette

GO explicite du propriétaire pour l’ensemble du lot, tests locaux et livraison d’un nouveau ZIP de préproduction. **Aucune publication GitHub autorisée pour la v18.** Aucun site utilisateur modifié. Le précédent ZIP est conservé dans `build/filewarden-0.1.18-dev-before-ui-pass.zip` (SHA-256 `2abd52030001a6e31fa38e5a68a309684c9f7d049793c9c8493cac119aa99759`).

Implémenté :

1. Recherche/filtres Documents et Corbeille homogènes, panneaux repliés par défaut, groupes Type/Période d’ajout/Taille, pastilles retirables, boutons appliquer/réinitialiser et normalisation des extensions. Cartes de corbeille mobiles et conservation réelle.
2. États publics vides/no-result centrés, pleine largeur en grille, pictogrammes, effacement de recherche et marges haut/bas équilibrées, couleurs personnalisées préservées.
3. Mise à la corbeille multiple confirmée dans les dossiers et Documents à classer, limite 50, droits de chaque document, refus des sélections périmées et métadonnées/audit atomiques ; aucune suppression définitive nouvelle.
4. Modales centrées pour les actions, sans réduire la liste ; formulaires serveur et revue de destination conservés, annulation/Échap/focus, fermeture possible pendant le chargement en lecture seule, protection contre doubles confirmations et scroll mobile. Destinations lisibles et espacées, actions de sauvegarde avec écart 12 px.
5. Diagnostic distinguant Sodium natif absent/API défaillante/dérivation en échec ; conseils hébergeur FR/EN. Liens vers les réparations qui ouvrent/surlignent leur catégorie sans conversion au clic, y compris anomalie découverte après affichage initial.
6. Assistant volontaire options/posts/postmeta : double capacité, nonce, sauvegarde indépendante et maintenance confirmées, limites 200 000 lignes/64 Mio par table, exclusion SQL, empreintes du contenu/structure, conversion/reprise par table et contrôle final. Aucun rollback global des ALTER promis ; écritures externes à suspendre séparément. Le disque PHP ne sert pas de preuve pour une base distante.
7. Sauvegarde complète (défaut) ou transfert simplifié : documents/arborescence obligatoires ; corbeille, droits, créateurs, réglages, apparence/logo et audit facultatifs, avec omission réelle des données. Manifestes v1 importables, nouveau v2 authentifié ; conteneur FWBACK01 et schéma 7 conservés. Sans droits source : privé administration selon les exceptions explicites de lecture globale. Attribution globale au compte restaurateur par défaut ; correspondances individuelles seulement si retenues. Aucun compte/privilège créé ; acteurs historiques source distincts. Restauration sur bibliothèque vide maintenue.

Validation déjà acquise : 110 tests PHP / 387 assertions, huit tests JS, PHPCS sans erreur/avertissement, 749 entrées FR/EN ; intégration historique et mise à jour réelle ZIP public v17 sur WordPress 6.8 et 7.1.5, HTTP de sécurité ; 64 combinaisons d’options et quatre restaurations chiffrées réelles, anciennes archives schéma 6 et nouveau manifeste v2, mauvaises phrases/altérations/rollback/reprises ; interface réelle de restauration complète ignorée et simplifiée, téléchargement protégé, conversion guidée réelle avec lien Diagnostic et confirmations. Chromium/Firefox EN/FR, 320/375 px, clavier et repli sans JS ; non-régression Publication/Diagnostic/journal réussie sur les deux WordPress. Centrage final et fermeture des modales pendant le chargement vérifiés. Le 27 novembre, intégration et HTTP complets repassés sur deux installations propres avec langue explicite pour le banc HTTP, puis chaque octet du ZIP comparé aux sources et aux deux installations.


Archive finale : **372 fichiers / 4442810 octets**, SHA-256 `a2a910ad84e7e6dd133acbb8dce42a22792c14a9e105baf1642228de370017b2`. [ZIP](build/filewarden-0.1.18-dev.zip), [empreinte](build/filewarden-0.1.18-dev.zip.sha256), [preuve de cette passe](docs/validation/v0.1.18-dev-ui-pass-local.json). Les deux serveurs HTTP de recette finale et la base locale sont arrêtés après vérification d’identité ; fixtures et preuves préservées. Livraison locale seulement ; le propriétaire effectuera la recette sur son hébergement.

Incident du banc conservé : un test ajoutait un utilisateur après le début de la maintenance, modifiant une option WordPress ; le contrôle a correctement refusé la reprise. Aucun point de reprise n’a été effacé. Préparation des utilisateurs déplacée avant la maintenance, cas corrigés réussis sur les fixtures propres `v18uifinalb715` / `v18uifinalb68`, avec preuve que l’ALTER défaillant simulé a effectivement été atteint. Les anciennes fixtures et leurs checkpoints restent conservés, sans site utilisateur concerné.

Retour réel de préproduction : le propriétaire a converti ses trois tables WordPress en InnoDB et activé Sodium. Son rapport est 11/11 et sa sauvegarde a réussi. Sa restauration n’est pas encore confirmée terminée sur son site ; aucune compatibilité complète PHP 8.5/LiteSpeed n’est revendiquée. MySQL 8, Elementor Pro réel, CI distante, gros volumes et audit humain complet d’accessibilité hors recette locale. Dans les fixtures de migration, chiffrement réel mais contrôle HTTPS simulé pour le serveur local.

[Périmètre](docs/lot-ui-v0.1.18-dev.md) · [Guide sauvegarde](docs/backup.md) · [Notes](docs/releases/v0.1.18-dev.md).

## Candidat précédent — historique avant la passe UI

GO consolidé reçu le 23 novembre après validation de la démonstration Diagnostic. Les sept axes initiaux sont conservés ; les six compléments sont implémentés : correctif de démarrage/erreurs de sauvegarde, Popup Elementor, collecte IP facultative/expiration, filtres Action puis Statut du journal, bandeau Code court transversal, Diagnostic en dernière catégorie des Réglages. [Périmètre](docs/lot-corrections-v0.1.18-dev.md).

**Livraison ZIP locale uniquement pour la recette du propriétaire. Aucun push, tag ni release GitHub 0.1.18-dev autorisé ou réalisé.** Aucun site utilisateur modifié.

- Schéma 7 additif, identifiants/fichiers/réglages préservés ; archives de schéma 6 et 7 acceptées. Reprise réelle d'une sauvegarde ancienne vérifiée : migration différée sous verrou, reprise complète, migration à la requête suivante et contrôle des octets. Collecte IP désactivée par défaut, conservation 30 jours bornée par le journal.
- 104 tests PHP / 349 assertions, huit tests JS, PHPCS sans erreur/avertissement. FR/EN : 691 entrées. PHP 8.2.35, MariaDB 10.11.11, WordPress 6.8 et 7.1.5. Intégration historique complète et installation du ZIP public 0.1.17-dev → candidat réussies sur les deux versions ; suites HTTP et finales réussies sur les deux versions.
- Diagnostic réel et rapport expurgé, témoins privés nettoyés, filtres vides/inconnus/périmètres, expiration IP et rejet de faux proxy vérifiés. Nouveaux parcours navigateur et anciens parcours Publication passent dans Chromium/Firefox en français ; passe anglaise réussie dans les deux navigateurs.
- Migration réelle schéma 7 entre installations à clés/IDs distincts et restauration d'une archive schéma 6 réussies, y compris mauvaise phrase, altération, rollback SQL/options/logo et reprise avant/après activation. Le chiffrement est réel ; seul le contrôle HTTPS est simulé dans cette fixture locale spécialisée.
- Échec initial en préproduction : le retour utilisateur confirme désormais la réussite des tests de chiffrement et de création de sauvegarde après activation de Sodium chez l'hébergeur. Les trois tables WordPress ont aussi été converties en InnoDB par le propriétaire pour préparer la restauration. Ce retour réel est distinct de la recette locale PHP 8.2 et ne valide pas tous les parcours PHP 8.5/LiteSpeed.
- Elementor Pro lui-même non installé localement : API compatible simulée avec véritable iframe, click/Entrée et secours WordPress vérifiés. Prévoir sa recette réelle sur le site. PHP 8.5/LiteSpeed, MySQL 8, CI distante, gros volumes et audit humain complet d'accessibilité non exécutés.

L'archive initiale (356 fichiers, SHA-256 167150cbd5dee74d1acfe1f9f83b6403417275c586509731584a52443a8223bf) est préservée séparément dans build/filewarden-0.1.18-dev-initial-before-complements.zip ; ses preuves restent historiques. Le ZIP corrigé final contient 365 fichiers / 4402672 octets ; SHA-256 2abd52030001a6e31fa38e5a68a309684c9f7d049793c9c8493cac119aa99759. Chaque fichier a été comparé aux sources et aux deux installations finales testées. Les douze serveurs HTTP jetables et la base locale sont arrêtés après vérification d'identité ; fixtures et preuves conservées. [Preuve finale](docs/validation/v0.1.18-dev-complements-local.json).

[Notes](docs/releases/v0.1.18-dev.md) · [Preuve initiale historique](docs/validation/v0.1.18-dev-local.json) · [Guide sauvegarde](docs/backup.md).

## Dernière publication — 0.1.17-dev

Publiée le 22 novembre sur autorisation explicite, tag `v0.1.17-dev`, commit `427c23b12fdfcf505be43b90a482f2455b36e560`. [Release](https://github.com/MikaPST/filewarden/releases/tag/v0.1.17-dev). CI source 17/17, release 18/18 ; ZIP public 317 fichiers / 4 296 097 octets, SHA-256 `296109dc3d6d5b3662e6cd04d891827ca0ac340d7cab67916ccce966d0d41d1f`. [Preuve source](docs/validation/v0.1.17-dev-ci.json), [release](docs/validation/v0.1.17-dev-release-ci.json), [ZIP public](docs/validation/v0.1.17-dev-published.json). Résultats historiques distincts du candidat actuel.

## Version précédente — repères historiques

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

## Compléments du lot

Le regroupement des propositions autrefois réservées à la version suivante est validé et développé. Diagnostic et bandeau transversal inclus. Historiques de versions/activité, sauvegardes planifiées et autres catégories suggérées restent reportés. Voir docs/prochain-lot.md pour les décisions historiques, remplacées par le GO consolidé du 23 novembre.
