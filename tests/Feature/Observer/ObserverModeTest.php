<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use App\Support\ObserverMode;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->learner = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie']);
    $this->promotion = Promotion::factory()->create();
    $this->promotion->learners()->attach($this->learner);

    $this->path = LearningPath::factory()->create();
    $this->sequence = Sequence::factory()->create();
    $this->path->sequences()->attach($this->sequence);
    $this->promotion->learningPaths()->attach($this->path);
    $this->promotion->sequenceReleases()->create(['sequence_id' => $this->sequence->id, 'released_at' => now()]);
});

function observe(User $learner): void
{
    test()->post(route('observer.start', $learner))->assertRedirect(route('dashboard'));
}

it('montre au formateur l’espace de l’apprenant observé', function () {
    $this->actingAs($this->trainer);
    observe($this->learner);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learner/Dashboard')
            ->where('auth.user.id', $this->learner->id)
            ->where('observer.learner', 'Marie Curie'));

    $this->get(route('my-learning-paths.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('learner/LearningPaths')
            ->where('learningPaths.0.sequences.0.id', $this->sequence->id));
});

it('est en lecture seule', function () {
    $this->actingAs($this->trainer);
    observe($this->learner);

    $this->from(route('my-learning-paths.index'))
        ->put(route('my-learning-paths.complete', [$this->promotion, $this->path, $this->sequence]), ['completed' => true])
        ->assertRedirect(route('my-learning-paths.index'));

    $this->patch(route('profile.update'), ['first_name' => 'Pirate', 'last_name' => 'X', 'email' => 'x@example.com']);

    expect(SequenceCompletion::count())->toBe(0)
        ->and($this->learner->fresh()->first_name)->toBe('Marie');
});

it('ne consomme pas les célébrations de l’apprenant', function () {
    $framework = Framework::factory()->create();
    $competency = Competency::factory()->for($framework)->create();
    $this->promotion->framework()->associate($framework)->save();
    Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)
        ->create(['competency_id' => $competency->id, 'value' => 1]);

    $this->actingAs($this->trainer);
    observe($this->learner);
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('celebrations', []));

    expect(DB::table('competency_celebrations')->count())->toBe(0);
});

it('renvoie vers l’accueil apprenant depuis une page formateur', function () {
    $this->actingAs($this->trainer);
    observe($this->learner);

    $this->get(route('promotions.index'))->assertRedirect(route('dashboard'));
});

it('se quitte et ramène le formateur là où il était', function () {
    $this->actingAs($this->trainer)
        ->from(route('learners.edit', $this->learner))
        ->post(route('observer.start', $this->learner));

    $this->delete(route('observer.stop'))->assertRedirect(route('learners.edit', $this->learner));

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('trainer/Dashboard')
            ->where('auth.user.id', $this->trainer->id)
            ->where('observer', null));
    expect(session()->has(ObserverMode::LEARNER_KEY))->toBeFalse();
});

it('garde le formateur comme utilisateur de la session', function () {
    $this->actingAs($this->trainer);
    observe($this->learner);
    $this->get(route('dashboard'));

    expect(auth()->id())->toBe($this->trainer->id);
});

it('est réservé au formateur, et seulement pour un apprenant', function () {
    $this->actingAs($this->learner)
        ->post(route('observer.start', User::factory()->create()))
        ->assertForbidden();

    $this->actingAs($this->trainer)
        ->post(route('observer.start', User::factory()->trainer()->create()))
        ->assertNotFound();
});

it('s’arrête si l’apprenant observé n’existe plus', function () {
    $this->actingAs($this->trainer);
    observe($this->learner);
    $this->learner->delete();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('trainer/Dashboard')
            ->where('observer', null));
});
