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

| Évolution              | Contenu                                                                                                                                                                    |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Interface mobile first | Navigation par onglets sur mobile, tutoiement, écrans du kit traduits                                                                                                      |
| Expérience apprenant   | Accueil avec anneau de progression, pastilles de niveau, filtres, célébration des acquisitions                                                                             |
| Espace formateur       | Promotion active dans le menu, vues **par compétence** et **par apprenant**, validation en un geste avec enregistrement automatique, carte de chaleur                      |
| Charte « labo »        | Papier, encre indigo, vert acide, filets fins, Space Grotesk / Inter / JetBrains Mono                                                                                      |
| Retraits               | Validation rapide, système de badges, suppression de compte depuis le profil                                                                                               |
| Séquences réalisées    | L'apprenant valide chaque séquence terminée ; le formateur suit la promotion par séquence ou par apprenant                                                                 |
| Mode observateur       | Le formateur voit l'espace d'un apprenant tel qu'il le voit, en lecture seule                                                                                              |
| Comptes apprenants     | Le formateur corrige l'email d'un apprenant, lui envoie un lien de réinitialisation ou lui donne un mot de passe provisoire                                                |
| Import de dossiers     | Un dossier de cours glissé depuis l'ordinateur : une séquence par fichier Markdown, avec ses images et ses pièces jointes ; pièces jointes ajoutées aussi depuis l'éditeur |
| Compte démo            | Un faux apprenant par promotion, email inventé et mot de passe simple, remis à zéro à chaque déconnexion, absent des résultats                                             |
| Finitions et nettoyage | Favicon, composants et layouts du kit inutilisés supprimés                                                                                                                 |

Environ 260 tests Pest, Larastan niveau 7, CI GitHub Actions.

## Mise en production sur le VPS Ionos

Voir le [guide de déploiement](deploiement.md). En ligne depuis novembre 2026, avec déploiement continu.

- [x] Préparer le serveur : `deploy/server-setup.sh` (Apache, PHP-FPM 8.4, MariaDB, `.env`, worker, HTTPS).
- [x] Brancher le déploiement continu (secrets GitHub, `DEPLOY_ENABLED`).
- [x] Premier déploiement, compte formateur (`php8.4 artisan lms:create-trainer`), test réel d'une invitation.
- [ ] Mettre en place la sauvegarde quotidienne de la base et des images ([guide](deploiement.md#5-exploitation)).
- [ ] Après la fusion de la phase 2 : relancer `deploy/server-setup.sh` sur le VPS pour relever la limite d'envoi des images.

## Phase 2 : séquences pédagogiques et parcours

Voir le [brief de la phase 2](brief-phase-2.md) : séquences en Markdown avec images et icônes, parcours, affectation à une promotion et ouverture séquence par séquence.

| Étape | Contenu                                                                                                    | État |
| ----- | ---------------------------------------------------------------------------------------------------------- | ---- |
| 8     | Séquences : création, modification, suppression, éditeur Markdown avec barre d'outils et aperçu, rendu sûr | ✅   |
| 9     | Images, palette d'icônes, duplication                                                                      | ✅   |
| 10    | Parcours                                                                                                   | ✅   |
| 11    | Affectation à une promotion, ouverture séquence par séquence                                               | ✅   |
| 12    | Espace apprenant : parcours et lecture                                                                     | ✅   |
| 13    | Déploiement : limites d'envoi, sauvegarde des images                                                       | ✅   |
| 14    | Bibliothèque d'icônes SVG (Administration > Icônes), affichées à la taille du texte                        | ✅   |

## Quiz dans les séquences

Voir le [brief des quiz](brief-quiz.md) : un quiz par séquence, lancé par `[Quizz]`, une question à la fois avec chronomètre, tentatives multiples et suivi par le formateur.

| Étape | Contenu                                                                               | État |
| ----- | ------------------------------------------------------------------------------------- | ---- |
| 14    | Quiz d'une séquence : réglages, questions à la main et par JSON, images               | ✅   |
| 15    | Code court `[Quizz]`, lecteur dans la séquence, chronomètre, correction serveur, test | ✅   |
| 16    | Score, correction, tentatives multiples                                               | ✅   |
| 17    | Résultats de la promotion pour le formateur                                           | ✅   |
| 18    | Guide utilisateur, architecture                                                       | ✅   |

## Quiz express

Voir le [brief du quiz express](brief-quiz-express.md) : un quiz joué en salle, sans compte, par QR code ; le formateur fait défiler les questions, points et bonus de rapidité, classement puis correction.

| Étape | Contenu                                                                                                              | État |
| ----- | -------------------------------------------------------------------------------------------------------------------- | ---- |
| 19    | Quiz express : liste, éditeur partagé avec le quiz des séquences, import JSON, images                                | ✅   |
| 20    | Session : lancement, QR code, salle d'attente, nom du participant, retour après rechargement                         | ✅   |
| 21    | Déroulé en direct : questions, chronomètre, points et bonus, question suivante, texte en images, QR code sur le côté | ✅   |
| 22    | Classement, correction projetée et sur le téléphone, fin de session                                                  | ✅   |
| 23    | Sessions passées ; guide utilisateur, architecture                                                                   | ✅   |

## Idées pour la suite

- Historique complet des évaluations d'une compétence (déjà stocké, pas encore affiché).
- Export des résultats d'une promotion (CSV / PDF).
- Notification au formateur quand des autoévaluations sont à examiner.
- Réordonner les compétences d'un référentiel par glisser-déposer.
- Mode sombre retravaillé dans toute l'application.
