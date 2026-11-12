<?php

use App\Models\LearningPath;
use App\Models\Sequence;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

/**
 * @return list<string>
 */
function sequenceTitles(LearningPath $path): array
{
    return $path->sequences()->pluck('title')->all();
}

it('est réservé au formateur', function () {
    $path = LearningPath::factory()->create();
    $sequence = Sequence::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('learning-paths.index'))->assertForbidden();
    $this->get(route('learning-paths.show', $path))->assertForbidden();
    $this->post(route('learning-paths.store'), ['name' => 'X'])->assertForbidden();
    $this->put(route('learning-paths.update', $path), ['name' => 'X'])->assertForbidden();
    $this->delete(route('learning-paths.destroy', $path))->assertForbidden();
    $this->post(route('learning-paths.sequences.store', $path), ['sequence_id' => $sequence->id])->assertForbidden();
    $this->put(route('learning-paths.sequences.reorder', $path), ['sequences' => []])->assertForbidden();
});

it('liste les parcours par nom avec leur nombre de séquences', function () {
    $path = LearningPath::factory()->create(['name' => 'Numérique de base']);
    $path->sequences()->attach(Sequence::factory()->count(2)->create()->pluck('id'));
    LearningPath::factory()->create(['name' => 'Coder']);

    $this->actingAs($this->trainer)
        ->get(route('learning-paths.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learning-paths/Index')
            ->has('learningPaths', 2)
            ->where('learningPaths.0.name', 'Coder')
            ->where('learningPaths.1.name', 'Numérique de base')
            ->where('learningPaths.1.sequences_count', 2));
});

it('crée un parcours', function () {
    $this->actingAs($this->trainer)
        ->get(route('learning-paths.create'))
        ->assertInertia(fn (Assert $page) => $page->component('learning-paths/Create'));

    $response = $this->actingAs($this->trainer)
        ->post(route('learning-paths.store'), ['name' => 'Numérique de base', 'description' => 'Premiers pas']);

    $path = LearningPath::sole();

    $response->assertRedirect(route('learning-paths.show', $path));
    expect($path->name)->toBe('Numérique de base')
        ->and($path->description)->toBe('Premiers pas');
});

it('valide le parcours', function (array $data, string $field) {
    LearningPath::factory()->create(['name' => 'Déjà pris']);

    $this->actingAs($this->trainer)
        ->post(route('learning-paths.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'nom manquant' => [['name' => ''], 'name'],
    'nom déjà utilisé' => [['name' => 'Déjà pris'], 'name'],
    'nom trop long' => [['name' => str_repeat('a', 256)], 'name'],
    'description trop longue' => [['name' => 'Ok', 'description' => str_repeat('a', 2001)], 'description'],
]);

it('modifie un parcours en gardant son nom unique', function () {
    $path = LearningPath::factory()->create(['name' => 'Numérique']);

    $this->actingAs($this->trainer)
        ->get(route('learning-paths.edit', $path))
        ->assertInertia(fn (Assert $page) => $page->component('learning-paths/Edit')->where('learningPath.name', 'Numérique'));

    $this->actingAs($this->trainer)
        ->put(route('learning-paths.update', $path), ['name' => 'Numérique', 'description' => 'Mise à jour'])
        ->assertRedirect(route('learning-paths.show', $path));

    expect($path->fresh()->description)->toBe('Mise à jour');
});

it('affiche un parcours avec ses séquences dans l’ordre et celles qu’on peut ajouter', function () {
    $path = LearningPath::factory()->create();
    $souris = Sequence::factory()->create(['title' => 'Souris']);
    $clavier = Sequence::factory()->create(['title' => 'Clavier']);
    Sequence::factory()->create(['title' => 'Coder']);
    $path->sequences()->attach([$souris->id => ['position' => 1], $clavier->id => ['position' => 2]]);

    $this->actingAs($this->trainer)
        ->get(route('learning-paths.show', $path))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learning-paths/Show')
            ->where('learningPath.id', $path->id)
            ->where('sequences', [
                ['id' => $souris->id, 'title' => 'Souris', 'summary' => $souris->summary],
                ['id' => $clavier->id, 'title' => 'Clavier', 'summary' => $clavier->summary],
            ])
            ->has('availableSequences', 1)
            ->where('availableSequences.0.title', 'Coder'));
});

it('ajoute une séquence à la fin du parcours, une seule fois', function () {
    $path = LearningPath::factory()->create();
    $souris = Sequence::factory()->create(['title' => 'Souris']);
    $clavier = Sequence::factory()->create(['title' => 'Clavier']);

    $this->actingAs($this->trainer)
        ->post(route('learning-paths.sequences.store', $path), ['sequence_id' => $souris->id])
        ->assertRedirect(route('learning-paths.show', $path));
    $this->actingAs($this->trainer)
        ->post(route('learning-paths.sequences.store', $path), ['sequence_id' => $clavier->id]);

    expect(sequenceTitles($path))->toBe(['Souris', 'Clavier']);

    $this->actingAs($this->trainer)
        ->post(route('learning-paths.sequences.store', $path), ['sequence_id' => $souris->id])
        ->assertSessionHasErrors('sequence_id');
    $this->actingAs($this->trainer)
        ->post(route('learning-paths.sequences.store', $path), ['sequence_id' => 999])
        ->assertSessionHasErrors('sequence_id');
});

it('réordonne les séquences du parcours', function () {
    $path = LearningPath::factory()->create();
    [$a, $b, $c] = Sequence::factory()->count(3)->sequence(['title' => 'A'], ['title' => 'B'], ['title' => 'C'])->create();
    $path->sequences()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2], $c->id => ['position' => 3]]);

    $this->actingAs($this->trainer)
        ->put(route('learning-paths.sequences.reorder', $path), ['sequences' => [$c->id, $a->id, $b->id]])
        ->assertRedirect(route('learning-paths.show', $path));

    expect(sequenceTitles($path))->toBe(['C', 'A', 'B']);
});

