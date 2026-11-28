# FileWarden 0.1.19-dev — lot autorisé

La passe suivante validée le 28 novembre est décrite dans [les corrections de préproduction](lot-corrections-v0.1.19-dev.md). Elle remplace notamment l’accès au tri Type prévu initialement ci-dessous par son retrait de l’interface, avec conservation du filtre par extension.

GO global reçu le 27 novembre pour l’ensemble des 16 points : implémentation, tests locaux, livraison ZIP. Les mentions de GO attendu sont historiques et levées par cet accord. Aucun push, tag ou publication autorisé. Le candidat conserve le nom 0.1.19-dev pour recette.

27 novembre 2026. Le propriétaire transmet les corrections successivement. Préparation validée ; attendre son GO consolidé avant toute implémentation. Une première `0.1.19-alpha.1` sera envisagée après les corrections, les tests locaux et la recette de préproduction. Aucune publication autorisée à ce stade.

## Correction 1 — largeur du menu contextuel pendant le défilement

Signalement : ouvrir le menu « … » d’un document, puis faire défiler la page vers le bas ou vers le haut. Le menu devient progressivement beaucoup trop large. Capture de préproduction fournie par le propriétaire.

Constat statique : dans `assets/workspace.js`, le repositionnement passe le panneau en `position: fixed`, mesure `offsetWidth` et écrit `left` à chaque événement de défilement. Le CSS `.fw-context-menu` conserve `right: 0` et ne fixe pas sa largeur. Cette combinaison peut étirer le panneau, puis réutiliser cette largeur accrue lors du calcul suivant. Cause probable identifiée par lecture du code ; reproduction instrumentée et correction à réaliser après GO.

Comportement attendu :

- Conserver un menu compact, adapté aux libellés, de largeur stable après des défilements répétés dans les deux sens.
- Maintenir un positionnement cohérent par rapport au bouton et les limites de l’écran.
- Conserver l’accès aux actions, la fermeture, Échap et le fonctionnement au clavier.
- Vérifier le défilement, le redimensionnement et les écrans mobiles dans Chromium et Firefox, avec mesure de la largeur pour empêcher cette régression.

## Évolution 2 — sélection intégrée au tableau et actions contextuelles

Proposition et maquette interactive validées explicitement le 27 novembre. Le GO de développement global reste attendu.

- Déplacer la case « Sélectionner les documents visibles » dans la première colonne de l’en-tête, alignée sur les cases des documents. Sélection limitée aux documents visibles, avec état intermédiaire pour une sélection partielle et libellé accessible.
- Retirer le bandeau permanent de sélection et la liste permanente des destinations.
- Afficher une barre compacte juste au-dessus du tableau uniquement lorsqu’au moins un document est coché : compteur, « Déplacer / classer », « Mettre à la corbeille », désélectionner.
- Choisir la destination dans une fenêtre modale, en conservant les vérifications et confirmations serveur existantes. Mise à la corbeille récupérable, sans suppression définitive implicite.
- Masquer les actions à nouveau lorsque la sélection est vide. Conserver les couleurs FileWarden (navy et rouge pour la corbeille), le clavier et une disposition mobile sans débordement.
- Préserver les limites de sélection et les contrôles d’autorisation existants ; la refonte visuelle ne doit pas étendre les droits.

Maquette : `filewarden-selection-tableau-019.html` dans le répertoire de visualisations du chat. Elle simule les actions sans modifier la bibliothèque ; ses pictogrammes illustratifs ne remplacent pas les assets de fichiers du plugin.

## Évolution 3 — recherche compacte, tri dans les en-têtes et date d’arrivée

Proposition et démonstration ordinateur/mobile entièrement validées par le propriétaire le 27 novembre. Attendre le GO consolidé avant développement.

