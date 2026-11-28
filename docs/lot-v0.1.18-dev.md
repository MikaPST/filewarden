# FileWarden 0.1.18-dev — périmètre validé

Validation du propriétaire et GO de développement reçus le 22 novembre 2026. Développement des sept axes réalisé ; recette locale et contrôle final du ZIP validés le 23 novembre. Livraison locale pour préproduction uniquement, sans publication GitHub.

## Récapitulatif consolidé des sept axes validés

1. **Lisibilité des textes** : score de contrastes, états AA/AAA/À améliorer/À compléter, détails repliables et mise à jour du brouillon dans Publication → Apparence publique.
2. **Renommage direct** des documents et dossiers, en conservant extension documentaire, UUID/liens et autorisations.
3. **Sélection multiple** pour classer, déplacer et restaurer des documents, avec barre d’actions contextuelle.
4. **Tris et filtres** nom/date/taille/type, combinables avec la recherche.
5. **Recherche globale autorisée**, avec périmètre dossier courant ou bibliothèque accessible et emplacement des résultats.
6. **Pictogrammes documentaires** : intégrer les dernières références fournies, distinguer PPT/PPTX et JPG/JPEG, utiliser AUDIO pour la famille Audio et la nouvelle pellicule Vidéo, retirer les anciens gabarits Présentation/Archive, conserver FILE comme repli. Voir l’inventaire détaillé ci-dessous.
7. **Sauvegarde chiffrée et migration**, approche et interface entièrement validées : catégorie dédiée des Réglages, onglets Sauvegarder/Restaurer, export complet protégé par phrase et restauration guidée sur bibliothèque vide avec contrôle des correspondances et rechiffrement cible.

Exigences communes : FR/EN, clavier/mobile, charte FileWarden, autorisations/CSRF/audit, conservation des réglages et documents existants, tests de non-régression et vérification du ZIP installé. Historiques de versions et d’activité reportés. Le GO de développement est acquis ; aucune publication autorisée.

## Gestion documentaire — fonctionnalités et démonstration validées

Le 22 novembre 2026, le propriétaire valide les quatre fonctionnalités et leur démonstration interactive :

1. **Renommage direct des documents et dossiers**, accessible par un crayon depuis le document ou le titre du dossier, avec confirmation du nom ; conserver les liens permanents et les droits. Le renommage documentaire conserve l’extension.
2. **Sélection multiple**, avec cases individuelles, sélection des résultats affichés et barre d’actions contextuelle pour classer/déplacer les documents ; restauration multiple depuis la corbeille.
3. **Tri et filtres par nom, date, taille et type de fichier**, combinables avec la recherche. La maquette regroupe les filtres dans un panneau repliable et le tri dans une commande compacte.
4. **Recherche globale autorisée**, avec choix entre le dossier courant et toute la bibliothèque accessible ; afficher l’emplacement des résultats pour retrouver leur dossier.

Aucun résultat, nom de dossier, compteur ou extrait non autorisé ne devra être révélé par la recherche réelle. Chaque mutation devra conserver les contrôles serveur de capacité, d’accès, de nonce et d’audit ; les déplacements ne doivent jamais supprimer les restrictions conservées. Les comportements complets de conflits et d’échecs partiels restent à spécifier avec le lot final : les simulations de la maquette ne constituent pas à elles seules une spécification serveur exhaustive.

Référence approuvée : `filewarden-gestion-documents-018.html`, dans les visualisations de cette conversation. Les fichiers et opérations de cette démonstration sont fictifs. Cette validation porte sur le périmètre et la présentation ; le GO de développement a été reçu. Conserver les fonctionnalités existantes et prévoir FR/EN, clavier, mobile et non-régression des autorisations.

Les historiques de versions et d’activité d’un document sont explicitement reportés à un lot ultérieur.

## Lisibilité des textes : score des contrastes AA / AAA

Dans Publication → Apparence publique → Couleurs de l’interface, ajouter sous le message actuel de vérification des contrastes un encart compact **Lisibilité des textes**, conforme à la démonstration validée.

- Score explicite : **8/8 contrastes conformes**, fondé sur les huit associations texte/fond actuellement vérifiées. Calculer le total à partir de la liste commune des vérifications.
- **Bon contraste — AA** si toutes les associations atteignent 4,5:1, sans atteindre toutes le seuil renforcé.
- **Contraste renforcé — AAA** si toutes les associations atteignent 7:1.
- **À améliorer** avec le nombre réel de contrastes conformes, par exemple 6/8, lorsqu’au moins une association est insuffisante.
- **À compléter** et score non calculé lorsqu’un code couleur est invalide ou incomplet.
- Badge AA/AAA ou état explicite, pictogramme et libellé ; la couleur ne porte pas seule l’information. Barre discrète de huit segments, sans bordure décorative, reflétant les vérifications.
- Mise à jour immédiate lorsque les couleurs ou la palette changent, sur le même brouillon que l’aperçu. Conserver les dix champs et les réglages complémentaires repliables.
- **Voir le détail des contrastes** : panneau replié par défaut, listant les associations, leurs rapports et leur niveau AA/AAA/insuffisant. Les rapports restent accessibles ici pour les utilisateurs souhaitant les consulter.

