<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->promotion = Promotion::factory()->create();
    $this->path = LearningPath::factory()->create(['name' => 'Numérique']);
    [$this->souris, $this->mail] = Sequence::factory()->count(2)
        ->sequence(['title' => 'Souris'], ['title' => 'Mail'])
        ->create();
    $this->path->sequences()->attach([
        $this->souris->id => ['position' => 1],
        $this->mail->id => ['position' => 2],
    ]);
});

function assignPath(Promotion $promotion, LearningPath $path): void
{
    $promotion->learningPaths()->attach($path);
}

it('est réservé au formateur', function () {
    assignPath($this->promotion, $this->path);

    $this->actingAs(User::factory()->create());

    $this->get(route('promotions.learning-paths.index', $this->promotion))->assertForbidden();
    $this->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $this->path->id])->assertForbidden();
    $this->delete(route('promotions.learning-paths.destroy', [$this->promotion, $this->path]))->assertForbidden();
    $this->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => true])->assertForbidden();
});

it('affecte un parcours à une promotion, toutes ses séquences masquées', function () {
    $this->actingAs($this->trainer)
        ->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $this->path->id])
        ->assertRedirect(route('promotions.learning-paths.index', $this->promotion));

    expect($this->promotion->learningPaths()->pluck('name')->all())->toBe(['Numérique'])
        ->and($this->promotion->releasedSequenceIds())->toBe([]);
});

it('refuse d’affecter deux fois le même parcours, ou à une promotion archivée', function () {
    assignPath($this->promotion, $this->path);

    $this->actingAs($this->trainer)
        ->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $this->path->id])
        ->assertSessionHasErrors('learning_path_id');

    $archived = Promotion::factory()->create(['archived_at' => now()]);

    $this->actingAs($this->trainer)
        ->post(route('promotions.learning-paths.store', $archived), ['learning_path_id' => $this->path->id])
        ->assertSessionHasErrors('learning_path_id');
    expect($archived->learningPaths()->count())->toBe(0);
});

it('accepte plusieurs parcours par promotion', function () {
    $other = LearningPath::factory()->create(['name' => 'Autonomie']);

    $this->actingAs($this->trainer)->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $this->path->id]);
    $this->actingAs($this->trainer)->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $other->id]);

    expect($this->promotion->learningPaths()->count())->toBe(2);
});

it('ouvre puis masque une séquence pour une promotion', function () {
    assignPath($this->promotion, $this->path);
    Carbon::setTestNow('2026-11-20 09:30:00');

    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => true])
        ->assertRedirect();

    expect($this->promotion->releasedSequenceIds())->toBe([$this->souris->id])
        ->and($this->promotion->sequenceReleases()->sole()->released_at->toDateTimeString())->toBe('2026-11-20 09:30:00')
        ->and($this->promotion->sequenceReleases()->sole()->released_by)->toBe($this->trainer->id);

    // Opening again keeps the first opening date.
    Carbon::setTestNow('2026-11-21 10:00:00');
    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => true]);
    expect($this->promotion->sequenceReleases()->sole()->released_at->toDateTimeString())->toBe('2026-11-20 09:30:00');

    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => false]);

    expect($this->promotion->releasedSequenceIds())->toBe([]);
});

it('n’ouvre que les séquences des parcours de la promotion', function () {
    $outside = Sequence::factory()->create();
    assignPath($this->promotion, $this->path);

    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $outside]), ['visible' => true])
        ->assertNotFound();
});

it('ne change plus l’ouverture d’une promotion archivée', function () {
    assignPath($this->promotion, $this->path);
    $this->promotion->archive();

    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => true])
        ->assertSessionHasErrors('visible');

    expect($this->promotion->releasedSequenceIds())->toBe([]);
});

it('garde l’ouverture propre à chaque promotion', function () {
    $other = Promotion::factory()->create();
    assignPath($this->promotion, $this->path);
    assignPath($other, $this->path);

    $this->actingAs($this->trainer)
        ->put(route('promotions.sequences.release', [$this->promotion, $this->souris]), ['visible' => true]);

    expect($this->promotion->releasedSequenceIds())->toBe([$this->souris->id])
        ->and($other->releasedSequenceIds())->toBe([]);
});

it('affiche les parcours de la promotion avec l’état de chaque séquence', function () {
    assignPath($this->promotion, $this->path);
    LearningPath::factory()->create(['name' => 'Autonomie']);
    $this->promotion->sequenceReleases()->create([
        'sequence_id' => $this->mail->id,
        'released_at' => '2026-11-20 09:30:00',
    ]);

    $this->actingAs($this->trainer)
        ->get(route('promotions.learning-paths.index', $this->promotion))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('promotions/LearningPaths')
            ->where('promotion.id', $this->promotion->id)
            ->has('learningPaths', 1)
            ->where('learningPaths.0.name', 'Numérique')
            ->where('learningPaths.0.sequences.0.title', 'Souris')
            ->where('learningPaths.0.sequences.0.released_at', null)
            ->where('learningPaths.0.sequences.1.title', 'Mail')
            ->where('learningPaths.0.sequences.1.released_at', fn (string $date) => str_starts_with($date, '2026-11-20'))
            ->where('availableLearningPaths', [['id' => LearningPath::where('name', 'Autonomie')->value('id'), 'name' => 'Autonomie']]));
});

it('retire un parcours d’une promotion avec ses ouvertures, sauf celles d’un autre parcours affecté', function () {
    $other = LearningPath::factory()->create();
    $other->sequences()->attach($this->mail);
    assignPath($this->promotion, $this->path);
    assignPath($this->promotion, $other);
    foreach ([$this->souris, $this->mail] as $sequence) {
        $this->promotion->sequenceReleases()->create(['sequence_id' => $sequence->id, 'released_at' => now()]);
    }

    $this->actingAs($this->trainer)
        ->delete(route('promotions.learning-paths.destroy', [$this->promotion, $this->path]))
        ->assertRedirect(route('promotions.learning-paths.index', $this->promotion));

    expect($this->promotion->learningPaths()->pluck('learning_paths.id')->all())->toBe([$other->id])
        ->and($this->promotion->releasedSequenceIds())->toBe([$this->mail->id]);
});

it('bloque la suppression d’un parcours affecté à une promotion', function () {
    assignPath($this->promotion, $this->path);

    $this->actingAs($this->trainer)
        ->delete(route('learning-paths.destroy', $this->path))
        ->assertSessionHasErrors('learning_path');

    expect(LearningPath::count())->toBe(1);
});

it('indique les promotions d’un parcours', function () {
    assignPath($this->promotion, $this->path);

    $this->actingAs($this->trainer)
        ->get(route('learning-paths.show', $this->path))
        ->assertInertia(fn (Assert $page) => $page
            ->where('promotions', [['id' => $this->promotion->id, 'name' => $this->promotion->name]]));
});
