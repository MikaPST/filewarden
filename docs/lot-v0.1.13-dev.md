# FileWarden 0.1.13-dev — publication du correctif validé

Le propriétaire a autorisé le 14 novembre 2026 la publication du candidat **0.1.12-dev.1** sous le numéro **0.1.13-dev**. Le [tableau des dix évolutions](lot-v0.1.12-dev.1.md), ses preuves et son ancienne empreinte restent conservés comme historique.

La publication ne change aucune fonctionnalité ni règle de sécurité. Le numéro du plugin, les descriptions, les notes et l’attente de version du test de mise à jour sont actualisés. Le schéma reste **6**. Le workflow de release distribue désormais l’artefact `filewarden-tested-candidate`, exactement installé et testé dans WordPress, avec un fichier SHA-256.

Vérifications réussies pour ce numéro : [CI 4420e1c](https://github.com/MikaPST/filewarden/actions/runs/43578019444), huit jobs réussis. PHP 8.2–8.4, 75 tests/130 assertions, huit tests JavaScript, PHPCS et audit Composer ; mises à jour depuis 0.1.11-dev/0.1.12-dev sur WordPress 6.8 et version courante, Chromium/Firefox, FR/EN. Aucun fichier des services, des permissions ou de l’interface n’a changé depuis le candidat validé ; seuls l’en-tête et la constante de version changent dans le code du plugin.

Publication effectuée après réussite des contrôles. Les anciennes releases restent intactes. Aucun déploiement sur l’hébergement utilisateur. La nouvelle demande d’accueil Documents reste une proposition UI/UX distincte.

## Publication vérifiée

- [Préversion 0.1.13-dev et fichiers joints](https://github.com/MikaPST/filewarden/releases/tag/v0.1.13-dev), tag au commit `7862027`.
- [Workflow de release réussi](https://github.com/MikaPST/filewarden/actions/runs/43578350911), incluant la matrice complète avant publication.
- ZIP : `filewarden-0.1.13-dev.zip`, 274 fichiers runtime, schéma 6.
- SHA-256 : `22f79af57e726eda6a167a66fc471245078016f8002771d36ead0f403196612c`. Archive publiée téléchargée, empreinte comparée au fichier joint et aux métadonnées GitHub ; chaque fichier décompressé correspond au tag.
- Le ZIP provient de l’artefact `filewarden-tested-candidate`, installé dans les vrais tests WordPress. Aucun outil de développement ni secret inclus.

Le premier essai de publication s’est arrêté sur une lecture trop précoce du message de confirmation des exports dans Firefox ; les fichiers téléchargés avaient passé les contrôles de contenu. Le parcours relancé après diagnostic est réussi. Le test attend désormais explicitement la fin de cette mise à jour asynchrone sur main (`685ba4b`), sans retirer d’assertion ni modifier le plugin publié.
