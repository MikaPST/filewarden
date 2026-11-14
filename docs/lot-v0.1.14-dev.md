# FileWarden 0.1.14-dev — réception locale

Développement autorisé le 14 novembre 2026 pour les trois améliorations du [lot UI/UX validé](prochain-lot-uiux.md). **Candidat initial à corriger après recette utilisateur ; publication et push GitHub non autorisés.** Schéma 6 conservé ; aucune migration de documents ni de droits. Base Git locale : `0ca87e3`, modifications du lot non poussées.

Retour du propriétaire après les essais locaux ci-dessous : notifications non visibles pendant ses actions, cause non encore identifiée ; positionnement demandé en superposition à l’interface, et non dans une zone réservée. Voir [les corrections en cours de collecte](corrections-v0.1.14-dev.md). Les résultats suivants décrivent le candidat initial et ne constituent pas la validation de ces corrections.

## Contenu et conformité

| Amélioration | Implémentation | Réception |
|---|---|---|
| Accueil Documents essentiel | `includes/AdminWorkspace.php`, `assets/admin.css` : arbre uniquement latéral, dossier de 160 px centré au-dessus du titre, phrase FR exacte, action de création autorisée, titre adapté si aucun dossier ordinaire | `tests/uiux-browser.cjs`, `tests/french-browser.cjs` ; pas de faux statut de dossier sur l’accueil |
| Hiérarchie guidée | Même vue : guides d’ascendance, sélection Navy/texte et icône blancs, ancêtres mis en valeur, commandes natives de dépliage séparées des liens ; bouton secondaire avec écart de 10 px et hauteur minimale de 44 px | Navigation et dépliage clavier/souris, sélection, dimensions, captures et petit écran |
| Notifications communes | `includes/Notice.php`, `assets/notices.js`, `assets/admin.css` : quatre types, titres, descriptions, pictogrammes, fermeture, pile de trois cartes et surplus conservé | Tous les types temporaires, messages persistants, temps indépendants, surplus, focus et texte sûr |
| Durée et sauvegarde | `includes/SettingsConsole.php`, `includes/AdminController.php` : Général/Notifications, cinq choix stricts 5/10/15/20/30 s, défaut 10 s ; option `filewarden_notification_seconds` | Durées enregistrées/rechargées, sauvegardes partielles, valeurs forgées, nonce et capacités |
| Temps restant | Horloge monotone commune à la barre et à l’expiration ; pauses combinées survol/focus/onglet masqué/fenêtre inactive ; cartes masquées en pause | Horloge navigateur contrôlée : 100/50/0 %, reprise sans remise à zéro, expiration et réduction des animations |

Les cartes disposent d’une zone réservée en haut à droite. Le contenu s’organise à côté sur grand écran et sous les cartes sur petit écran, pour garder les commandes accessibles. Les avertissements nécessitant une intervention et les erreurs sont persistants par défaut ; l’API permet explicitement des messages temporaires de chacun des quatre types. Une notification persistante ne montre aucune barre de temps.

## Inventaire des retours raccordés

- **Documents/dossiers** : création, ajout et import, déplacement/copie, droits, suppression de dossier, corbeille/restauration et suppression définitive ; refus d’accès, indisponibilité et stockage bloqué passent par `Notice`.
- **Téléversements multiples** : chaque réponse confirmée utilise le composant commun ; les états par fichier et la progression d’envoi restent associés à leur fichier. Aucun succès à la seule fin du transfert.
- **Réglages** : enregistrement confirmé, erreurs et avertissements de configuration ; les informations permanentes de diagnostic restent lisibles dans leur contexte.
- **Audit** : purge confirmée, indisponibilité, export en préparation puis fichier reçu/téléchargement demandé, ou erreur persistante. Les téléchargements CSV/TXT/JSONL restent de vrais fichiers.
- **Publication** : confirmation réelle de copie, erreur de copie et échec du prévisualiseur. Les états locaux de prévisualisation restent également associés au contrôle concerné.
- **Démarrage impossible** : avis serveur sûr et persistant. Les pages natives de refus WordPress pour nonce/capacité restent gérées par WordPress ; leurs protections ne sont pas contournées.

Seuls les avis possédant la classe FileWarden sont transformés. Les avis WordPress et d’autres extensions restent intacts. Sans JavaScript, le serveur conserve un titre, un message échappé et un rôle accessible persistant.

## Sécurité et non-régression

- Contrôles d’ACL, capacités, nonces d’opération, verrouillages et stockage conservés. La nouvelle preuve de retour est liée au résultat, à l’opération, au dossier, au document et à l’utilisateur WordPress ; elle ne constitue jamais une autorisation. Une URL `result=success` forgée ne produit pas de succès visuel.
- Messages échappés et données asynchrones insérées comme texte ; aucun détail d’exception technique ou chemin physique affiché. Les noms de documents renvoyés par le serveur sont encore réautorisés.
- L’option nouvelle ne réinitialise pas les autres réglages et ne dépend pas d’une migration de schéma. Le test de mise à jour conserve aussi une préférence de notification déjà enregistrée.
- Les lecteurs d’écran reçoivent des statuts/alertes adaptés, sans annonce de chaque image de la barre. La fermeture d’un contrôle actif restitue le focus ; l’apparition ne le vole pas. La recette humaine complète au lecteur d’écran reste distincte des contrôles automatiques.

