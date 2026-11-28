# Compléments documentaires v19 — conception avec le propriétaire

État du 28 novembre 2026 : GO consolidé reçu, implémentation et recette locale terminées. Le ZIP antérieur (SHA-256 69eece3320980f186987272984d0e6ebcfdeee32a9bd5389cbee7ecac3b19085) est préservé sous build/filewarden-0.1.19-dev-before-document-features.zip. Aucun accord de publication GitHub/tag/Alpha.

## Modification explicite des droits — comportement validé

Un déplacement/reclassement conserve les restrictions antérieures et la pastille (i). Un enregistrement explicite dans Modifier les droits remplace la règle du fichier et retire ses restrictions antérieures conservées, sans case supplémentaire ; la pastille disparaît après succès confirmé. Les restrictions du dossier actuel et de ses ancêtres restent applicables. Aucun effacement automatique des droits à l’entrée dans Documents à classer ; cette collection reste non publiée et réservée à la gestion.

Texte français validé : « Les nouveaux droits remplaceront les anciennes restrictions, tout en respectant les droits du dossier actuel. » Prévoir FR/EN, audit, contrôles de capacités/nonces, verrouillage cohérent et test d’échec sans activation partielle. La disparition de la pastille doit refléter les droits réellement retirés, pas un masquage visuel.

## Détection des doublons — parcours et approche validés

- Comparer le contenu d’origine, indépendamment du nom : taille, empreinte SHA-256 avant chiffrement, puis comparaison octet par octet des candidats pour confirmer l’identité exacte. Comparaisons privées par flux bornés, sans copie publique en clair.
- Ne révéler un doublon, son nom ou son emplacement que si l’utilisateur est autorisé à consulter le document existant. Vérifier les droits côté serveur lors de la détection et lors des actions suivantes ; aucun contournement via une empreinte client.
- Même nom avec contenu différent : conflit de nom distinct, sans écrasement automatique. Un document visuellement identique avec des octets/métadonnées différents n’est pas un doublon exact.
- Encadré ambré dans la ligne d’import : contenu identique, nom/emplacement de l’existant autorisé, Voir le document existant et Importer quand même. Possibilité de retirer le fichier de la sélection.
- L’utilisateur peut importer les autres fichiers sans traiter le doublon immédiatement. Le choix est propre au fichier et à cet import, pas une exclusion globale des prochaines détections.
- L’encadré disparaît après confirmation de la copie ou retrait. Après import, aucune pastille ni avertissement permanent de doublon dans la bibliothèque. Les copies restent des documents indépendants avec leurs propres identités et droits ; aucune fusion/alias destructive.
- Maquette approuvée : filewarden-doublons-import-maquette.html dans le répertoire de visualisations du chat. Comparaisons avec la corbeille, limites de traitement et courses entre imports à préciser dans le plan technique sans divulgation de données non autorisées.

## Fiche document — proposition et approche validées

Le propriétaire valide complètement la proposition et l’approche après la maquette filewarden-fiche-document-maquette.html : fiche en modale centrée dans le backend, accès par nom du document et menu Informations ; bouton œil conservé pour aperçu direct. Icône de type originale, nom, emplacement autorisé, Aperçu/Télécharger, description illustrative, format/taille/import/arrivée dans le dossier/auteur, synthèse de l’accès effectif. Fermeture sans mutation et retour à la bibliothèque ; adaptation mobile. Les détails de l’explication des droits et l’édition de description seront travaillés séparément avec le propriétaire. Ne pas exposer de chemin physique ni de métadonnées hors autorisation. Validation de conception enregistrée ; développement consolidé non encore lancé.

## Fonctionnalités documentaires validées et livrées

### Explication de l’accès effectif — approche et affichage validés

Dans la fiche, synthèse immédiatement visible (audience effective et phrase expliquant la limitation), puis Comprendre l’accès repliable. Détail séparant règle du fichier, dossier actuel, tous les ancêtres applicables et restrictions antérieures ; celles-ci restent identifiées en ambre avec la pastille. Toutes les règles s’appliquent ensemble ; Public n’efface pas les autres restrictions. Mention explicite de l’exception de lecture globale et du traitement particulier de Documents à classer.

Le propriétaire confirme cette disposition après clarification du parcours : explication dans la fiche, bouton Modifier les droits ouvrant la modale dédiée, avec reprise de la synthèse des restrictions du dossier dans cette modale et détail complet repliable. Conception validée, développement autorisé par le GO consolidé du 28 novembre.

Maquette filewarden-comprendre-acces-maquette.html : exemples public, limité par le dossier, anciennes restrictions et non publié ; modification simulée des droits reprenant la décision validée de retirer les restrictions antérieures après enregistrement explicite, sans publier automatiquement un document à classer. Les exemples sont fictifs ; aucun changement runtime. En implémentation, explication issue des contrôles serveur existants, pas d’une simple approximation dans le navigateur. Vérifier l’autorisation de chaque information, ne pas inventer le nom d’un ancien dossier disparu, distinguer non publié/corbeille/inaccessible et bloquer si la chaîne est incohérente ou non vérifiable. La lecture de l’explication n’accorde pas la capacité de modifier les droits.

### Description facultative et recherche — approche validée, limite 400 caractères

Approche validée par le propriétaire, avec réduction explicite de la limite à 400 caractères et compteur « 0 / 400 », dans la fiche comme à l’import. Texte simple facultatif, ajout/modification/suppression depuis la fiche par les comptes autorisés à éditer le document. Vide reste accepté ; pas d’éditeur riche ni de HTML. À l’import, champ replié facultatif propre à chaque fichier, sans application globale involontaire à une sélection multiple. Édition dans la fiche avec Enregistrer/Annuler et compteur ; la suppression de la description ne supprime jamais le fichier. Recherche sur nom et description, uniquement parmi les documents autorisés, avec extrait de la description dans les résultats et sans lecture automatique du contenu du fichier. La description reste une métadonnée soumise à la même autorisation que le document. Affichage public éventuel à confirmer séparément, sans publication automatique de la description dans ce lot.

Maquette filewarden-description-document-maquette.html présentée et approuvée pour les parcours fiche en lecture/édition, ajout à l’import et recherche d’un mot présent seulement dans la description. La limite de 1 000 figurant dans la maquette initiale est remplacée par la décision finale de 400 caractères. Le GO consolidé a autorisé l’implémentation des quatre fonctionnalités. Runtime, traductions, tests et ZIP sont terminés ; voir docs/validation/v0.1.19-dev-document-local.json.

Send / partage temporaire réservé à l’Alpha ou la Bêta. Historique des versions et historique individuel toujours différés.
