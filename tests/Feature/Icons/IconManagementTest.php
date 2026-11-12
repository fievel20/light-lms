<?php

use App\Models\Icon;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

const ROCKET = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="640"><path d="M0 0h640v640H0z"/></svg>';

beforeEach(function () {
    Storage::fake('local');
    $this->trainer = User::factory()->trainer()->create();
});

function svgFile(string $name, string $content = ROCKET): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

it('est réservé au formateur', function () {
    $icon = Icon::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('icons.index'))->assertForbidden();
    $this->post(route('icons.store'), ['icons' => [svgFile('fusee.svg')]])->assertForbidden();
    $this->put(route('icons.update', $icon), ['name' => 'X', 'monochrome' => true])->assertForbidden();
    $this->delete(route('icons.destroy', $icon))->assertForbidden();
});

it('liste les icônes et les séquences qui les utilisent', function () {
    $rocket = Icon::factory()->create(['name' => 'Fusée']);
    $mouse = Icon::factory()->create(['name' => 'Souris']);
    Sequence::factory()->create(['title' => 'Envoyer un mail', 'content' => "![Fusée](icon:{$rocket->id}) Départ"]);
    // icon:1 must not match icon:12.
    Sequence::factory()->create(['title' => 'Autre', 'content' => "![x](icon:{$rocket->id}2)"]);

    $this->actingAs($this->trainer)
        ->get(route('icons.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('icons/Index')
            ->has('icons', 2)
            ->where('icons.0.name', 'Fusée')
            ->where('icons.0.markdown', "![Fusée](icon:{$rocket->id})")
            ->where('icons.0.url', route('icons.show', $rocket))
            ->where('icons.0.sequences', ['Envoyer un mail'])
            ->where('icons.1.id', $mouse->id)
            ->where('icons.1.sequences', []));
});

it('importe plusieurs SVG, nettoyés, nommés d’après le fichier', function () {
    Icon::factory()->create(['name' => 'Fusée']);

    $this->actingAs($this->trainer)
        ->post(route('icons.store'), ['icons' => [
            svgFile('Fusée.svg'),
            svgFile('loupe-bleue.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onload="alert(1)"><path fill="#00f" d="M0 0h1v1H0z"/><path fill="#f00" d="M1 1h1v1H1z"/></svg>'),
        ]])
        ->assertRedirect(route('icons.index'));

    $rocket = Icon::query()->where('name', 'Fusée 2')->sole();
    $magnifier = Icon::query()->where('name', 'loupe bleue')->sole();

    expect($rocket->monochrome)->toBeTrue()
        ->and($magnifier->monochrome)->toBeFalse()
        ->and(Storage::disk('local')->get($rocket->path))->toContain('viewBox="0 0 640 640"')
        ->and(Storage::disk('local')->get($magnifier->path))->not->toContain('onload');
});

it('refuse les fichiers qui ne sont pas des SVG, sans rien importer', function (UploadedFile $file) {
    $this->actingAs($this->trainer)
        ->post(route('icons.store'), ['icons' => [svgFile('ok.svg'), $file]])
        ->assertSessionHasErrors('icons.1');

    expect(Icon::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'png' => fn () => UploadedFile::fake()->image('photo.png'),
    'faux svg' => fn () => svgFile('faux.svg', '<html><script>alert(1)</script></html>'),
    'trop lourd' => fn () => svgFile('gros.svg', '<svg xmlns="http://www.w3.org/2000/svg">'.str_repeat('<!-- -->', 70_000).'</svg>'),
]);

it('renomme une icône et change son mode de couleur', function () {
    $icon = Icon::factory()->create(['name' => 'Fusée', 'monochrome' => true]);
    Icon::factory()->create(['name' => 'Souris']);

    $this->actingAs($this->trainer)
        ->put(route('icons.update', $icon), ['name' => 'Décollage', 'monochrome' => false])
        ->assertRedirect(route('icons.index'));

    expect($icon->fresh())->name->toBe('Décollage')->monochrome->toBeFalse();

    $this->put(route('icons.update', $icon), ['name' => 'Souris', 'monochrome' => false])
        ->assertSessionHasErrors('name');
});

it('supprime une icône et son fichier', function () {
    $icon = Icon::factory()->create();

    $this->actingAs($this->trainer)
        ->delete(route('icons.destroy', $icon))
        ->assertRedirect(route('icons.index'));

    expect(Icon::count())->toBe(0);
    Storage::disk('local')->assertMissing($icon->path);
});

it('sert le SVG à toute personne connectée, sans lui permettre d’exécuter du code', function () {
    $icon = Icon::factory()->create();

    $this->get(route('icons.show', $icon))->assertRedirect(route('login'));

    $response = $this->actingAs(User::factory()->create())
        ->get(route('icons.show', $icon))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'");
});
