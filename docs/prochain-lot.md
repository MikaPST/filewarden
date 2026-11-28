# Compléments de la 0.1.18-dev — décisions et historique

**État au 23 novembre : GO consolidé reçu, développement effectué et recette locale réussie.** Les mentions « réservé à la suivante » et « pas encore codé » ci-dessous décrivent les étapes antérieures, remplacées par ce GO. Voir le périmètre effectif dans [le lot de corrections](lot-corrections-v0.1.18-dev.md). Elementor, IP, filtres, bandeau Code court et Diagnostic sont inclus dans le ZIP local corrigé ; aucune publication GitHub n'est autorisée.

Le 23 novembre 2026, le propriétaire signale que son bouton de connexion ouvre une popup Elementor à l’aide d’une action `#elementor-action...`. Le champ actuel accepte volontairement seulement les URL absolues HTTP/HTTPS.

Proposition présentée : conserver Connexion WordPress et Page personnalisée, puis ajouter un mode explicite **Popup Elementor** avec un identifiant positif (1252 dans l’exemple du propriétaire). L’intégration doit déclencher la popup dans la page parente, car FileWarden est affiché dans un iframe. Ne pas autoriser JavaScript arbitraire, des messages inter-origines non vérifiés ou n’importe quelle action encodée.

Le propriétaire choisit explicitement de réserver cette correction à la version suivante. Aucun changement du mécanisme de connexion dans 0.1.18-dev pour cette demande. Le détail technique, les replis sans Elementor et la recette d’intégration restent à valider avec ce prochain lot.

Le propriétaire demande également une pastille **(i)** à côté du libellé de la troisième option. Prévoir une aide accessible au clavier et au toucher, une explication concise et un lien vers la documentation officielle Elementor, ouvert dans un nouvel onglet avec les attributs de protection habituels : https://elementor.com/help/how-to-trigger-a-popup-on-a-link-click/ . Cette demande reste réservée au prochain lot.

## Adresse IP dans le journal — demande du 23 novembre 2026

Demande explicite : enregistrer et consulter l’adresse IP à l’origine d’un accès refusé. Le propriétaire demande aussi un avis sur les autres événements pertinents. Réservé à la prochaine version, sans changement de 0.1.18-dev.

Proposition à valider : collecte configurable, conservation dédiée limitée, affichage dans les détails techniques avec permissions du journal et traitement cohérent dans les exports/purges. Événements possibles : changements de droits, suppressions définitives, export/restauration de bibliothèque. Éviter une collecte systématique de toutes les consultations. Une IP ne prouve pas l’identité d’une personne.

Exigences techniques à préparer : valider IPv4/IPv6 côté serveur, gérer la rétention/effacement, ne pas inventer d’IP pour les tâches système ou événements historiques. Partir de REMOTE_ADDR ; ne faire confiance aux en-têtes transmis que si le proxy est explicitement fiable/configuré. Préserver les contrôles d’accès, éviter les injections de journal et ne pas exposer d’IP dans le frontend ou à des lecteurs non autorisés. Choix par défaut, durée, liste d’événements et éventuel masquage à arrêter avec le propriétaire avant GO du lot.

Références : https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html et https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/X-Forwarded-For .

Le propriétaire confirme l’inclusion de la journalisation des IP dans le prochain lot, avec le mode Popup Elementor. Les détails de configuration/collecte restent à consolider avant développement.

## Lisibilité des types d’événements — proposition du 23 novembre

Le propriétaire interroge la pertinence de la répétition des événements « demandé » et de leur résultat dans le filtre du journal. Constat dans AuditConsole : des événements *_requested et des événements d’achèvement distincts existent réellement. Ne pas supprimer ni renommer leurs clés techniques, ni convertir une demande en succès.

Proposition à valider : regrouper le filtre par famille/action (Copie de document, Déplacement, Mise à la corbeille, Restauration, Suppression définitive, etc.), avec un filtre de statut séparé et des libellés humains plus courts. Exemples : Copie — Demande reçue ; Copie — Réussie ; Déplacement — Refusé ; Téléchargement — Démarré. Affichage simplifié avec statut explicite, détails techniques conservés ; FR/EN et exports cohérents. Le statut « Démarré » n’atteste jamais la réception complète d’un fichier par le navigateur. Les événements système sans preuve de résultat restent indéterminés, pas assimilés au succès. Préserver les traces historiques, autorisations et compteurs filtrés. Aucune implémentation dans 0.1.18-dev.

