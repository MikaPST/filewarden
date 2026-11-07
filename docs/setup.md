# Installer FileWarden sur une préproduction

## Essayer le dépôt, l’import et la publication

1. Ouvre **FileWarden → Documents**, puis un dossier. Téléverse un PDF, JPEG ou PNG. Après validation, tu retrouves le fichier dans ce même dossier.
2. Pour réutiliser un média local, ouvre **Importer depuis la médiathèque** sur la page Documents. Tu peux aussi choisir **Copy to FileWarden** depuis la liste WordPress **Médias**. FileWarden crée une copie protégée. L’URL de l’original conserve ses droits d’accès et peut rester publique.
3. Ouvre le document. Les images s’affichent dans la fiche. Pour un PDF, FileWarden affiche la première page en image lorsque Imagick et la configuration de l’hébergeur permettent le rendu PDF. Le fichier complet reste téléchargeable.
4. Clique sur **Publish this folder**, puis copie le shortcode dans le widget **Code court** d’Elementor. Teste la page publiée depuis un compte autorisé et une fenêtre privée. La liste du dossier s’affiche dans un cadre du même site. FileWarden vérifie les droits à chaque affichage.
5. Exclure `wp-admin/admin-post.php` du cache de l’hébergeur ou du CDN. L’URL publique du site et celle de l’administration doivent avoir la même origine pour que le cadre s’affiche.

## Mode standard pour hébergement mutualisé

Après activation, ouvre **FileWarden**. Sans configuration explicite, le plugin crée `wp-content/filewarden-storage` à la première vérification. PHP doit disposer de Sodium et pouvoir écrire dans `wp-content`. Le site doit pouvoir joindre sa propre URL de contenu en HTTPS avec un certificat valide. Un CDN sur un autre nom d’hôte ou un serveur qui bloque ces requêtes peut empêcher la validation.

FileWarden tente d’installer une règle Apache `.htaccess` et vérifie le résultat avec deux fichiers témoins temporaires sans contenu sensible. Le témoin public doit être lisible et celui du stockage doit recevoir un refus. Sur Nginx, demander à l’hébergeur de bloquer l’accès direct au dossier dans sa configuration. Le diagnostic affiche la cause du refus. Vérifie aussi les alias et domaines alternatifs éventuels.

Les documents sont chiffrés avec Sodium. Pour sauvegarder ou déplacer le site, conserve ensemble le dossier chiffré et la base WordPress contenant la clé `filewarden_managed_key`. Protège ces sauvegardes. Sans la clé d’origine, les fichiers ne sont plus lisibles. Les téléchargements déchiffrent le document dans un fichier temporaire PHP supprimé à la fermeture. Ce répertoire temporaire doit être privé et offrir assez d’espace libre.

Les installations avec `FILEWARDEN_STORAGE_DIR` conservent leur mode hors racine. Ne change pas de mode sur une installation contenant des documents sans migration dédiée. Les instructions suivantes couvrent la configuration avancée hors racine et les essais communs.

Cette version `0.1.1-dev` sert à valider les fondations. Elle ne constitue pas la V1 complète et ne doit pas recevoir de documents confidentiels avant la validation de l’hébergement.

## 1. Préparer WordPress

- Utiliser un site de préproduction WordPress **classique**, distinct de la production. Le multisite est bloqué.
- Prévoir WordPress 6.8+, PHP 8.2+ avec `fileinfo`, MySQL 8.0+ ou MariaDB 10.11+ et HTTPS.
- Générer le ZIP avec `php tools/build.php`, puis installer `build/filewarden-0.1.1-dev.zip` depuis Extensions → Ajouter → Téléverser une extension.
- Activer FileWarden avec un administrateur. L’activation crée trois tables et attribue les capacités FileWarden au rôle administrateur. Les autres rôles ne reçoivent aucun nouveau droit par défaut.
- L’entrée **FileWarden** apparaît dans l’administration. Au départ, le stockage est bloqué.

## 2. Configurer un dossier réellement privé

Demander à l’hébergeur un dossier **hors de toutes les racines Web** du compte, accessible au processus PHP. Exemple Linux à adapter :

```text
/home/compte/public_html/          ← site WordPress
/home/compte/filewarden-private/   ← fichiers privés
```

Créer le dossier avec des permissions restrictives (`0700` sous Linux) et un propriétaire adapté au processus PHP. Aucun compte non fiable ne doit pouvoir y écrire. Le plugin crée les fichiers en `0600`. Sous Windows, appliquer des ACL équivalentes.

