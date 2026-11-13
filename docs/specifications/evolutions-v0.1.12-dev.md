# FileWarden v0.1.12-dev — Cahier des évolutions validées

**Document destiné à Codex**  
**État : spécification fonctionnelle — aucun changement de code ni publication n’est autorisé par ce document seul.**  
**Version cible demandée : `0.1.12-dev`**

## Objectif

Décrire le périmètre fonctionnel, l’interface, la sécurité et les critères de validation des évolutions convenues pour FileWarden. L’implémentation devra préserver les comportements existants hors de ce périmètre.

Les décisions de la référence conversationnelle disponibles ici détaillent explicitement l’évolution 10. Pour les autres points, ce document reprend le lot validé de navigation et d’interfaces conservé dans `docs/lot-navigation-interfaces.md`, `docs/specifications/requirements.md`, `docs/security.md` et `SUIVI.md`. Le découpage ci-dessous en dix chantiers sert à rendre le travail exploitable par Codex ; il ne prétend pas reconstituer une numérotation historique absente des éléments disponibles. Si une règle ne figure pas dans ces sources, elle est marquée **non spécifiée** et ne doit pas être inventée.

## Règles de conduite pour Codex

1. Avant toute implémentation, lire `AGENTS.md`, `SUIVI.md`, `README.md`, `docs/specifications/requirements.md`, `docs/architecture.md`, `docs/permissions.md`, `docs/security.md`, `docs/roadmap.md`, `docs/branding.md`, ainsi que le code et tests concernés.
2. Vérifier l’état Git et les éventuelles modifications de l’utilisateur. Ne pas les écraser.
3. Conserver l’architecture, les interfaces publiques, les données, identifiants, politiques, liens permanents et intégrations existants sauf demande explicite dans ce cahier.
4. Ne pas transformer une proposition non confirmée en exigence. En cas de conflit ou de décision manquante qui affecte le comportement, demander un arbitrage avant de coder cette partie.
5. La validation du développement et l’autorisation de publication sont distinctes. Ce document ne permet pas de créer une release, un tag, de déployer ou d’installer sur le site utilisateur.

## Périmètre — dix chantiers

### 1. Navigation dans les dossiers

- Permettre la navigation descendante dans les sous-dossiers autorisés depuis une publication.
- Afficher un fil d’Ariane pour revenir dans le parcours publié. Ne jamais révéler les parents situés hors du dossier publié.
- Limiter la recherche frontend au dossier actuellement ouvert pour ce lot.
- La navigation, les listings, les aperçus et les téléchargements appliquent tous les mêmes autorisations côté serveur.
- Dans Documents, l’accueil présente les dossiers créés par l’utilisateur ; les dossiers de premier niveau restent créables via « Aucun dossier parent ».

**Acceptation :** un visiteur ne voit que les dossiers et documents autorisés. Un utilisateur autorisé peut parcourir les descendants du dossier publié puis revenir. Les liens directs ne contournent pas la politique d’accès.

### 2. Héritage et règles d’accès

- Les nouveaux sous-dossiers et fichiers suivent dynamiquement les droits de leur parent par défaut ; des restrictions additionnelles restent possibles.
- Les droits résultent de l’intersection des règles du document et de tous ses dossiers ancêtres. Un fichier « Public » ne rend pas public un dossier restreint.
- Pour un nouveau dossier de premier niveau, créer des droits explicites ; conserver la présélection « Utilisateurs connectés » lorsqu’elle existe dans le flux actuel.
- Ne pas convertir silencieusement les politiques existantes.
- Texte commun validé : « Les règles les plus restrictives s’appliquent : celles du fichier et de son dossier. Un fichier “Public” reste privé si son dossier est restreint. »
- Aide validée pour l’option : « Hériter : suivre automatiquement les droits du dossier. »

**Acceptation :** modifier un parent met à jour l’héritage dynamique ; aucun enfant ne contourne un ancêtre restreint. Les politiques historiques restent inchangées lors d’une migration.

### 3. Remplacement de Racine par « Documents à classer »

- Retirer « Racine » de l’usage courant. Les documents existants sans dossier restent conservés dans la collection système « Documents à classer », distincte visuellement et réservée à la gestion autorisée.
- Cette collection est permanente : elle n’est ni publiable, ni supprimable comme un dossier ordinaire, ni soumise à la rétention de la corbeille.
- Les anciennes publications de la racine et les accès frontend directs aux fichiers non classés sont bloqués jusqu’au reclassement. Les fichiers et identifiants sont conservés ; l’accès public antérieur ne l’est pas.
- Le reclassement doit préserver les règles propres aux fichiers et tenir compte de la restriction héritée pertinente. Présenter les droits résultants avant confirmation.
- Signaler clairement « Non publié » dans l’interface de gestion.

