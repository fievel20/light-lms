<?php

use App\Models\User;

it('redirige un visiteur vers la connexion', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('redirige un utilisateur connecté vers son tableau de bord', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
