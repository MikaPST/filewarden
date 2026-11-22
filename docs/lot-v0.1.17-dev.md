# FileWarden 0.1.17-dev — lot développé pour recette locale

Première demande reçue le 20 novembre 2026. GO de développement donné le même jour après validation du récapitulatif. Livraison demandée : ZIP local testé, destiné à l’installation manuelle par le propriétaire en préproduction. Aucun push, tag ni publication GitHub demandé pour ce candidat.

## 1. Couleurs des boutons secondaires identiques aux exports du journal

Le propriétaire remplace son choix précédent de turquoise plein par le style déjà utilisé pour les exports CSV/TXT/JSONL :

- **État normal** : même fond clair légèrement teinté, même texte/pictogramme Navy et même bordure que TXT et JSONL dans la capture fournie.
- **Survol** : mêmes fond, bordure et texte/pictogramme que CSV survolé dans cette capture.
- Réutiliser les styles et jetons des exports existants plutôt qu’une approximation des couleurs de la capture. Conserver les pictogrammes appropriés à chaque action et le focus clavier accessible de FileWarden.

### Boutons concernés

L’inventaire actuel de `fw-button-accent` comprend les trois composants suivants :

| Bouton | Emplacement | Référence actuelle |
|---|---|---|
| Rechercher | Documents, recherche dans le dossier | `includes/AdminWorkspace.php` |
| Choisir dans la médiathèque | Ajouter des documents, source Médiathèque | `includes/AdminController.php` |
| Ouvrir le journal d’audit | Réglages → Journal d’audit | `includes/SettingsConsole.php` |

Style partagé actuel : `assets/admin-controls.css`. Appliquer le changement à toutes les occurrences de ces composants ; les fonctions de recherche, sélection et navigation restent identiques.

### Critères de réception

1. Les trois boutons reprennent les couleurs calculées des exports à l’état normal et au survol, y compris texte, pictogramme et bordure.
2. Le thème de couleur du profil WordPress ne modifie pas ces états.
3. Navigation clavier, focus Navy, état désactivé, libellés FR/EN et petites résolutions restent utilisables.
4. Recherche, sélection dans la médiathèque, ouverture du journal et téléchargements d’export conservent leur fonctionnement.

## 2. En-tête : proportions et disposition validées

Le propriétaire trouve le logo 0.1.16-dev trop petit et demande de retrouver la taille de la 0.1.15-dev, en augmentant aussi FILEWARDEN pour rééquilibrer l’identité visuelle. Un visuel est demandé avant validation.

Proportions validées par le propriétaire le 20 novembre 2026 :

- Logo officiel à **56 × 56 px** sur ordinateur et **44 × 44 px** sur mobile, conformément à la 0.1.15-dev ; asset et proportions inchangés.
- FILEWARDEN à **20 px** sur ordinateur et **18 px** sur mobile, au-dessus du titre.
- Titre de page conservé à **27 px** sur ordinateur et **23 px** sur mobile.
- Logo et FILEWARDEN sur une même première ligne, centrés verticalement l’un par rapport à l’autre ; titre de page sur une ligne distincte en dessous. L’ensemble reste en haut à gauche et la navigation existante est conservée.

Le propriétaire a validé les proportions puis le nouveau visuel avec cette disposition. Disposition intégrée dans le candidat local.

## 3. Arborescence : davantage de largeur, lignes plus compactes

Demande du propriétaire : le panneau Dossiers est trop étroit ; l’élargir pour rendre les libellés et l’arborescence plus confortables. Réduire légèrement la hauteur et l’espacement vertical des lignes afin de voir davantage de dossiers simultanément.

