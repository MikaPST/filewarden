# FileWarden 0.1.16-dev — lot autorisé

GO de développement reçu le 15 novembre 2026. Le propriétaire autorise également le push, le tag et la release avec ZIP **uniquement après réussite des tests**. Aucun déploiement sur son site n’est inclus. Ce document consolide les décisions de ce nouveau lot ; les dix évolutions originales restent un périmètre de non-régression distinct.

## Décisions validées

1. **En-tête** : logo officiel et nom FILEWARDEN sur une première ligne, même hauteur visuelle ; nom légèrement agrandi. Titre de page sur la seconde ligne, taille actuelle conservée. Proportions et couleurs du logo inchangées.
2. **Formats acceptés** : notice intégrée sous le choix de la source, repliée par défaut sur ordinateur et mobile. Formats sous forme d’étiquettes ; précisions ZIP/macros visibles même repliée. Ne pas ajouter la phrase reliant les deux sources. Limite effective de taille conservée.
3. **Copier/déplacer** : bouton Navy « Vérifier la destination » avec dossier/coché ; bouton secondaire « Classer plus tard » avec le pictogramme exact du dossier spécial, espacement correct et boutons pleine largeur sur deux lignes dans l’inspecteur. Un déplacement ne propose pas son emplacement actuel ; depuis Documents à classer, ni cette destination ni le raccourci ne sont proposés. Message explicite et vérification indisponible sans autre destination. Règles de copie inchangées.
4. **Aperçu/téléchargement** : variante Icônes affinées, fond discret, pictogrammes Navy, survol teinté turquoise, infobulles accessibles et cibles tactiles suffisantes.
5. **Boutons harmonisés** : Rechercher, Choisir dans la médiathèque et Ouvrir le journal d’audit en turquoise plein, texte/pictogrammes Navy. Loupe, bibliothèque et journal respectivement. Exports CSV/TXT/JSONL sur fond légèrement teinté, Navy, icône de téléchargement ; téléchargement direct inchangé. Recenser les autres boutons FileWarden qui héritent accidentellement du thème du profil, sans modifier WordPress ni imposer les couleurs du plugin aux publications du site. Couleurs des actions de droits et suppression conservées.
6. **Compteur de récupération** : pastille pleine ambre foncé, chiffre blanc, à côté du libellé. Pas de parenthèses ; aucune pastille à zéro. Comptage autorisé, hors corbeille, inchangé.
7. **États d’interaction** : retirer les cadres issus du profil au clic souris ; conserver un focus clavier distinct aux couleurs FileWarden. Interactions turquoise discrètes selon le rôle de l’action. Onglet actif : libellé renforcé et soulignement **Navy**, jamais turquoise. Catégorie de réglages sélectionnée : fond/bordure Navy et texte/icône blancs, sans contour du profil.
8. **Mode tous formats** : dans Stockage et sécurité, section Types de fichiers, case décochée par défaut. Avertissement et confirmation avant activation, puis enregistrement explicite. Rappel orange tant que le mode est actif. Masquer la notice Formats acceptés dans les deux sources. Autoriser les formats/contenus normalement refusés comme pièces jointes protégées, sans exécution ni nouvelle prévisualisation active. Garder droits, nonces, limites, clés opaques, protection du stockage, contrôles des chemins et intégrité. Journaliser activation/désactivation avec l’acteur WordPress. Aucun changement des règles de téléversement de la médiathèque WordPress et aucune publication automatique.
9. **État de la base** : une ligne Base de données sous Stockage dans les Détails de l’état FileWarden. Pas de grand bloc technique en situation conforme. Toute anomalie ou reprise incomplète reste signalée même lorsque les détails sont repliés ; diagnostic et réparation restent accessibles dans Stockage et sécurité.
10. **Langues** : réglage global dans Général au-dessus des notifications, pictogramme languages. Automatique (langue du **site** WordPress) par défaut, Français et English ; repli anglais pour langue non prise en charge ou traduction absente. Choix persistant après mise à jour, validé côté serveur et réservé aux gestionnaires des réglages. Application après enregistrement confirmé et rechargement. Tous les textes FileWarden PHP/JavaScript, administration/publication/messages traduits ; noms des ressources, données saisies, codes techniques du journal et schéma d’export inchangés. Les fenêtres natives WordPress suivent WordPress. Catalogues extensibles, pluriels, accessibilité et petites résolutions vérifiés.

## Organisation du développement et réception

