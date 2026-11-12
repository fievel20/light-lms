# Architecture

## Stack

| Couche        | Technologie                                                                                     |
| ------------- | ----------------------------------------------------------------------------------------------- |
| Backend       | Laravel 13, PHP 8.4+ ; authentification Fortify (connexion, mot de passe oublié, 2FA, passkeys) |
| Front         | Inertia 3 + Svelte 5 (runes) + TypeScript, Tailwind 4, composants shadcn-svelte                 |
| Routes typées | Laravel Wayfinder : `resources/js/actions` et `resources/js/routes`, générés (non versionnés)   |
| Base          | SQLite en local et en tests, MariaDB en production (VPS partagé avec d’autres sites)            |
| File          | Driver `database` : les emails d'invitation partent en file d'attente                           |
| Tests         | Pest 5 (tests de fonctionnalité sur base SQLite en mémoire)                                     |

L'application suit le starter kit Svelte officiel : les contrôleurs renvoient des pages Inertia (`Inertia::render('dossier/Page', [...props])`) qui correspondent à `resources/js/pages/dossier/Page.svelte`.

## Modèle de données

```mermaid
erDiagram
    users ||--o{ promotion_user : "inscrit"
    promotions ||--o{ promotion_user : "contient"
    frameworks ||--o{ promotions : "affecté à"
    frameworks ||--o{ competencies : "contient"
    competencies ||--o{ scale_levels : "niveaux"
    promotions ||--o{ assessments : ""
    users ||--o{ assessments : "apprenant / évaluateur"
    competencies ||--o{ assessments : ""

    users {
        string first_name
        string last_name
        string email
        string role "trainer | learner"
        string password "nullable tant que non activé"
        timestamp invited_at
        timestamp activated_at
    }
    promotions {
        string name "unique"
        bigint framework_id "nullable"
        date starts_on
        date ends_on
        timestamp archived_at
    }
    frameworks {
        string name "unique"
        text description
    }
    competencies {
        bigint framework_id
        string code "unique par référentiel"
        string name
        string type "binary | scale"
        smallint validation_threshold "scale uniquement"
        int position
    }
    scale_levels {
        bigint competency_id
        smallint value "unique par compétence"
        string label
    }
    assessments {
        bigint promotion_id
        bigint learner_id
        bigint competency_id
        bigint assessor_id
        string source "self | trainer"
        smallint value
        text comment
        timestamp created_at
    }
    sequences {
        string title
        string summary "nullable"
        mediumtext content "Markdown"
    }
    learning_paths {
        string name "unique"
        text description
    }
    learning_path_sequence {
        bigint learning_path_id
        bigint sequence_id "unique par parcours"
        int position
    }
    learning_path_promotion {
        bigint learning_path_id
        bigint promotion_id "unique par parcours"
    }
    sequence_releases {
        bigint promotion_id
        bigint sequence_id "unique par promotion"
        timestamp released_at
        bigint released_by
    }
    sequence_images {
        bigint sequence_id
        string path "disque privé local"
        string original_name
        string mime_type
        int size
    }
    icons {
        string name UK
        string path "disque privé local, SVG nettoyé"
        bool monochrome "suit la couleur du texte"
        int size
    }
```

Points clés :

- **`users.name`** n'existe plus en base : c'est un attribut calculé (`Prénom Nom`), ajouté à la sérialisation pour les composants du kit.
- **`role`** n'est pas assignable en masse ; il se fixe explicitement (commande `lms:create-trainer`, factory).
- **`assessments`** est en ajout seul : chaque autoévaluation ou validation crée une ligne. L'état courant est la ligne la plus récente par (apprenant, compétence, source). Les évaluations sont rattachées à la promotion : un apprenant inscrit dans deux promotions a deux historiques distincts.
- **`sequences`** (phase 2) : ateliers en Markdown, pas encore reliés aux promotions ; les parcours viennent ensuite (voir le [brief de la phase 2](brief-phase-2.md)).
- **`icons`** : bibliothèque commune à toutes les séquences, écrite `![nom](icon:{id})` dans le Markdown. À l'import, `App\Support\SvgIcon` nettoie le SVG (`enshrined/svg-sanitize` : scripts, événements, références distantes), remplace la taille fixe par une `viewBox` et repère les icônes d'une seule couleur. Le rendu (`IconRenderer`) produit une `<img class="icon">`, ou pour une icône monochrome un masque CSS rempli de `currentColor` ; la classe `.icon` (app.css) fixe la hauteur à 1,25 em.
- Clés étrangères : supprimer une compétence évaluée est bloqué en base (`restrictOnDelete`), tout comme supprimer un référentiel affecté à une promotion.