- Conserver le design de hiérarchie guidée, les niveaux, les guides verticaux et les pictogrammes.
- Prévoir une largeur mieux adaptée à l’espace disponible ; aucune largeur précise n’a encore été validée.
- Réduire raisonnablement les espacements/paddings des lignes, sans rogner les cibles tactiles ni la navigation clavier.
- Garder le retour à la ligne des noms longs, sans débordement, et préserver l’espace utile pour la liste des documents et l’inspecteur.
- Vérifier grands/petits écrans, dossiers imbriqués et longs libellés ; le comportement d’ouverture, la sélection et les permissions restent inchangés.

## 4. Fil d’Ariane des dossiers imbriqués

Demande du propriétaire : afficher un fil d’Ariane au niveau du nom du dossier courant lorsque celui-ci appartient à une arborescence imbriquée.

- Exemple correspondant à la capture : **Documents › Documents Public › Test**.
- Présenter le chemin dans l’en-tête du dossier courant, à proximité de son nom, en conservant une hiérarchie visuelle claire.
- Permettre de rejoindre les dossiers parents autorisés ; distinguer le dossier courant, non cliquable, avec une indication accessible de page courante.
- Sans dossier parent, conserver la présentation simple du nom ; ne pas inventer de niveau parent pour le dossier spécial Documents à classer.
- Prévoir le retour à la ligne des longs chemins sur petits écrans, sans chevaucher les actions du dossier.
- Respecter les droits existants : ne révéler aucun nom ni lien de dossier non autorisé et vérifier les accès lors de la navigation.
- Vérifier les dossiers à plusieurs niveaux, les noms identiques dans des branches différentes, les noms longs, les changements de sélection, les permissions et la navigation clavier.

## 5. Ajouter du contenu depuis le dossier courant — barre d’actions et formulaires intégrés

Demande du propriétaire : pouvoir ajouter des documents ou créer un sous-dossier directement depuis le panneau du dossier sélectionné. Il retient la barre d’actions, avec correction de son emplacement et réutilisation des formulaires complets existants.

- **Recherche conservée à sa place** sous l’en-tête du dossier. La barre d’actions se trouve **en dessous de la recherche**, avant le contenu, avec **Ajouter des documents** (Navy, file-plus-2) et **Créer un sous-dossier** (secondaire, folder-plus). La variante avec un menu Ajouter n’est pas retenue.
- Dans l’ajout contextuel de documents, supprimer le sélecteur **Dossier de destination** : le dossier ouvert est la destination. Garder le chemin visible dans le contexte et le libellé **Ajouter dans ce dossier**. Cette simplification s’applique aux sources ordinateur et médiathèque.
- Conserver le choix de destination dans l’entrée globale Ajouter des documents tant qu’elle existe. Le parent du formulaire de création de sous-dossier reste présélectionné ; la présente demande porte uniquement sur le sélecteur de destination des documents.
- Reprendre les interfaces des menus dédiés : sources ordinateur/médiathèque, sélection des documents, notice des formats et règle d’accès ; création de dossier avec nom, parent et règle d’accès. Réutiliser les composants et traitements existants lors du développement.
- Visuel ajusté accueilli favorablement : ouvrir le formulaire sous les actions, à la place de la liste, sans déplacer la recherche, puis retrouver le contenu du même dossier après succès confirmé. Un seul formulaire ouvert à la fois, fermeture/annulation accessibles.
- Propositions complémentaires présentées : hériter des droits par défaut, afficher le chemin complet sur mobile et demander confirmation avant de quitter un formulaire modifié. La proposition antérieure de rendre la destination contextuelle modifiable est remplacée par sa suppression ci-dessus.
- La recommandation de retirer les onglets Ajouter des documents et Créer un dossier au profit des actions intégrées est désormais accueillie favorablement par le propriétaire ; organiser leur remplacement avec le parcours initial décrit au point 6. Réutiliser les fonctionnalités existantes plutôt que supprimer leurs traitements.
- Conserver les actions de droits/suppression/publication et les validations de fichiers, de stockage et de destination. Contrôler séparément les capacités de création de dossier et d’ajout de document, côté serveur, sans déduire un droit d’écriture du droit de lecture.
- Vérifier côté serveur l’existence et les droits d’ajout dans le dossier ciblé même sans sélecteur visible ; en cas de changement de sélection pendant un formulaire ouvert, ne jamais rediriger silencieusement les documents vers un autre dossier.
- Aucun sous-dossier ne doit être créé sous le dossier spécial Documents à classer ; traiter ce contexte selon ses règles existantes.
- Prévoir empilement des actions sur mobile, navigation clavier, annulation sans changement et gestion explicite des erreurs. Vérifier que le dossier parent/destination ne change pas silencieusement et que le nouveau contenu apparaît dans le bon dossier après réussite.

