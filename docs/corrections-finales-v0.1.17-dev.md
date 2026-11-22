# FileWarden 0.1.17-dev — dernières corrections de recette

GO reçu le 22 novembre 2026. Même version 0.1.17-dev et schéma 6 ; ZIP local testé pour installation manuelle en préproduction. Aucun push, tag ou release GitHub. Conserver fonctionnalités, autorisations, documents et réglages existants.

1. **Navigation Publication** : onglets Intégration du dossier / Apparence publique sous le titre Publication, sur le fond général au-dessus des cartes, comme Documents/Corbeille. Soulignement Navy, clavier, bascule locale, mêmes formulaires/aperçu et brouillons conservés.
2. **Options contextuelles** : dossier à publier toujours visible. Autorisé : Liste détaillée/Grille visible ; présentation d’espace inaccessible et connexion des visiteurs masquées. Accès refusé : inverse. Les choix masqués restent conservés dans le code court. Couleurs, nom et logo communs aux deux modes. Sans JavaScript, maintenir l’accès aux formulaires et aux choix.
3. **Palette de couleurs** remplace Modèle de départ. Aide exacte : « Choisissez une palette prédéfinie ou personnalisez vos couleurs, tout en conservant votre nom et votre logo personnalisés. » Quatre cartes et dix couleurs conservées.
4. **Code court** : carte compacte indépendante au-dessus de l’aperçu, à droite, visible dans les deux onglets. Titre et bouton Copier avec pictogrammes, champ lisible et confirmation. Aide exacte : Copiez ce code dans le bloc « Code court » de WordPress ou dans le widget « Code court » d’Elementor. Actualisation selon l’intégration. Mobile : réglages, Code court, Aperçu.
5. **Pastille (i)** : ambré #805400 sur fond #FFF6E8, comme Documents à classer. Position près du badge et infobulle conservées. Compléter la charte des couleurs fonctionnelles ; réutiliser les variables et vérifier les contrastes.
6. **FR/EN** : actualiser les textes et noms accessibles concernés dans les deux catalogues ; conserver le choix de langue du plugin et ses messages/pluriels existants.

## Validation

Unités/JS, syntaxe/PHPCS et catalogues ; intégration WordPress et sécurité existante ; navigateurs FR/EN, ordinateur/mobile/tactile, sans JS, modes/onglets sans perte des choix, code généré et copie réelle/refusée, placement, identité persistante et contraste ambré. Mise à jour depuis le dernier ZIP livré ; comparaison ZIP/sources/installations. Conserver le précédent ZIP et une preuve distincte.

## Résultat du 22 novembre 2026

Terminé et validé localement : 88 tests PHP / 192 assertions, huit tests JS, PHPCS, syntaxe (70 PHP / 30 JS), audit Composer et 472 entrées sources FR/EN. Intégration complète et tests HTTP sur WordPress 7.1.5 ; mise à jour réelle sur 6.8 vérifiant l’intégralité des lignes documents/dossiers/audit, les réglages, la langue, l’apparence et les octets protégés. Le bloc Code court natif WordPress est exercé sur les deux versions.

Chromium/Firefox FR/EN valident la navigation externe, les options contextuelles et leurs valeurs conservées, les brouillons, la palette et ses aides exactes, la carte Code court compacte au-dessus de l’aperçu, la copie réelle/refusée, l’ambré et son contraste, les largeurs 375/320 px et le repli sans JS. Le contrôle visuel a détecté puis supprimé une hauteur minimale héritée de 420 px ; assertions de compacité ajoutées. Les suites précédentes de correction ont aussi été exercées avant ce dernier ajustement de hauteur. La grande suite navigateur historique des releases antérieures n’a pas été intégralement répétée pour ce lot d’affichage/textes.

ZIP final : `build/filewarden-0.1.17-dev.zip`, **317 fichiers / 4 296 190 octets**, SHA-256 `d83119e346919b82e2071b833df3957cb9b66ae63ec09ad68e69df68c1e4b59a`. Identique à tous les fichiers sources empaquetés et aux deux installations finales. Les 46 assets d’icônes, logo et illustration de l’archive précédente restent identiques. Aucun rebuild après la validation finale.

Le précédent ZIP b5ff1e7e reste conservé dans `build/candidate-v0.1.17-dev-corrected-2026-11-21`. Aucun push, tag ni release GitHub. Services de test identifiés arrêtés ; captures et fixtures conservées sous `.tools/v17last-dev/` et la fixture réutilisée `.tools/v17fix-dev/v17fix68/`.

[Preuves, diagnostics et limites](validation/v0.1.17-dev-final-corrections-local.json). Recette locale Windows/PHP 8.2.35/MariaDB 10.11.11 ; autres PHP/MySQL/Linux/CI distante et hébergement utilisateur non validés dans ce lot. Installation manuelle et recette de préproduction à effectuer par le propriétaire.
