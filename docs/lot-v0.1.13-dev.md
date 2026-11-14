# FileWarden 0.1.13-dev — publication du correctif validé

Le propriétaire a autorisé le 14 novembre 2026 la publication du candidat **0.1.12-dev.1** sous le numéro **0.1.13-dev**. Le [tableau des dix évolutions](lot-v0.1.12-dev.1.md), ses preuves et son ancienne empreinte restent conservés comme historique.

La publication ne change aucune fonctionnalité ni règle de sécurité. Le numéro du plugin, les descriptions, les notes et l’attente de version du test de mise à jour sont actualisés. Le schéma reste **6**. Le workflow de release distribue désormais l’artefact `filewarden-tested-candidate`, exactement installé et testé dans WordPress, avec un fichier SHA-256.

Vérifications réussies pour ce numéro : [CI 4420e1c](https://github.com/MikaPST/filewarden/actions/runs/43578019444), huit jobs réussis. PHP 8.2–8.4, 75 tests/130 assertions, huit tests JavaScript, PHPCS et audit Composer ; mises à jour depuis 0.1.11-dev/0.1.12-dev sur WordPress 6.8 et version courante, Chromium/Firefox, FR/EN. Aucun fichier des services, des permissions ou de l’interface n’a changé depuis le candidat validé ; seuls l’en-tête et la constante de version changent dans le code du plugin.

Publication en préparation : aucun tag 0.1.13-dev n’est créé avant la réussite des contrôles de la branche. Les anciennes releases restent intactes. Aucun déploiement sur l’hébergement utilisateur n’est effectué. La nouvelle demande d’accueil Documents fait l’objet d’une proposition UI/UX distincte.
