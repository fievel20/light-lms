<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'trainer'])
        ->get('/_test/espace-formateur', fn () => 'ok');
});

it('redirige un invité vers la connexion', function () {
    $this->get('/_test/espace-formateur')->assertRedirect(route('login'));
});

it('refuse l\'accès à un apprenant', function () {
    $this->actingAs(User::factory()->create())
        ->get('/_test/espace-formateur')
        ->assertForbidden();
});

it('autorise l\'accès au formateur', function () {
    $this->actingAs(User::factory()->trainer()->create())
        ->get('/_test/espace-formateur')
        ->assertOk();
});