## Regroupement décidé le 23 novembre 2026

Le propriétaire souhaite regrouper les compléments préparés pour la version suivante dans la 0.1.18-dev, encore locale et en recette. Les mentions historiques « version suivante » ci-dessus décrivent la décision initiale, désormais remplacée par ce regroupement. Pas de publication GitHub sans nouvel accord explicite. La collecte des améliorations continue ; présenter un récapitulatif consolidé et attendre le GO du propriétaire avant de développer les nouveaux compléments.

Périmètre à intégrer au récapitulatif : Popup Elementor avec aide (i) et lien officiel, journalisation des IP sur les événements sensibles selon les modalités à consolider, refonte des filtres du journal par Action/Statut et libellés plus lisibles. Le propriétaire valide explicitement la démo des filtres et demande une disposition **verticale**, Action puis Statut, dans la colonne existante trop étroite ; choix multiples par cases à cocher, appliquer/réinitialiser, conservation des filtres de dates et exports cohérents. Cette disposition remplace la proposition côte à côte, même sur ordinateur.

Correctif ciblé de sauvegarde en cours de recette locale : supprimer la dépendance à mbstring pour vérifier la phrase UTF-8 et remplacer le message d’erreur générique par des références sûres. La cause de l’échec précis sur la préproduction reste à confirmer ; voir docs/validation/v0.1.18-dev-backup-correction-local.json. Les compléments Elementor/IP/filtres ne sont pas encore codés.

## Publication — bandeau Code court transversal

Demande du 23 novembre, disposition explicitement validée ensuite par le propriétaire : le bloc Code court, placé seulement au-dessus de l’aperçu dans la colonne droite, décale celui-ci par rapport aux réglages. Conserver les onglets au-dessus du contenu ; placer ensuite un bandeau Code court compact sur toute la largeur, puis les deux colonnes Réglages et Aperçu alignées en haut. Le bandeau reste commun aux onglets Intégration du dossier et Apparence publique.

Présentation proposée : pictogramme et titre courts, code lisible sur une à deux lignes selon l’espace disponible avec adaptation si plus long, bouton Copier à droite et aide WordPress/Elementor discrète. Conserver le code exact, sa mise à jour dynamique, les noms accessibles, les traductions FR/EN, la sélection/copie manuelle et le retour de confirmation. Sur mobile : bandeau pleine largeur avec retour à la ligne, puis Réglages et Aperçu empilés ; aucun débordement horizontal ni code tronqué. Aucune modification runtime à ce stade ; intégrer au récapitulatif avant GO consolidé.

## Diagnostic — ajout au lot validé, interface en démonstration

Le 23 novembre, le propriétaire retient la fonctionnalité Diagnostic pour la 0.1.18-dev et demande une démonstration avant développement. Créer une nouvelle catégorie **Diagnostic**, toujours tout en bas de la navigation des Réglages. Toutes les autres suggestions (préférences d’affichage, sélection des formats, alertes e-mail, délégation par rôle, sauvegardes planifiées) sont réservées aux versions ultérieures et ne sont pas ajoutées à ce lot.

Interface proposée dans filewarden-diagnostic-018.html : bouton Lancer le diagnostic, rapport technique téléchargeable et aperçu du contenu expurgé ; synthèse des contrôles exécutés avec états Vérifié/Bloquant/Non vérifié, détails repliables par groupe et action conseillée/référence sûre lorsqu’un contrôle échoue. Groupes proposés : environnement PHP, chiffrement Sodium/Argon2id, protection et accès au stockage privé, tables de base, espace et limites, tâches FileWarden actives. Un contrôle inaccessible/inconcluant reste non vérifié ; aucune certification globale de sécurité ou de compatibilité n’est déduite du bilan.

La démonstration est entièrement simulée, clairement étiquetée, sans requête vers l’hébergement ; scénarios réussi, bloquant et incomplet. Elle ne diagnostique pas la préproduction. Le rapport fictif ne contient ni phrase, clé, mot de passe, chemin physique, IP, nom de document ou identité de compte. En développement réel, vérifier les permissions et nonces, borner les tests et ne créer que les témoins privés temporaires nécessaires, avec nettoyage ; aucune conversion de table, modification des documents ou réparation automatique. Ne pas exposer de traces brutes. FR/EN, clavier et mobile requis. L’ordre exact des vérifications et la présentation sont à valider après la démo et à intégrer au GO consolidé.
