<?php

use Illuminate\Support\Facades\Route;

/*
  Apache on Debian/Ubuntu maps /icons/ to its own images (mods-enabled/alias.conf):
  a route under it never reaches Laravel and answers 404 in production only.
*/
it('n’utilise pas les adresses réservées par Apache', function () {
    $reserved = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => '/'.ltrim($route->uri(), '/'))
        ->filter(fn (string $uri) => str_starts_with($uri, '/icons/'))
        ->values();

    expect($reserved)->toBeEmpty();
});

it('sert les icônes sous /icones', function () {
    expect(route('icons.index', absolute: false))->toBe('/icones')
        ->and(route('icons.show', 3, absolute: false))->toBe('/icones/3');
});