**Acceptation :** aucun fichier non classé n’est accessible par une ancienne publication ou URL frontend. Le reclassement ne rend pas involontairement public un document auparavant restreint.

### 4. Suppression d’un dossier

- Ajouter la suppression au menu d’actions d’un dossier ordinaire, avec confirmation.
- Si le dossier contient des fichiers, les récupérer dans « Documents à classer » sans effacer les fichiers ni leurs UUID. Les documents déjà dans la corbeille conservent leur état et leur rattachement de récupération.
- Bloquer la suppression tant que le dossier contient des sous-dossiers. Pas de suppression récursive implicite ; l’utilisateur traite explicitement les sous-dossiers d’abord.
- Préserver les restrictions héritées pertinentes pour éviter qu’un reclassement ultérieur ouvre l’accès.
- En cas d’échec, ne pas laisser de documents orphelins ou exposés. Cette opération ne purge pas le journal d’audit.

**Acceptation :** contenu et identités sont conservés, les sous-dossiers bloquent l’opération, les refus et échecs sont sûrs et explicites.

### 5. Présentation frontend en cas d’accès restreint

- Proposer trois présentations configurables par publication : **Compact**, **Carte centrée** et **Bandeau éditorial**.
- **Carte centrée** est le choix par défaut.
- Un visiteur anonyme est invité à se connecter via le mécanisme WordPress hors du cadre de publication, puis revient à la page d’origine.
- Un utilisateur connecté sans droit reçoit une information neutre, sans promesse d’accès ni nouveau bouton de connexion.
- Une adresse invalide ou un accès impossible ne doit pas révéler l’existence d’un dossier privé, son nom, son contenu ni le nombre de documents. Un incident technique reçoit un message distinct sans détail sensible.
- Le concepteur de publication peut prévisualiser l’état inaccessible sans modifier les droits.

**Acceptation :** les trois variantes sont accessibles et lisibles ; aucun texte, état ou compteur ne divulgue des métadonnées privées. Le rendu reste responsive.

### 6. Interface Publication

- Harmoniser son style avec l’écran Documents.
- Proposer la sélection du dossier, avec sélecteur Liste/Grille en boutons joints, icônes et libellés accessibles.
- Actualiser ensemble le code court et l’aperçu lorsque les paramètres changent.
- Afficher le code court complet, y compris sur mobile.
- Afficher « Copié » seulement après une copie réellement réussie ; sinon expliquer l’échec.
- Encadrer l’aperçu dynamique et prévoir ses états de chargement et d’erreur.
- L’aperçu d’administration utilise les droits du compte connecté et peut donc différer de la vue d’un visiteur ; le préciser.
- Enregistrer le style d’accès restreint par publication et le synchroniser avec l’aperçu.

**Acceptation :** paramètres, code court et aperçu restent synchronisés ; aucun succès de copie n’est affiché si l’action a échoué ; le code reste copiable et lisible sur petit écran.

### 7. Interface Réglages et diagnostic de stockage

- Regrouper visuellement les réglages et harmoniser l’écran avec les interfaces FileWarden existantes.
- Garder le diagnostic de stockage visible et son état explicite : **validé**, **bloqué** ou **non vérifié**.
- Ne pas affaiblir les contrôles de stockage ni masquer un échec ou un état indéterminé.

**Acceptation :** l’état du diagnostic est visible sans action de navigation supplémentaire et n’est pas ambigu. Les contrôles et valeurs existants continuent de fonctionner.

### 8. Journal d’audit : affichage et navigation

- Conserver la page actuelle avec événements à gauche et panneau « Filtres et exports » à droite ; le panneau conserve son rôle et sa largeur actuelle lorsque possible.
- Organiser le panneau en sections visuellement distinctes : filtres, exports, maintenance.
- Fournir une vue **Simplifiée** en français par défaut et une vue **Technique** présentant les événements structurés. Le changement de vue ne change ni les droits ni le jeu de données.
- La vue simplifiée utilise des libellés compréhensibles et identifie les utilisateurs WordPress, documents et dossiers lorsque ces informations sont disponibles.
- Permettre de déplier les détails techniques d’un événement.
- Afficher les horaires dans le fuseau indiqué par l’interface (la maquette utilise Europe/Paris) et proposer une pagination.
- La maquette interactive contenait des données fictives ; l’implémentation doit utiliser les événements réels du plugin.

