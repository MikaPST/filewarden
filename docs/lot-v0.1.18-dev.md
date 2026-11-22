# FileWarden 0.1.18-dev — périmètre validé

Validation du propriétaire reçue le 22 novembre 2026 après la démonstration interactive. État : spécification validée pour la prochaine version, développement non démarré. Le lot peut être complété par les prochaines demandes avant son GO de développement. Aucun changement de version du code, d’archive ou de publication GitHub à ce stade.

## Gestion documentaire — démonstration demandée avant développement

Demande du 22 novembre : renommage direct des documents et dossiers en conservant liens et droits ; sélection multiple pour classer, déplacer ou restaurer ; tris et filtres par nom, date, taille et type ; recherche globale sur les seuls documents autorisés. La disposition et les interactions sont proposées dans une maquette, à valider avant de coder ce périmètre. Aucun résultat, nom de dossier, compteur ou extrait non autorisé ne devra être révélé par la recherche réelle. Chaque mutation devra conserver les contrôles serveur de capacité, d’accès, de nonce et d’audit ; les déplacements ne doivent jamais supprimer les restrictions conservées. Les comportements de conflits et d’échecs partiels restent à spécifier avec le lot final.

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

## Références

- [WCAG 2.2 — contraste minimum, niveau AA](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
- [WCAG 2.2 — contraste renforcé, niveau AAA](https://www.w3.org/WAI/WCAG22/Understanding/contrast-enhanced.html)
- Démonstration approuvée : `filewarden-score-contraste-aa-aaa.html`, dans les visualisations de cette conversation. Elle sert de référence visuelle, pas de code runtime à importer tel quel.
