<?php

use App\Enums\Role;
use App\Models\User;

it('crée un apprenant par défaut', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::Learner)
        ->and($user->isTrainer())->toBeFalse()
        ->and($user->isLearner())->toBeTrue();
});

it('peut créer un formateur via la factory', function () {
    $user = User::factory()->trainer()->create();

    expect($user->role)->toBe(Role::Trainer)
        ->and($user->isTrainer())->toBeTrue()
        ->and($user->isLearner())->toBeFalse();
});

it('ne permet pas de modifier le rôle par assignation de masse', function () {
    $user = User::factory()->create();

    $user->update(['role' => Role::Trainer]);

    expect($user->fresh()->role)->toBe(Role::Learner);
});