**Acceptation :** mêmes événements et identifiants sous-jacents dans les deux vues ; détails dépliables ; aucune donnée d’audit révélée à un rôle non autorisé.

### 9. Journal d’audit : filtres et maintenance

- Conserver le filtre par type d’événement et la période « Du » / « Au » ; afficher le fuseau horaire utilisé.
- Proposer les filtres avancés dans une section repliable, notamment une recherche par utilisateur WordPress, et un bouton **Réinitialiser**.
- Appliquer les filtres explicitement. L’export et la purge utilisent le périmètre appliqué ; l’affichage et la pagination ne réduisent pas le périmètre côté serveur.
- Purge manuelle : bouton **« Préparer la purge »**, rouge `#B42318`, avec pictogramme de poubelle, vérification des droits et confirmation obligatoire avant suppression. Utiliser les contrôles d’autorisation WordPress et la capacité de purge distincte de la simple lecture d’audit.
- Afficher le périmètre et le nombre d’événements concernés, confirmer le caractère irréversible, conserver une trace de purge. Proposer l’export avant suppression.
- La purge manuelle complète la rétention automatique. Ne pas importer de journaux externes dans le journal actif.

**Acceptation :** compte sans droit ne peut ni consulter ni purger. Une purge ne dépasse pas le périmètre confirmé, conserve une trace, et ses erreurs sont signalées sans prétendre qu’elle a réussi.

### 10. Exports du journal et finitions de l’évolution validée

Les trois boutons déclenchent **directement le téléchargement d’un fichier**, jamais une copie dans le presse-papiers.

| Bouton | Fichier attendu | Usage indicatif |
|---|---|---|
| CSV | `filewarden-audit-YYYY-MM-DD.csv` | Tableurs et analyse |
| TXT | `filewarden-audit-YYYY-MM-DD.txt` | Logs traditionnels |
| JSONL | `filewarden-audit-YYYY-MM-DD.jsonl` | SIEM et puits de logs |

- Chaque format a son pictogramme dédié, conserve la charte graphique et offre les états visuels au survol et pendant le traitement, ainsi qu’une notification de réussite ou d’échec.
- Exporter tous les événements correspondant aux filtres appliqués, pas uniquement ceux de la page courante.
- Les exports sont structurés, parsables, indépendants des vues Simplifiée/Technique et soumis aux contrôles d’autorisation WordPress.
- Conserver les identifiants techniques et un schéma stable pour faciliter l’ingestion par des outils de logs.
- Chaque événement inclut l’identifiant WordPress numérique et l’identifiant lisible du compte (`user_login`) capturés au moment de l’action, lorsqu’un compte connecté existe. Ne pas inclure l’adresse e-mail. Les événements historiques sans ce champ restent lisibles par identifiant et peuvent être rapprochés du compte WordPress courant lorsqu’il existe.
- Schéma des lignes CSV/TXT conservé et étendu : `id`, `created_at_UTC`, `event`, `actor_id`, `actor_login`, `object_id_or_count`. TXT utilise le format tabulé actuel. JSONL utilise un objet par ligne avec ces mêmes clés et types numériques pour `id`, `actor_id` et `object_id_or_count`; `actor_login` vaut `null` quand aucun compte n’est associé.
- Le nom du fichier inclut la date du site au format `YYYY-MM-DD`. Les champs d’événement et leurs identifiants techniques ne dépendent pas du mode d’affichage.

**Acceptation :** le clic télécharge effectivement le bon fichier ; le presse-papiers n’est pas utilisé. Les trois exports couvrent l’intégralité du jeu filtré et restent indépendants de la pagination et du mode d’affichage. Échec et réussite sont annoncés clairement.

## Exigences transversales de sécurité

- **Refus par défaut** : l’autorisation ne peut pas être établie, l’action échoue sans accès ni modification.
- Vérifier côté serveur l’authentification, les capacités WordPress et les nonces contre les actions CSRF pertinentes. Un nonce n’est pas une autorisation.
- Ne jamais se fier à un bouton masqué, un identifiant fourni par le navigateur, un filtre client ou un lien imprévisible pour protéger les données.
- Réutiliser les services d’autorisation et d’audit existants. Autoriser chaque lecture, export, aperçu, téléchargement, mutation et purge au moment de la requête.
- Valider, assainir et échapper les données selon leur contexte ; utiliser les API WordPress et requêtes préparées.
- Prévenir les formules CSV (préfixes dangereux) et les injections dans TXT/JSONL ; protéger les réponses privées contre mise en cache et divulgation de chemins.
- Réduire les données personnelles dans les journaux ; appliquer la rétention et garder une trace de purge sans recréer les événements supprimés.
- Maintenir la compatibilité multisite hors périmètre : le projet cible le WordPress simple site uniquement.

