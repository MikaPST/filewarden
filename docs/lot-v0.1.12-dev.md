# FileWarden 0.1.12-dev — réalisation et réception

Référence : [cahier des charges validé](specifications/evolutions-v0.1.12-dev.md).
Le propriétaire a autorisé le développement et la publication GitHub après réussite des tests le 13 novembre 2026. Aucune installation sur son site n’est incluse.

## Couverture des dix évolutions

| Évolution | Réalisation | Vérifications |
| --- | --- | --- |
| 1. Navigation | Parcours descendant autorisé, fil d’Ariane borné, recherche dans le dossier courant ; conservation du code livré en 0.1.11-dev. | Navigation WordPress, HTTP et parcours navigateur existants. |
| 2. Héritage | Intersection dynamique des politiques et restrictions des ancêtres ; conservation des politiques existantes. | PHPUnit, intégration WordPress, révocation immédiate et refus HTTP. |
| 3. Documents à classer | Collection de récupération conservée, non publiable ; UUID et contenu conservés. | Refus frontend, récupération et reclassement sans élargissement des droits. |
| 4. Suppression des dossiers | Suppression des feuilles avec récupération transactionnelle ; blocage des sous-dossiers. | Intégration et parcours de gestion existants. |
| 5. Accès restreint | Compact, Carte centrée par défaut, Bandeau éditorial ; messages neutres et connexion WordPress. | Trois styles, refus anonyme/connecté et absence de métadonnées privées. |
| 6. Publication | Paramètres, aperçu et code court synchronisés ; code complet sur mobile ; retour de copie. | Parcours navigateur existants et captures ordinateur/mobile. |
| 7. Réglages | Regroupement et diagnostic toujours visible ; contrôles de stockage conservés. | Intégration, parcours Réglages et capture. |
| 8. Audit | Vues simplifiée/technique, détails structurés dépliables, acteurs lisibles et noms des ressources selon leurs ACL. | HTTP, parcours navigateur et refus des noms privés à un lecteur d’audit sans accès au contenu. |
| 9. Filtres et purge | Filtre exact par login WordPress, type/période, réinitialisation ; vue conservée ; préparation de purge rouge avec confirmation et trace. | Filtre SQL hostile, lecture sans droit de purge, périmètre signé et exclusion des événements postérieurs. |
| 10. Exports | CSV, TXT tabulé et JSONL ; téléchargements datés, pictogrammes, états de traitement/échec et annonce du fichier reçu. | Exports HTTP et téléchargements Chromium/Firefox sur 1 005 événements filtrés, indépendants de la pagination et du mode d’affichage. |

## Données et sécurité

- Le schéma 5 ajoute `actor_login` sans supprimer les journaux ni toucher aux documents, clés, UUID ou politiques. Les migrations sont répétables. Les installations plus anciennes passent par la même migration.
- Chaque nouvelle ligne conserve l’ID et le login WordPress au moment de l’action. Le login mémorisé reste disponible si le compte disparaît. Un ancien événement sans login peut être rapproché du compte actuel ; sinon seul l’ID est disponible. Pas d’e-mail ajouté.
- La lecture d’audit et la purge conservent leurs capacités distinctes. Un nom de document/dossier exige aussi la lecture du contenu selon les règles existantes. Les exports restent réservés aux lecteurs d’audit avec nonce valide ; aucune ressource temporaire publique.
- Les exports utilisent le même instantané et les mêmes filtres, par lots serveur de 1 000 lignes. CSV/TXT neutralisent les formules et séparateurs ; JSONL encode les caractères au format JSON.
- Avec JavaScript, le navigateur reçoit le fichier dans un Blob avant de déclencher son téléchargement, ce qui permet de signaler les refus et erreurs réseau. Cela utilise de la mémoire proportionnelle à l’export. Sans JavaScript, les liens conservent le téléchargement natif avec flux serveur. L’interface annonce la réception et la demande de téléchargement ; elle ne prétend pas confirmer une sauvegarde sur disque.
- Les couleurs d’action utilisent les jetons sémantiques de la charte 1.1 intégrée depuis GitHub. Les pictogrammes approuvés `folder-plus`, `file-plus-2`, `shield-check` et `trash-2` sont embarqués depuis Lucide 0.468.0 avec leur licence ISC, sans dépendance d’exécution. Publication reste sans pictogramme. Les règles de contenu frontend restent configurables.

## État des validations

Contrôles locaux réussis : 50 tests PHP / 71 assertions, 8 tests JavaScript, PHPCS, syntaxe PHP/JavaScript et compilation de 238 traductions françaises. Le ZIP de développement a été généré.

Les tests d’intégration WordPress/MySQL, HTTP et Chromium/Firefox ont réussi sur WordPress 6.8 et la version courante : [preuve du code final b6968e4](https://github.com/MikaPST/filewarden/actions/runs/43488511977). Les captures ordinateur/mobile ont été inspectées. Les scénarios attendent la fin de la navigation des panneaux et conservent des preuves en cas d’échec. Les preuves de publication sont consignées dans SUIVI.md après réussite du workflow de release. Ces tests ne remplacent pas la réception sur l’hébergement réel.