- Consolider les décisions ; préserver le schéma 6, les politiques, identités, octets et options existants.
- Centraliser les nouvelles politiques d’import et de langue ; valider les opérations sensibles côté serveur, conserver les téléchargements en pièce jointe et les protections indépendantes du format.
- Implémenter les composants visuels partagés, les transferts et l’état de la base ; compléter les catalogues de traduction et la prise en charge JavaScript.
- Vérifier syntaxe, PHPCS, PHPUnit, JavaScript et audit des dépendances. Ajouter des refus pertinents (capacité, nonce, confirmation, valeurs invalides), des tests du mode permissif et de son retour au mode strict, sans exécution des fichiers d’essai.
- Installer le ZIP dans des WordPress jetables après mise à jour depuis les versions publiées. Tester HTTP/ACL, stockage, corbeille, réparation de base, publications, exports, langues FR/EN/automatique/repli, absence de mélange et de propagation aux autres domaines WordPress, profils de couleurs, clavier et mobile, Chromium/Firefox.
- Enregistrer les résultats réels, corriger tout échec ; CI complète puis tag/release conditionnels. Vérifier le ZIP public contre le tag, le checksum et les notes. Aucun résultat ne sera annoncé réussi avant preuve.

## État

Implémentation et recette locale complète réussies. Les deux WordPress 6.8/7.1.4 ont installé le ZIP final après mise à jour réelle depuis 0.1.15-dev, puis passé les suites métier, HTTP et Chromium/Firefox EN/FR. [Preuve locale](validation/v0.1.16-dev-local.json). CI et publication conditionnelles encore à réaliser à cette étape ; aucun résultat distant n’est présumé.

## Matrice de réception

| Point | Implémentation principale | Vérification concrète |
|---|---|---|
| 1 — En-tête | `AdminBrand.php`, `admin-controls.css` | Logo chargé depuis le ZIP, hauteur et alignement mesurés, titre en dessous ; les quatre pages d’administration. |
| 2 — Notice | `AdminController.php`, `admin-controls.css` | Repli initial ordinateur/320/375 px, 12 formats, dépliage, deux sources et masquage en mode tous formats. |
| 3 — Transferts | `AdminWorkspace.php` | Déplacement réel et revue des droits conservés ; destination actuelle exclue, raccourci absent depuis la récupération, absence de dossier vérifiée sur installation neuve. |
| 4 — Actions de ligne | `AdminWorkspace.php`, pictogrammes locaux, CSS | Aperçu PDF/image et téléchargement réels, libellés accessibles, comportements clavier et mobile dans les suites historiques. |
| 5 — Boutons | Contrôleurs, `AuditConsole.php`, CSS partagé | Couleurs calculées sous profils sunrise/ectoplasm, choix de médiathèque, recherche et téléchargement réel des trois exports. |
| 6 — Pastille | `AdminWorkspace.php`, `upload-queue.js` | Zéro absent et premier document visible sur installation neuve ; compte autorisé et corbeille exclus dans la non-régression. |
| 7 — Interaction | `admin-controls.css`, `settings.js` | Bordure et sélection Navy mesurées, absence d’ombre au clic, clavier/rail et navigation historique ; capture après stabilisation de la transition. |
| 8 — Tous formats | `ImportPolicy.php`, `DocumentService.php`, `SettingsConsole.php`, sources JS | Annuler/Échap/confirmer/enregistrer, refus nonce/capacité/valeurs ; HTML/SVG/PHP/DOCM/TXT actif/binaires en pièces jointes opaques, octets identiques, refus aperçu/anonyme, taille et retour strict. |
| 9 — Santé de base | `SettingsConsole.php` | Ordre des quatre lignes, absence du grand bloc conforme ; anomalies, réparation MyISAM réelle et contrôles de reprise historiques. |
| 10 — Langues | `Language.php`, catalogues, contrôleurs, `translation-catalogs.py` | FR/EN/automatique, site et profil discordants, site non pris en charge et variante française, isolation du cœur WP, publications et JS, 393 entrées et pluriels/paramètres contrôlés. |

Les scripts `v16-integration.php`, `v16-http.py` et `v16-browser.cjs` s’ajoutent aux suites historiques ; `fresh-install.php` exige une base jetable vide et refuse tout autre contexte. Les résultats locaux, CI puis de publication sont consignés séparément dans `docs/validation/` à leur achèvement. Une définition de workflow ne remplace pas une exécution réussie.

## Limites de réception

Les tests utilisent des documents synthétiques et des installations jetables. Aucune installation ni modification de base n’est effectuée sur le site du propriétaire. La recette sur son hébergeur, les logiciels externes utilisés pour ouvrir les pièces jointes et une revue humaine complète au lecteur d’écran ne sont pas couverts. Les boîtes natives WordPress conservent la langue WordPress. Le choix FileWarden est global au plugin ; ce lot ne crée pas un choix individuel par visiteur.
