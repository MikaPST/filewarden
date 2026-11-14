# FileWarden 0.1.14-dev — corrections et réception

Les six retours de recette sont regroupés dans ce document. **Le propriétaire a donné son GO explicite au développement correctif le 14 novembre 2026. Les six corrections sont intégrées et réceptionnées ; le code a été poussé sur main sur son accord distinct et la CI GitHub est réussie. Tag, release et déploiement restent soumis à des accords distincts.** Les acquis du candidat initial sont conservés, ainsi que son ZIP et ses preuves historiques. Le périmètre, l’ordre des travaux et les critères convenus ci-dessous restent la référence ; les résultats du nouveau candidat sont consignés séparément en fin de document.

## Synthèse du périmètre

| N° | Correction à réaliser | Résultat attendu |
|---|---|---|
| 1 | Notifications invisibles et positionnement incorrect | Retour visible après les actions, cartes en superposition en haut à droite, non modales, sans déplacement de la page |
| 2 | Noms longs débordant dans Documents | Nom complet contenu dans sa colonne, retour à la ligne même sans espaces, colonnes voisines lisibles |
| 3 | Pictogramme central trop grand et trop simple | Proposition retenue par le GO global : dossier plein Navy/turquoise de 80 px, centré au-dessus du titre |
| 4 | Arborescence trop espacée verticalement | Même Hiérarchie guidée, légèrement plus compacte, noms et zones de clic préservés |
| 5 | Suppression de dossiers sans effet ni explication | Suppression autorisée réellement exécutée, fichiers récupérés et protégés, réussite/refus/échec expliqué |
| 6 | Présentation des catégories de Réglages | **Rail compact validé**, menu vertical et extensible, descriptif sous le titre à droite, bascule sans rechargement ni perte de saisie |

Les points 1 et 5 sont des anomalies fonctionnelles signalées par le propriétaire ; leur cause propre à son hébergement n’est pas établie. Le succès des tests sur le candidat initial ne les clôture pas. Le masquage des avis par une règle CSS WordPress générique et le refus de métadonnées transactionnelles sont reproduits sur des installations jetables ; ne pas les présenter comme les causes prouvées sur le site utilisateur. Le Rail compact et le dossier plein de 80 px sont retenus dans le GO global du lot correctif.

## Notifications — affichage et superposition

1. **Anomalie signalée :** le propriétaire ne voit pas les notifications après ses actions. Cause non déterminée et anomalie non encore reproduite sur son installation. Vérifier le chemin réel de retour de chaque action, le chargement et l’exécution du composant, ainsi que sa visibilité ; ne pas attribuer le problème au cache ou au ZIP sans preuve.
2. **Positionnement confirmé :** cartes superposées à l’interface principale, en haut à droite, sous la barre WordPress. Le candidat initial réservait de la place dans le flux de la page ; cette disposition doit être remplacée. L’apparition ne doit pas déplacer ni redimensionner le contenu principal.
3. **Comportement :** notifications flottantes non modales, sans voile de fond, blocage de l’interface ni vol du focus. Conserver les fermetures, quatre types, réglage de durée, barres, pauses combinées, messages persistants, empilement et surplus accessibles.
4. **Réception à compléter :** reproduire les actions concernées, contrôler la visibilité réelle après retour serveur et navigation, le positionnement en superposition, le défilement, les petits écrans et les éventuels dialogues FileWarden. Garder les contrôles de non-divulgation, de permissions et de non-régression.

## Documents — noms longs contenus dans leur colonne

- **Anomalie montrée :** dans le tableau central de Documents, le nom du fichier déborde de la colonne Document et recouvre les valeurs Taille et Règle du fichier. Le propriétaire parle de noms de dossiers ; la capture montre précisément un nom de document long contenant un identifiant et des tirets.
- **Résultat attendu :** garder le nom complet dans sa propre colonne, avec retour à la ligne et hauteur de ligne adaptée. Les suites longues sans espaces doivent également pouvoir se couper ; ne pas remplacer cette demande par une troncature ou un renommage.
- Préserver la place du pictogramme, l’alignement et la lisibilité des colonnes voisines, des règles, des boutons et des actions. Vérifier également le confinement des noms longs de dossiers dans l’arborescence.
- **Réception :** noms avec espaces, tirets, identifiants longs et suites sans séparateurs ; plusieurs niveaux de dossiers ; ordinateur, petite largeur et panneau d’action ouvert. Aucun chevauchement ni perte du nom complet ; liens et opérations conservés.

