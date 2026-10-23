<?php

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->framework = Framework::factory()->create();
    $this->binary = Competency::factory()->for($this->framework)->create(['code' => 'C1', 'position' => 1]);
    $this->scale = Competency::factory()->for($this->framework)
        ->scale([1 => 'Découverte', 2 => 'Guidé', 3 => 'Autonome'], threshold: 3)
        ->create(['code' => 'C2', 'position' => 2]);

    $this->promotion = Promotion::factory()->for($this->framework)->create();
    $this->learner = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Dupont']);
    $this->promotion->learners()->attach($this->learner);
});

describe('fiche de l\'apprenant', function () {
    it('affiche ses compétences avec autoévaluation et validation', function () {
        Assessment::factory()->selfAssessed($this->learner)->for($this->promotion)->create(['competency_id' => $this->scale->id, 'value' => 3]);
        Assessment::factory()->byTrainer($this->trainer, $this->learner)->for($this->promotion)->create(['competency_id' => $this->scale->id, 'value' => 2]);

        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Learner')
                ->where('learner.name', 'Marie Dupont')
                ->where('promotion.id', $this->promotion->id)
                ->has('competencies', 2)
                ->where('competencies.1.self.value', 3)
                ->where('competencies.1.trainer.value', 2)
                ->where('competencies.1.acquired', false));
    });

    it('refuse un apprenant qui n\'est pas dans la promotion', function () {
        $stranger = User::factory()->create();

        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.show', [$this->promotion, $stranger]))
            ->assertNotFound();
    });

    it('est réservée au formateur', function () {
        $this->actingAs($this->learner)
            ->get(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->assertForbidden();
    });
});

describe('validation', function () {
    it('enregistre la validation du formateur avec un commentaire', function () {
        $this->actingAs($this->trainer)
            ->from(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->learner, $this->scale]), [
                'value' => 3,
                'comment' => 'Projet final très réussi.',
            ])
            ->assertRedirect(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::sole();

        expect($assessment->source)->toBe(AssessmentSource::Trainer)
            ->and($assessment->assessor_id)->toBe($this->trainer->id)
            ->and($assessment->learner_id)->toBe($this->learner->id)
            ->and($assessment->value)->toBe(3)
            ->and($assessment->comment)->toBe('Projet final très réussi.');
    });

    it('rend la compétence acquise selon la dernière validation', function () {
        $url = route('promotions.learners.assessments.store', [$this->promotion, $this->learner, $this->binary]);

        $this->actingAs($this->trainer)->post($url, ['value' => 1]);
        $this->travel(1)->minute();
        $this->actingAs($this->trainer)->post($url, ['value' => 0, 'comment' => 'À retravailler']);

        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('competencies.0.trainer.value', 0)
                ->where('competencies.0.trainer.comment', 'À retravailler')
                ->where('competencies.0.acquired', false));

        expect(Assessment::count())->toBe(2);
    });

    it('valide la saisie', function (array $data, string $field) {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->learner, $this->scale]), $data)
            ->assertSessionHasErrors($field);

        expect(Assessment::count())->toBe(0);
    })->with([
        'valeur manquante' => [['value' => ''], 'value'],
        'valeur hors échelle' => [['value' => 9], 'value'],
        'commentaire trop long' => [['value' => 2, 'comment' => str_repeat('a', 2001)], 'comment'],
    ]);

    it('refuse une compétence hors du référentiel', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->learner, Competency::factory()->create()]), ['value' => 1])
            ->assertNotFound();
    });

    it('refuse un apprenant hors de la promotion', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, User::factory()->create(), $this->binary]), ['value' => 1])
            ->assertNotFound();
    });

    it('refuse la validation dans une promotion archivée', function () {
        $this->promotion->archive();

        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->learner, $this->binary]), ['value' => 1])
            ->assertSessionHasErrors('value');
    });

    it('est réservée au formateur', function () {
        $this->actingAs($this->learner)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->learner, $this->binary]), ['value' => 1])
            ->assertForbidden();
    });
});
