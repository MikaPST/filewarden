# Compléments validés — 0.1.18-dev

GO explicite reçu le 23 novembre 2026, après validation de la démonstration Diagnostic. Livraison locale en ZIP pour recette par le propriétaire ; aucune publication GitHub autorisée pour ce candidat. Les sept axes initiaux restent inclus.

## Périmètre réalisé

1. **Sauvegarde** : validation UTF-8 de la phrase sans dépendance à mbstring, format FWBACK01 et coût Argon2id inchangés ; erreurs ciblées avec référence sûre, sans exception brute. Espace inconnu et insuffisant distingués. La cause exacte de l'échec sur la préproduction PHP 8.5/LiteSpeed n'est pas établie.
2. **Connexion des visiteurs** : troisième mode Popup Elementor, identifiant entier positif, aide accessible (i) et lien officiel. Déclenchement sur la page parente de même origine à partir d'une action utilisateur ; repli sur la connexion WordPress si l'API Elementor Pro manque. Aucun JavaScript ou lien d'action arbitraire accepté.
3. **IP du journal** : option désactivée par défaut, conservation dédiée de 30 jours bornée par celle du journal ; collecte des refus et événements sensibles explicitement sélectionnés. IP validées, sans collecte des consultations ordinaires ni des tâches système. En-têtes de proxy ignorés sauf configuration serveur explicite. Affichage réservé aux détails/exports du journal autorisés, effacement à échéance.
4. **Journal** : filtres à choix multiples Action puis Statut, l'un sous l'autre ; filtre technique exact dans les filtres avancés. Demande, réussite, échec, refus, démarrage et situation indéterminée restent distincts. Une demande ou un téléchargement démarré n'est jamais présenté comme terminé. Historique et clés techniques conservés ; même périmètre pour résultats, exports et revue de purge.
5. **Publication** : bandeau Code court compact sur toute la largeur, sous les onglets ; réglages et aperçu alignés en dessous. Mise à jour et copie préservées, ordre mobile code → réglages → aperçu.
6. **Diagnostic** : dernière catégorie des Réglages ; lancement volontaire, onze contrôles réels dans six groupes, états Vérifié/Bloquant/Non vérifié, détails/actions/références et rapport expurgé téléchargeable. Aucune réparation automatique, conversion de table ou modification de document. Un résultat inconcluant ne devient pas un succès.

## Compatibilité et sécurité

Migration additive du schéma 6 vers 7 : deux colonnes d'IP et index d'expiration dans le journal. Identifiants, fichiers et réglages existants préservés ; collecte toujours inactive après mise à jour. Migration différée si une sauvegarde ou une réparation ancienne est en cours, pour préserver sa reprise. Les archives de schéma 6 restent acceptées ; la restauration désactive la collecte IP et rétablit la connexion WordPress standard.

Exports du journal versionnés en schéma 3, avec actor_ip nullable et statut explicite. Les identités historiques d'un autre site ne doivent pas être assimilées à un compte local ayant le même identifiant numérique.

Traductions françaises et anglaises regroupées dans les catalogues livrés. Les historiques de versions/activité, sauvegardes planifiées et autres nouvelles catégories suggérées restent reportés.

## Recette

Les preuves finales sont dans docs/validation/v0.1.18-dev-complements-local.json. Les preuves de la livraison initiale et du premier correctif de sauvegarde restent historiques et distinctes.

Le déclenchement Elementor est vérifié avec une API compatible simulée sur de véritables iframes WordPress ; Elementor Pro lui-même n'est pas installé dans la fixture locale. Sa recette sur le site du propriétaire reste nécessaire. PHP 8.5/LiteSpeed et l'hébergement réel ne sont pas validés par ces tests locaux. Le diagnostic n'est ni une certification de sécurité ni une validation complète de compatibilité.
