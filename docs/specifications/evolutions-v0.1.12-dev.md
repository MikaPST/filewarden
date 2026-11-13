# FileWarden — Référence fonctionnelle et UI/UX

**Version cible :** `0.1.12-dev`  
**Statut :** Spécifications validées — implémentation à vérifier  
**Dépôt :** https://github.com/MikaPST/filewarden  
**Destinataire :** Codex / équipe de développement

## 1. Objectif et règles impératives

Cette spécification décrit les dix évolutions validées pour FileWarden. Elle complète le cahier des charges fonctionnel en précisant les interfaces et comportements attendus.

**Une évolution n'est terminée que lorsque :**
- Son comportement fonctionnel est réellement implémenté.
- Son interface correspond à la conception validée.
- Les droits d'accès et contrôles de sécurité sont préservés.
- Les tests correspondants passent.
- Le résultat est visible dans le plugin WordPress effectivement installé.

Ne jamais considérer un changement de numéro de version comme une preuve d'implémentation.

Avant toute intervention, consulter `AGENTS.md`, `SUIVI.md`, `README.md`, `docs/branding.md` et les documents d'architecture existants.

### Identité visuelle

| Élément | Couleur |
|---|---|
| Midnight Navy | `#0B162C` |
| Deep Navy | `#1C2942` |
| Slate Blue | `#3B556D` |
| Turquoise | `#5FC2BA` |
| Blanc | `#FFFFFF` |
| Actions destructives | `#B42318` |

Respecter les composants WordPress, les règles d'accessibilité WCAG 2.2 AA, les états clavier, les contrastes, les écrans mobiles et les traductions FR/EN.

Les exemples suivants représentent des **maquettes de disposition**, et non des captures d'une implémentation existante.

---

## 2. Évolution 01 — Compteur « Documents à classer »

### Interface attendue

Dans l'arborescence de la page Documents, afficher un compteur directement à côté du nom du dossier spécial.

**Exemple :**

`▣ Documents à classer   (5)`  
`   Non publié`

Le compteur indique le nombre de **documents non publiés contenus dans cet espace**, et non le nombre de dossiers.

### Comportement

- Actualiser le compteur après ajout, déplacement, publication, suppression ou restauration.
- Afficher `0` lorsque l'espace est vide.
- Conserver le badge « Non publié ».
- Ne pas inclure les documents de la corbeille.
- Ne pas exposer de documents non autorisés à un utilisateur.

### Acceptation

Créer trois documents non publiés : le compteur affiche `(3)`. Publier un document : le compteur devient `(2)` sans rechargement complet si l'interface actuelle fonctionne dynamiquement.

---

## 3. Évolution 02 — Déplacer un document vers « Documents à classer »

### Interface attendue

Dans le panneau de déplacement d'un document :

**Déplacer le document**

Destination :
- Documents
- Documents Public
- Test
- **Documents à classer — Non publié**

Actions :

`[ Annuler ]` `[ Classer plus tard ]` `[ Déplacer ]`

### Comportement

Le bouton « Classer plus tard » constitue un raccourci vers le dossier spécial. La destination reste également sélectionnable dans la liste.

Si un document publié est déplacé vers cet espace, afficher une confirmation :

**Retirer ce document de la publication ?**

« Le document ne sera plus accessible depuis les publications FileWarden. Il restera disponible dans Documents à classer pour les utilisateurs autorisés. »

`[ Annuler ]` `[ Confirmer le déplacement ]`

Conserver les vérifications de permissions, la cohérence du stockage et l'audit.

### Acceptation

Un document publié déplacé vers « Documents à classer » devient non publié et disparaît des vues publiques. Son compteur de classement augmente.

---

## 4. Évolution 03 — Cohérence des boutons de gestion des dossiers

### Interface attendue

Sur les écrans de gestion des dossiers, distinguer immédiatement les actions :

- **Publier ce dossier** : fond Deep Navy `#1C2942`, texte blanc, **sans pictogramme**.
- **Modifier les droits** : style secondaire contrasté, cohérent avec la palette FileWarden.
- **Supprimer le dossier** : rouge destructif `#B42318`.