## 6. Navigation simplifiée et création intégrée du premier dossier — validées

Le propriétaire approuve la centralisation des ajouts dans Documents et valide la démonstration de création du premier dossier dans le panneau d’accueil.

- Navigation proposée : **Documents / Corbeille**. Les deux anciens onglets d’action sont remplacés par les points d’entrée intégrés, en préservant l’ensemble des opérations existantes.
- Depuis l’accueil sans sélection, le bouton **Créer un dossier** ouvre le formulaire dans le panneau central. Le bouton de création de l’arborescence mène au même formulaire de création à la racine.
- Distinguer la bibliothèque sans dossier utilisateur (**Créez votre premier dossier**) de la bibliothèque avec dossiers mais sans sélection (**Ouvrez un dossier**). Ne pas confondre le dossier spécial avec un dossier parent utilisateur.
- Parcours validé : nom, emplacement **Racine de la bibliothèque** indiqué sans sélecteur inutile pour le premier dossier, et choix explicite des droits à la racine, où aucun parent n’existe. Préserver les contrôles de permissions existants.
- Annuler/fermer restitue le contexte initial ; proposer une confirmation si le formulaire a été modifié. Après succès serveur, ajouter le dossier à l’arborescence et l’ouvrir pour permettre l’ajout de documents. Ne pas publier automatiquement son contenu.
- L’ajout de documents sans dossier sélectionné reste à détailler dans le parcours final ; la recommandation précédente était une destination clairement annoncée vers Documents à classer. Ne pas perdre cette possibilité lors du retrait des onglets.
- Vérifier bibliothèque vide, bibliothèque existante sans sélection, accès refusé, erreurs de création, changement de contexte, annulation, FR/EN, clavier et mobile. Aucun succès ne doit être affiché avant confirmation serveur ; la démonstration seule simule la création.

## 7. Notes de récupération répétées — présentation validée

Le propriétaire signale le cumul de bandeaux « Fichier récupéré : les restrictions de son ancien dossier restent applicables après son reclassement. » et demande un affichage discret, par exemple une pastille d’information avec explication au survol.

### Diagnostic du code actuel

- `includes/AdminWorkspace.php`, méthode listing(), lignes 355–356 au moment du diagnostic : un paragraphe d’avertissement est produit pour **chaque document ayant retained_rules non vide**, directement dans tbody avant tr. Ce placement HTML est invalide et sépare visuellement la note de sa ligne. Le code n’affiche pas cette note systématiquement pour tous les documents.
- `includes/FolderLifecycle.php` sauvegarde les règles de l’ancien dossier et de ses ancêtres dans chaque document lors de la récupération après suppression d’un dossier. `includes/Lifecycle.php` sauvegarde également la chaîne de règles lors d’un déplacement volontaire vers Documents à classer.
- Le déclenchement actuel ne compare pas les restrictions de l’ancien dossier à celles du fichier : la présence de règles sauvegardées ne prouve pas qu’elles restreignent davantage l’accès. Ces règles restent prises en compte après reclassement ; l’affichage n’est pas limité au dossier spécial.

### Présentation validée par le propriétaire

