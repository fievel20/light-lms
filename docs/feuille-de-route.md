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

## Évolutions depuis le MVP

| Évolution              | Contenu                                                                                                                                               |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Interface mobile first | Navigation par onglets sur mobile, tutoiement, écrans du kit traduits                                                                                 |
| Expérience apprenant   | Accueil avec anneau de progression, pastilles de niveau, filtres, célébration des acquisitions                                                        |
| Espace formateur       | Promotion active dans le menu, vues **par compétence** et **par apprenant**, validation en un geste avec enregistrement automatique, carte de chaleur |
| Charte « labo »        | Papier, encre indigo, vert acide, filets fins, Space Grotesk / Inter / JetBrains Mono                                                                 |
| Retraits               | Validation rapide, système de badges, suppression de compte depuis le profil                                                                          |
| Finitions et nettoyage | Favicon, composants et layouts du kit inutilisés supprimés                                                                                            |

Environ 240 tests Pest, Larastan niveau 7, CI GitHub Actions.

## Prochaine étape : mise en production sur le VPS Ionos

Voir le [guide de déploiement](deploiement.md). À faire :

- [ ] Préparer le serveur (Nginx, PHP 8.4-FPM, MySQL 8, Supervisor, certificat Let's Encrypt, pare-feu).
- [ ] Créer la base et l'utilisateur MySQL, le `.env` de production.
- [ ] Configurer le SMTP Ionos et tester l'envoi réel d'une invitation.
- [ ] Lancer le worker de file d'attente (Supervisor).
- [ ] Brancher le déploiement automatique (GitHub Actions, secrets SSH).
- [ ] Mettre en place la sauvegarde quotidienne de la base.
- [ ] Créer le compte formateur : `php artisan lms:create-trainer`.

## Idées pour la suite

- Historique complet des évaluations d'une compétence (déjà stocké, pas encore affiché).
- Export des résultats d'une promotion (CSV / PDF).
- Notification au formateur quand des autoévaluations sont à examiner.
- Réordonner les compétences d'un référentiel par glisser-déposer.
- Mode sombre retravaillé dans toute l'application.
