# Feuille de route

## MVP v1 : terminé

| Étape     | Contenu                                                                      | Commit    |
| --------- | ---------------------------------------------------------------------------- | --------- |
| 0         | Laravel 13 + kit Svelte, Pest, français, inscription publique désactivée, CI | `ad2dec7` |
| US1       | Rôles, middleware `trainer`, commande `lms:create-trainer`                   | `68a35ba` |
| US2       | Promotions : CRUD, archivage                                                 | `577ae95` |
| US3/US4   | Prénom/nom, apprenants par promotion, import CSV, invitations email          | `0b5d9cd` |
| US5–US7   | Référentiels, compétences binaires ou à échelle, import JSON, affectation    | `765a5ef` |
| US8/US9   | Autovalidation, validation formateur, historique, verrouillages              | `40e0723` |
| US10–US12 | Tableaux de bord apprenant et formateur, suivi de promotion, fiche apprenant | `ae02dda` |

219 tests Pest, Larastan niveau 7, CI GitHub Actions.

## Prochaine étape : mise en production sur le VPS Ionos

À faire :

- [ ] **Serveur** (Ubuntu 24.04, offre de base) : Nginx, PHP 8.4-FPM avec extensions Laravel, MySQL 8, Composer, Node 22 (build), certificat Let's Encrypt, pare-feu (SSH, HTTP, HTTPS).
- [ ] **Base** : base et utilisateur MySQL dédiés ; `DB_CONNECTION=mysql` dans le `.env` de production ; dimensionner PHP-FPM et MySQL pour la petite offre.
- [ ] **Emails** : SMTP Ionos (`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.ionos.fr`, port 587, adresse d'expédition à créer) ; tester l'envoi réel d'une invitation.
- [ ] **File d'attente** : worker permanent (`php artisan queue:work`) géré par Supervisor ou systemd, redémarré à chaque déploiement (`php artisan queue:restart`). Sans lui, les invitations ne partent pas.
- [ ] **Déploiement automatique** : workflow GitHub Actions déclenché sur `main` après la CI, déploiement SSH sans interruption (dossiers `releases/` + lien symbolique `current`, `.env` et `storage/` partagés) : `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`, `optimize`. Secrets (hôte, clé SSH) configurés dans GitHub par le propriétaire du dépôt.
- [ ] **Exploitation** : sauvegarde quotidienne de la base, rotation des logs, `APP_DEBUG=false`, `APP_ENV=production`.
- [ ] **Premier lancement** : `php artisan lms:create-trainer`.

## Finitions identifiées

- [ ] Traduire les écrans du kit encore en anglais : connexion, mot de passe oublié, profil, sécurité (2FA, passkeys), apparence, menu utilisateur.
- [ ] Retirer la suppression de compte du profil (au moins pour le formateur, qui est unique).
- [ ] Remplacer le logo et le nom du kit (`AppLogo`), le favicon.
- [ ] La disposition alternative `AppHeader` du kit (non utilisée) contient encore les liens d'origine.

## Idées pour la suite (hors MVP)

- Historique complet des évaluations d'une compétence (déjà stocké, pas encore affiché).
- Export des résultats d'une promotion (CSV / PDF).
- Notifications au formateur quand des autoévaluations sont à examiner.
- Réordonner les compétences d'un référentiel par glisser-déposer.
- Plusieurs formateurs.