### Associations contrôlées

Les mêmes paires doivent alimenter le contrôle serveur existant et l’indicateur client : texte principal/fond, texte principal/cartes, texte des boutons/bouton, texte des boutons/survol, liens/cartes, liens/fond, texte secondaire/cartes, texte secondaire/fond.

Comparer les ratios non arrondis aux seuils ; un rapport de 4,499:1 reste insuffisant même s’il s’affiche arrondi à 4,50. Les seuils appliqués correspondent aux textes courants. Le score porte sur les contrastes de texte de ces associations ; il ne constitue pas une évaluation ou certification globale de l’accessibilité du plugin ou du site. Les couleurs décoratives et bordures ne sont pas évaluées par ce compteur.

### Préservation et accessibilité de l’interface

Conserver la validation et le refus d’enregistrement des couleurs insuffisantes côté serveur, ainsi que le comportement actuel du bouton Enregistrer. Le nouveau score présente les résultats et ne modifie pas les autorisations ni la règle de validation. Les palettes enregistrées, noms/logos, brouillons, aperçu, code court et documents sont préservés.

Prévoir FR/EN pour tous les libellés, noms accessibles et messages ; affichage responsive jusqu’à 320 px, navigation clavier, détails accessibles et annonces adaptées aux lecteurs d’écran. Réutiliser les couleurs fonctionnelles FileWarden (succès/ambré), les icônes et les styles communs. La démonstration comporte des sélecteurs d’exemples AA/AAA/À améliorer : ces commandes de démonstration ne sont pas des réglages à ajouter au plugin.

### Critères de recette

1. Les palettes actuelles et les couleurs personnalisées produisent le nombre exact de contrastes conformes.
2. Les cas AA, AAA, insuffisant, égalité aux seuils, limite juste inférieure et couleurs invalides sont vérifiés côté serveur et client, sans divergence d’arrondi.
3. Les détails correspondent au score et aux couleurs réellement utilisées dans l’aperçu.
4. Les modifications restent des brouillons jusqu’à un enregistrement explicite ; le score n’assouplit aucun contrôle existant.
5. Les états sont compréhensibles en FR/EN, au clavier, sans dépendre uniquement de la couleur, sur ordinateur et mobile ; prévoir un repli utile sans JS.
6. Vérifier sur le ZIP installé la persistance des réglages et la non-régression des autres fonctions de Publication. Proportionner les tests au changement et consigner les résultats réels.

## Complément — nouveaux pictogrammes de types de fichiers

Le 22 novembre 2026, le propriétaire fournit quinze SVG : **CSV, DOCX, EPUB, HTML, JSON, MD, ODP, ODS, ODT, PPTX, SQL, SVG, XLSX, XML et YAML**. Originaux conservés sans transformation dans `docs/assets/v0.1.18-dev/file-types/`, avec intégration réalisée dans les assets runtime et comparaison des 31 originaux fournis.