- Retirer les bandeaux répétés et afficher une petite pastille **i** dans la cellule **Accès effectif** du document concerné, sans colonne supplémentaire pour conserver l’espace de lecture.
- Intitulé proposé : **Droits antérieurs conservés**. Description : **Ce fichier conserve les règles d’accès de son ancien emplacement, même après son reclassement.** Ce libellé évite de prétendre que les règles sauvegardées sont toujours plus restrictives.
- Une seule pastille par document concerné, aucune pour un document sans règles conservées ; ne pas rattacher une note globale à tous les documents du dossier.
- Explication accessible au survol et au focus clavier, ouvrable/fermable au clic ou au toucher, avec fermeture par Échap et un seul panneau d’information ouvert à la fois. Nom accessible rattaché au document, texte FR/EN, positionnement sans découpe par le tableau ni débordement mobile. Préserver les avertissements critiques distincts.
- Conserver strictement les règles sauvegardées et leur application côté serveur : cette correction est une modification de présentation, pas une suppression des restrictions.
- Vérifier un mélange de documents récupérés et ajoutés directement, chaîne publique ou restreinte, déplacement volontaire, suppression de dossier et reclassement ultérieur, recherche/pagination, clavier et mobile. Vérifier le balisage du tableau et l’absence d’élargissement d’accès ; ne pas masquer une erreur de règles invalides derrière une note rassurante.

## 8. Corbeille : pictogrammes, recherche et améliorations complémentaires validés

Le propriétaire valide l’ensemble des demandes et propositions ci-dessous.

### Demandes du propriétaire

- Afficher à côté de chaque nom le même pictogramme de type de fichier que dans Documents. Réutiliser la correspondance des types et les assets existants, avec repli générique pour un type inconnu, sans ouvrir ni prévisualiser un fichier en corbeille.
- Proposer une recherche par nom clairement identifiable et conserver le filtre **Dossier**.
- Harmoniser la barre avec FileWarden : libellés visibles, texte indicatif **Rechercher un document dans la corbeille…**, pictogramme de loupe, bouton au style secondaire validé pour la v17 et affichage adapté au mobile.
- Recherche et dossier se combinent ; conserver leurs valeurs après application et distinguer une corbeille vide d’une recherche sans résultat.

### Constat dans le code

`includes/TrashConsole.php`, lignes 20–33 au diagnostic : la recherche est déjà transmise à DocumentCollection::rows avec le filtre dossier ; le champ existant n’a aucun placeholder et son libellé est masqué visuellement, ce qui correspond au grand champ vide de la capture. Le rendu du nom est un simple texte sans pictogramme. Le fonctionnement réel de recherche devra être vérifié lors du développement ; sa présence dans le code ne vaut pas validation fonctionnelle.

### Améliorations complémentaires validées

- Afficher sous la date de mise en corbeille le délai restant ou la date d’éligibilité à la purge automatique, calculé selon la durée de conservation configurée. Ne pas promettre une heure d’exécution exacte du nettoyage planifié.
- Ajouter une flèche de restauration à **Restaurer** et le pictogramme de corbeille habituel à **Supprimer définitivement**. Conserver les libellés visibles, Danger Red pour la suppression et les confirmations existantes.
- Afficher le nombre de résultats autorisés et un lien **Réinitialiser les filtres** lorsqu’une recherche ou un filtre est actif.

### Vérifications et non-régression

- Types PDF/images/bureautique/texte et type inconnu, noms longs et caractères spéciaux, recherche seule et combinée à un dossier, résultat vide, remise à zéro, FR/EN, clavier et mobile.
- Les résultats, chemins et compteurs restent limités aux documents autorisés ; aucune régression de restauration, de destination obligatoire lorsque l’origine a disparu, de purge, de rétention ou de règles conservées.
- Les filtres de consultation ne doivent pas modifier silencieusement le périmètre de **Vider la corbeille** : préserver le contrôle serveur et annoncer explicitement le périmètre dans la confirmation.

## 9. Publication : personnalisation de l’apparence publique — proposition et recommandations validées