## Accueil Documents — pictogramme plus petit et plus travaillé

- **Demande du propriétaire :** le grand dossier central est trop imposant ; réduire sa taille et remplacer le simple contour épais par un dossier plus stylisé, éventuellement plein. Cette correction remplace la taille initiale de 160 px précédemment validée, sans remettre en cause l’accueil épuré.
- Conserver le pictogramme centré au-dessus d’« Ouvrez un dossier », la phrase d’aide validée et le bouton de création. La demande vise le pictogramme central de l’accueil, pas une refonte de tous les pictogrammes de l’arborescence ni du logo officiel FileWarden.
- **Proposition retenue dans le feu vert global :** dossier plein légèrement arrondi, Deep Navy avec discret accent turquoise, 80 px. Cette dimension est le choix d’implémentation de la proposition acceptée ; elle ne constituait pas une mesure imposée initialement par le propriétaire.
- **Réception :** équilibre visuel avec le titre et les espaces, netteté du pictogramme et proportions préservées sur ordinateur et mobile, charte respectée, aucune modification du fonctionnement de l’accueil ou des permissions.

## Arborescence — hauteur légèrement plus compacte

- **Design confirmé :** le propriétaire apprécie l’arborescence actuelle ; conserver la Hiérarchie guidée, ses repères, son indentation, ses pictogrammes et ses états de sélection.
- **Ajustement demandé :** réduire légèrement l’espace vertical entre les dossiers et les marges/paddings superflus, pour afficher davantage de dossiers à hauteur de panneau équivalente. Aucune hauteur chiffrée n’a été imposée.
- Garder des zones de clic confortables pour les liens et les commandes de dépliage distinctes, sans chevauchement des cibles ni régression clavier/tactile. Préserver le bouton de création et son espacement pictogramme/texte validé.
- Les noms sur plusieurs lignes doivent augmenter naturellement la hauteur de leur ligne ; ne pas forcer une hauteur fixe qui coupe le texte. Conserver des guides d’ascendance correctement alignés et les informations de Documents à classer.
- **Réception :** comparer l’encombrement vertical avec le candidat initial, vérifier les dossiers imbriqués, noms courts/longs, sélection, dépliage, focus et affichage mobile. Réduction modérée de l’espacement, sans refonte graphique.

## Suppression de dossiers — opération sans effet ni retour visible

- **Anomalie signalée :** le propriétaire indique que les dossiers ne sont pas supprimés, qu’ils soient vides ou contiennent des fichiers. Aucune notification visible n’explique le résultat ou le refus. Cause non encore identifiée ; ne pas conclure que seul l’affichage du message est en cause.
- **Correction attendue :** vérifier séparément le parcours de confirmation/envoi, le traitement serveur et le retour d’opération. La suppression autorisée d’un dossier feuille vide ou contenant des documents doit réellement aboutir, puis être confirmée clairement. Si elle est refusée ou échoue, afficher une explication utile et sûre, sans faux succès ni détails techniques confidentiels.
- **Règles préservées :** les documents d’un dossier supprimé restent récupérés dans Documents à classer, avec leurs octets, UUID et restrictions héritées conservés. Les fichiers déjà en corbeille gardent leur état et leur origine. Ne pas remplacer ce comportement par une destruction de fichiers pour faire disparaître le dossier.
- Les permissions, la confirmation, le nonce, les verrouillages et la transaction restent obligatoires. Conserver le blocage des dossiers ayant des sous-dossiers et de la collection système ; rendre ces refus visibles et compréhensibles. Vérifier l’exigence de tables transactionnelles, sans présenter cette cause comme établie sur l’installation du propriétaire.
- **Réception :** dossier feuille vide ; dossier feuille avec un ou plusieurs fichiers ; documents déjà en corbeille ; dossier avec sous-dossiers ; droits insuffisants ; nonce invalide ; échec de transaction/récupération. Vérifier en base et dans l’interface le résultat réel, le compteur de récupération, l’intégrité des documents et de leurs droits, ainsi que la notification de réussite ou d’échec. Échec : pas de suppression partielle ni de succès visuel.