- Sur ordinateur, intégrer le périmètre « Rechercher dans » à l’extrémité droite du champ de recherche, avec une séparation discrète et un libellé accessible. Bouton « Rechercher », puis bouton « Filtres » immédiatement à droite sur la même ligne.
- Conserver les critères complémentaires dans un panneau repliable en dessous ; indiquer le nombre de filtres actifs dans une pastille.
- Supprimer la liste « Trier par ». Trier par clic sur les en-têtes Document, Taille et Ajouté le ; inverser l’ordre au clic suivant, avec indicateur visuel et état accessible du tri. Préserver les tris et filtres existants utiles, notamment le type de fichier, en précisant leur accès lors de l’implémentation.
- Ajouter la colonne « Ajouté le » entre Taille et Règle du fichier, date et heure secondaire. Elle correspond à l’arrivée dans le dossier actuel, actualisée lors d’un déplacement ; conserver séparément la date d’import d’origine. Vérifier les données existantes et prévoir une reprise explicite sans inventer d’anciennes dates d’arrivée inconnues.
- Sur mobile, garder le périmètre dans le champ, les boutons Rechercher/Filtres sur la ligne suivante, des colonnes et actions compactes, la sélection et les modales utilisables sans débordement. Préserver l’accès aux informations de droits même si les colonnes secondaires sont adaptées à l’écran.
- Associer la nouvelle disposition aux actions de sélection de l’évolution 2. Maintenir toutes les autorisations, la recherche limitée aux dossiers accessibles, les contrôles serveur, la limite de sélection et les traductions FR/EN.

Maquette validée : `filewarden-recherche-responsive-019.html` dans le répertoire de visualisations du chat. Données et pictogrammes illustratifs ; aucune modification de la bibliothèque réelle.

## Correction 4 — infobulle du bouton Renommer

Demande explicite du propriétaire : le bouton crayon de renommage des documents doit afficher « Renommer » au survol, comme les actions Aperçu et Télécharger. Réutiliser le même composant et style d’infobulle, et rendre le libellé disponible au focus clavier avec un nom accessible. Traduire en FR/EN. Le bouton reste sous forme de pictogramme, sans ajout permanent de texte dans la ligne du tableau. Attendre le GO global avant implémentation.

## Correction 5 — modification des droits d’un document en modale centrée

Demande explicite du propriétaire : remplacer le panneau latéral droit « Modifier les droits » d’un fichier par une modale centrée, cohérente avec les autres actions déjà converties.

- Réutiliser le comportement et le style des modales existantes ; ne pas réduire la largeur du tableau à l’ouverture.
- Conserver le nom et le pictogramme de fichier, l’accès effectif, les restrictions du dossier, le choix de règle d’accès et les explications d’héritage, ainsi que les éventuels champs conditionnels existants.
- Conserver Enregistrer les droits, Annuler, la fermeture, Échap, la gestion du focus et le retour au déclencheur ; adaptation aux petits écrans.
- Préserver intégralement les autorisations, nonces, validations et calculs des droits côté serveur. Mettre à jour les traductions FR/EN si nécessaire.

Attendre le GO global avant implémentation. Cette demande vise les droits d’un document ; elle n’autorise pas à étendre automatiquement la refonte à d’autres écrans.

## Correction 6 — finition de la modale Renommer

Signalement du propriétaire : bouton de fermeture trop haut et étroit ; encadrement navy du champ trop imposant (effet de double cadre sur la capture).

- Rendre le bouton de fermeture carré, croix centrée et proportions cohérentes avec les autres modales, en préservant une cible tactile suffisante et le nom accessible.
- Supprimer l’effet de double encadrement du champ de renommage et harmoniser sa bordure avec les champs FileWarden. Garder un indicateur de focus discret mais visible pour l’usage au clavier ; ne pas supprimer indistinctement tous les styles de focus.
- Conserver le focus initial, les comportements de sélection du texte et les validations du renommage, sans confondre la sélection native du texte et le contour de focus signalé.
- Vérifier les styles hérités, les dimensions et le rendu ordinateur/mobile avant de généraliser la finition aux composants partagés concernés.

Attendre le GO global avant implémentation.

## Évolution 7 — lecteur d’aperçu compact

Proposition et maquette interactive ordinateur/mobile validées par le propriétaire. Attendre le GO global avant développement.