## Règles métier

| Règle                                              | Où                                                                          |
| -------------------------------------------------- | --------------------------------------------------------------------------- |
| Valeurs admises d'une compétence                   | `Competency::acceptsValue()` / `allowedValues()`                            |
| Compétence acquise                                 | `Competency::isAcquiredWith()` — validation formateur seulement             |
| Écart / à examiner                                 | `App\Support\CompetencyCell`                                                |
| Inscription d'un apprenant                         | `App\Actions\Learners\EnrollLearner`                                        |
| Import CSV                                         | `App\Actions\Learners\ImportLearners`                                       |
| Création / modification d'une compétence           | `App\Actions\Frameworks\SaveCompetency`                                     |
| Import JSON                                        | `App\Actions\Frameworks\ImportFramework`                                    |
| Validation d'une compétence (formulaire et import) | `App\Validation\CompetencyRules`                                            |
| Enregistrement d'une évaluation                    | `App\Actions\Assessments\RecordAssessment`                                  |
| Progression                                        | `App\Queries\PromotionProgress`, `LearnerCompetencies`, `LatestAssessments` |
| Compétences nouvellement acquises à fêter          | `App\Actions\Progress\PullCelebrations`                                     |
| Séquences lisibles par un apprenant                | `Sequence::isReadableBy()`, `App\Queries\LearnerLearningPaths`              |
| Rendu Markdown sûr (HTML échappé, liens externes)  | `App\Support\MarkdownRenderer`                                              |
| Fil d'activité de l'apprenant                      | `App\Queries\LearnerActivity`                                               |

Détail des définitions :