## UI/UX, accessibilité et cohérence

- Lire `docs/branding.md` avant tout changement visuel. Réutiliser les jetons, composants, icônes approuvées et styles du projet ; pas de nouvelle identité graphique.
- Respecter les conventions WordPress, les thèmes clair/sombre lorsqu’ils sont pris en charge, la navigation clavier, le focus visible, les libellés accessibles, le contraste WCAG 2.2 AA visé et les préférences de réduction des animations.
- Conserver des états de chargement, vide, réussite, refus et erreur compréhensibles. Ne pas utiliser la couleur comme seul indicateur.
- Tester les interfaces sur ordinateur et mobile ; les noms, code court et actions restent utilisables sans défilement horizontal indu.
- « Harmonisation » ne vaut pas autorisation de remplacer globalement la charte ou de modifier les écrans hors périmètre.

## Tests et critères de non-régression

Codex devra ajouter ou mettre à jour des tests ciblés pour les changements de sécurité et de comportement, puis lancer les contrôles déjà définis par le dépôt et le lot. Rapporter les commandes réellement exécutées et leurs résultats ; distinguer les vérifications impossibles des réussites. Ne jamais déclarer une vérification exécutée si elle ne l’a pas été.

### Tests fonctionnels à couvrir

- Navigation parent/enfant, fil d’Ariane borné, recherche limitée au dossier courant.
- Héritage dynamique, intersection de toutes les règles, modification d’un parent et préservation des règles préexistantes.
- Accès anonyme / connecté autorisé / connecté refusé ; listing, metadata, URL directe, aperçu et téléchargement.
- Blocage de publication et d’accès direct pour Documents à classer ; stabilité des UUID et conservation des documents.
- Suppression d’un dossier vide, avec fichiers, avec fichiers en corbeille, avec sous-dossiers et scénario d’échec intermédiaire.
- Synchronisation des paramètres Publication, du code court et de l’aperçu ; succès et échec de copie.
- Diagnostic de stockage dans ses trois états.
- Vues d’audit, détails dépliables, filtres dates/types/utilisateur, réinitialisation et pagination.
- Exports CSV/TXT/JSONL avec filtre vide et filtre actif, résultats multi-pages, zéro résultat, caractères spéciaux et données hostiles. Vérifier téléchargement, noms/extensions, validité syntaxique et absence de troncature à la page.
- Purge : droits de lecture seuls, capacité de purge, nonce absent/invalide, périmètre, confirmation annulée/acceptée, erreurs et trace retenue.

### Non-régression à préserver

- Les fonctions déjà livrées : gestion des documents/dossiers, prévisualisation et téléchargement protégés, shortcodes, Liste/Grille, droits hérités, politiques éditables, déplacement/copie, corbeille/restauration, réglages et diagnostic de stockage.
- Compatibilité des URL/UUID existants et données migrées ; migrations répétables sans perte, duplication ni modification silencieuse des droits.
- Retention de la corbeille (30 jours par défaut, configurable) et du journal (365 jours par défaut, configurable) selon la configuration présente.
- Aucune donnée de document réelle, secret, clé ou identifiant sensible dans tests, fixtures, logs, exports non autorisés ou dépôt.
- Aucune release, tag, push de publication ou déploiement sans accord distinct explicite.

### Conditions de validation du lot

Le lot est prêt à être présenté lorsque les critères d’acceptation applicables sont couverts, les contrôles du dépôt sont exécutés et les régressions de sécurité sont absentes. Les tests unitaires seuls ne prouvent pas l’acceptation complète WordPress/hôte. La validation du code par le propriétaire ne vaut pas autorisation de publication.

## Décisions explicitement non fixées

Les éléments disponibles ne fixent pas les limites de taille ou stratégie de streaming des exports, ni le comportement visuel d’un export sans résultats. Ne pas introduire de limite arbitraire ni modifier la sémantique des exports sans décision. Les exportations doivent rester paginées côté serveur et ne pas dépendre de la taille d’une page de l’interface.

## Sources de référence

- Conversation « Projet plugin WordPress », évolution 10 validée et précision sur les téléchargements CSV/TXT/JSONL.
- `docs/lot-navigation-interfaces.md` — périmètre et décisions validés de navigation et interfaces.
- `docs/specifications/requirements.md` — exigences produit et règles de sécurité de référence.
- `docs/security.md`, `docs/permissions.md`, `docs/branding.md`, `docs/architecture.md`.
- `AGENTS.md` et `SUIVI.md` — workflow, état et règles de contribution.