Ne pas utiliser la même apparence pour une action ordinaire et une action destructive.

### Comportement

Conserver les actions existantes et leurs permissions. Les changements sont principalement visuels, sauf si un comportement existant est défectueux.

### Acceptation

Les boutons ont une hiérarchie visuelle cohérente sur tous les écrans où ils apparaissent.

---

## 5. Évolution 04 — Modernisation « Ajouter des documents »

### Interface attendue

En haut du formulaire, présenter un sélecteur moderne à deux options :

`[ Depuis mon ordinateur ] [ Depuis la médiathèque ]`

L'option active doit être visuellement identifiable.

Pour « Depuis mon ordinateur » :

**Ajouter des documents**

Zone de dépôt :
« Glissez vos documents ici ou sélectionnez des fichiers »

Afficher les contraintes d'import, le nom des fichiers sélectionnés, leur taille, leur état de validation et les éventuelles erreurs.

Actions :

`[ Modifier les droits ]`  
`[ Supprimer le dossier ]`  
`[ Ajouter dans ce dossier ]`

Le dernier bouton est Deep Navy avec un pictogramme d'ajout ou de téléversement.

### Comportement

- Conserver le basculement entre ordinateur et médiathèque WordPress.
- Étendre les types de documents acceptés aux formats sûrs pertinents.
- Autoriser les archives ZIP avec contrôles renforcés.
- Refuser les exécutables, scripts et contenus actifs dangereux.
- Contrôler taille, extension, type MIME et contenu.
- Prévenir les archives ZIP malveillantes : décompression excessive, traversée de chemins, limites de contenu, etc.
- Préserver le dossier de destination et ses règles d'accès.

### Acceptation

Un document valide est ajouté au dossier sélectionné. Un fichier dangereux est refusé avec un message explicite. Aucun fichier refusé n'est rendu accessible directement.

---

## 6. Évolution 05 — Suppression de dossier et notifications globales

### Correction fonctionnelle

Corriger le défaut actuel : après confirmation de suppression d'un dossier, FileWarden peut afficher :

« Opération refusée ou indisponible. Vérifiez les droits, les champs et la configuration du stockage. »

Identifier la cause réelle au lieu de masquer l'erreur.

### Interface de confirmation

**Supprimer ce dossier ?**

Afficher le nom du dossier, les conséquences de l'opération et les éventuels éléments concernés.

`[ Annuler ]` `[ Supprimer le dossier ]`

Le bouton de suppression est rouge.

### Notifications globales

Créer un système cohérent pour toutes les pages d'administration :

- Succès
- Information
- Avertissement
- Erreur

Chaque notification doit présenter un message compréhensible, une icône appropriée et une possibilité de fermeture manuelle.

Pour les notifications temporaires, prévoir une durée suffisamment longue, de l'ordre de **10 secondes minimum**, et un indicateur visuel de progression. Ne pas faire disparaître automatiquement une erreur critique avant qu'elle puisse être lue. Prévoir les états de focus et d'accessibilité.

### Acceptation

La suppression fonctionne pour un utilisateur autorisé, échoue proprement pour un utilisateur non autorisé et ne contourne jamais les protections du stockage.

---

## 7. Évolution 06 — Modernisation « Créer un dossier »

### Interface attendue

**Créer un dossier**

Nom du dossier : `[________________]`

Dossier parent : `[ Sélectionner une destination ▼ ]`

Droits d'accès : « Les droits du dossier parent seront hérités selon les règles FileWarden. »

Actions :

`[ Modifier les droits ]`  
`[ Supprimer le dossier ]`  
`[ Créer le dossier ]`

Le bouton principal est Deep Navy.

### Comportement

- Conserver les règles de destination et d'héritage.
- Améliorer les espacements, labels, états d'erreur et messages de confirmation.
- Ne pas modifier silencieusement les permissions héritées.

### Acceptation

Créer un dossier dans un parent sélectionné fonctionne sans altérer les droits des autres dossiers.

---

## 8. Évolution 07 — Corbeille globale

### Interface attendue

