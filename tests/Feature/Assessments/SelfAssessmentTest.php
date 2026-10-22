<?php

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->framework = Framework::factory()->create(['name' => 'Développeur Web']);
    $this->binary = Competency::factory()->for($this->framework)->create(['code' => 'C1', 'position' => 1]);
    $this->scale = Competency::factory()->for($this->framework)
        ->scale([1 => 'Découverte', 2 => 'Guidé', 3 => 'Autonome'], threshold: 2)
        ->create(['code' => 'C2', 'position' => 2]);

    $this->promotion = Promotion::factory()->for($this->framework)->create(['name' => 'DWWM 2026']);
    $this->learner = User::factory()->create();
    $this->promotion->learners()->attach($this->learner);
});

describe('page « Mes compétences »', function () {
    it('affiche les compétences du référentiel de la promotion avec les dernières évaluations', function () {
        $trainer = User::factory()->trainer()->create();
        Assessment::factory()->selfAssessed($this->learner)->for($this->promotion)->create(['competency_id' => $this->scale->id, 'value' => 1, 'created_at' => now()->subDay()]);
        Assessment::factory()->selfAssessed($this->learner)->for($this->promotion)->create(['competency_id' => $this->scale->id, 'value' => 3]);
        Assessment::factory()->byTrainer($trainer, $this->learner)->for($this->promotion)->create(['competency_id' => $this->scale->id, 'value' => 2, 'comment' => 'Bien']);

        $this->actingAs($this->learner)
            ->get(route('my-competencies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('learner/Competencies')
                ->where('promotion.name', 'DWWM 2026')
                ->where('framework.name', 'Développeur Web')
                ->has('promotions', 1)
                ->has('competencies', 2)
                ->where('competencies.0.code', 'C1')
                ->where('competencies.0.self', null)
                ->where('competencies.0.trainer', null)
                ->where('competencies.0.acquired', false)
                ->where('competencies.1.code', 'C2')
                ->where('competencies.1.self.value', 3)
                ->where('competencies.1.trainer.value', 2)
                ->where('competencies.1.trainer.comment', 'Bien')
                ->where('competencies.1.acquired', true));
    });

    it('n\'affiche que ses propres évaluations', function () {
        $other = User::factory()->create();
        $this->promotion->learners()->attach($other);
        Assessment::factory()->selfAssessed($other)->for($this->promotion)->create(['competency_id' => $this->binary->id, 'value' => 1]);

        $this->actingAs($this->learner)
            ->get(route('my-competencies.index'))
            ->assertInertia(fn (Assert $page) => $page->where('competencies.0.self', null));
    });

    it('permet de choisir parmi ses promotions', function () {
        $other = Promotion::factory()->for(Framework::factory())->create(['name' => 'CDA 2027', 'starts_on' => now()->addYear()]);
        $other->learners()->attach($this->learner);

        $this->actingAs($this->learner)
            ->get(route('my-competencies.index', ['promotion' => $this->promotion->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('promotions', 2)
                ->where('promotion.id', $this->promotion->id));
    });

    it('refuse une promotion dont l\'apprenant ne fait pas partie', function () {
        $foreign = Promotion::factory()->create();

        $this->actingAs($this->learner)
            ->get(route('my-competencies.index', ['promotion' => $foreign->id]))
            ->assertNotFound();
    });

    it('gère l\'absence de promotion ou de référentiel', function () {
        $alone = User::factory()->create();

        $this->actingAs($alone)
            ->get(route('my-competencies.index'))
            ->assertInertia(fn (Assert $page) => $page->where('promotion', null)->where('competencies', []));

        $this->promotion->update(['framework_id' => null]);

        $this->actingAs($this->learner)
            ->get(route('my-competencies.index'))
            ->assertInertia(fn (Assert $page) => $page->where('framework', null)->where('competencies', []));
    });

    it('est réservée aux apprenants', function () {
        $this->actingAs(User::factory()->trainer()->create())
            ->get(route('my-competencies.index'))
            ->assertForbidden();
    });
});

describe('autovalidation', function () {
    it('enregistre l\'autoévaluation de l\'apprenant', function () {
        $this->actingAs($this->learner)
            ->from(route('my-competencies.index'))
            ->post(route('my-competencies.store', [$this->promotion, $this->scale]), ['value' => 2])
            ->assertRedirect(route('my-competencies.index'))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::sole();

        expect($assessment->source)->toBe(AssessmentSource::Self)
            ->and($assessment->learner_id)->toBe($this->learner->id)
            ->and($assessment->assessor_id)->toBe($this->learner->id)
            ->and($assessment->promotion_id)->toBe($this->promotion->id)
            ->and($assessment->value)->toBe(2);
    });

    it('conserve l\'historique des autoévaluations', function () {
        $this->actingAs($this->learner)->post(route('my-competencies.store', [$this->promotion, $this->binary]), ['value' => 1]);
        $this->actingAs($this->learner)->post(route('my-competencies.store', [$this->promotion, $this->binary]), ['value' => 0]);

        expect(Assessment::count())->toBe(2);
    });

    it('refuse une valeur hors échelle', function (string $competency, mixed $value) {
        $this->actingAs($this->learner)
            ->post(route('my-competencies.store', [$this->promotion, $this->{$competency}]), ['value' => $value])
            ->assertSessionHasErrors('value');

        expect(Assessment::count())->toBe(0);
    })->with([
        'binaire à 2' => ['binary', 2],
        'échelle à 4' => ['scale', 4],
        'échelle à 0' => ['scale', 0],
        'valeur manquante' => ['scale', ''],
        'texte' => ['scale', 'expert'],
    ]);

    it('refuse une compétence hors du référentiel de la promotion', function () {
        $foreign = Competency::factory()->create();

        $this->actingAs($this->learner)
            ->post(route('my-competencies.store', [$this->promotion, $foreign]), ['value' => 1])
            ->assertNotFound();
    });

    it('refuse une promotion dont l\'apprenant ne fait pas partie', function () {
        $other = Promotion::factory()->for($this->framework)->create();

        $this->actingAs($this->learner)
            ->post(route('my-competencies.store', [$other, $this->binary]), ['value' => 1])
            ->assertNotFound();
    });

    it('refuse l\'autovalidation dans une promotion archivée', function () {
        $this->promotion->archive();

        $this->actingAs($this->learner)
            ->post(route('my-competencies.store', [$this->promotion, $this->binary]), ['value' => 1])
            ->assertSessionHasErrors(['value' => 'Cette promotion est archivée : les évaluations sont closes.']);
    });

    it('est réservée aux apprenants', function () {
        $this->actingAs(User::factory()->trainer()->create())
            ->post(route('my-competencies.store', [$this->promotion, $this->binary]), ['value' => 1])
            ->assertForbidden();
    });
});