Dans `wp-config.php`, avant le chargement de WordPress :

```php
define( 'FILEWARDEN_STORAGE_DIR', '/home/compte/filewarden-private' );
```

Vérifier avec l’hébergeur que :

- Aucun domaine, sous-domaine, alias serveur, lien symbolique public ou CDN ne publie ce dossier.
- Les sauvegardes et répertoires temporaires contenant des documents ne sont pas publics.
- PHP peut lire et écrire ce dossier ; le serveur fournit une valeur correcte de `DOCUMENT_ROOT`.
- Les autres racines publiques sont déclarées, le cas échéant :

```php
define( 'FILEWARDEN_PUBLIC_ROOTS', array( '/home/compte/autre-site-public' ) );
```

Une fois ces vérifications réalisées, ajouter :

```php
define( 'FILEWARDEN_STORAGE_VERIFIED', true );
```

**Cette constante est une attestation de l’opérateur**, pas une détection automatique de toute la configuration Apache/Nginx/CDN. Les diagnostics vérifient les chemins canoniques, leur disponibilité, leur séparation des racines connues et refusent les entrées symboliques. Ils ne peuvent pas découvrir des alias serveur non déclarés. Refaire la vérification lors d’un changement d’hébergement ou de configuration ; retirer l’attestation jusqu’à sa validation.

Un hébergement qui ne permet aucun dossier privé compatible ne peut pas être utilisé pour ce prototype. Aucun repli dans la médiathèque ou protection uniquement par `.htaccess` n’est prévu.

## 3. Tester avec des fichiers factices

1. Vérifier le diagnostic depuis FileWarden.
2. Créer un dossier réservé à un rôle, puis un sous-dossier public.
3. Déposer un petit PDF, JPEG ou PNG. La limite par défaut est 50 Mo, également limitée par PHP et WordPress.
4. Ouvrir la fiche document puis télécharger le fichier.
5. Tester le lien déconnecté et avec un compte sans le rôle : il doit être refusé, même si le document ou le sous-dossier est public.
6. Tester avec un compte autorisé. Un compte ayant uniquement `filewarden_read_all_files` ne doit pas pouvoir déposer ou configurer.
7. Redéposer un fichier de même nom : il doit être refusé avec une invitation à renommer. Le remplacement par version n’existe pas encore.
8. Déposer un fichier PHP renommé en `.pdf` : il doit être refusé.
9. Retirer temporairement `FILEWARDEN_STORAGE_VERIFIED` : les dépôts et téléchargements doivent être bloqués. Les métadonnées déjà autorisées restent consultables.
10. Vérifier les journaux. `download_started` signale un début de transmission, pas la preuve d’un téléchargement intégral.

Exclure les routes FileWarden de tout cache serveur/CDN susceptible d’ignorer `Cache-Control: private, no-store`. Le serveur ne doit pas compresser les réponses binaires en ignorant leurs longueurs et plages.

## Limitations de cette étape

- Le dépôt avec une politique choisie nécessite **upload + manage_permissions**, ainsi que la lecture du dossier parent. La page d’administration exige aussi **manage_settings**.
- L’API permet les dépôts autorisés, mais il n’y a pas encore de formulaire frontend.
- La modification, la suppression, la corbeille et la création de version restent à développer. Ne pas supprimer manuellement un fichier référencé par la base.
- Les signatures/MIME ne constituent pas un antivirus ni une désactivation des fonctions actives d’un PDF. Le PDF original est envoyé en pièce jointe. Son aperçu est une image de la première page.
- Fichiers et base ne forment pas une transaction atomique entre deux systèmes. Un arrêt brutal peut laisser un fichier orphelin. Aucun nettoyage automatique des fichiers orphelins n’est lancé afin de protéger les documents actifs.
- Si l’écriture de l’audit final échoue après insertion, l’opération peut être conservée malgré un message d’erreur. Vérifier la liste avant de réessayer. Les téléchargements nécessitent un journal disponible.
- La purge utilise WP-Cron : elle dépend des visites ou d’un déclenchement serveur régulier, avec au maximum 10 000 suppressions d’anciens événements par exécution.
- Désactivation et désinstallation conservent documents, tables et capacités. La désactivation retire la tâche planifiée. Une politique de suppression explicite sera conçue séparément.

Noter les versions exactes de WordPress, PHP, base, serveur Web et les résultats. Le jalon `0.1.0` ne peut être déclaré accepté sans validation d’intégration et de stockage sur l’environnement cible.