La page Corbeille ne doit plus afficher l'arborescence des dossiers.

Elle doit présenter une vue globale de tous les documents supprimés autorisés :

| Document | Emplacement d'origine | Date de suppression | Actions |
|---|---|---|---|
| Contrat.pdf | Documents / Administratif | 12/11/2026 | Restaurer |
| Notice.pdf | Documents / Public | 10/11/2026 | Restaurer |

Prévoir une recherche et des filtres adaptés.

### Actions

`[ Restaurer ]`  
`[ Supprimer définitivement ]`  
`[ 🗑 Vider la corbeille ]`

La dernière action est rouge avec un pictogramme de corbeille.

### Cas particuliers

- Conserver l'ancien chemin du document même si le dossier d'origine a été supprimé.
- Si le dossier d'origine n'existe plus, demander une nouvelle destination valide.
- Ne pas restaurer automatiquement dans un emplacement non autorisé.
- Respecter la durée de conservation configurée.
- Avant de vider la corbeille, afficher une confirmation explicite indiquant que la suppression est irréversible.

### Acceptation

La corbeille affiche tous les documents supprimés accessibles à l'utilisateur, indépendamment du dossier précédemment sélectionné.

---

## 9. Évolution 08 — Refonte de la page Publication

### Interface attendue

En haut : sélection du véritable dossier à publier.

Ensuite, distinguer clairement :

`[ Affichage autorisé ] [ Accès refusé ]`

Dans la zone de configuration :

- Options de liste ou de grille.
- Présentation des documents.
- Apparence du message d'accès refusé.
- Shortcode généré et bouton de copie.

Dans une zone adjacente ou compacte :

**Aperçu en direct**

Afficher les **véritables documents du dossier sélectionné**, et non des données fictives.

L'aperçu doit rester contenu dans une zone raisonnable, éventuellement défilante ou redimensionnable, sans occuper toute la page.

### Comportement

- Les modifications de configuration doivent être reflétées dans l'aperçu.
- Préserver la simulation des accès autorisés et refusés.
- Ne jamais contourner les permissions pour alimenter l'aperçu.
- Préparer l'architecture à de futures personnalisations fines des couleurs, sans imposer dès maintenant des options non validées.

### Acceptation

Le shortcode existant continue de fonctionner et l'aperçu reflète le dossier réellement sélectionné.

---

## 10. Évolution 09 — Refonte de la page Réglages

### Interface attendue

Créer une navigation verticale à gauche et un panneau de réglages à droite.

**Navigation :**
- Général
- Stockage et sécurité
- Documents et limites
- Conservation
- Journal d'audit

En haut de page, afficher un véritable état de santé FileWarden.

Exemple :

**État de FileWarden**
- Stockage : vérifié / avertissement / critique / non vérifié
- Configuration : état constaté
- Dernière vérification : date réelle ou non vérifié

### Comportement

- Changer de catégorie sans recharger toute la page lorsque possible.
- Ne pas créer de rubriques vides pour de futures fonctionnalités.
- Conserver toutes les valeurs configurées, notamment les délais de conservation.
- Ne jamais annoncer un état « sécurisé » sans vérification effective.
- Préserver les validations côté serveur et les permissions WordPress.

### Acceptation

Naviguer entre catégories, modifier un réglage et enregistrer ne réinitialise aucune autre valeur.

---

## 11. Évolution 10 — Refonte du Journal d'audit

### Principe fondamental

**Conserver la structure visuelle existante :**

- Grande zone sombre Midnight Navy pour les événements à gauche.
- Panneau « Filtres et exports » à droite.

Ne pas remplacer cette disposition par une interface totalement différente.

### Deux modes d'affichage

`[ Vue simplifiée ] [ Vue technique ]`

**Vue simplifiée — exemple :**

**Document copié**  
Utilisateur : administrateur  
Document : Contrat entretien.pdf  
Date : 11/11/2026 à 20:09:48

`[ Afficher les détails techniques ▼ ]`

**Vue technique — exemple :**

`event_type: document_copy_completed`  
`actor_id: 2`  
`resource_type: document`  
`resource_id: 26`  
`outcome: success`