## Réglages — navigation verticale modernisée et extensible

- **Demande confirmée :** conserver un menu vertical de catégories à gauche et un panneau à droite, avec une présentation plus soignée conforme à FileWarden. Le changement de catégorie doit afficher immédiatement les réglages correspondants, sans rechargement complet de la page.
- Préserver les valeurs saisies mais pas encore enregistrées lorsqu’on passe d’une catégorie à l’autre. Garder une catégorie active clairement repérable, des noms lisibles et la navigation clavier. Les réglages enregistrés, notamment une durée de notification déjà personnalisée, ne doivent pas être réinitialisés par la refonte.
- Prévoir l’ajout de futures catégories à partir d’une définition commune des catégories/panneaux, sans refaire tout le menu ou dupliquer un traitement propre à chaque nouvel élément. Pour ce lot, conserver les cinq catégories existantes : Général, Stockage et sécurité, Documents et limites, Conservation, Journal d’audit. Les diagnostics de santé existants restent présents.
- **Choix validé par le propriétaire : « Rail compact »**, deuxième proposition. Menu vertical léger avec pictogrammes et libellés, catégorie active sur fond Deep Navy avec texte et pictogramme blancs. Le descriptif reste visible **sous le titre de la catégorie dans le panneau de droite**, comme dans la maquette ; ne pas le supprimer ni le déplacer sous chaque entrée du menu. Le propriétaire retient explicitement cette présentation plutôt que Navigation guidée.
- Un aperçu interactif démontre les deux présentations, les changements de catégories, la conservation des champs et les commandes clavier. Il ne modifie aucun réglage WordPress ; ses valeurs d’exemple ne remplacent ni les valeurs du site ni le défaut validé de 10 secondes.
- **Réception de la maquette :** contrôles des panneaux, valeurs saisies conservées, touches Haut/Bas/Début/Fin, absence de navigation réseau et absence de débordement à 320/736/1024 px ; captures inspectées. Cela ne constitue pas une réception de l’implémentation dans WordPress.
- **Réception future du correctif :** même comportement dans le vrai écran de réglages, sans navigation complète au clic sur une catégorie ; valeurs et sélection cohérentes, catégories extensibles, responsive conservant une navigation verticale, sauvegarde autorisée et vérifiée côté serveur, nonces/capacités et autres réglages préservés.

## Ordre de développement convenu

| Lot | Travail | Fichiers principalement concernés | Preuve de fin de lot |
|---|---|---|---|
| A — Diagnostic et corrections fonctionnelles | Reproduire l’absence de notifications et l’échec de suppression, suivre chaque action jusqu’au résultat serveur, corriger les causes établies ; mettre les notifications en superposition | `includes/AdminController.php`, `includes/AdminWorkspace.php`, `includes/Notice.php`, `includes/FolderLifecycle.php`, `includes/Repository.php`, `assets/notices.js`, `assets/admin.css` ; contrôleurs/appels de notification concernés après inventaire | Actions réelles de réussite et d’échec testées, état serveur vérifié, message visible et exact ; dossiers feuilles supprimés sans perte de documents |
| B — Documents et arborescence | Contenir les noms longs, réduire légèrement les espacements, remplacer et réduire le pictogramme central selon la proposition retenue | `assets/admin.css`, `includes/AdminWorkspace.php`, pictogramme dans `assets/icons/` si nécessaire | Captures avant/après et mesures de débordement, noms complets, navigation et cibles accessibles préservées |
| C — Réglages Rail compact | Intégrer la présentation validée, centraliser les métadonnées des catégories, conserver la bascule locale et les valeurs de formulaire | `includes/SettingsConsole.php`, `assets/settings.js`, `assets/admin.css`, traductions FR/EN | Clics et clavier sans rechargement du document, descriptif correct, valeurs conservées puis sauvegardées avec autorisation |
| D — Réception et nouveau candidat | Consolider les tests, vérifier le ZIP réellement installé, compléter le suivi et les notes de correction | Tests existants et cas ciblés, `tools/build.php` si l’empaquetage l’exige, documentation du lot | Nouveau ZIP identifié par SHA-256, matrice des six points renseignée et limites explicites, présentation au propriétaire avant tout push |

