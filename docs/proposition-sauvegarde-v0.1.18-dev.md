# Sauvegarde et migration — approche validée pour 0.1.18-dev

Demande reçue le 22 novembre 2026 : sauvegarder/exporter et importer/restaurer toute la bibliothèque sur une autre installation, avec protection chiffrée. **Le propriétaire valide explicitement l’ensemble de l’approche puis la démonstration graphique interactive.** La validation porte sur le périmètre, la conception décrite ici et la disposition de `filewarden-sauvegarde-migration-018.html` ; le GO global a ensuite été donné et le développement est terminé avec recette locale concluante le 23 novembre. Ce document conserve la conception validée. Les paramètres finalement retenus figurent dans [le guide implémenté](backup.md) et [les notes](releases/v0.1.18-dev.md).

## État vérifié et périmètre proposé

Le stockage géré actuel dérive les clés de fichiers à partir d’une clé maîtresse aléatoire stockée dans l’option WordPress `filewarden_managed_key` (ManagedStorage::key). Le stockage privé explicitement placé hors racine web peut fonctionner sans ce backend chiffré. Copier le code ou les seuls fichiers chiffrés ne constitue pas une sauvegarde documentaire restaurable. Références locales : includes/ManagedStorage.php, includes/StorageService.php et docs/deployment.md.

Proposer un export logique versionné de la bibliothèque : octets des documents, UUID, dossiers et arborescence, noms/dates/types/tailles/empreintes, règles propres et héritées conservées, corbeille et origine/restauration, journal d’audit avec identités historiques, réglages fonctionnels et identité publique (dont le logo personnalisé réellement incorporé, pas seulement son ID de médiathèque). Inclure les Documents à classer. Les historiques de versions non implémentés ne sont pas annoncés comme présents.

Ce paquet concerne FileWarden. Les comptes/mots de passe WordPress, thèmes, autres extensions, wp-config.php et la base WordPress générale restent hors périmètre. Les chemins de stockage, secrets et états de vérification de l’hébergement source ne doivent pas devenir la configuration du site cible.

## Protection et portabilité proposées

- Format dédié versionné, par exemple `.fwbackup` (suffixe proposé, non validé), avec manifeste structuré et strictement validé, sans SQL ou code exécutable provenant de l’archive.
- Chiffrement authentifié Sodium secretstream XChaCha20-Poly1305, avec dérivation Argon2id de la clé de protection à partir d’une phrase secrète et d’un sel aléatoire. Définir explicitement les versions, paramètres bornés, authentification du contexte et marqueur final ; limiter les paramètres fournis par un fichier avant toute allocation coûteuse. Utiliser les primitives éprouvées, sans algorithme cryptographique maison.
- Clé de sauvegarde indépendante du stockage de l’installation source. Transférer les documents du lecteur de stockage existant vers le flux chiffré de sauvegarde, sans déposer de copie en clair sur disque ni inclure la clé maîtresse source. Les métadonnées, noms, droits et réglages sensibles font aussi partie du contenu chiffré.
- À la restauration, vérifier intégralement le paquet avant activation puis chiffrer les documents pour le stockage vérifié du site cible. Ne jamais remplacer la clé d’une bibliothèque existante par celle d’un autre site.
- Phrase secrète jamais conservée en clair, dans une URL ou dans les journaux ; expliquer qu’une sauvegarde protégée par une phrase perdue n’est pas récupérable sans moyen de récupération expressément conçu. Aucune porte dérobée proposée.
- Les copies temporaires et checkpoints restent privés ; authentification et capacité spécifique à chaque contrôle, téléchargement et étape. Purge bornée des fichiers temporaires après succès, annulation, expiration ou échec. Définir la gestion des secrets des travaux par lots avant implémentation ; ne pas persister un mot de passe en clair pour assurer la reprise.

## Cohérence et restauration

Obtenir un instantané cohérent des métadonnées et des octets. Coordonner les mutations FileWarden, déplacements, suppressions et tâches de purge pendant sa préparation ; ne pas promettre un export cohérent par simple copie de tables et de fichiers en parallèle. Vérifier les tailles, empreintes, doublons, UUID, références et fin authentifiée du paquet. Encadrer les tailles, entrées, chemins, liens symboliques et consommation mémoire/CPU/disque. Ne pas extraire de chemins arbitraires fournis par le paquet.

Pour la première itération, **recommander une restauration dans une bibliothèque FileWarden vide** sur une installation compatible. La fusion et le remplacement d’une bibliothèque déjà remplie restent des choix distincts à valider, avec traitement des collisions et retour arrière. Pas d’écrasement automatique.

Parcours : sélectionner le paquet et saisir sa phrase → analyser et vérifier → afficher un bilan → résoudre les correspondances de rôles et d’utilisateurs → confirmer → préparer le stockage privé et les données → activer après validation → présenter un compte rendu. Un échec ne doit pas laisser des documents publics, des fichiers orphelins actifs ou une bibliothèque partiellement utilisable ; préparer isolément et définir un protocole de reprise/annulation pour la base et le système de fichiers. Ne pas prétendre qu’une transaction SQL couvre à elle seule les fichiers.

