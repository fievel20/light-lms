<?php

use App\Models\Sequence;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

it('est réservé au formateur', function () {
    $sequence = Sequence::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('sequences.index'))->assertForbidden();
    $this->get(route('sequences.show', $sequence))->assertForbidden();
    $this->post(route('sequences.store'), ['title' => 'X', 'content' => 'X'])->assertForbidden();
    $this->put(route('sequences.update', $sequence), ['title' => 'X', 'content' => 'X'])->assertForbidden();
    $this->delete(route('sequences.destroy', $sequence))->assertForbidden();
    $this->post(route('sequences.preview'), ['content' => 'X'])->assertForbidden();
});

it('liste les séquences par titre', function () {
    Sequence::factory()->create(['title' => 'Utilisation souris']);
    Sequence::factory()->create(['title' => 'Envoyer un mail', 'summary' => 'Premier mail']);

    $this->actingAs($this->trainer)
        ->get(route('sequences.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sequences/Index')
            ->has('sequences', 2)
            ->where('sequences.0.title', 'Envoyer un mail')
            ->where('sequences.0.summary', 'Premier mail')
            ->where('sequences.1.title', 'Utilisation souris')
            ->missing('sequences.0.content'));
});

it('crée une séquence', function () {
    $this->actingAs($this->trainer)
        ->get(route('sequences.create'))
        ->assertInertia(fn (Assert $page) => $page->component('sequences/Create'));

    $response = $this->actingAs($this->trainer)->post(route('sequences.store'), [
        'title' => 'Envoyer un mail',
        'summary' => 'Premier mail depuis l’ordinateur',
        'content' => "1. Connecte-toi\n2. Ouvre Internet",
    ]);

    $sequence = Sequence::sole();

    $response->assertRedirect(route('sequences.show', $sequence));
    expect($sequence->title)->toBe('Envoyer un mail')
        ->and($sequence->summary)->toBe('Premier mail depuis l’ordinateur')
        ->and($sequence->content)->toBe("1. Connecte-toi\n2. Ouvre Internet");
});

it('valide la séquence', function (array $data, string $field) {
    $this->actingAs($this->trainer)
        ->post(route('sequences.store'), [...['title' => 'Ok', 'content' => 'Ok'], ...$data])
        ->assertSessionHasErrors($field);
})->with([
    'titre manquant' => [['title' => ''], 'title'],
    'titre trop long' => [['title' => str_repeat('a', 256)], 'title'],
    'résumé trop long' => [['summary' => str_repeat('a', 501)], 'summary'],
    'contenu manquant' => [['content' => ''], 'content'],
    'contenu trop long' => [['content' => str_repeat('a', 100_001)], 'content'],
]);

it('affiche une séquence rendue en HTML', function () {
    $sequence = Sequence::factory()->create(['title' => 'Souris', 'content' => "🎯 **12/20**\n\n<script>x</script>"]);

    $this->actingAs($this->trainer)
        ->get(route('sequences.show', $sequence))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sequences/Show')
            ->where('sequence.title', 'Souris')
            ->where('sequence.html', fn (string $html) => str_contains($html, '🎯 <strong>12/20</strong>')
                && ! str_contains($html, '<script>')));
});

it('modifie une séquence', function () {
    $sequence = Sequence::factory()->create();

    $this->actingAs($this->trainer)
        ->get(route('sequences.edit', $sequence))
        ->assertInertia(fn (Assert $page) => $page
            ->component('sequences/Edit')
            ->where('sequence.content', $sequence->content));

    $this->actingAs($this->trainer)
        ->put(route('sequences.update', $sequence), ['title' => 'Google Lens 2', 'summary' => null, 'content' => 'Nouveau'])
        ->assertRedirect(route('sequences.show', $sequence));

    expect($sequence->fresh())
        ->title->toBe('Google Lens 2')
        ->summary->toBeNull()
        ->content->toBe('Nouveau');
});

it('supprime une séquence', function () {
    $sequence = Sequence::factory()->create();

    $this->actingAs($this->trainer)
        ->delete(route('sequences.destroy', $sequence))
        ->assertRedirect(route('sequences.index'));

    expect(Sequence::count())->toBe(0);
});

it('rend l’aperçu du contenu en cours d’écriture', function () {
    $this->actingAs($this->trainer)
        ->postJson(route('sequences.preview'), ['content' => "## Étapes\n\n<b>x</b>"])
        ->assertOk()
        ->assertJson(fn ($json) => $json
            ->where('html', fn (string $html) => str_contains($html, '<h2>Étapes</h2>') && str_contains($html, '&lt;b&gt;')));

    $this->actingAs($this->trainer)
        ->postJson(route('sequences.preview'), ['content' => str_repeat('a', 100_001)])
        ->assertUnprocessable();
});
