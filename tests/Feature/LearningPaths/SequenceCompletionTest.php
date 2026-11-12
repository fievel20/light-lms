<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->learner = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie']);
    $this->promotion = Promotion::factory()->create();
    $this->promotion->learners()->attach($this->learner);

    $this->path = LearningPath::factory()->create(['name' => 'Numérique du quotidien']);
    [$this->souris, $this->clavier, $this->mail] = Sequence::factory()->count(3)
        ->sequence(['title' => 'Souris'], ['title' => 'Clavier'], ['title' => 'Mail'])
        ->create();
    $this->path->sequences()->attach([
        $this->souris->id => ['position' => 1],
        $this->clavier->id => ['position' => 2],
        $this->mail->id => ['position' => 3],
    ]);
    $this->promotion->learningPaths()->attach($this->path);

    foreach ([$this->souris, $this->mail] as $sequence) {
        $this->promotion->sequenceReleases()->create(['sequence_id' => $sequence->id, 'released_at' => '2026-11-01 09:00:00']);
    }
});

function completeRoute(Promotion $promotion, LearningPath $path, Sequence $sequence): string
{
    return route('my-learning-paths.complete', [$promotion, $path, $sequence]);
}

describe('côté apprenant', function () {
    it('valide une séquence terminée, une seule fois, puis peut l’annuler', function () {
        $this->actingAs($this->learner)
            ->put(completeRoute($this->promotion, $this->path, $this->souris), ['completed' => true])
            ->assertRedirect();

        $completion = SequenceCompletion::sole();
        expect($completion->user_id)->toBe($this->learner->id)
            ->and($completion->promotion_id)->toBe($this->promotion->id)
            ->and($completion->completed_at->toDateTimeString())->toBe('2026-11-20 10:00:00');

        // Validated again later: the first date is kept.
        Carbon::setTestNow('2026-11-22 10:00:00');
        $this->put(completeRoute($this->promotion, $this->path, $this->souris), ['completed' => true]);
        expect(SequenceCompletion::sole()->completed_at->toDateTimeString())->toBe('2026-11-20 10:00:00');

        $this->put(completeRoute($this->promotion, $this->path, $this->souris), ['completed' => false]);
        expect(SequenceCompletion::count())->toBe(0);
    });

    it('refuse une séquence masquée, un parcours non affecté ou une autre promotion', function () {
        $otherPath = LearningPath::factory()->create();
        $otherPath->sequences()->attach($this->souris);
        $otherPromotion = Promotion::factory()->create();

        $this->actingAs($this->learner);

        $this->put(completeRoute($this->promotion, $this->path, $this->clavier), ['completed' => true])->assertNotFound();
        $this->put(completeRoute($this->promotion, $otherPath, $this->souris), ['completed' => true])->assertNotFound();
        $this->put(completeRoute($otherPromotion, $this->path, $this->souris), ['completed' => true])->assertNotFound();

        expect(SequenceCompletion::count())->toBe(0);
    });

    it('refuse de valider dans une promotion archivée', function () {
        $this->promotion->archive();

        $this->actingAs($this->learner)
            ->put(completeRoute($this->promotion, $this->path, $this->souris), ['completed' => true])
            ->assertSessionHasErrors('completed');

        expect(SequenceCompletion::count())->toBe(0);
    });

    it('est réservé aux apprenants', function () {
        $this->actingAs(User::factory()->trainer()->create())
            ->put(completeRoute($this->promotion, $this->path, $this->souris), ['completed' => true])
            ->assertForbidden();
    });

    it('montre les séquences terminées dans mes parcours et sur la séquence', function () {
        SequenceCompletion::create([
            'promotion_id' => $this->promotion->id,
            'sequence_id' => $this->mail->id,
            'user_id' => $this->learner->id,
            'completed_at' => '2026-11-15 14:00:00',
        ]);
        // Another learner's completion does not count.
        SequenceCompletion::create([
            'promotion_id' => $this->promotion->id,
            'sequence_id' => $this->souris->id,
            'user_id' => User::factory()->create()->id,
            'completed_at' => '2026-11-15 14:00:00',
        ]);

        $this->actingAs($this->learner)
            ->get(route('my-learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('learningPaths.0.sequences.0.completed_at', null)
                ->where('learningPaths.0.sequences.1.completed_at', '2026-11-15T14:00:00.000000Z'));

        $this->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->mail]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sequence.completed_at', '2026-11-15T14:00:00.000000Z')
                ->where('promotion.archived', false));
    });
});

describe('côté formateur', function () {
    it('liste les séquences ouvertes et qui les a terminées', function () {
        $ada = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $this->promotion->learners()->attach($ada);

        SequenceCompletion::create([
            'promotion_id' => $this->promotion->id,
            'sequence_id' => $this->souris->id,
            'user_id' => $ada->id,
            'completed_at' => '2026-11-15 14:00:00',
        ]);
        // Completed in another promotion: not counted here.
        SequenceCompletion::create([
            'promotion_id' => Promotion::factory()->create()->id,
            'sequence_id' => $this->mail->id,
            'user_id' => $this->learner->id,
            'completed_at' => '2026-11-15 14:00:00',
        ]);

        $this->actingAs(User::factory()->trainer()->create())
            ->get(route('promotions.sequence-completions.index', $this->promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promotions/SequenceCompletions')
                ->where('promotion.id', $this->promotion->id)
                ->has('learningPaths', 1)
                ->where('learningPaths.0.name', 'Numérique du quotidien')
                ->has('learningPaths.0.sequences', 2)
                ->where('learningPaths.0.sequences.0.title', 'Souris')
                ->where('learningPaths.0.sequences.1.title', 'Mail')
                ->has('learners', 2)
                ->where('learners.0.name', 'Marie Curie')
                ->where('learners.1.name', 'Ada Lovelace')
                ->where('completions', [
                    ['learner_id' => $ada->id, 'sequence_id' => $this->souris->id, 'completed_at' => '2026-11-15T14:00:00.000000Z'],
                ]));
    });

    it('est réservé au formateur', function () {
        $this->actingAs($this->learner)
            ->get(route('promotions.sequence-completions.index', $this->promotion))
            ->assertForbidden();
    });
});
