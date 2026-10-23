<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $framework = Framework::factory()->create();
    $this->c1 = Competency::factory()->for($framework)->create(['position' => 1]);
    $this->c2 = Competency::factory()->for($framework)->create(['position' => 2]);
    $this->promotion = Promotion::factory()->for($framework)->create();

    $this->anne = User::factory()->create(['first_name' => 'Anne', 'last_name' => 'Arnaud']);
    $this->bruno = User::factory()->create(['first_name' => 'Bruno', 'last_name' => 'Blanc']);
    $this->chloe = User::factory()->create(['first_name' => 'Chloé', 'last_name' => 'Caron']);
    $this->promotion->learners()->attach([$this->anne->id, $this->bruno->id, $this->chloe->id]);
});

function toReview(User $learner, Competency $competency): void
{
    Assessment::factory()->for(test()->promotion)->selfAssessed($learner)
        ->create(['competency_id' => $competency->id, 'value' => 1]);
}

it('ouvre en mode rapide le premier apprenant qui a des autoévaluations à examiner', function () {
    toReview($this->chloe, $this->c1);
    toReview($this->bruno, $this->c2);

    $this->actingAs($this->trainer)
        ->get(route('promotions.review', $this->promotion))
        ->assertRedirect(route('promotions.learners.show', [$this->promotion, $this->bruno, 'mode' => 'rapide']));
});

it('revient au suivi quand tout est examiné', function () {
    $this->actingAs($this->trainer)
        ->get(route('promotions.review', $this->promotion))
        ->assertRedirect(route('promotions.progress', $this->promotion))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Tout est à jour, aucune autoévaluation à examiner ✨']);
});

it('indique sur la fiche le prochain apprenant à examiner', function () {
    toReview($this->anne, $this->c1);
    toReview($this->chloe, $this->c1);
    toReview($this->chloe, $this->c2);

    $this->actingAs($this->trainer)
        ->get(route('promotions.learners.show', [$this->promotion, $this->anne]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextToReview', ['id' => $this->chloe->id, 'name' => 'Chloé Caron', 'to_review' => 2]));

    $this->actingAs($this->trainer)
        ->get(route('promotions.learners.show', [$this->promotion, $this->chloe]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextToReview', ['id' => $this->anne->id, 'name' => 'Anne Arnaud', 'to_review' => 1]));
});

it('n\'indique aucun suivant quand il n\'y a rien d\'autre à examiner', function () {
    toReview($this->anne, $this->c1);

    $this->actingAs($this->trainer)
        ->get(route('promotions.learners.show', [$this->promotion, $this->anne]))
        ->assertInertia(fn (Assert $page) => $page->where('nextToReview', null));
});

it('est réservé au formateur', function () {
    $this->actingAs($this->anne)
        ->get(route('promotions.review', $this->promotion))
        ->assertForbidden();
});
