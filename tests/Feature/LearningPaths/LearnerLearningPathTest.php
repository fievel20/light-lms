<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceImage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->learner = User::factory()->create();
    $this->promotion = Promotion::factory()->create(['name' => 'Numérique 2026']);
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
});

function release(Promotion $promotion, Sequence $sequence, string $at = '2026-11-01 09:00:00'): void
{
    $promotion->sequenceReleases()->create(['sequence_id' => $sequence->id, 'released_at' => $at]);
}

it('est réservé aux apprenants', function () {
    $this->actingAs(User::factory()->trainer()->create())
        ->get(route('my-learning-paths.index'))
        ->assertForbidden();
});

it('liste les séquences ouvertes de ma promotion, dans l’ordre du parcours', function () {
    release($this->promotion, $this->mail, '2026-11-18 09:00:00');
    release($this->promotion, $this->souris);

    $this->actingAs($this->learner)
        ->get(route('my-learning-paths.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learner/LearningPaths')
            ->where('promotion.id', $this->promotion->id)
            ->has('learningPaths', 1)
            ->where('learningPaths.0.name', 'Numérique du quotidien')
            ->has('learningPaths.0.sequences', 2)
            ->where('learningPaths.0.sequences.0.title', 'Souris')
            ->where('learningPaths.0.sequences.0.is_new', false)
            ->where('learningPaths.0.sequences.1.title', 'Mail')
            ->where('learningPaths.0.sequences.1.is_new', true)
            ->missing('learningPaths.0.sequences.0.content'));
});

it('ne montre ni les parcours sans séquence ouverte, ni ceux d’une autre promotion', function () {
    $other = Promotion::factory()->create();
    $otherPath = LearningPath::factory()->create();
    $otherPath->sequences()->attach($this->clavier);
    $other->learningPaths()->attach($otherPath);
    release($other, $this->clavier);

    $this->actingAs($this->learner)
        ->get(route('my-learning-paths.index'))
        ->assertInertia(fn (Assert $page) => $page->where('learningPaths', []));
});

it('affiche une séquence ouverte avec les voisines ouvertes du parcours', function () {
    release($this->promotion, $this->souris);
    release($this->promotion, $this->mail);
    $this->clavier->update(['content' => 'masquée']);
    $this->souris->update(['content' => "1. Lance le jeu\n\n🎯 **12/20**"]);

    $this->actingAs($this->learner)
        ->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->souris]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learner/Sequence')
            ->where('sequence.title', 'Souris')
            ->where('sequence.html', fn (string $html) => str_contains($html, '🎯 <strong>12/20</strong>'))
            ->where('learningPath.name', 'Numérique du quotidien')
            ->where('previous', null)
            ->where('next.title', 'Mail'));
});

it('refuse une séquence masquée, hors parcours ou d’une promotion qui n’est pas la mienne', function () {
    release($this->promotion, $this->souris);
    $stranger = Promotion::factory()->create();
    $stranger->learningPaths()->attach($this->path);
    release($stranger, $this->clavier);
    $outside = Sequence::factory()->create();
    release($this->promotion, $outside);

    $this->actingAs($this->learner);

    $this->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->clavier]))->assertNotFound();
    $this->get(route('my-learning-paths.show', [$this->promotion, $this->path, $outside]))->assertNotFound();
    $this->get(route('my-learning-paths.show', [$stranger, $this->path, $this->clavier]))->assertNotFound();
});

it('garde lisibles les séquences ouvertes d’une promotion archivée', function () {
    release($this->promotion, $this->souris);
    $this->promotion->archive();

    $this->actingAs($this->learner)
        ->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->souris]))
        ->assertOk();
});

it('montre les images d’une séquence ouverte à l’apprenant, pas les autres', function () {
    $open = SequenceImage::factory()->for($this->souris)->create();
    $hidden = SequenceImage::factory()->for($this->clavier)->create();
    release($this->promotion, $this->souris);

    $this->actingAs($this->learner);

    $this->get(route('sequences.images.show', [$this->souris, $open]))->assertOk();
    $this->get(route('sequences.images.show', [$this->clavier, $hidden]))->assertNotFound();
});