Ces fichiers étaient les points d’entrée identifiés à la lecture du candidat initial. Le diagnostic a limité les modifications : le service de suppression/récupération et les règles d’accès ne sont pas réécrits. Les fichiers effectivement modifiés sont indiqués dans la matrice de réception.

### Diagnostic avant correction des anomalies

- Distinguer ce qui est affiché, ce qui est envoyé, ce que le serveur accepte/refuse, ce qui est effectivement enregistré et le retour reçu par le navigateur. Reproduire la suppression vide et avec documents séparément, et vérifier les avis sur plusieurs opérations et pages.
- Vérifier la présence et la version des fichiers JavaScript/CSS réellement chargés, l’initialisation du composant, les erreurs JavaScript, les données de retour, la visibilité et les déplacements d’avis par WordPress ou d’autres extensions. Ne pas attribuer une cause au cache ou à une extension sans preuve.
- Pour la suppression, contrôler la confirmation, la soumission, le dossier cible, les droits, le nonce, les sous-dossiers, les verrous et l’exigence de tables transactionnelles. Une cause doit être identifiée avant de choisir son correctif ; ne supprimer aucune protection pour obtenir artificiellement un succès.
- Les notifications superposées doivent disposer d’un placement indépendant du flux et tenir compte de la barre WordPress, du défilement, des menus et des dialogues. Ne pas introduire de voile, de vol de focus, de chevauchement entre cartes ou de déplacement des réglages.
- Si l’échec propre au site reste impossible à reproduire localement, documenter précisément ce qui manque pour le diagnostic et demander uniquement les informations nécessaires. Ne pas déclarer le problème résolu sur la seule présence du code ou sur le passage des anciens tests. Aucun test destructif sur les documents du site utilisateur.

## Plan de tests après implémentation

Les vérifications suivantes composent la recette convenue. Ce tableau définit les essais ; les résultats réellement acquis et les réserves figurent dans la réception ci-dessous, distincte de celle du candidat initial.

| Domaine | Essais requis |
|---|---|
| Notifications réelles | Création/ajout/import, déplacement/copie, droits, corbeille/restauration, suppression de dossier, réglages, publication et audit ; succès confirmé et refus/échec explicite. Tester les parcours avec navigation/retour serveur et ceux asynchrones. Pas de succès forgé via l’URL ni de données confidentielles dans le message. |
| Superposition et temps | Quatre types ; position en haut à droite après retour, défilement et redimensionnement ; page et menu Réglages non redimensionnés. Durées 5/10/15/20/30 s, défaut 10 uniquement si aucune préférence valide ; barre 100/50/0 %, pauses combinées, reprise, messages persistants sans décompte, fermeture et surplus accessibles. Avis des autres extensions préservés. |
| Suppression et conservation | Feuille vide, un fichier, plusieurs fichiers et document déjà en corbeille ; vraie suppression en base et dans l’arbre, compteur de récupération, octets/UUID/règles conservés. Sous-dossiers et collection système protégés. Accès refusé, nonce invalide, échec de transaction et rollback sans effet partiel ni faux succès. Vérifier le journal. |
| Libellés et densité | Noms courts/longs, espaces/tirets/UUID et suites sans séparateurs ; plusieurs profondeurs, panneau d’action ouvert et fermé, zoom et petites largeurs. Aucun chevauchement, troncature ou zone de clic superposée. Arbre moins haut à contenu comparable ; hauteur adaptée aux noms multilignes. |
| Accueil | Pictogramme choisi plus petit et net, proportions et centrage ; titre, phrase exacte, bouton et cas sans dossier ordinaire conservés. Ne modifier ni le logo officiel ni les autres icônes sans nécessité liée à la demande. |
| Réglages | Rail compact, pictogrammes/libellés, sélection Navy et descriptif à droite. Clic et touches Haut/Bas/Début/Fin ; document inchangé lors de la bascule, champs modifiés retrouvés au retour. Sauvegarde réelle, conservation d’une durée personnalisée de 5 s et des autres catégories, contrôle des capacités et nonces, validation des champs masqués. Ajout d’une catégorie de test pour vérifier le mécanisme extensible sans livrer de réglage fictif. |
| Accessibilité et langues | FR/EN, focus, annonces, clavier, contrastes, réduction des animations ; largeurs 320/375/768/1280 px et contrôle visuel. Compléter les contrôles automatiques par une recette humaine au lecteur d’écran avant toute déclaration de conformité complète. |
| Non-régression et paquet | Tests PHP/JS, syntaxe, PHPCS, audit des dépendances et intégration/HTTP existants ; parcours des dix évolutions originales. Installations jetables neuves, WordPress 6.8 et version de référence récente disponible ; mise à jour depuis 0.1.13-dev et remplacement du candidat initial 0.1.14-dev, avec données et réglages conservés. Vérifier les actifs réellement servis malgré le même numéro de version de développement. |

