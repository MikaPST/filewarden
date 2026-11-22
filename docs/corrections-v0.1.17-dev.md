# FileWarden 0.1.17-dev — lot correctif validé

Ce premier lot correctif est complété par les [dernières corrections validées le 22 novembre](corrections-finales-v0.1.17-dev.md). Son archive b5ff reste une preuve historique ; le dernier ZIP livré et son empreinte figurent dans le document final.

GO du propriétaire reçu le 21 novembre 2026 après validation des propositions et de la maquette. Même version 0.1.17-dev, schéma 6 conservé. Livraison : ZIP local testé à installer manuellement en préproduction ; aucun push, tag ou release GitHub dans ce lot.

## Décisions implémentées

1. Dans Documents à classer, placer la pastille d’information des restrictions conservées à côté du badge Non publié, sur la même ligne. Explication sous la ligne ; pastille uniquement pour les fichiers concernés. Maintenir survol, focus, clic/toucher et fermeture Échap.
2. Publication : deux onglets au-dessus du panneau de configuration, comme Documents/Corbeille (icône, texte, soulignement Navy). Intégration du dossier active par défaut, Apparence publique en second. Bascule sans rechargement, aperçu commun à droite, conservation des choix et brouillons. Repli accessible sans JavaScript.
3. Modèles : quatre cartes compactes FileWarden/Neutre/Nature/Personnalisé, une ligne si la place le permet, deux par ligne sinon. Capsules de trois couleurs représentatives bordées finement et segments lisibles. Personnalisé reflète les couleurs en cours, devient actif lorsqu’une des dix couleurs change ; le sélectionner conserve les valeurs. Préserver les six réglages principaux, les quatre complémentaires, contrastes et enregistrement explicite.
4. Importer un logo : icône standard upload (flèche vers le haut et bac), en contour. Afficher le logo / Afficher le nom sur une même ligne, avec retour naturel à la ligne sur petit écran. Conservation des restrictions PNG/JPEG et capacités média.
5. Intégration du dossier : Connexion des visiteurs, réglage commun aux publications du site. WordPress par défaut, ou URL personnalisée absolue HTTP(S) validée. Choix persistant réservé aux réglages, nonce et audit existants. Bouton Ouvrir la page pour vérifier et aperçu du lien. Les codes courts existants en bénéficient sans réécriture. Mode WordPress : utiliser wp_login_url avec son filtre et le retour actuel. Mode personnalisé : respecter l’adresse enregistrée, sans ajouter automatiquement un paramètre de retour ; le retour dépend de la page cible. Aucun changement des ACL ni authentification implémentée par FileWarden.
6. Aperçu : titre à gauche, deux groupes compacts à droite. Autorisé (shield-check) / Accès refusé (lock-keyhole), libellés conservés et boutons accolés. Ordinateur (monitor) / Mobile (smartphone), icônes seules, noms accessibles et infobulles survol/focus/toucher. Sélection Navy/blanc. Groupes distincts, repli sur petits écrans, cibles tactiles confortables.
7. Frontend et aperçu : icônes de liste, grille, recherche, œil et téléchargement dans les boutons correspondants. Libellés conservés. Icônes fines cohérentes, couleur du texte configurable, pictogrammes de types de fichiers et leurs couleurs inchangés.
8. Grille : décision finale qui remplace le centrage vertical strict initial. Hauteur adaptée au contenu, marge régulière de 16 px, icône/nom/informations centrés horizontalement ; espacements compacts, actions centrées et alignées en bas d’une rangée. Aucun grand espace réservé au-dessus de l’icône. Noms longs lisibles et retours à la ligne. Ne pas imposer une hauteur fixe.
9. Sous-dossiers frontend : section Sous-dossiers au-dessus de la recherche, boutons ajustés au libellé et de largeur bornée, retour à la ligne, icône visible et accent discret. Aucun étirement d’un dossier unique sur toute la largeur. Navigation et filtrage des autorisations conservés.
10. Fil d’Ariane frontend : barre de chemin discrètement teintée, icône de dossier, séparateurs ›, dossier courant marqué et non cliquable, parents autorisés cliquables avec survol/focus. Le chemin reste limité au dossier publié ; pas de noms non autorisés, repli mobile.

