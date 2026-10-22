<?php

use App\Models\User;

it('ne propose pas de page d\'inscription publique', function () {
    $this->get('/register')->assertNotFound();
});

it('refuse la création de compte via le formulaire d\'inscription', function () {
    $this->post('/register', [
        'name' => 'Intrus',
        'email' => 'intrus@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::count())->toBe(0);
    $this->assertGuest();
});