Sur un autre WordPress, ne jamais réutiliser aveuglément des IDs de comptes ou créer/accorder des rôles privilégiés. Exiger une correspondance explicite ; les rôles absents ou non résolus ne doivent pas élargir l’accès. Conserver les identités d’audit comme historiques du site source, distinctes des comptes locaux et des événements de restauration. Bloquer la publication tant que les correspondances d’accès ne sont pas validées. Les réglages sensibles (dont l’opt-in tous formats), identités de média et URL de connexion nécessitent une revue adaptée au site cible, sans activation implicite d’un réglage dangereux ni import d’un jeton d’aperçu/session.

Conserver les UUID documentaires et de dossiers quand la restauration complète le permet. Cela préserve les références logiques/codes courts ; un changement de domaine ne conserve pas automatiquement les anciennes URL absolues : leur redirection relève de la migration du site.

## Interface proposée

Ajouter une catégorie **Sauvegarde et migration** au rail vertical des Réglages, après Stockage et sécurité, sans quitter le fonctionnement dynamique existant. Dans le panneau de droite, deux onglets de même style que les autres navigations FileWarden :

- **Sauvegarder** : contenu et volume estimé, statut de protection, phrase secrète et confirmation/affichage, bouton Créer une sauvegarde chiffrée ; progression réelle par étapes, puis Télécharger la sauvegarde uniquement après confirmation serveur de sa préparation. Prévoir génération d’une phrase forte et aide à sa conservation.
- **Restaurer** : choix d’un paquet, phrase secrète, bouton Analyser la sauvegarde, bilan de compatibilité et d’intégrité, correspondances des rôles/utilisateurs, confirmation puis Restaurer la bibliothèque. Le téléchargement du paquet et sa restauration restent des opérations différentes.

Utiliser la charte, les notifications réutilisables, FR/EN, navigation clavier et mise en page mobile. Les grandes bibliothèques nécessitent des tâches par lots et une progression/panne explicite ; limites d’envoi du serveur, quota disque et disponibilité des primitives cryptographiques sont vérifiés avant lancement. Les seuils, durées de conservation temporaires et paramètres exacts restent à définir selon les contraintes réelles, sans valeur arbitraire annoncée comme acquise.

Opérations réservées à des capacités spécifiques d’export complet et de restauration, attribuées par défaut aux administrateurs, et protégées contre CSRF. Une capacité de simple lecture globale ou de consultation des réglages ne suffit pas à accorder l’export de toute la bibliothèque. Journaliser création, téléchargement, analyse, confirmation, succès, annulation et échec, sans secret ni données confidentielles dans les messages.

## Validation à prévoir avant livraison

Aller-retour réel entre deux WordPress distincts (préfixe, domaine, clé et IDs d’utilisateurs différents) ; égalité des octets et métadonnées, arborescence/corbeille/règles conservées ; refus d’accès identiques avec comptes de test ; rôles absents et références inconnues bloqués ; conservation du logo, paramètres et références logiques ; aucun privilège WordPress créé implicitement. Tester phrase erronée, paquet altéré/tronqué/réordonné, version incompatible, chemins/pièces malveillants, paramètres KDF abusifs, tailles excessives, espace disque insuffisant, interruptions, reprise, accès non autorisé et CSRF, liens de téléchargement expirés, nettoyage et non-régression de la bibliothèque source. Ne pas livrer sur la seule preuve d’un export réussi : la restauration est le test déterminant.

## Référence de la démonstration graphique

`filewarden-sauvegarde-migration-018.html` présente le rail des réglages, les onglets Sauvegarder/Restaurer, la progression, l’analyse et les correspondances. Les volumes, rôles et comptes sont fictifs. Le bouton Utiliser un fichier d’exemple et la phrase générée fixe servent uniquement à la démonstration ; aucune véritable archive n’est chiffrée ou téléchargée et aucun fichier choisi n’est lu. Le minimum de 16 caractères affiché pour l’essai n’est pas une politique de phrase secrète définitive. Ne pas importer ce code de simulation dans le plugin.

Vérifications de la maquette : parcours de sauvegarde/restauration, saisie erronée, confirmation et blocage des correspondances incomplètes, annulation, absence de téléchargement réel, affichage ordinateur/mobile 320 px sans débordement. Ce sont des contrôles de démonstration, distincts des tests fonctionnels et cryptographiques du futur composant serveur. La validation visuelle complète du propriétaire est reçue le 22 novembre 2026.

## Sources techniques consultées

- [PHP Sodium secretstream](https://www.php.net/manual/en/function.sodium-crypto-secretstream-xchacha20poly1305-init-push.php)
- [PHP Argon2 / dérivation de clé](https://www.php.net/manual/en/function.sodium-crypto-pwhash.php)
- [OWASP — gestion des clés et sauvegardes](https://cheatsheetseries.owasp.org/cheatsheets/Key_Management_Cheat_Sheet.html)
