# FileWarden 0.1.15-dev — cahier du lot validé

GO global reçu le 14 novembre 2026. Push, tag et release avec ZIP autorisés par avance uniquement après réussite des tests. Le dernier choix pour le point 09 est le **dossier sécurisé en contour** ; les variantes pleines ne sont pas retenues. Les décisions de conception ci-dessous fixent le périmètre. L’état réel d’implémentation et les preuves figurent dans [le compte rendu du lot](lot-v0.1.15-dev.md) et SUIVI.md.

## 01 — Suppression de dossier : contrôle des tables transactionnelles

### Retour utilisateur confirmé

Après confirmation finale, le dossier reste présent et la notification affiche :

> Dossier non supprimé — La suppression exige des tables de base de données transactionnelles (InnoDB). Demandez à votre hébergeur de vérifier les trois tables FileWarden. Aucun dossier n’a été supprimé.

Le retour identifie le refus `transaction`, déclenché par `Repository::require_transactions()` avant les mutations de `FolderLifecycle::remove()`. Le message seul ne distinguait pas un moteur incompatible d’une lecture impossible. **Les trois définitions SHOW CREATE TABLE complètes fournies ensuite par le propriétaire confirment ENGINE=MyISAM pour les documents, dossiers et journal d’audit.** Cette incompatibilité explique le refus transactionnel ; la suppression reste à retester après une conversion vérifiée. La raison historique de leur création en MyISAM et celle des premières lectures de métadonnées vides ne sont pas établies.

### Informations nécessaires

- Moteur déclaré et présence des tables dont le suffixe est `filewarden_documents`, `filewarden_folders` et `filewarden_audit_logs`, avec leur préfixe réel.
- Un export **de structure uniquement** de ces trois tables (blocs CREATE TABLE, dont ENGINE) suffit pour une première vérification. Aucun dump complet WordPress, INSERT, compte utilisateur ou contenu documentaire requis.
- Si les trois tables sont InnoDB, comparer la lecture effectuée par la connexion WordPress au résultat d’administration de la base : les droits ou la connexion peuvent différer. Ne pas conclure qu’une lecture réussie par un compte phpMyAdmin valide automatiquement celle du plugin.
- Le propriétaire propose un dump SQL ; la structure ciblée suffit. Les définitions complètes sont désormais reçues, comme indiqué ci-dessous.
- Résultats reçus : `default_storage_engine = InnoDB`, mais la sélection de métadonnées filtrée initiale ne retourne aucune ligne. Cela ne renseigne pas encore sur le moteur effectif des tables existantes.
- SHOW TABLES confirme ensuite les trois noms `wp_filewarden_audit_logs`, `wp_filewarden_documents` et `wp_filewarden_folders` dans la base sélectionnée. Le résultat transmis de SHOW TABLE STATUS ne contient pas de valeurs Name/Engine exploitables. Le nom réel de la base et les liens d’administration privés ne sont pas consignés dans le dépôt public.
- Après des résultats initialement tronqués, les trois SHOW CREATE TABLE complets sont reçus : `wp_filewarden_documents`, `wp_filewarden_folders`, `wp_filewarden_audit_logs` utilisent tous **MyISAM**. Le moteur par défaut actuel InnoDB ne convertit pas les tables existantes. Aucune donnée de document, aucun nom de base privé ni valeur AUTO_INCREMENT ne sont repris dans ce suivi public.

### Constat dans le code d’installation

`Installer::migrate()` crée les trois tables avec leur charset/collation, **sans clause ENGINE explicite**. La création dépend donc du moteur par défaut du serveur au moment de l’installation. Cela peut produire des tables incompatibles avec l’exigence transactionnelle, même si les tests sur les serveurs utilisant InnoDB par défaut passent. Ce défaut de définition est établi dans le code et les tables du site sont bien MyISAM ; leur historique d’installation/import reste inconnu.

### Correction à définir après diagnostic

- Distinguer « moteur incompatible confirmé », « table manquante » et « métadonnées non vérifiables » ; donner un retour fiable et compréhensible.
- Pour le cas confirmé, préparer une conversion ciblée des trois tables MyISAM vers InnoDB, avec sauvegarde complète incluant les données, conservation du stockage, pause des mutations et contrôle avant/après des données, capacités et contraintes d’hébergement. Ne pas réinitialiser ni recréer les tables. Aucune migration sur le site autorisée ou exécutée par le diagnostic.
- Pour les installations déjà InnoDB, conserver leurs tables et leur contenu ; corriger le contrôle ou sa compatibilité si un cas de lecture défaillante est reproduit, sans conversion inutile.
- Imposer explicitement InnoDB lors des nouvelles créations et prévoir le traitement des tables existantes au cours d’une mise à jour validée ; modifier seulement le moteur par défaut ne répare pas les tables déjà créées.
- Vérifier l’installation initiale et les mises à jour pour ne pas laisser croire que la suppression fonctionnera quand ses prérequis ne sont pas satisfaits.
- Préserver transactions, nonces, capacités, verrous, rollback, octets, UUID et restrictions héritées ; la suppression de dossier doit conserver les documents récupérables. Ne pas retirer la protection pour masquer l’échec.