- Remplacer le grand en-tête par une barre compacte, cible indicative 48–56 px sur ordinateur : petit logo réel FileWarden, nom du fichier, métadonnées discrètes, navigation, zoom et actions.
- Navigation avec flèches, page modifiable et total. Retirer la ligne séparée redondante « Page 1 / 9 ».
- Zoom avec boutons moins/plus et choix largeur, page entière et pourcentages ; conserver les capacités réelles du lecteur existant et vérifier le comportement effectif, la maquette ne constituant pas une implémentation PDF.
- Actions ouvrir dans un nouvel onglet et télécharger sous forme de pictogrammes avec infobulles, noms accessibles et accès clavier.
- Fond neutre, document centré, séparation fine sous la barre ; commandes disponibles pendant le défilement. Respecter l’identité FileWarden et les réglages d’apparence applicables.
- Sur mobile, identité/actions puis navigation/zoom en rangées compactes, commandes tactiles et repli supplémentaire si nécessaire sur les écrans très étroits, sans débordement.
- Préserver les contrôles d’accès, la protection du document et les comportements des formats d’aperçu existants. Aucune fonction d’annotation ou d’édition PDF ajoutée implicitement. Traductions FR/EN et vérification clavier/ordinateur/mobile.

Maquette validée : `filewarden-lecteur-compact-019.html` dans le répertoire de visualisations du chat. Document et symbole de marque illustratifs ; utiliser les assets officiels à l’implémentation.

## Évolution 8 — titre du dossier, crayon discret et fil d’Ariane visible

Proposition et maquette validées explicitement par le propriétaire. Le crayon doit apparaître seul, sans fond ni contour de bouton visible. Attendre le GO global avant développement.

- Retirer le bouton textuel Renommer sous le nom du dossier. Placer le pictogramme crayon immédiatement à droite du titre, aligné verticalement, avec infobulle « Renommer le dossier » et ouverture de la modale de renommage.
- Conserver un contrôle sémantique accessible au clavier, un nom accessible, un focus visible uniquement lorsque pertinent et une cible tactile suffisante, sans chrome de bouton permanent.
- Placer le fil d’Ariane au-dessus du titre, en remplacement du libellé « DOSSIER ACTUEL ». Fond légèrement teinté, espacement confortable, pictogramme dossier initial et chevrons ; parents cliquables et dossier courant en navy/gras avec état accessible.
- Adapter les chemins imbriqués et noms longs aux petits écrans par retour à la ligne, sans masquer des destinations essentielles. Conserver le crayon près du titre et les autres actions et états du dossier existants.
- Respecter les contrôles d’accès aux dossiers parents et au renommage, ainsi que les traductions FR/EN.

Maquette validée : `filewarden-titre-dossier-019.html` dans le répertoire de visualisations du chat. Renommage simulé uniquement.

## Évolution 9 — aide et saisie de l’identifiant de popup Elementor

Approche validée explicitement par le propriétaire : accès direct à la liste des fenêtres modales et champ acceptant l’ID ou le code court Elementor. Attendre le GO global avant développement.

- Remplacer le lien vers la documentation par « Ouvrir les fenêtres modales Elementor », ouvert dans un nouvel onglet pour préserver les réglages en cours. Construire l’URL via l’administration WordPress du site courant et les paramètres `post_type=elementor_library`, `tabs_group=popup`, `elementor_library_type=popup`. Aucun domaine codé en dur ; respecter les droits d’accès.
- Expliquer où récupérer le numéro dans la colonne Code court : `[elementor-template id="1252"]` correspond à l’ID 1252.
- Conserver le libellé « Identifiant de la popup Elementor » et ajouter l’aide « Saisissez l’identifiant ou collez le code court Elementor. »
- Accepter un identifiant positif seul ou le code court Elementor prévu, extraire l’ID et afficher sa valeur normalisée ; stocker seulement l’identifiant numérique.
- Analyser la saisie sans jamais exécuter le code court. Valider strictement côté serveur, traiter les valeurs ambiguës ou invalides avec un message clair, et préserver les contrôles existants du réglage et de la popup.
- Préserver le mode de connexion WordPress de secours et les prérequis Elementor existants. Actualiser FR/EN, accessibilité et tests de saisie/navigation.

## Évolution 10 — sélecteur de langue plus compact

Proposition validée par le propriétaire : réduire la hauteur de la liste dans Réglages > Général et conserver une présentation sobre sans drapeaux.

- Viser une hauteur de 40–44 px, avec espacement intérieur réduit et cohérent avec les autres champs ; préserver le confort tactile et le focus clavier.
- Afficher les choix « Automatique — Langue du site », « FR — Français » et « EN — English », avec traduction des libellés pertinents selon la langue active.
- Conserver le pictogramme de traduction à côté du titre Langue, le sélecteur natif et le comportement existant du mode automatique, de la langue de secours et des préférences.
- Ne pas ajouter de drapeaux. Prévoir une présentation extensible aux futures langues sans changer les identifiants de locale stockés.