Le propriétaire valide la proposition et les recommandations, avec maintien des pictogrammes réels des types de fichiers et centrage de l’icône et du nom dans les cartes en grille. Le GO global a été reçu le 20 novembre 2026.

### Demande et décision de portée

- Le propriétaire demande de personnaliser les couleurs du frontend : fond, boutons et autres éléments de l’interface publique, avec modèles préconfigurés.
- Par défaut, proposer le modèle **FileWarden**, ses couleurs et son logo officiel inchangé.
- Permettre de choisir un logo personnel **PNG ou JPEG** et de modifier le nom affiché au public. Recommander un champ **Nom affiché au public**, qui ne renomme ni le plugin WordPress, ni ses identifiants, ni ses données techniques.
- Réponse explicite du propriétaire : **une apparence commune à toutes les publications du site** pour cette version. La sélection du dossier et du mode liste/grille reste propre à chaque intégration, comme aujourd’hui. Ne pas ajouter de profils graphiques indépendants par publication dans ce lot.
- Le nom et l’identité du plugin dans l’administration restent FileWarden. Cette personnalisation concerne ses interfaces publiques, conformément à docs/branding.md.

### Organisation validée

Dans Publication, conserver l’intégration du dossier/code court et ajouter des réglages à gauche avec aperçu à droite :

1. **Modèle de départ** : cartes de modèles avec nom et palette : **FileWarden**, **Neutre**, **Nature**, conformément à la proposition validée. FileWarden reste le modèle par défaut.
2. **Couleurs de l’interface** : fond général, cartes/champs, texte principal, fond et texte des boutons, liens/pictogrammes. Réglages complémentaires repliés : survol, texte secondaire, bordures, accent décoratif. Sélecteur de couleur et valeur hexadécimale, avec contrôle des contrastes pertinents.
3. **Nom et logo affichés** : nom public, import du logo PNG/JPEG, aperçu miniature, retour au logo FileWarden ; choix Afficher le nom / Afficher le logo. Le changement de palette conserve l’identité personnalisée.
4. **Enregistrement explicite** : aperçu immédiatement actualisé, indication des modifications non enregistrées, bouton **Enregistrer l’apparence**. Aucun changement public avant succès serveur. Bouton **Rétablir FileWarden** avec confirmation ; son résultat reste à enregistrer.
5. **Aperçu** : liste/grille, largeur ordinateur/mobile, affichage autorisé/accès refusé. Sur petit écran, panneaux empilés et sections repliables. Les autres options actuelles de Publication, notamment styles d’accès indisponible et génération du code court, restent disponibles même si elles ne sont pas toutes représentées dans la maquette.

La démonstration utilise des documents fictifs et une importation de logo locale au navigateur ; elle ne publie rien. Ses limites de taille/dimensions d’image sont des garde-fous de démonstration, pas des limites de production déjà validées.

### Pictogrammes et présentation en grille

- Réutiliser les vrais pictogrammes de types de fichiers existants, avec leur correspondance et leurs couleurs, dans l’aperçu de Publication et le frontend réel, en liste comme en grille. Les pictogrammes génériques PDF de la démonstration ne constituent pas une demande de remplacement du catalogue existant.
- Les réglages de couleur des pictogrammes fonctionnels (recherche, dossiers, actions) ne doivent pas recolorer ou remplacer les pictogrammes de types de fichiers.
- Précision du propriétaire : en grille, **centrer verticalement le bloc icône + nom dans sa carte**, en plus du centrage horizontal. Garder l’icône au-dessus du nom, les noms longs lisibles par retour à la ligne, sans débordement ni perte du nom accessible. Conserver l’ordre de lecture et les actions disponibles sur ordinateur et mobile.
- Illustration validée par le propriétaire : bloc d’identité (icône, nom et informations de fichier) centré en hauteur et largeur ; boutons Aperçu/Télécharger séparés en bas de carte. La hauteur de démonstration n’est pas une dimension de production imposée.
- Le centrage concerne les cartes de fichiers en grille ; ne pas modifier silencieusement l’alignement de la liste détaillée ni celui des dossiers.

