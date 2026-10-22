# Light LMS

Un LMS léger permettant de créer une cohorte, des sessions de formation et faire de la validation de compétences.

Laravel 13 + Inertia/Svelte 5, développé en TDD, mobile first. Voir le [brief du MVP](docs/brief.md).

## Installation locale

Prérequis : PHP 8.4+, Composer, Node 22+ (par exemple via [Laravel Herd](https://herd.laravel.com)).

```bash
composer setup
composer dev
```

## Tests et qualité

```bash
composer test       # Pint, Larastan, Pest
composer ci:check   # tout ce que lance la CI (front compris)
```

## Licence

GPL v3, voir [LICENSE](LICENSE).