- Employer le pictogramme dédié pour les extensions correspondantes, en conservant les autres pictogrammes existants sauf les deux gabarits explicitement retirés ci-dessous.
- **RTF, TIF, TIFF, EML et MSG** : cinq SVG supplémentaires fournis par le propriétaire et conservés à l’identique sous `rtf.svg`, `tif.svg`, `tiff.svg`, `eml.svg` et `msg.svg` dans le dossier de préparation. Employer le visuel correspondant à chaque extension dans toutes les interfaces ; TIF et TIFF disposent chacun de leur fichier dédié. Ces ajouts concernent la représentation des documents et conservent les règles existantes de formats autorisés et d’aperçu. RTF utilise désormais sa référence dédiée plutôt que l’ancien pictogramme de texte partagé.
- **Audio — dernière référence avec bandeau AUDIO** : utiliser `filewarden-audio-AUDIO.svg`, fourni par le propriétaire et conservé à l’identique sous `audio.svg` dans le dossier de préparation. Le bandeau générique **AUDIO** représente la famille Audio existante (MP3/WAV/OGG), sans changement des extensions associées ou des règles d’import. L’ancien visuel à bandeau MP3 (`filewarden-audio-note-style-canva.svg`) reste conservé sous `variants/audio-mp3.svg` comme réserve graphique ; aucune correspondance distincte MP3 n’est prévue à ce stade. Préserver la nouvelle référence AUDIO dans toutes les interfaces.
- **Mise à jour Vidéo** : remplacer l’ancien visuel « Vidéo » par `filewarden-video-pellicule.svg`, fourni par le propriétaire et conservé à l’identique sous `video.svg` dans le dossier de préparation. Utiliser ce pictogramme pour la famille Vidéo existante dans toutes les interfaces, sans changement des extensions associées ou des règles d’import.
- **Archives ZIP/RAR/7Z** : le propriétaire fournit `zip.svg`, `rar.svg` et `7z.svg`, conservés à l’identique dans le dossier de préparation. Utiliser chaque visuel pour son extension dans toutes les interfaces, en remplacement du pictogramme commun d’archive pour ces trois correspondances. Cette évolution concerne leur représentation et conserve les règles existantes d’acceptation, de contrôle et de traitement des archives, sans extraction automatique.
- **Gabarits retirés à la demande du propriétaire** : supprimer de la collection 0.1.18-dev les anciennes icônes génériques « Présentation » (`slides.svg`) et « Archive » (`archive.svg`). Les correspondances dédiées PPT/PPTX/ODP et ZIP/RAR/7Z les remplacent. Les usages ont été basculés et les deux assets retirés du runtime. Le repli pour les extensions inconnues reste `file.svg`.
- **Mise à jour PDF fournie et demandée pour cette version** : `filewarden-pdf-acrobat.svg`, conservé sans transformation dans le même dossier de préparation. Au développement, remplacer le visuel PDF actuel par cet original, avec une présentation cohérente dans Documents, la corbeille, les fiches et les vues publiques. Cette modification concerne le pictogramme et conserve les mécanismes d’aperçu et de téléchargement PDF.
- **PPT et PPTX distincts — correction explicite du propriétaire** : utiliser `filewarden-ppt-pictogramme-pptx.svg` **uniquement pour `.ppt`**, malgré son nom de fichier. Pour **`.pptx`**, utiliser le fichier dédié `pptx.svg` joint à cette correction et conservé à l’identique dans le dossier de préparation. La présentation doit afficher deux entrées séparées **PPT** et **PPTX** ; cette décision remplace l’association précédente « PPT / PPTX ». ODP garde son pictogramme dédié. Appliquer ces correspondances dans toutes les interfaces, sans changement des règles de formats autorisés.
- **Compléments JPG/JPEG/TXT/PNG fournis** : `jpg.svg`, `jpeg.svg`, `txt.svg` et `png.svg`, conservés dans le même dossier de préparation. **Dernière décision : les JPG et JPEG initiaux sont remplacés par les nouveaux SVG bleus** `filewarden-jpg-bleu.svg` et `filewarden-jpeg-bleu.svg`, copiés à l’identique sous les noms de destination `jpg.svg` et `jpeg.svg`. Ces deux versions bleues font désormais référence pour `.jpg` et `.jpeg` ; TXT et PNG restent inchangés. Le nouveau TXT devient la référence pour `.txt` ; la suppression locale préexistante de l’ancien `assets/icons/text.svg` ne dispense pas de vérifier les autres extensions encore associées à cette ancienne icône et leur repli. Conserver les autres pictogrammes et la règle des extensions inconnues.
- **Extension non reconnue : utiliser systématiquement l’icône générique existante `file.svg`.** Préserver également ce repli pour un document sans extension ; reconnaître les extensions sans distinction de casse.
- Utiliser le même choix d’icône dans Documents, la corbeille, les fiches et les affichages publics liste/grille.
- Préserver les règles d’import et de sécurité existantes : l’ajout d’une icône ne modifie pas la configuration des formats autorisés. Prévoir la présence des assets dans le ZIP et des vérifications des correspondances/replis au moment du développement.

## Sauvegarde chiffrée et migration

Sauvegarde chiffrée et migration : [approche entièrement validée par le propriétaire le 22 novembre 2026](proposition-sauvegarde-v0.1.18-dev.md). La démonstration graphique `filewarden-sauvegarde-migration-018.html` et sa disposition détaillée sont également validées. GO global reçu ; développement réalisé et recette locale en cours.

## Références

- [WCAG 2.2 — contraste minimum, niveau AA](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
- [WCAG 2.2 — contraste renforcé, niveau AAA](https://www.w3.org/WAI/WCAG22/Understanding/contrast-enhanced.html)
- Démonstration approuvée : `filewarden-score-contraste-aa-aaa.html`, dans les visualisations de cette conversation. Elle sert de référence visuelle, pas de code runtime à importer tel quel.

## État final du candidat

Sept axes implémentés et recette locale concluante. Les prescriptions au futur ci-dessus décrivent le cahier validé. Paramètres retenus, traitement atomique des sélections (50 maximum), limites et preuves : [notes](releases/v0.1.18-dev.md), [preuve structurée](validation/v0.1.18-dev-local.json) et [guide de sauvegarde](backup.md). Aucune publication GitHub.