### Cadre technique et sécurité à préserver lors du développement

- Paramètres persistants et réutilisables, conservés après mise à jour. Bibliothèques existantes, liens documentaires, UUID, paramètres de code court et intégrations Gutenberg/Elementor restent fonctionnels ; définir explicitement le comportement des personnalisations déjà existantes lors de l’implémentation.
- Couleurs et états centralisés dans des jetons strictement limités aux composants publics FileWarden, y compris leurs panneaux de détail/aperçu et états indisponibles. Ne pas recolorer le thème du site ou l’administration WordPress. Préserver des focus visibles, contrastes, informations d’erreur et navigation clavier.
- Validation serveur des valeurs autorisées ; pas de CSS/HTML/JavaScript libre via les champs. Nom traité comme texte, correctement échappé. Les contrôles de contraste de la maquette portent sur les paires de texte représentées et ne constituent pas une certification complète d’accessibilité.
- Enregistrement réservé à la capacité de réglages existante, avec nonce et contrôles serveur. Respecter également les capacités WordPress pour l’import ou la sélection d’un média ; la lecture globale des documents ne confère aucun droit de personnalisation.
- Un logo est une image destinée à être publique : ne pas réutiliser automatiquement des documents confidentiels du stockage protégé. Vérifier extension, MIME réel, décodage et ressources consommées, conserver les proportions, gérer l’échec et le retour au logo officiel. Refuser SVG/HTML et autres contenus actifs pour ce champ même si le mode tous formats des documents est activé. Implémentation retenue : médiathèque WordPress native, PNG/JPEG local de 2 Mo maximum et 6 000 pixels maximum par côté ; nom public limité à 64 caractères.
- Les modèles et l’identité visuelle ne modifient jamais les permissions, la publication effective, les nonces, la protection du stockage, les règles conservées ou les réponses de téléchargement. L’aperçu réel doit continuer à respecter les droits du compte et ne pas dévoiler le contenu accessible seulement à d’autres comptes.
- Prévoir textes FR/EN sans traduire ni altérer le nom personnalisé de l’organisation, valeurs par défaut sûres et restauration du modèle officiel sans suppression de documents ou médias utilisateurs.

### Validation prévue

- Modèle par défaut et changement de modèle, couleurs valides/invalides, contraste normal/survol, conservation du nom/logo au changement de palette, enregistrement/rechargement, annulation et rétablissement explicite.
- Logos PNG/JPEG, fichiers renommés ou malformés, SVG/HTML rejetés, dépassements de limites, transparence/proportions, capacité média refusée, tentative CSRF et nom contenant du balisage.
- Propagation de l’apparence commune aux publications existantes/nouvelles sans copier les réglages dans chaque code court ; isolation du thème et des styles d’administration, coexistence de plusieurs bibliothèques sur une page, prise en compte des caches.
- Liste/grille, dossiers et navigation, recherche, aperçu/détail/téléchargement, refus d’accès, rôles distincts, clavier, mobile, FR/EN, intégrations actuelles et maintien intégral des ACL.
- Pictogrammes corrects pour chaque type connu et repli générique existant pour les autres ; maintien de leurs couleurs après changement de modèle. Vérifier le centrage icône/nom en grille pour des noms courts, longs ou sans espaces, à plusieurs largeurs, et la cohérence aperçu/frontend réel.

Les neuf axes sont intégrés au candidat local 0.1.17-dev. Recette locale du ZIP validée le 21 novembre 2026 ; aucune publication GitHub dans ce lot.

## Matrice d’implémentation et de recette du candidat

Les références ci-dessous désignent le code distribué et les scénarios exécutables. Les résultats finaux, versions d’outils et empreinte du ZIP sont consignés dans `docs/validation/v0.1.17-dev-local.json` et `docs/testing.md`. Une définition de test ou de workflow ne constitue pas à elle seule une exécution réussie.