it('refuse un ordre qui ne reprend pas exactement les séquences du parcours', function (Closure $order) {
    $path = LearningPath::factory()->create();
    [$a, $b] = Sequence::factory()->count(2)->create();
    $outside = Sequence::factory()->create();
    $path->sequences()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $this->actingAs($this->trainer)
        ->put(route('learning-paths.sequences.reorder', $path), ['sequences' => $order($a, $b, $outside)])
        ->assertSessionHasErrors('sequences');
})->with([
    'incomplet' => [fn ($a, $b, $outside) => [$a->id]],
    'en double' => [fn ($a, $b, $outside) => [$a->id, $a->id]],
    'séquence étrangère' => [fn ($a, $b, $outside) => [$a->id, $outside->id]],
]);

it('retire une séquence du parcours sans la supprimer', function () {
    $path = LearningPath::factory()->create();
    [$a, $b, $c] = Sequence::factory()->count(3)->sequence(['title' => 'A'], ['title' => 'B'], ['title' => 'C'])->create();
    $path->sequences()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2], $c->id => ['position' => 3]]);

    $this->actingAs($this->trainer)
        ->delete(route('learning-paths.sequences.destroy', [$path, $b]))
        ->assertRedirect(route('learning-paths.show', $path));

    expect(sequenceTitles($path))->toBe(['A', 'C'])
        ->and($path->sequences()->pluck('position')->all())->toBe([1, 2])
        ->and(Sequence::count())->toBe(3);
});

it('supprime un parcours sans supprimer ses séquences', function () {
    $path = LearningPath::factory()->create();
    $path->sequences()->attach(Sequence::factory()->create());

    $this->actingAs($this->trainer)
        ->delete(route('learning-paths.destroy', $path))
        ->assertRedirect(route('learning-paths.index'));

    expect(LearningPath::count())->toBe(0)
        ->and(Sequence::count())->toBe(1);
});

it('indique les parcours qui utilisent une séquence', function () {
    $sequence = Sequence::factory()->create();
    LearningPath::factory()->create(['name' => 'Numérique'])->sequences()->attach($sequence);
    LearningPath::factory()->create(['name' => 'Autonomie'])->sequences()->attach($sequence);

    $this->actingAs($this->trainer)
        ->get(route('sequences.edit', $sequence))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths', ['Autonomie', 'Numérique']));

    $this->actingAs($this->trainer)
        ->get(route('sequences.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sequences.0.learning_paths', ['Autonomie', 'Numérique']));
});

it('retire une séquence supprimée de ses parcours', function () {
    $path = LearningPath::factory()->create();
    [$a, $b] = Sequence::factory()->count(2)->sequence(['title' => 'A'], ['title' => 'B'])->create();
    $path->sequences()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $this->actingAs($this->trainer)->delete(route('sequences.destroy', $a));

    expect(sequenceTitles($path))->toBe(['B']);
});