## Vérifications

La réception finale a réussi sur des installations **jetables**, WordPress 6.8 et 7.1.4, PHP 8.2.35, MariaDB 10.11.11, Playwright 1.65.0, Chromium et Firefox, en anglais puis en français. Aucun test ne vise le site utilisateur. [Sorties des deux réceptions finales et identité du ZIP](validation/v0.1.14-dev-local.txt).

Vérifiés : 75 tests PHP/130 assertions ; huit tests JavaScript ; syntaxe PHP/JS ; PHPCS ; manifeste Composer et audit sans avis de vulnérabilité connu ; secours Sodium WordPress. Les tests d’intégration, HTTP et navigateurs ont réussi sur le ZIP effectivement installé.

| Contrôle final | WordPress 6.8 | WordPress 7.1.4 |
|---|---|---|
| Mise à jour du vrai ZIP 0.1.13-dev vers 0.1.14-dev, puis migration répétée sans perte | Réussi | Réussi |
| Documents/octets/UUID/droits/dossiers/stockage/historique et options conservés | Réussi | Réussi |
| Intégration, ACL, capacités, nonces, sécurité des formats et retours protégés | Réussi | Réussi |
| HTTP : neuf formats supplémentaires, identité des fichiers, refus anonyme, aperçu et téléchargement protégés, héritage, révocation, exports | Réussi | Réussi |
| Chromium et Firefox : parcours existants, trois améliorations, réglages, temporisation et sécurité | Réussi | Réussi |
| Français : accueil exact, compteur, ajout, corbeille, réglages, notifications et journal | Réussi | Réussi |

Les tests de notifications contrôlent aussi les quatre types temporaires, les messages persistants, les temps indépendants, les pauses combinées, le surplus conservé, les refus tardifs remis au premier plan, le focus après fermeture, les avis d’autres extensions laissés intacts et le retour sans JavaScript. Une vérification serveur supplémentaire conserve un retour générique confirmé lorsque l’accès au document a changé, sans divulguer son nom.

Captures du ZIP final inspectées pour les deux WordPress, dans `build/screenshots/wp-6-8-final-zip/` et `build/screenshots/wp-7-1-4-final-zip/` : accueil, notifications avec barre à mi-parcours et erreur persistante, réglage lisible. Largeurs 320/375/768/1280 px contrôlées, absence de chevauchement avec les commandes de réglages. Contrastes mesurés sur les cartes finales succès/erreur de WordPress 7.1.4 : 5,87:1 et 6,57:1 pour les titres sur leur fond. Les messages des captures de temporisation sont des exemples synthétiques de réception ; les opérations réelles sont vérifiées par les parcours distincts.

Cas intermédiaires documentés : un rejeu sur une base déjà modifiée par les navigateurs a rencontré une collision de nom prévue ; les réceptions utilisent ensuite de nouvelles bases. Un test de refus tardif a exposé un message laissé dans le surplus ; le composant a été corrigé pour le remettre au premier plan, et ce cas fait désormais partie de la réception. Un contrôle Firefox d’export activait un lien avant la fin du chargement du document : le téléchargement natif réussissait mais le test attendait le retour JavaScript. L’attente de navigation du test a été corrigée ; le contrôle ciblé puis les deux réceptions finales complètes ont réussi. Aucun échec final laissé sans résolution.

La CI GitHub complète (notamment PHP 8.3/8.4 et MySQL 8.0) ne peut pas être annoncée exécutée pour ce candidat avant son push autorisé. Le workflow conserve les mises à jour depuis 0.1.11/0.1.12 et ajoute 0.1.13 ; l’artefact de release proviendra toujours du ZIP installé et testé par ce workflow.

## Archive et décision

Candidat : `build/filewarden-0.1.14-dev.zip`, **2 743 517 octets**, **274 fichiers distribués**. Chaque fichier a été comparé octet pour octet au code de travail et aux deux installations finales. Versions d’en-tête/constante 0.1.14-dev vérifiées, schéma 6 conservé, outils/tests/dépendances de développement exclus. Empreinte locale :

```text
b5ebe433bd93b5f0cc0bfacbee18a3b4e446eb09db8ea5d5f9f9075334ec79d5
```

Le fichier `.zip.sha256` et le manifeste SHA-256 par fichier sont disponibles dans `build/`. Le ZIP n’a pas été reconstruit après sa réception finale. Une reconstruction par la CI après push aura sa propre vérification et son propre artefact testé ; cette empreinte identifie le candidat **local** remis au propriétaire.

Les serveurs WordPress et MariaDB jetables ont été arrêtés après la recette ; ZIP, captures et preuves sont conservés. La validation locale ne vaut ni publication GitHub ni recette de l’hébergement utilisateur. Aucun tag, push, release ou déploiement utilisateur effectué ; attendre le nouvel accord explicite du propriétaire.
