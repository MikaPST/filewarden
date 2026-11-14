# FileWarden 0.1.15-dev — demandes en cours de collecte

Le propriétaire annonce les améliorations du prochain lot, en commençant par le blocage de suppression ci-dessous. **Collecte et diagnostic uniquement : aucun développement 0.1.15-dev, changement de version, migration ou déploiement commencé.** Consolider les autres demandes et obtenir le GO du lot avant de coder.

## 01 — Suppression de dossier : contrôle des tables transactionnelles

### Retour utilisateur confirmé

Après confirmation finale, le dossier reste présent et la notification affiche :

> Dossier non supprimé — La suppression exige des tables de base de données transactionnelles (InnoDB). Demandez à votre hébergeur de vérifier les trois tables FileWarden. Aucun dossier n’a été supprimé.

Le retour identifie le refus `transaction`, déclenché par `Repository::require_transactions()` avant les mutations de `FolderLifecycle::remove()`. Il ne prouve pas à lui seul qu’une table utilise MyISAM : le contrôle emploie aussi cette erreur lorsqu’il ne peut pas lire/vérifier les métadonnées ou ne retrouve pas les trois tables attendues. La version 0.1.14-dev essaie information_schema puis les métadonnées des tables ; le moteur effectif et la cause sur ce site restent à relever.

### Informations nécessaires

- Moteur déclaré et présence des tables dont le suffixe est `filewarden_documents`, `filewarden_folders` et `filewarden_audit_logs`, avec leur préfixe réel.
- Un export **de structure uniquement** de ces trois tables (blocs CREATE TABLE, dont ENGINE) suffit pour une première vérification. Aucun dump complet WordPress, INSERT, compte utilisateur ou contenu documentaire requis.
- Si les trois tables sont InnoDB, comparer la lecture effectuée par la connexion WordPress au résultat d’administration de la base : les droits ou la connexion peuvent différer. Ne pas conclure qu’une lecture réussie par un compte phpMyAdmin valide automatiquement celle du plugin.
- Le propriétaire propose un dump SQL ; la structure ciblée suffit. Export pas encore reçu.
- Résultats reçus : `default_storage_engine = InnoDB`, mais la sélection de métadonnées filtrée initiale ne retourne aucune ligne. Cela ne renseigne pas encore sur le moteur effectif des tables existantes.
- SHOW TABLES confirme ensuite les trois noms `wp_filewarden_audit_logs`, `wp_filewarden_documents` et `wp_filewarden_folders` dans la base sélectionnée. Le résultat transmis de SHOW TABLE STATUS ne contient pas de valeurs Name/Engine exploitables. Le nom réel de la base et les liens d’administration privés ne sont pas consignés dans le dépôt public.
- Les trois SHOW CREATE TABLE ciblés sont demandés ; attendre les clauses ENGINE et, au besoin, les définitions de structure. Ne pas convertir sur la seule base du message générique ni du moteur par défaut.

### Constat dans le code d’installation

`Installer::migrate()` crée les trois tables avec leur charset/collation, **sans clause ENGINE explicite**. La création dépend donc du moteur par défaut du serveur. Cela peut produire des tables incompatibles avec l’exigence transactionnelle, même si les tests sur les serveurs utilisant InnoDB par défaut passent. Ce défaut de définition est établi dans le code ; le moteur effectivement utilisé sur le site utilisateur reste à confirmer par les résultats demandés.

### Correction à définir après diagnostic

- Distinguer « moteur incompatible confirmé », « table manquante » et « métadonnées non vérifiables » ; donner un retour fiable et compréhensible.
- Si les tables sont déjà InnoDB, corriger le contrôle ou sa compatibilité avec la connexion réelle, sans conversion inutile.
- Si une table est réellement non transactionnelle, préparer une conversion ciblée vers InnoDB, avec sauvegarde et contrôle des données, capacités et contraintes d’hébergement. Aucune conversion automatique aveugle ni élargissement de privilèges.
- Vérifier l’installation initiale et les mises à jour pour ne pas laisser croire que la suppression fonctionnera quand ses prérequis ne sont pas satisfaits.
- Préserver transactions, nonces, capacités, verrous, rollback, octets, UUID et restrictions héritées ; la suppression de dossier doit conserver les documents récupérables. Ne pas retirer la protection pour masquer l’échec.

### Recette à préciser dans le lot consolidé

Tables InnoDB existantes ; moteur non transactionnel ; métadonnées refusées ou incomplètes ; table manquante ; refus de conversion ; préfixe personnalisé ; suppression vide et avec documents ; panne au milieu de récupération et rollback ; droits insuffisants ; nonce invalide ; sauvegarde et conservation des données avant/après toute migration éventuelle. Reproduire le cas réel avant de le déclarer résolu.

Références : [contrôle actuel](../includes/Repository.php), [suppression/récupération](../includes/FolderLifecycle.php), [conversion InnoDB officielle](https://dev.mysql.com/doc/refman/8.4/en/converting-tables-to-innodb.html), [métadonnées de tables](https://dev.mysql.com/doc/refman/8.4/en/show-table-status.html).
