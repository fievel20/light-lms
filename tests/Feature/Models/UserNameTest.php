<?php

use App\Models\User;

it('expose le nom complet à partir du prénom et du nom', function () {
    $user = User::factory()->make(['first_name' => 'Marie', 'last_name' => 'Dupont']);

    expect($user->name)->toBe('Marie Dupont')
        ->and($user->toArray())->toMatchArray([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'name' => 'Marie Dupont',
        ]);
});

it('trie les utilisateurs par nom puis prénom', function () {
    User::factory()->create(['first_name' => 'Zoé', 'last_name' => 'Martin']);
    User::factory()->create(['first_name' => 'Anne', 'last_name' => 'Martin']);
    User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Durand']);

    expect(User::query()->orderByName()->get()->pluck('name')->all())
        ->toBe(['Paul Durand', 'Anne Martin', 'Zoé Martin']);
});