Les deux vues affichent les mêmes événements et partagent filtres et pagination.

### Améliorations des libellés

- Traduire les noms d'événements en français lisible.
- Conserver les codes techniques internes pour l'API et les exports.
- Remplacer `User #2` par le véritable identifiant WordPress lorsque disponible.
- Afficher « Visiteur non connecté » pour un événement réellement anonyme.
- Identifier distinctement les actions système.
- Remplacer les libellés de type `Object/count #26` par le nom réel du document ou dossier lorsque connu.
- Préserver des instantanés de noms pour les futures suppressions et renommages.
- Ne pas inventer les informations manquantes dans les anciens journaux.

### Panneau « Filtres et exports »

Conserver le panneau latéral avec :
- Filtre de dates.
- Filtre par type d'événement, avec libellés compréhensibles.
- Réinitialisation des filtres.
- Filtres avancés, notamment utilisateur WordPress.
- Zone d'export.
- Zone de purge clairement séparée.

### Trois boutons d'export

**CSV — Télécharger le fichier CSV**  
Pictogramme : document tableur.

**TXT — Télécharger le fichier TXT**  
Pictogramme : document texte.

**JSONL — Télécharger le fichier JSONL**  
Pictogramme : fichier JSON.

**Important : chaque bouton doit télécharger directement un fichier. Il ne doit pas copier le contenu dans le presse-papiers.**

Exemples de noms :

- `filewarden-audit-2026-11-13.csv`
- `filewarden-audit-2026-11-13.txt`
- `filewarden-audit-2026-11-13.jsonl`

Les exports doivent respecter les filtres actifs et inclure tous les résultats filtrés, et pas uniquement la page visible.

Pour JSONL : un objet JSON par ligne, champs stables, horodatage UTC ISO 8601, schéma versionné et codes d'événements non traduits.

Pour CSV : colonnes stables, échappement correct et protection contre l'injection de formules de tableur.

Pour TXT : informations techniques lisibles et structurées.

Ne pas exporter de secrets ou de données sensibles inutiles.

### Purge

Bouton rouge avec pictogramme de corbeille.

Afficher une confirmation indiquant le périmètre et le nombre d'événements concernés, après vérification des permissions et des règles de conservation applicables.

### Acceptation

Changer de vue ne modifie pas les données. Les trois exports produisent réellement des fichiers téléchargeables. La purge ne peut pas s'exécuter sans confirmation explicite.

---

## 12. Exigences de non-régression

Pour chacune des dix évolutions :

1. Comparer le comportement initial au comportement attendu.
2. Préserver les contrôles de permissions côté serveur.
3. Tester les utilisateurs administrateurs et les rôles moins privilégiés.
4. Tester les accès anonymes lorsque pertinents.
5. Préserver les URLs sécurisées et les contrôles de téléchargement.
6. Vérifier la compatibilité WordPress et PHP annoncée par le projet.
7. Vérifier les interfaces en français et en anglais.
8. Vérifier les écrans mobiles, le clavier et les lecteurs d'écran.
9. Tester les états vides, erreurs réseau, données anciennes et volumes importants.
10. Ne pas modifier les réglages existants sans action explicite de l'utilisateur.

## 13. Vérification avant livraison

Codex doit fournir un tableau de conformité :

| Évolution | Fonctionnel | UI/UX | Sécurité | Tests | Validée |
|---|---|---|---|---|---|
| 01 — Compteur | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 02 — Déplacement | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 03 — Boutons | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 04 — Ajout | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 05 — Suppression / notifications | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 06 — Création dossier | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 07 — Corbeille | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 08 — Publication | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 09 — Réglages | À vérifier | À vérifier | À vérifier | À vérifier | Non |
| 10 — Audit | À vérifier | À vérifier | À vérifier | À vérifier | Non |

Le statut « Validée » ne peut être attribué qu'après démonstration du comportement réel et contrôle de l'interface installée.

**Aucune release, archive finale ou publication GitHub ne doit être effectuée sans validation explicite du propriétaire du projet.**