Attendre le GO global avant implémentation.

## Ajustement 11 — titre du menu Préférences

Le propriétaire demande de renforcer le titre Préférences, actuellement trop petit et discret, pour identifier clairement le menu latéral des réglages.

Proposition : titre navy d’environ 16–18 px, graisse semi-grasse, espacement normal des lettres plutôt que le style de petit libellé actuel, alignement avec le contenu du menu, marge confortable et séparation fine sous le titre. Garder la hiérarchie avec le titre principal de page et une présentation adaptée au mobile. Conserver un intitulé sémantique de navigation et sa traduction FR/EN.

Attendre le GO global avant implémentation.

## Ajustement 12 — espacement commun sous les titres de page

Demande explicite du propriétaire : réduire le grand espace entre le titre Publication et les onglets, et appliquer un espacement cohérent entre le titre et le contenu sur les autres pages FileWarden.

- Identifier les marges et espacements cumulés de l’en-tête et des navigations avant de choisir une règle partagée ; ne pas attribuer une cause précise sans inspection.
- Réduire l’espace sous le titre Publication et conserver un rythme commun pour les pages avec onglets et les pages avec contenu direct (Documents/Corbeille, Publication, Réglages, Journal et autres écrans concernés).
- Garder une respiration visuelle suffisante et une adaptation mobile ; éviter les décalages dus à des marges par page ou à des éléments masqués.
- Harmoniser ce seul espacement sans modifier les proportions validées du logo, du nom FileWarden et du titre de page.

Attendre le GO global avant implémentation.

## Évolution 13 — palettes FileWarden sombre et Terracotta

Les deux propositions et la maquette interactive sont validées explicitement par le propriétaire. Ajouter deux palettes prédéfinies à Publication > Apparence publique, sans remplacer le choix courant. Attendre le GO global avant développement.

Couleurs validées :

| Rôle | FileWarden sombre | Terracotta |
| --- | --- | --- |
| Fond | #101a2b | #faf2ec |
| Cartes et champs | #192840 | #fffaf6 |
| Texte principal | #e6edf7 | #432c25 |
| Fond des boutons | #76d6ce | #914833 |
| Texte des boutons | #102338 | #ffffff |
| Liens et icônes d’action | #9fe4de | #85432e |
| Survol des boutons | #a0e7df | #743724 |
| Texte secondaire | #b6c4d8 | #755b50 |
| Bordures | #3d536f | #ddc7ba |
| Accent | #76d6ce | #b25f45 |

- Cartes de palette compactes avec leurs pastilles bordées, cohérentes avec les palettes existantes et la personnalisation.
- Conserver nom et logo personnalisés lors du changement de palette, ainsi que les pictogrammes officiels des types de fichiers. La maquette utilise des symboles illustratifs et ne les remplace pas.
- Appliquer tous les rôles aux états réels du frontend et de l’aperçu, liste/grille, mobile, formulaires, liens, survol et focus ; conserver les mécanismes d’enregistrement/réinitialisation et les traductions FR/EN.
- Calculs préliminaires : six paires texte/fond, texte/carte, texte secondaire/carte, lien/carte, bouton et survol ont été vérifiées. Minimum sombre 8,37:1, minimum Terracotta 6,01:1 sur ces paires. Cela qualifie uniquement ces contrastes de texte, pas une conformité globale d’accessibilité. Vérifier l’ensemble des paires et états réels lors de l’intégration avec le score de contraste existant.

Maquette validée : `filewarden-palettes-sombre-terracotta-019.html` dans le répertoire de visualisations du chat.

## Évolution 14 — sauvegarde complète/personnalisée et configuration seule

Le propriétaire valide l’ensemble de la proposition. Attendre le GO global avant développement. Ce point étend la sauvegarde sélective v18 : documents et dossiers ne sont plus systématiquement obligatoires pour un export de configuration seule.