| Axe | Implémentation | Vérifications |
|---|---|---|
| 1. Boutons | `assets/admin-controls.css`, composants existants `fw-button-accent` | `v16-controls-browser.cjs`, profils WordPress, focus et fonctionnement recherche/médiathèque/journal |
| 2. En-tête | `AdminBrand.php`, `admin-controls.css` | `v17-browser.cjs`, `v16-browser.cjs`, proportion 56/44 et titre séparé |
| 3. Arborescence | `admin-controls.css`, `AdminWorkspace.php` | Parcours workspace/mobile, niveaux et noms longs, sélection et cibles tactiles |
| 4. Fil d’Ariane | `AdminWorkspace::breadcrumbs()` | Intégration et navigateur : parents autorisés, dossier courant, chemin imbriqué |
| 5. Actions contextuelles | `AdminWorkspace.php`, `AdminController.php`, `workspace.js` | Import réel, source médiathèque, cible cachée fixée, recherche immobile, droits, formulaires/cancellation |
| 6. Création intégrée | mêmes composants, anciennes routes maintenues | `v17-fresh-browser.cjs`, `fresh-install.php`, ouverture après succès, droits racine explicites et héritage enfant |
| 7. Notes de récupération | `AdminWorkspace.php`, `workspace.js` | `v17-integration.php`, `v17-browser.cjs` : une pastille par fichier concerné, aucun bandeau répété, survol/clic/Échap et ACL conservées |
| 8. Corbeille | `TrashConsole.php`, mêmes pictogrammes `Publication::icon()` | Recherche/filtre combinés, types PDF/image, compteur/délai, restauration et purge confirmées dans les scénarios historiques |
| 9. Apparence commune | `FrontendAppearance.php`, `AppearanceConsole.php`, `appearance.js`, `Publisher.php`, `Publication.php`, `Viewer.php` et CSS publics | PHPUnit, intégration, sécurité des logos, deux navigateurs : modèles/nom/logo, brouillon isolé, sauvegarde, CSRF/capacités, contraste, CSP, rétablissement, mobile et centrage exact en grille |

### Choix techniques appliqués

Le [lot correctif validé le 21 novembre](corrections-v0.1.17-dev.md) complète ces neuf axes et remplace les décisions initiales de centrage vertical strict et d’intégration repliable. Ses tests et son archive font l’objet d’une preuve distincte du candidat initial.

- Apparence stockée dans une option WordPress versionnée, indépendante des documents et du schéma 6. Les intégrations déjà présentes reçoivent les valeurs communes à leur prochain chargement ; aucun code court à remplacer.
- Choix de logo via la médiathèque native, PNG/JPEG de même origine dans le répertoire de téléversement, 2 Mo et 6 000 pixels par côté au maximum. Vérification réelle du format, de l’extension et des droits média ; refus d’un média supprimé ou incompatible, repli visuel sur le logo officiel si nécessaire.
- Nom public en texte brut, 64 caractères maximum. Seulement dix variables de couleur hexadécimales complètes ; pas de CSS libre, huit paires de contraste contrôlées à 4,5:1, y compris le survol.
- Rétablissement du modèle officiel d’abord dans l’aperçu ; enregistrement explicite nécessaire. Les changements de palette conservent l’identité. Aucun changement des fichiers, autorisations, liens, médias originaux ou réglages linguistiques.
- Après correction : deux onglets locaux Intégration du dossier/Apparence publique conservent leurs formulaires et brouillons, avec un aperçu commun. Le nom et le logo de l’administration restent officiels.

### Recette manuelle sur la préproduction

Après sauvegarde et installation du ZIP, vérifier les neuf axes dans les rôles réellement utilisés sur le site, ainsi que les éventuels caches/extensions du site. Les tests locaux couvrent des documents synthétiques et des environnements isolés ; ils ne remplacent pas la validation du stockage et de l’hébergement cible ni une revue humaine complète au lecteur d’écran.
