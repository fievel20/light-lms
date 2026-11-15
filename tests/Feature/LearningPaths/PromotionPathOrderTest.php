<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->promotion = Promotion::factory()->create();
    [$this->souris, $this->mail, $this->web] = LearningPath::factory()->count(3)
        ->sequence(['name' => 'Souris'], ['name' => 'Mail'], ['name' => 'Web'])
        ->create();
});

function pathNames(Promotion $promotion): array
{
    return $promotion->learningPaths()->pluck('name')->all();
}

it('ajoute un parcours affecté à la fin de la liste', function () {
    $this->actingAs($this->trainer);

    foreach ([$this->web, $this->souris, $this->mail] as $path) {
        $this->post(route('promotions.learning-paths.store', $this->promotion), ['learning_path_id' => $path->id]);
    }

    expect(pathNames($this->promotion))->toBe(['Web', 'Souris', 'Mail']);
});

it('réordonne les parcours de la promotion', function () {
    $this->promotion->learningPaths()->attach([
        $this->souris->id => ['position' => 1],
        $this->mail->id => ['position' => 2],
        $this->web->id => ['position' => 3],
    ]);

    $this->actingAs($this->trainer)
        ->put(route('promotions.learning-paths.reorder', $this->promotion), [
            'learning_paths' => [$this->web->id, $this->souris->id, $this->mail->id],
        ])
        ->assertRedirect(route('promotions.learning-paths.index', $this->promotion));

    expect(pathNames($this->promotion))->toBe(['Web', 'Souris', 'Mail']);
});

it('n’influence pas l’ordre des autres promotions', function () {
    $other = Promotion::factory()->create();
    foreach ([$this->promotion, $other] as $promotion) {
        $promotion->learningPaths()->attach([
            $this->souris->id => ['position' => 1],
            $this->mail->id => ['position' => 2],
        ]);
    }

    $this->actingAs($this->trainer)->put(route('promotions.learning-paths.reorder', $this->promotion), [
        'learning_paths' => [$this->mail->id, $this->souris->id],
    ]);

    expect(pathNames($this->promotion))->toBe(['Mail', 'Souris'])
        ->and(pathNames($other))->toBe(['Souris', 'Mail']);
});

it('refuse une liste qui ne correspond pas aux parcours affectés', function (Closure $order) {
    $this->promotion->learningPaths()->attach([
        $this->souris->id => ['position' => 1],
        $this->mail->id => ['position' => 2],
    ]);

    $this->actingAs($this->trainer)
        ->put(route('promotions.learning-paths.reorder', $this->promotion), ['learning_paths' => $order($this)])
        ->assertSessionHasErrors('learning_paths');

    expect(pathNames($this->promotion))->toBe(['Souris', 'Mail']);
})->with([
    'incomplète' => [fn ($test) => [$test->mail->id]],
    'en double' => [fn ($test) => [$test->mail->id, $test->mail->id]],
    'non affecté' => [fn ($test) => [$test->mail->id, $test->web->id]],
]);

it('montre les parcours dans cet ordre au formateur et aux apprenants', function () {
    $learner = User::factory()->create();
    $this->promotion->learners()->attach($learner);
    $this->promotion->learningPaths()->attach([
        $this->web->id => ['position' => 1],
        $this->souris->id => ['position' => 2],
    ]);
    foreach ([$this->web, $this->souris] as $path) {
        $sequence = Sequence::factory()->create();
        $path->sequences()->attach($sequence);
        $this->promotion->sequenceReleases()->create(['sequence_id' => $sequence->id, 'released_at' => now()]);
    }

    $this->actingAs($this->trainer)
        ->get(route('promotions.learning-paths.index', $this->promotion))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.name', 'Web')
            ->where('learningPaths.1.name', 'Souris'));

    $this->actingAs($learner)
        ->get(route('my-learning-paths.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.name', 'Web')
            ->where('learningPaths.1.name', 'Souris'));
});

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('promotions.learning-paths.reorder', $this->promotion), ['learning_paths' => []])
        ->assertForbidden();
});