Réutiliser les suites et helpers existants, notamment `tests/notifications-browser.cjs`, `tests/uiux-browser.cjs`, `tests/notification-integration.php`, `tests/ten-evolutions-browser.cjs`, `tests/ten-evolutions-integration.php`, `tests/french-browser.cjs`, `tests/upgrade-fixture.php` et les suites HTTP/ACL. Ajouter des assertions portant sur le résultat observable ou l’état serveur, pas seulement sur une classe CSS ou un texte présent dans le code. Les tests de suppression restent limités à des données synthétiques jetables.

## Préservation des acquis et livraison

- Garder les documents, UUID, règles héritées, stockage séparé, récupération, corbeille, publication, prévisualisations, formats validés et vrais téléchargements d’audit opérationnels. Aucune migration de schéma ni réinitialisation d’options prévue par ce lot.
- Conserver le ZIP initial et ses preuves historiques avant de préparer un autre candidat. Produire un nouveau ZIP corrigé de **0.1.14-dev**, inspecter sa liste de fichiers et comparer les octets distribués aux sources et à l’installation de test ; enregistrer son empreinte distincte et les captures associées.
- Pour chacun des six points, consigner le correctif effectif, les fichiers concernés, les tests réellement exécutés, leur résultat et toute réserve. Ne pas reprendre les résultats de l’ancien candidat comme preuves du nouveau.
- Ne présenter le correctif comme prêt que lorsque les anomalies bloquantes sont résolues et les vérifications applicables réussies. Si un contrôle n’est pas exécutable ou un problème reste non reproduit/non résolu, le signaler explicitement.
- Présenter le candidat au propriétaire avant tout push. La CI GitHub complète n’est pas exécutée par cette préparation ; elle nécessite le push autorisé. Publication, tag et déploiement utilisateur restent soumis à des accords explicites distincts.

## État de la réception corrective

GO explicite reçu ; corrections intégrées et réception locale terminée le 14 novembre 2026. Les preuves détaillées sont dans [le journal correctif](validation/v0.1.14-dev-corrections-local.txt). Les preuves du candidat initial restent inchangées et distinctes. **Candidat prêt pour la recette du propriétaire ; push du code puis CI réussis sur son autorisation. Aucune validation de son hébergement n’est revendiquée.**

### Matrice des six corrections