- Mode Sauvegarde complète : masquer les cases, inclure tous les composants prévus et afficher un résumé explicite du contenu. Conserver ce mode comme choix par défaut.
- Renommer Transfert simplifié en Sauvegarde personnalisée ; afficher les options uniquement dans ce mode et actualiser tous les libellés, aides, récapitulatifs et messages associés FR/EN.
- Regrouper les composants : Bibliothèque (documents, arborescence, corbeille), Informations associées (droits d’accès, références des créateurs), Configuration (réglages FileWarden, apparence publique), Historique (journal d’audit).
- Permettre une sauvegarde des réglages et de l’apparence sans documents, ainsi qu’un choix d’arborescence. Inclure automatiquement les dossiers nécessaires aux documents retenus ; rendre explicites les dépendances, masquer les choix non pertinents de droits/créateurs et interdire une sauvegarde sans composant sélectionné. Définir les dépendances corbeille/arborescence et préserver les droits privés par défaut en l’absence de permissions source.
- Afficher un résumé du contenu choisi avant création. Physiquement exclure les composants non retenus du paquet chiffré et du manifeste ; valider leur cohérence côté serveur, sans se fier aux seules cases de l’interface.
- Permettre la restauration d’une configuration seule sur une destination non vide, avec présentation et confirmation des paramètres remplacés. Ne pas modifier ses documents, dossiers, droits, créateurs ni sa corbeille. Maintenir l’exigence de bibliothèque vide pour une restauration de bibliothèque. Définir séparément le traitement des paquets contenant un historique ou une arborescence : ils ne doivent pas être considérés comme configuration seule.
- Conserver le chiffrement existant, l’authentification du paquet, les contrôles de capacité/nonces et les précautions existantes de restauration de réglages de sécurité. Préserver la compatibilité d’import des sauvegardes précédentes ; adapter versionnement/validation des composants et rollback aux nouveaux cas.

Terminologie validée : remplacer Phrase secrète par Mot de passe de la sauvegarde dans toute la fonctionnalité (création, confirmation, génération, affichage, restauration, erreurs, aides et documentation), en FR/EN, sans changer les paramètres cryptographiques ou affaiblir les validations.

Libellés : « Mot de passe de la sauvegarde — au moins 16 caractères », « Confirmer le mot de passe », « Générer un mot de passe fort », « Afficher le mot de passe ». Aide : « Ce mot de passe protège votre sauvegarde chiffrée. Conservez-le séparément : FileWarden ne peut pas le récupérer. » Clarifier que les mots de passe des comptes WordPress ne sont jamais exportés.

Vérifier notamment les dépendances, l’absence réelle des fichiers exclus, la restauration de configuration sur bibliothèque non vide et sa non-altération, les confirmations, la compatibilité des anciens paquets, les échecs/rollback et la préservation des protections.

## Ajustement 15 — sous-menu WordPress Bibliothèque

Le propriétaire valide « Bibliothèque » au singulier à la place de « Documents » dans le sous-menu WordPress de FileWarden. Utiliser « Library » en anglais.

- Conserver le titre de page « Bibliothèque de documents » et l’onglet Documents à l’intérieur de l’interface.
- Conserver les identifiants techniques de page, routes, liens et autorisations ; le changement concerne le libellé de navigation, sans renommage général des mentions de documents.
- Actualiser les traductions et les références de navigation pertinentes dans la documentation.

Attendre le GO global avant implémentation.

## Ajustement 16 — auteur et sites de l’extension

Le propriétaire retient l’attribution avec son vrai nom et fournit explicitement le lien personnel.

- Auteur affiché : « Michaël PASTOR » à la place de MikaPST.
- Lien de l’auteur : `https://michael-pastor.fr`.
- Site de l’extension retenu : `https://filewarden.org`. La recommandation de le publier comme site officiel suppose un domaine contrôlé et une page prête ; disponibilité et destination publiques à vérifier avant finalisation, sans affirmer qu’elles ont déjà été vérifiées.
- Actualiser les métadonnées WordPress et mentions d’auteur pertinentes de façon cohérente, sans modifier les identifiants GitHub, la propriété du dépôt ou d’autres identifiants techniques.
- La langue ne modifie pas le nom propre. Le numéro de version reste soumis à la décision finale de release (dev / éventuelle alpha).

Attendre le GO global avant implémentation. Aucun site externe modifié par cette demande.

Les autres corrections seront ajoutées à ce document avant consolidation du lot.
