# Light LMS

Un LMS léger pour suivre la validation de compétences par promotion : le formateur crée ses promotions, y inscrit des apprenants, leur affecte un référentiel de compétences ; les apprenants s'autoévaluent et le formateur valide.

Laravel 13 + Inertia / Svelte 5, développé en TDD, pensé mobile first. Interface en français.

## Fonctionnalités (MVP)

| Formateur (administrateur unique)                                              | Apprenant                                                 |
| ------------------------------------------------------------------------------ | --------------------------------------------------------- |
| Promotion active choisie dans le menu, vue d'ensemble                          | Activation du compte depuis l'email d'invitation          |
| Promotions : création, modification, archivage                                 | Autoévaluation en un geste (pastilles de niveau, filtres) |
| Apprenants : ajout manuel ou import CSV, invitation par email                  | Accueil : progression par promotion, activité récente     |
| Référentiels : compétences binaires ou à échelle, import JSON                  | Célébration des compétences nouvellement acquises         |
| Validation **par compétence** ou **par apprenant**, enregistrement automatique |                                                           |
| Suivi : carte de chaleur apprenants × compétences                              |                                                           |

## Démarrage rapide

Prérequis : PHP 8.4+, Composer, Node 22+ (par exemple via [Laravel Herd](https://herd.laravel.com)).

```bash
composer setup                 # dépendances, .env, clé, migrations, build
php artisan migrate:fresh --seed   # données de démonstration
composer dev                   # serveur, Vite, file d'attente → http://localhost:8000
```

Comptes de démonstration (mot de passe `password`) : `formateur@example.com` et `apprenant@example.com`.

En production, le compte formateur se crée avec :

```bash
php artisan lms:create-trainer
```

## Documentation

| Document                                       | Contenu                                                   |
| ---------------------------------------------- | --------------------------------------------------------- |
| [Guide utilisateur](docs/guide-utilisateur.md) | Parcours du formateur et de l'apprenant, écran par écran  |
| [Formats d'import](docs/formats-import.md)     | Fichier CSV des apprenants, fichier JSON des référentiels |
| [Architecture](docs/architecture.md)           | Modèle de données, règles métier, organisation du code    |
| [Développement](docs/developpement.md)         | Installation, commandes, TDD, CI, conventions             |
| [Feuille de route](docs/feuille-de-route.md)   | Ce qui est fait, ce qui reste (déploiement, finitions)    |
| [Brief initial](docs/brief.md)                 | Cahier des charges du MVP                                 |

## Qualité

```bash
composer test       # Pint, Larastan, Pest
composer ci:check   # tout ce que lance la CI (front compris)
```

## Licence

GPL v3, voir [LICENSE](LICENSE).