| N° | Implémentation effective | Preuve de fonctionnement du nouveau ZIP | État |
|---|---|---|---|
| 1 | Notice.php, notices.js, admin.css et dépendances AdminController : classes propres, région fixe hors du workspace, position sous la barre WP et dans la couche du dialogue ouvert | Retours serveur réels ; carte visible malgré CSS masquant les avis WP ; panneau non déplacé, focus non volé, défilement et hit-test ; dialogue natif ; durées, pauses, quatre types, pile, erreurs persistantes et secours sans JS | Validé localement sur les quatre couples WP/navigateur ; compatibilité du site à confirmer |
| 2 | admin.css : min-width:0, largeur contenue et overflow-wrap:anywhere sur le lien et les libellés de dossiers | Fichier de plus de 200 caractères incluant suite sans séparateurs et UUID : nom intégral, bornes du lien dans sa cellule à 1280/768/375 px, opérations et téléchargement identiques ; libellé du dossier contenu dans l’arbre | Validé localement |
| 3 | Nouveau assets/icons/folder-welcome.svg, accueil 80 × 80 px, dessin plein Navy/Slate/turquoise | Taille réellement calculée, SVG chargé, titre/phrase exacte FR/EN conservés, accueil sans second arbre ; captures ordinateur/mobile inspectées | Validé localement |
| 4 | admin.css : marges des nœuds 1 px, padding vertical 7 px, ligne minimale 40 px / 44 px sur pointeur tactile | Ligne simple mesurée à 40 px, noms multilignes libres, sélection Navy/blanc, dépliage distinct sans navigation, clavier, bouton de création avec 10 px d’écart ; captures | Validé localement |
| 5 | Repository::require_transactions : métadonnées alternatives exactes des trois tables si information_schema échoue ; AdminWorkspace : retours explicites et persistants en cas de refus | Confirmations réelles : feuille vide normale et métadonnées restreintes supprimées ; feuille avec fichier supprimée et compteur +1 ; octets conservés en récupération, ancienne URL non publiée. Transactions ou métadonnées invérifiables et écriture refusée : message visible, dossier et fichier conservés. Suite existante : rollback au second fichier, ACL héritées et corbeille conservées | Validé localement ; la cause exacte de l’échec sur le site utilisateur reste non établie |
| 6 | SettingsConsole::sections, settings.js, admin.css et quatre pictogrammes : rail vertical, descriptif sous le titre à droite, catégories centralisées | Clics sans nouveau document, saisies conservées, sauvegarde/rechargement 5 s et limite 29 MB, retour aux paramètres de recette ; catégorie supplémentaire de test affichée puis retirée ; clavier, sélection Navy/blanc et mobile vertical | Validé localement |

### Diagnostic et limites du correctif de suppression

Le propriétaire confirme que le dossier reste présent après le bouton de confirmation finale. Sur le candidat initial conservé, une création puis suppression réelles de feuille vide ont réussi. Une règle CSS générique masquant les avis WordPress masque aussi son ancien retour : ce cas est reproduit puis corrigé par l’isolation des classes et de la couche flottante. **Cela ne prouve pas que cette règle explique le site du propriétaire.**

Les essais supplémentaires simulent le refus des métadonnées information_schema : le nouveau contrôle vérifie alors les noms et moteurs des trois tables via SHOW TABLE STATUS. Il n’autorise l’opération que si les trois tables attendues sont bien transactionnelles. Tables non transactionnelles, métadonnées toujours indisponibles, échec de début de transaction ou de récupération restent refusés sans effet partiel. Aucune conversion de tables ni modification de droits n’a été introduite. Le code result exact du site a été demandé, sans réponse complémentaire à ce stade ; le diagnostic propre à l’hébergement demeure à confirmer lors de sa recette. Ne pas déclarer cette anomalie distante définitivement résolue sur la seule réception locale.

### Vérifications exécutées

- PHP 8.2.35 : **75 tests unitaires, 130 assertions**, huit tests JavaScript ; syntaxe de 51 fichiers PHP et 20 fichiers JS ; PHPCS et contrôle du diff réussis.
- Composer : manifeste/lock validés et audit réussi, sans avis de vulnérabilité connu au moment du contrôle. Secours Sodium de WordPress : limites de blocs, aller-retour et altérations vérifiés.
- **WordPress 6.8 et 7.1.4 / MariaDB 10.11.11** : bases et documents synthétiques neufs, préfixe non standard. Vrai ZIP publié 0.1.13-dev → ZIP initial 0.1.14-dev → ZIP corrigé du même numéro, conservation des octets, UUID, champs, dossiers, réglages et historique ; migration répétée sans perte.
- Intégration/HTTP : dix évolutions originales, nouveaux formats contrôlés dont ZIP non extrait, import, autorisations et révocation immédiate, lecture/preview/téléchargement, ranges, CSRF, corbeille/restauration/purge, compteur, publication et exports CSV/TXT/JSONL avec acteurs WordPress. Les refus de métadonnées et pannes de récupération sont injectés uniquement dans les installations jetables ; **aucun crochet de panne n’est distribué**.
- Chromium et Firefox sur les deux WordPress : parcours complets, horloge des notifications, focus/clavier, mobile, réduction des animations, réussite et refus effectifs. Réception française dans les deux navigateurs sur chaque WordPress. Captures finales françaises stabilisées après animation, avec vrai enregistrement serveur des réglages et test de carte au premier plan.
- Les URL des fichiers CSS/JS servis sont vérifiées contre le SHA-256 de leurs octets : remplacement du même numéro de développement sans réutilisation d’une ancienne URL d’actif.
- La réception locale ne couvrait pas PHP 8.3/8.4 ni MySQL 8.0 ; ces points ont ensuite été vérifiés par la CI ci-dessous. **Restent non exécutées :** recette humaine complète au lecteur d’écran et recette de l’hébergement utilisateur. Le journal local historique demeure inchangé.

