# Prochain lot — retour Plugin Check du propriétaire

Rapport reçu le 28 novembre 2026 pendant la préparation de l’Alpha. Généré à 14:43:45 sur la préproduction du propriétaire. GO explicite reçu ensuite pour préparer la Bêta en corrigeant les dix signalements. Candidat : `0.1.19-beta.1`, sans nouvel ajout fonctionnel. Le rapport brut et ses liens d’administration ne sont pas publiés dans le dépôt.

10 signalements : deux erreurs de métadonnées et huit avertissements, dont un i18n et sept SQL. Ils ne constituent pas en eux-mêmes une preuve de vulnérabilité ou de conformité complète. Première lecture des fragments SQL : constantes choisies par le serveur ou valeurs préparées ; aucune entrée brute de requête identifiée dans les fragments signalés. Une revue et des tests ciblés restent nécessaires avant classement définitif.

| Fichier | Ligne du rapport | Point |
|---|---:|---|
| readme.txt | — | `missing_readme_header_tested` : Tested up to absent ; renseigner uniquement la branche WordPress réellement testée. |
| readme.txt | — | `no_stable_tag` : Stable tag absent ; arrêter la convention de distribution WordPress.org au moment de cette publication. Ne pas présenter une Alpha GitHub comme une version stable de l’annuaire. |
| includes/Language.php | 87 | `load_plugin_textdomainFound` : chargement déconseillé pour les extensions hébergées dans l’annuaire. Préserver le choix propre à FileWarden FR/EN, le repli anglais et les catalogues embarqués hors annuaire avant refactorisation. |
| includes/AuditHistory.php | 146 | `UnescapedDBParameter` : `$network`, liste de colonnes fixe selon disponibilité du schéma. |
| includes/AuditHistory.php | 167 | Même code : `$where`, conditions préparées par une méthode privée. Vérifier types, jokers, apostrophes, filtres et autorisations. |
| includes/AuditHistory.php | 201 | Même code : `$where` dans la purge, vérifier le périmètre signé et les transactions en plus de la préparation. |
| includes/BackupManifest.php | 47 | Même code : `$columns`, liste fixe de champs selon table/schéma. |
| includes/BackupManifest.php | 51 | Même code : `$where`, clauses fixes selon composants booléens validés. |
| includes/DocumentCollection.php | 27 | Même code : `$where`, une des deux clauses de collection fixes ; droits avant comptage/affichage. |
| includes/WordPressTables.php | 106 | Même code : `$extra`, condition préparée d’exclusion du checkpoint et identifiants préparés. |

Objectif du lot : reproduire le contrôle sur le ZIP installé, corriger les vrais problèmes, rendre la construction des requêtes plus explicite si nécessaire et ne justifier de faux positif qu’avec un chemin de données et un test probants. Ne pas échapper une requête SQL complète comme du HTML, ne pas désactiver globalement les règles et ne pas supprimer aveuglément le chargement des traductions.

## Corrections du candidat

Les sept constructions signalées sont remplacées par des requêtes fixes avec valeurs et identifiants préparés au point d’exécution. Les filtres du journal utilisent des valeurs liées et une liste CSV issue uniquement du catalogue fixe des événements ; les événements inconnus restent distincts, y compris les différences de casse. Les périmètres de composants sont des booléens liés. Les anciennes colonnes de sauvegarde restent sélectionnées explicitement selon le schéma. Aucun code ni contrôle PCP n’est ignoré.

L’appel déconseillé de traduction est retiré ; le catalogue embarqué choisi est toujours chargé à init, sans modifier les autres domaines. `Tested up to: 7.1` correspond à la branche testée. `Stable tag` reprend exactement `0.1.19-beta.1` : le parseur officiel PCP accepte ce format pour vérifier la cohérence du paquet. Cela ne transforme pas la Bêta en stable ; la convention de publication de l’annuaire sera revue séparément avant toute soumission.

PCP officiel 2.2.0, installé depuis WordPress.org, exécuté sans exclusions sur le ZIP local : zéro erreur et zéro avertissement sur WordPress 6.8 et 7.1.5. Tests ciblés : 133 combinaisons Action/Statut, événements inconnus/casse, apostrophes et jokers littéraux, créateurs importés, bornes de purge, rollback sur échec d’audit, refus de capacité, catalogues indépendants et repli, quatre périmètres de sauvegarde, collection corbeille et exclusion exacte du checkpoint. Résultats finaux et empreinte dans le suivi et `docs/validation/v0.1.19-beta.1-local.json` une fois la recette terminée.

Références primaires : [format du readme WordPress](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) ; [chargement des traductions depuis WordPress 4.6](https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/) ; [contrôle officiel du readme](https://github.com/WordPress/plugin-check/blob/trunk/includes/Checker/Checks/Plugin_Repo/Plugin_Readme_Check.php). Les règles de validation PCP et la publication effective dans l’annuaire restent deux étapes distinctes.