## Validation convenue

Contrôles ciblés pendant chaque ensemble, puis une passe regroupée : syntaxe/PHPCS, unités et JS pour la logique, tests des URL/capacités/nonces et persistance, WordPress/HTTP pour les droits et les opérations documentaires. Navigateurs réels pour onglets et brouillons, dix couleurs, commandes d’aperçu, FR/EN, profils et mobile. Installer et tester le ZIP candidat exact ; ne pas reconstruire après validation. Préserver l’archive initiale et documenter résultats et limites réels. Pas de test unitaire artificiel pour une marge CSS.

## État

Les dix corrections sont implémentées et validées localement. Leurs parcours ciblés passent dans Chromium et Firefox, en FR/EN ; la non-régression regroupée des fonctionnalités existantes passe également. Le chargement des deux feuilles de style publiques identifie leur contenu pour éviter de conserver le CSS du premier ZIP portant la même version. Les cibles tactiles d’aperçu sont mesurées à 44 px minimum.

Livrable : `build/filewarden-0.1.17-dev.zip`, 317 fichiers, 4 294 924 octets ; SHA-256 `b5ff1e7e4f50ce37837038555541be07a054905ff2d4371444d592594a21df49`. Identique au code et aux trois installations finales. Le ZIP initial reste conservé séparément. [Preuve et limites](validation/v0.1.17-dev-corrections-local.json), [recette détaillée](testing.md). Aucun push, tag ou release GitHub.

| Correction | Implémentation | Preuves ciblées |
|---|---|---|
| 1. Pastille alignée | `AdminWorkspace.php`, `admin-controls.css` | Géométrie badge/pastille, fichier concerné uniquement, survol/focus/Échap ; règles conservées |
| 2. Onglets Publication | `Publisher.php`, `publisher.js` | Intégration par défaut, clavier, brouillons et même iframe conservés, repli sans JS |
| 3. Quatre modèles | `AppearanceConsole.php`, `FrontendAppearance.php`, `appearance.js` | Capsules bordées, dix contrôles conservés, Custom sans écrasement et couleurs reflétées, sauvegarde/contrastes |
| 4. Téléversement/visibilité | `AppearanceConsole.php`, `admin-controls.css`, Lucide upload | Pictogramme rendu, cases sur une ligne, restrictions et permissions des logos conservées |
| 5. Connexion des visiteurs | `PublicationLogin.php`, `AdminController.php`, `Publisher.php`, `Publication.php`, `publisher.js` | Défaut/filtres/retour WordPress, URL littérale, persistance, aperçu isolé, refus schémas/identifiants/CSRF/capacité, ACL inchangées |
| 6. Commandes d’aperçu | `Publisher.php`, `publisher.js`, `admin-controls.css` | Groupes accolés, icônes, noms accessibles, infobulle au focus, bascule réelle mobile/ordinateur |
| 7. Boutons publics | `Publication.php`, `publication.css`, icônes Lucide | Recherche/liste/grille/aperçu/téléchargement rendus ; vrais pictogrammes de fichiers préservés |
| 8. Grille compacte | `publication.css` | Marge haute 16 px mesurée, centrage horizontal, hauteur naturelle, actions, mise en page mobile |
| 9. Sous-dossiers | `Publication.php`, `publication.css` | Bouton borné sous 300 px pour la fixture courte, navigation réelle, permissions et racine publiées conservées |
| 10. Fil d’Ariane | `Publication.php`, `publication.css` | Barre/icône, courant non cliquable, navigation parent/enfant, limites de publication autorisées |

Tests maintenus : `tests/v17-corrections-browser.cjs`, `tests/v17-corrections-integration.php`, `tests/v17-corrections-http.py`, unités `PublicationLoginTest.php`/`FrontendAppearanceTest.php` et suites documentaires existantes. Les scénarios ciblés utilisent des WordPress locaux installés depuis le ZIP ; aucun site utilisateur n’est modifié.