### Archive à présenter avant push

- ZIP : [filewarden-0.1.14-dev.zip](../build/filewarden-0.1.14-dev.zip), **279 fichiers**, 2 747 761 octets.
- SHA-256 : **a89e68643c064308413464198285a4c71937d36c95b0c4ccf6efd1c94993961e** ; [empreinte](../build/filewarden-0.1.14-dev.zip.sha256), [manifeste par fichier](../build/filewarden-0.1.14-dev.manifest.json).
- Les 279 fichiers ont été comparés octet par octet aux sources et aux deux installations finales. Version 0.1.14-dev et schéma 6 vérifiés. Pas de .tools, vendor, tests, MU fixture, secrets ou configuration d’hébergement dans le ZIP.
- Comparaison au candidat initial : 12 fichiers runtime modifiés, cinq pictogrammes ajoutés ; les autres fichiers distribués sont identiques. Ancien ZIP conservé avec son SHA-256 b5ebe433bd93b5f0cc0bfacbee18a3b4e446eb09db8ea5d5f9f9075334ec79d5 et ses preuves historiques.
- [Notes de version préparées](releases/v0.1.14-dev.md), [accueil français](../build/review-v0.1.14-dev/accueil-fr-chromium.png), [réglages et notification française](../build/review-v0.1.14-dev/reglages-notification-fr-chromium.png), [mobile](../build/review-v0.1.14-dev/reglages-notification-mobile-fr-firefox.png). Les captures de toutes les suites sont conservées dans les dossiers de recette locaux.
- Base Git 0ca87e3 ; **code poussé sur main au commit 58f6cfe le 14 novembre 2026**, sur l’accord reçu après présentation du candidat. CI réussie ; suivi documentaire publié ensuite sans modifier les fichiers runtime. Aucun tag, release ou déploiement utilisateur autorisé. Préversion de développement uniquement.

### Push GitHub et validation distante — 14 novembre 2026

- Commit fonctionnel : [58f6cfee0963270897bea3ce7ef0fbba0b97478d](https://github.com/MikaPST/filewarden/commit/58f6cfee0963270897bea3ce7ef0fbba0b97478d), branche main. Arbre de travail propre après le commit et push en avance rapide, sans tag.
- [CI 43616653528](https://github.com/MikaPST/filewarden/actions/runs/43616653528) : **terminée avec succès, dix jobs réussis**. [Preuve structurée](validation/v0.1.14-dev-ci.json).
- PHP 8.2, 8.3 et 8.4 : syntaxe, PHPCS, **75 tests/130 assertions par version**, audit Composer et construction du ZIP réussis ; huit tests JavaScript réussis.
- MySQL 8.0 : six mises à jour réelles, depuis 0.1.11-dev, 0.1.12-dev et 0.1.13-dev, vers WordPress 6.8 et latest (7.1.4 dans les journaux). Intégration, HTTP, six corrections, dix évolutions originales et Chromium/Firefox FR/EN réussis sur chaque parcours.
- [Artefact filewarden-tested-candidate](https://github.com/MikaPST/filewarden/actions/runs/43616653528/artifacts/14122811563) : ZIP réellement installé et testé par le job WordPress 6.8 depuis 0.1.13-dev. Il est construit par la CI Linux ; son archive Actions et son empreinte sont distinctes du ZIP local Windows ci-dessus. Ne pas confondre le digest du conteneur Actions avec celui du ZIP de plugin qu’il contient.
- Code et documentation sont sur GitHub ; **aucune release, aucun tag ni déploiement utilisateur effectué**. Les limites de recette de l’hébergement et du lecteur d’écran restent applicables.