- **Acquise** : binaire → dernière validation = 1 ; échelle → dernière validation ≥ `validation_threshold`.
- **À examiner** : une autoévaluation existe et elle est plus récente que la dernière validation (ou il n'y a pas de validation).
- **Écart** : autoévaluation et validation existent et diffèrent.
- **Progression** : compétences acquises / compétences du référentiel, arrondi à l'entier ; moyenne simple des apprenants pour une promotion.

Verrous :

- promotion archivée → plus d'inscription ni d'évaluation ;
- compétence évaluée → type et valeurs de niveaux figés, suppression interdite ;
- promotion évaluée → référentiel figé ;
- parcours affecté → suppression interdite ; promotion archivée → plus d'affectation ni d'ouverture ;
- référentiel affecté → suppression interdite.

## Sécurité et accès

| Mécanisme            | Rôle                                                                                                                              |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Inscription publique | Désactivée (`Features::registration()` commenté dans `config/fortify.php`)                                                        |
| Middleware `trainer` | `EnsureUserIsTrainer` : 403 pour tout autre utilisateur                                                                           |
| Middleware `learner` | `EnsureUserIsLearner` : 403 pour tout autre utilisateur                                                                           |
| Liens d'invitation   | URL signée temporaire (7 jours), routes `guest` + `signed`, usage unique (refus si activé), limitée à 6 essais/min                |
| Appartenance         | Les contrôleurs vérifient qu'un apprenant appartient à la promotion (404 sinon) et qu'une compétence appartient à son référentiel |
| Compte non activé    | Mot de passe `null` : connexion impossible                                                                                        |

## Routes métier

| Accès        | Routes                                                                                                                                                                                                                                                                                                                                          |
| ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tous         | `GET /dashboard` (accueil formateur ou apprenant selon le rôle)                                                                                                                                                                                                                                                                                 |
| Formateur    | `promotions.*` (CRUD sans suppression, `archive`, `unarchive`, `progress` = `/promotions/{id}/suivi`)                                                                                                                                                                                                                                           |
| Formateur    | `current-promotion.update` (`PUT /promotion-active`) : promotion active mémorisée                                                                                                                                                                                                                                                               |
| Formateur    | `promotions.competencies.index / show` (`/promotions/{id}/competences[/{competence}]`)                                                                                                                                                                                                                                                          |
| Formateur    | `promotions.learners.index / show` (`/promotions/{id}/apprenants[/{apprenant}]`)                                                                                                                                                                                                                                                                |
| Formateur    | `promotions.learners.store / import / destroy`, `promotions.learners.assessments.store` (`quiet`)                                                                                                                                                                                                                                               |
| Formateur    | `learners.invitation.resend`                                                                                                                                                                                                                                                                                                                    |
| Formateur    | `frameworks.*`, `frameworks.import`, `frameworks.competencies.*` (liaisons imbriquées)                                                                                                                                                                                                                                                          |
| Formateur    | `sequences.*`, `sequences.preview` (aperçu JSON), `sequences.duplicate`, `sequences.images.store / show`, `learning-paths.*`, `learning-paths.sequences.store / reorder / destroy`, `promotions.learning-paths.index / store / destroy` (`/promotions/{id}/parcours`), `promotions.sequences.release`, `icons.index / store / update / destroy` |
| Apprenant    | `my-competencies.index` (`/mes-competences`), `my-competencies.store`                                                                                                                                                                                                                                                                           |
| Apprenant    | `my-learning-paths.index` (`/mes-parcours`), `my-learning-paths.show` (`/mes-parcours/{promotion}/{parcours}/{séquence}`)                                                                                                                                                                                                                       |
| Connecté     | `sequences.images.show` : formateur, ou apprenant à qui la séquence est ouverte (404 sinon) ; `icons.show` (SVG servi avec une CSP `default-src 'none'`)                                                                                                                                                                                        |
| Invité signé | `invitation.show`, `invitation.accept` (`/invitation/{user}`)                                                                                                                                                                                                                                                                                   |

Liste complète : `php artisan route:list --except-vendor`.

## Organisation du code

```
app/
├── Actions/          # opérations métier (Learners, Frameworks, Assessments)
├── Console/Commands/ # lms:create-trainer
├── Enums/            # Role, CompetencyType, AssessmentSource
├── Http/
│   ├── Controllers/  # contrôleurs fins : validation → action → redirection / page
│   ├── Middleware/   # trainer, learner
│   └── Requests/     # FormRequest (promotion, référentiel, compétence, apprenant)
├── Models/           # User, Promotion, Framework, Competency, ScaleLevel, Assessment, Sequence, SequenceImage, LearningPath, SequenceRelease
├── Notifications/    # LearnerInvitation (email, en file d'attente)
├── Queries/          # lectures complexes (progression, dernières évaluations)
├── Support/          # CompetencyCell, MarkdownRenderer
└── Validation/       # CompetencyRules (partagé formulaire / import JSON)

resources/js/
├── pages/            # une page Svelte par écran (promotions/, frameworks/, learner/, trainer/…)
├── components/       # composants métier (promotions/, learners/, frameworks/, assessments/, progress/)
│   └── ui/           # composants shadcn-svelte du kit
├── lib/              # utilitaires (dates FR, libellés de compétences, classes de champs)
└── types/            # types TypeScript des props
```

Conventions :

- les messages d'erreur métier sont levés comme `ValidationException` pour s'afficher dans les formulaires Inertia ;
- les notifications de succès passent par `Inertia::flash('toast', …)` ;
- le front est mobile first : cartes sur mobile, grilles ou tableaux à partir de `md`/`lg`, zones tactiles de 44 px (`h-11`) ramenées à 36 px (`sm:h-9`) sur grand écran.
