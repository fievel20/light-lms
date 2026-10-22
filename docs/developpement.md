# Développement

## Installation locale (Windows + Herd)

1. Installer [Laravel Herd](https://herd.laravel.com) : il fournit PHP 8.5, Composer, l'installeur Laravel et Node.
2. Cloner le dépôt puis, dans le dossier du projet :

```bash
composer setup
php artisan migrate:fresh --seed
composer dev
```

L'application est servie sur http://localhost:8000 ; `composer dev` lance aussi Vite (rechargement à chaud) et le worker de file d'attente (nécessaire pour les emails d'invitation).

### Git Bash / Tabby

Herd n'installe que des scripts `.bat`, que Git Bash ne trouve pas sous le nom `composer`. Ajouter dans `~/.bashrc` :

```bash
HERD_BIN="$HOME/.config/herd/bin"
export PATH="$HERD_BIN/php85:$HERD_BIN:/c/Program Files/nodejs:$PATH"
alias composer='php "$HERD_BIN/composer.phar"'
alias laravel='php "$HERD_BIN/laravel.phar"'
```

et dans `~/.bash_profile` : `test -f ~/.bashrc && . ~/.bashrc`. Sous PowerShell, rien à faire.

### Données de démonstration

`php artisan migrate:fresh --seed` crée :

| Données     | Détail                                                                              |
| ----------- | ----------------------------------------------------------------------------------- |
| Formateur   | `formateur@example.com` / `password`                                                |
| Apprenants  | `apprenant@example.com` / `password` + 8 apprenants générés + 2 invités non activés |
| Promotions  | DWWM 2026, CDA 2026, DWWM 2025 (archivée)                                           |
| Référentiel | Import de `docs/exemples/referentiel-dwwm.json`, affecté à DWWM 2026                |
| Évaluations | Autoévaluations et validations aléatoires dans DWWM 2026                            |

En local, les emails ne partent pas : ils sont écrits dans `storage/logs/laravel.log` (`MAIL_MAILER=log`), lien d'invitation compris.

## Commandes utiles

| Commande                                     | Usage                                                                          |
| -------------------------------------------- | ------------------------------------------------------------------------------ |
| `composer dev`                               | Serveur + Vite + file d'attente                                                |
| `php artisan test --compact`                 | Tests Pest                                                                     |
| `php artisan test --filter="..."`            | Un test ou un groupe                                                           |
| `composer test`                              | Pint (vérification), Larastan, tests                                           |
| `composer ci:check`                          | Tout ce que lance la CI, front compris                                         |
| `vendor/bin/pint`                            | Formate le PHP                                                                 |
| `npm run check:fix`                          | Formate et corrige le front (Vite+ : lint + format)                            |
| `npm run types:check`                        | `svelte-check` (types TypeScript et Svelte)                                    |
| `php artisan wayfinder:generate --with-form` | Régénère les routes typées après un changement de routes (fait aussi par Vite) |
| `php artisan lms:create-trainer`             | Crée le compte formateur                                                       |

## Méthode de travail : TDD

Chaque fonctionnalité suit le cycle :

1. **Rouge** : écrire les tests Pest du comportement attendu (en français, `it('…')`) et vérifier qu'ils échouent.
2. **Vert** : écrire le minimum de code pour les faire passer.
3. **Refactor** : nettoyer, puis `composer ci:check` doit être entièrement vert.

Les tests sont dans `tests/Feature/<Domaine>/` :

| Dossier       | Couvre                                                     |
| ------------- | ---------------------------------------------------------- |
| `Auth`        | Authentification du kit + inscription désactivée           |
| `Console`     | Commande `lms:create-trainer`                              |
| `Http`        | Middleware `trainer`                                       |
| `Models`      | Rôles, nom complet, valeurs et acquisition des compétences |
| `Promotions`  | CRUD, archivage, affectation du référentiel                |
| `Learners`    | Inscription, import CSV, invitations                       |
| `Frameworks`  | Référentiels, compétences, import JSON                     |
| `Assessments` | Autovalidation, validation, verrouillages                  |
| `Progress`    | Calcul de progression et tableaux de bord                  |

Outils de test utilisés : factories avec états (`trainer()`, `invited()`, `archived()`, `scale([...])`, `selfAssessed()`, `byTrainer()`), `assertInertia()` pour les props de page, `assertInertiaFlash()` pour les données flash, `Notification::fake()`, `UploadedFile::fake()->createWithContent()`, `travel()` pour le temps.

## Intégration continue

`.github/workflows/tests.yml` s'exécute à chaque push sur `main` et sur chaque pull request : installation (`composer setup`), puis `composer ci:check` (lint et formatage front, `svelte-check`, Pint, Larastan niveau 7, Pest).

## Git

- `main` reçoit des fusions en avance rapide de branches `feature/...` (une par lot de user stories) ;
- messages de commit en français, préfixés par la user story (`US2 : …`).

## Pièges connus

- **Larastan** a besoin de plus de mémoire que les 128 Mo du PHP de Herd : le script Composer passe `--memory-limit=1G`.
- **Dates** : l'application utilise `CarbonImmutable` (`Date::use()` dans `AppServiceProvider`) ; typer les propriétés de date des modèles en `CarbonImmutable`.
- **Règle `distinct`** de Laravel : avec deux jokers (`competencies.*.levels.*.value`) elle compare toutes les compétences entre elles ; l'unicité des niveaux est donc vérifiée par une règle maison (`CompetencyRules`).
- **Pages Inertia** : `ensure_pages_exist` est actif en test, une page référencée par un contrôleur doit exister.
- **Formulaires Inertia** : la confirmation avant envoi se fait avec la prop `onBefore` du composant `Form` (pas dans `options`) ; plusieurs formulaires sur une page utilisent `errorBag` pour ne pas partager leurs erreurs.
