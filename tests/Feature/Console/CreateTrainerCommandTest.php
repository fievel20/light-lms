<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('crée le compte formateur à partir des options', function () {
    $this->artisan('lms:create-trainer', [
        '--first-name' => 'Raphaël',
        '--last-name' => 'Loyet',
        '--email' => 'formateur@example.com',
        '--password' => 'motdepasse-solide',
    ])
        ->expectsOutputToContain('Compte formateur créé')
        ->assertSuccessful();

    $trainer = User::sole();

    expect($trainer->first_name)->toBe('Raphaël')
        ->and($trainer->last_name)->toBe('Loyet')
        ->and($trainer->name)->toBe('Raphaël Loyet')
        ->and($trainer->email)->toBe('formateur@example.com')
        ->and($trainer->role)->toBe(Role::Trainer)
        ->and($trainer->email_verified_at)->not->toBeNull()
        ->and(Hash::check('motdepasse-solide', $trainer->password))->toBeTrue();
});

it('demande les informations manquantes de façon interactive', function () {
    $this->artisan('lms:create-trainer')
        ->expectsQuestion('Prénom', 'Raphaël')
        ->expectsQuestion('Nom', 'Loyet')
        ->expectsQuestion('Adresse email', 'formateur@example.com')
        ->expectsQuestion('Mot de passe', 'motdepasse-solide')
        ->assertSuccessful();

    expect(User::sole()->isTrainer())->toBeTrue();
});

it('refuse de créer un second formateur', function () {
    User::factory()->trainer()->create();

    $this->artisan('lms:create-trainer', [
        '--first-name' => 'Autre',
        '--last-name' => 'Formateur',
        '--email' => 'autre@example.com',
        '--password' => 'motdepasse-solide',
    ])
        ->expectsOutputToContain('Un compte formateur existe déjà')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuse des informations invalides', function (array $options, string $message) {
    $this->artisan('lms:create-trainer', [
        '--first-name' => 'Raphaël',
        '--last-name' => 'Loyet',
        '--email' => 'formateur@example.com',
        '--password' => 'motdepasse-solide',
        ...$options,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(User::count())->toBe(0);
})->with([
    'email invalide' => [['--email' => 'pas-un-email'], 'adresse e-mail valide'],
    'mot de passe trop court' => [['--password' => 'court'], '8 caractères'],
    'prénom vide' => [['--first-name' => ''], 'obligatoire'],
    'nom vide' => [['--last-name' => ''], 'obligatoire'],
]);

it('refuse un email déjà utilisé par un apprenant', function () {
    User::factory()->create(['email' => 'formateur@example.com']);

    $this->artisan('lms:create-trainer', [
        '--first-name' => 'Raphaël',
        '--last-name' => 'Loyet',
        '--email' => 'formateur@example.com',
        '--password' => 'motdepasse-solide',
    ])
        ->expectsOutputToContain('déjà utilisée')
        ->assertFailed();
});