### Recette à préciser dans le lot consolidé

Tables InnoDB existantes ; moteur non transactionnel ; métadonnées refusées ou incomplètes ; table manquante ; refus de conversion ; préfixe personnalisé ; suppression vide et avec documents ; panne au milieu de récupération et rollback ; droits insuffisants ; nonce invalide ; sauvegarde et conservation des données avant/après toute migration éventuelle. Reproduire le cas réel avant de le déclarer résolu.

Références : [contrôle actuel](../includes/Repository.php), [suppression/récupération](../includes/FolderLifecycle.php), [conversion InnoDB officielle](https://dev.mysql.com/doc/refman/8.4/en/converting-tables-to-innodb.html), [métadonnées de tables](https://dev.mysql.com/doc/refman/8.4/en/show-table-status.html).

## 02 — Remplacement du dossier de l’accueil Documents

- Demande du propriétaire : remplacer uniquement le dossier au-dessus d’« Ouvrez un dossier » par son illustration fournie. **Dernière décision : conserver uniquement le PNG et supprimer la copie SVG du dépôt**, car elle embarque la même image sans apporter de contenu vectoriel. Cette décision remplace la demande initiale de conserver les deux formats.
- Original conservé sans modification dans `assets/illustrations/folder-welcome.png` (1 521 252 octets), 1254 × 1254. La copie SVG a été retirée d’assets/illustrations sur autorisation explicite du propriétaire ; seul le PNG est retenu pour l’affichage et le réemploi futurs.
- Conserver les proportions, couleurs et transparence d’origine, le cadre actuel de 80 × 80 px, le centrage au-dessus du titre, le texte d’aide et le bouton de création. Aucune taille supérieure demandée dans ce lot.
- À l’implémentation, ajouter uniquement le PNG à l’allowlist du ZIP et contrôler sa présence, son intégrité et l’absence de débordement sur mobile. Le PNG est raccordé au CSS et à l’empaquetage pendant ce lot.
- SHA-256 PNG : `1c53db681551b02161927461483fb472484f1d31d42fdd09e76a5133e5af236b`.

## 03 — Logo officiel dans l’en-tête du back-office

- Demande confirmée : afficher le **logo officiel FileWarden** à côté du bloc de titre, en haut à gauche de l’interface, pour renforcer l’identité du plugin. La capture de référence montre « FILEWARDEN » au-dessus de « Bibliothèque de documents » ; placer le logo à gauche de ce bloc.
- Réutiliser les ressources officielles existantes d’`assets/branding/`, avec transparence et proportions préservées. Ne pas utiliser l’illustration de dossier comme logo, ne pas redessiner ni recolorer le symbole officiel.
- Prévoir une taille discrète mais lisible, un espacement réel entre logo et texte et un alignement équilibré avec le bloc. Aucune dimension exacte n’a été imposée ; ajuster avec le rendu au moment de l’implémentation.
- Garder la hiérarchie du titre, les onglets, la navigation et l’indicateur de version. Vérifier les petits écrans, les titres longs et le zoom ; éviter une répétition inutile du nom pour les lecteurs d’écran.
- Prévoir une présentation cohérente des en-têtes des pages FileWarden ; ne pas modifier les pages d’administration WordPress extérieures au plugin ni les affichages publics du site.
- Vérifications prévues : ressource officielle réellement chargée et incluse dans le ZIP, netteté, contraste et proportions, alignement, absence de débordement et contrôle visuel ordinateur/mobile.

## 04 — Pictogrammes des sources d’ajout de documents

- Demande du propriétaire : ajouter à « Depuis mon ordinateur » et « Depuis la médiathèque » un pictogramme adapté, dans Ajouter des documents.
- **Proposition explicitement validée par le propriétaire le 14 novembre 2026** : ordinateur portable (`laptop-minimal`) et bibliothèque de fichiers (`library-big`), dans le style Lucide déjà utilisé par le plugin. La médiathèque accepte aussi des PDF et documents ; ne pas la représenter uniquement comme une galerie de photos.
- Conserver les libellés et les contrôles radio actuels. Présentation validée : pictogrammes à gauche du texte, taille cohérente d’environ 20 px et espace d’environ 10 px ; blancs sur la source active Navy, Navy sur la source inactive claire. Ces dimensions viennent de la proposition acceptée, pas d’une mesure initialement imposée par le propriétaire.
- Adaptation mobile, focus et sélection clavier préservés ; icônes décoratives sans répétition pour les lecteurs d’écran. Aucun changement du téléversement, de l’import, des validations, des permissions ou de la destination.
- L’aperçu interactif indépendant du plugin et ses deux états de sélection sont validés. Le GO global de développement est désormais reçu.

## 05 — Repérage coloré des événements dans le journal simplifié

- Demande du propriétaire : améliorer la lecture au premier coup d’œil grâce à une couleur selon le type d’événement, **uniquement dans la vue simplifiée**. Ne pas modifier la vue technique.
- **Proposition explicitement validée par le propriétaire le 14 novembre 2026** : conserver le fond Navy, les textes lisibles et l’ordre chronologique ; ajouter des libellés de catégorie colorés, des pictogrammes et un fin repère latéral. Éviter de colorer toute la ligne ou tout le texte.
- Palette sémantique validée : bleu pour journal/consultation/téléchargement ; turquoise pour configuration et droits ; vert pour les ajouts/restaurations confirmés ; orange pour la corbeille et les demandes d’actions destructives ; rouge pour les suppressions définitives et les refus/erreurs, distingués par leurs libellés et pictogrammes. Événements inconnus : style neutre, sans résultat inventé.
- La couleur ne doit pas faire passer une demande pour une réussite, ni une suppression confirmée pour un échec. Conserver les libellés explicites « demandé », « supprimé », « refusé » ; définir la correspondance des codes d’événement lors de l’implémentation à partir de l’inventaire réel.
- Préserver dates, acteurs WordPress, ressources autorisées, lien d’ouverture des détails et contenu technique. Les détails restent consultables dans la vue simplifiée ; leur contenu n’est pas réécrit par ce changement visuel.
- Aucun changement des événements stockés, exports CSV/TXT/JSONL, filtres, pagination, rétention ou droits de consultation. Ne pas révéler un nom de ressource actuellement masqué pour illustrer une catégorie.
- Contrastes adaptés au fond sombre, symboles et libellés en plus des couleurs, clavier et petits écrans. L’aperçu validé emploie des événements et un compte fictifs ; il ne consulte ni ne modifie le vrai journal. GO global reçu pour ce lot.

## 06 — Harmonisation du bouton « Préparer la purge »

- Demande du propriétaire : améliorer ce bouton dans Maintenance du journal et employer le même pictogramme et le même langage visuel que les boutons de suppression ; référence fournie : **« Supprimer le dossier »**, rouge avec corbeille blanche au trait.
- Constat actuel : AuditConsole emploie encore `dashicons-trash` pour la préparation et la confirmation de purge, alors que la suppression de dossier utilise l’icône Lucide `trash-2` du système FileWarden.
- À prévoir : réutiliser le pictogramme `trash-2` existant, blanc comme le texte, avec les mêmes proportions, alignement vertical, espacement, arrondis et états de bouton que la référence. Utiliser le token Danger Red existant ; supprimer l’incohérence du pictogramme coloré différemment du libellé.
- Conserver le libellé **« Préparer la purge »** et la distinction entre préparation, revue des événements sélectionnés et confirmation de suppression définitive. Le clic de préparation ne doit jamais exécuter directement une purge.
- Contrôler aussi la cohérence du bouton de confirmation dans ce même parcours. Préserver les filtres, le périmètre revu, la confirmation explicite, les capacités, nonces et traces d’audit.
- Vérifications prévues : même ressource d’icône que la suppression de dossier, couleur et contraste, espacement et centrage, focus clavier, libellé accessible, retour à la ligne sur petit écran, parcours préparer/confirmer et refus sans permission ou confirmation.
- **Demande incluse dans le lot dont le GO global est reçu.**

## 07 — Refonte visuelle de la navigation de la bibliothèque

- Demande du propriétaire : moderniser le menu montré dans sa capture, c’est-à-dire la barre interne « Documents / Ajouter des documents / Créer un dossier / Corbeille ».
- Après sa préférence initiale pour Navigation compacte, le propriétaire a signalé le risque de confusion avec des boutons d’action. **Design final explicitement validé le 14 novembre 2026 : onglets compacts soulignés, sans le libellé visible « Navigation FileWarden ».**
- Pictogrammes à gauche, texte actif renforcé et trait sous l’entrée courante, ligne de séparation avec le contenu. Conserver des entrées sans fond plein ; les boutons d’action remplis restent visuellement distincts du menu. Garder un libellé de navigation accessible aux lecteurs d’écran, sans ajouter de titre visible au-dessus des onglets.
- Les deux propositions antérieures (« Navigation compacte » à sélection Navy remplie et « Navigation affirmée » sur bandeau Navy) restent des références de discussion ; elles ne sont pas le design finalement retenu.
- Pictogrammes proposés : fichiers, ajout de fichier, création de dossier et corbeille, dans le style Lucide du plugin. Conserver les quatre libellés, leur ordre et leurs destinations. La Corbeille reste une entrée de navigation, distincte des boutons exécutant une suppression.
- Desktop horizontal, disposition adaptée aux petites largeurs (deux colonnes dans les propositions), libellés complets, focus visible, navigation clavier native et état actif identifiable autrement que par la couleur.
- Conserver le filtrage des entrées selon les capacités et les contrôles d’accès. Ne pas ajouter de compteur, nouvelle entrée, nouveau droit ou changement de comportement de téléversement/création/corbeille.
- Le menu latéral WordPress et le menu vertical des catégories de Réglages ne sont pas concernés par cette capture. Le logo officiel de l’en-tête reste une demande séparée du point 03.
- L’aperçu change seulement la sélection pour illustrer le design ; il ne prouve pas une navigation sans rechargement dans le plugin et n’ajoute pas cette exigence. GO global reçu pour ce lot.

## 08 — Vue rapide et compacte de l’état de FileWarden

- Demande du propriétaire : réduire nettement la hauteur de l’encadré d’état dans Réglages FileWarden, en conservant le code couleur selon l’état. Il doit devenir une vue rapide, sans occuper le grand espace actuel.
- **Design explicitement validé le 14 novembre 2026 : Bandeau synthétique.** Pictogramme, titre, statut explicite du stockage, dernière vérification et accès « Détails ». Les trois informations complètes existantes sont accessibles dans le panneau dépliable. Hauteur réduite en situation normale, sans hauteur fixe qui couperait un message long, et disposition adaptée au mobile.
- La proposition « Résumé en colonnes » n’est pas retenue.
- Conserver le sens des couleurs actuelles : turquoise pour stockage vérifié, Slate pour non vérifié et rouge Danger pour critique. Les limites de configuration gardent leur avertissement distinct. Un état de stockage vérifié ne doit pas annoncer abusivement que toutes les fonctions du plugin ou toute la sécurité du site sont validées.
- Garder les alertes importantes visibles même lorsque les détails sont fermés : statut non vérifié/critique, opérations sur fichiers bloquées, imports limités. La réduction de hauteur ne doit pas masquer un échec ni une action nécessaire.
- Préserver intégralement les informations de stockage et de configuration, les messages et la vraie date serveur de dernière vérification. Aucun nouveau contrôle, recalcul périodique, bouton de vérification simulée ou réglage de santé n’est demandé par cette refonte visuelle.
- Accessibilité : libellé et pictogramme en plus de la couleur, contrastes, dépliage clavier, état ouvert identifiable et messages longs lisibles. Conserver les catégories, formulaires, saisies et sauvegardes des réglages.
- L’aperçu permet de simuler les trois états et une configuration limitée via les contrôles de design ; il ne mesure pas le site ni ne change son état. Le GO global de développement est désormais reçu.

## 09 — Icône FileWarden dans le menu WordPress

- Demande du propriétaire : remplacer la feuille de document du menu latéral WordPress par une icône plus représentative de FileWarden.
- Icône actuelle confirmée dans `AdminController::menu()` : `dashicons-media-document`.
- **Choix final explicite du propriétaire : Dossier sécurisé en contour (`folder-lock`).** Ce choix remplace les essais précédents de dossier plein et le bouclier.
- Prévoir un pictogramme monochrome lisible autour de 20 px, aligné avec le libellé FileWarden et compatible avec les états normal, survol, actif et le menu WordPress replié. Respecter la palette d’administration choisie par l’utilisateur, sans imposer un fond FileWarden ni modifier les autres entrées WordPress.
- Il s’agit d’un symbole de navigation, distinct du logo officiel conservé sans modification et prévu dans l’en-tête au point 03. Aucun changement des libellés, sous-menus, destinations ou capacités.
- La variante en contour est retenue dans le lot dont le GO global est reçu. Les palettes et états de l’aperçu restent des démonstrations locales.
