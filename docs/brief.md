# Light LMS — Brief MVP v1 : référentiels de compétences par promotion

## Stack

| Couche          | Choix                                                                             |
| --------------- | --------------------------------------------------------------------------------- |
| Backend         | Laravel 13 (PHP 8.4+), Fortify pour l'authentification                            |
| Front           | Starter kit Svelte : Inertia + Svelte 5 + TypeScript + Tailwind 4 + shadcn-svelte |
| Base de données | MySQL 8 en production, SQLite en local et en tests                                |
| Tests           | Pest (TDD : rouge → vert → refactor)                                              |
| Qualité         | Pint, Larastan (niveau 7), `vp check`, `svelte-check`                             |
| Dépôt / CI      | GitHub `fievel20/light-lms`, GitHub Actions (`composer ci:check`)                 |
| Hébergement     | VPS Ionos (offre de base), Nginx + PHP-FPM, Let's Encrypt                         |
| Emails          | SMTP Ionos                                                                        |
| Langue          | Français uniquement                                                               |
| Approche        | Mobile first                                                                      |

## Rôles

| Rôle                                   | Droits                                                                                                               |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| **Formateur** (unique, administrateur) | Gère promotions, apprenants, référentiels, compétences ; valide les compétences ; accède à tous les tableaux de bord |
| **Apprenant**                          | Se connecte via invitation, autovalide ses compétences, consulte son tableau de bord                                 |

L'inscription publique est désactivée : seul le formateur crée des comptes.

## User stories

### Gestion (formateur)

- **US1** — Le compte formateur unique est créé via `php artisan lms:create-trainer`.
- **US2** — Je crée / modifie / archive une **promotion** (nom, date de début, date de fin).
- **US3** — Je crée des **apprenants** (nom, prénom, email), un par un ou par **import CSV**, et je les affecte à une ou plusieurs promotions.
- **US4** — Un apprenant créé reçoit un **email d'invitation** (lien signé, à durée limitée) pour définir son mot de passe. Je peux renvoyer l'invitation.
- **US5** — Je crée un **référentiel** (liste plate de compétences), manuellement ou par **import JSON**.
- **US6** — Une compétence est **binaire** (acquis / non acquis) ou sur une **échelle définie par compétence**, avec un seuil d'acquisition.
- **US7** — J'**affecte un référentiel** (un seul) à une promotion.

### Évaluation

- **US8** — En tant qu'apprenant, j'**autovalide** une compétence du référentiel de ma promotion.
- **US9** — En tant que formateur, je **valide** une compétence d'un apprenant, avec un commentaire facultatif.

### Tableaux de bord

- **US10** — Apprenant : mes compétences avec autoévaluation, validation formateur et progression (%), par promotion.
- **US11** — Formateur, par **promotion** : matrice apprenants × compétences sur grand écran, cartes par apprenant sur mobile.
- **US12** — Formateur, par **apprenant** : détail des compétences et écart entre autoévaluation et validation.

## Modèle de données

```
users              id, first_name, last_name, email, password?, role (trainer|learner),
                   invited_at, activated_at
promotions         id, name, starts_on, ends_on, archived_at, framework_id?
promotion_user     promotion_id, user_id
frameworks         id, name, description                         (référentiel)
competencies       id, framework_id, code, name, description, type (binary|scale),
                   validation_threshold?, position
scale_levels       id, competency_id, value, label               (si type = scale)
assessments        id, learner_id, competency_id, promotion_id, assessor_id,
                   source (self|trainer), value, comment, created_at   (historique)
```

- Une compétence est **acquise** si la **dernière évaluation du formateur** atteint le seuil
  (`1` pour une binaire, `validation_threshold` pour une échelle — par défaut le niveau maximal).
- L'autovalidation est affichée à côté, à titre indicatif.

## Formats d'import

### Référentiel (JSON)

```json
{
    "name": "Développeur Web",
    "competencies": [
        { "code": "C1", "name": "Maquetter une application", "type": "binary" },
        {
            "code": "C2",
            "name": "Développer une interface",
            "type": "scale",
            "validation_threshold": 3,
            "levels": [
                { "value": 1, "label": "Découverte" },
                { "value": 2, "label": "Guidé" },
                { "value": 3, "label": "Autonome" },
                { "value": 4, "label": "Expert" }
            ]
        }
    ]
}
```

Validation avant import (codes uniques, niveaux requis pour `scale`), import dans une transaction : tout ou rien.

### Apprenants (CSV)

```
nom;prenom;email
Dupont;Marie;marie.dupont@example.com
```

Les emails déjà existants sont rattachés à la promotion sans créer de doublon. Un rapport indique les lignes créées, rattachées et en erreur.

## Feuille de route

0. ✅ Initialisation : Laravel + kit Svelte, Pest, français, inscription publique désactivée, CI
1. ✅ US1 — Rôles et commande de création du formateur
2. US2 — Promotions
3. US3/US4 — Apprenants, import CSV, invitations
4. US5/US6/US7 — Référentiels, compétences, import JSON
5. US8/US9 — Autovalidation et validation
6. US10–US12 — Tableaux de bord
7. Préparation du VPS Ionos et déploiement continu
