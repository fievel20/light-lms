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
    $this->c1 = Competency::factory()->for($framework)->create(['code' => 'C1', 'name' => 'Maquetter', 'position' => 1]);
    $this->c2 = Competency::factory()->for($framework)->scale([1 => 'A', 2 => 'B', 3 => 'C'], threshold: 2)
        ->create(['code' => 'C2', 'name' => 'Coder', 'position' => 2]);
    $this->c3 = Competency::factory()->for($framework)->create(['code' => 'C3', 'name' => 'Déployer', 'position' => 3]);

    $this->promotion = Promotion::factory()->for($framework)->create(['name' => 'DWWM 2026']);
    $this->anne = User::factory()->create(['first_name' => 'Anne', 'last_name' => 'Arnaud']);
    $this->bruno = User::factory()->create(['first_name' => 'Bruno', 'last_name' => 'Blanc']);
    $this->chloe = User::factory()->invited()->create(['first_name' => 'Chloé', 'last_name' => 'Caron']);
    $this->promotion->learners()->attach([$this->anne->id, $this->bruno->id, $this->chloe->id]);
});

function assessment(string $who, User $learner, Competency $competency, int $value, ?string $comment = null): void
{
    $factory = Assessment::factory()->for(test()->promotion);
    $factory = $who === 'self' ? $factory->selfAssessed($learner) : $factory->byTrainer(test()->trainer, $learner);
    $factory->create([
        'competency_id' => $competency->id,
        'value' => $value,
        'comment' => $comment,
        'created_at' => $who === 'self' ? now()->subMinute() : now(),
    ]);
}

describe('par compétence', function () {
    it('liste les compétences dans l\'ordre avec leurs compteurs', function () {
        assessment('trainer', $this->anne, $this->c1, 1);
        assessment('self', $this->bruno, $this->c1, 1);

        $this->actingAs($this->trainer)
            ->get(route('promotions.competencies.index', $this->promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Competencies')
                ->where('promotion.id', $this->promotion->id)
                ->has('competencies', 3)
                ->where('competencies.0.code', 'C1')
                ->where('competencies.0.acquired_count', 1)
                ->where('competencies.0.to_review', 1)
                ->where('competencies.1.code', 'C2')
                ->where('learnersCount', 3));
    });

    it('affiche tous les apprenants pour une compétence, avec la précédente et la suivante', function () {
        assessment('self', $this->anne, $this->c2, 3);
        assessment('trainer', $this->anne, $this->c2, 2, 'Bon travail');

        $this->actingAs($this->trainer)
            ->get(route('promotions.competencies.show', [$this->promotion, $this->c2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Competency')
                ->where('competency.code', 'C2')
                ->has('competency.levels', 3)
                ->where('previous', ['id' => $this->c1->id, 'code' => 'C1'])
                ->where('next', ['id' => $this->c3->id, 'code' => 'C3'])
                ->has('learners', 3)
                ->where('learners.0', [
                    'id' => $this->anne->id,
                    'name' => 'Anne Arnaud',
                    'activated' => true,
                    'self' => 3,
                    'trainer' => 2,
                    'comment' => 'Bon travail',
                    'acquired' => true,
                    'to_review' => false,
                ])
                ->where('learners.2.name', 'Chloé Caron')
                ->where('learners.2.activated', false)
                ->where('learners.2.self', null));
    });

    it('n\'a pas de précédente pour la première compétence', function () {
        $this->actingAs($this->trainer)
            ->get(route('promotions.competencies.show', [$this->promotion, $this->c1]))
            ->assertInertia(fn (Assert $page) => $page->where('previous', null)->where('next.code', 'C2'));
    });

    it('refuse une compétence d\'un autre référentiel', function () {
        $this->actingAs($this->trainer)
            ->get(route('promotions.competencies.show', [$this->promotion, Competency::factory()->create()]))
            ->assertNotFound();
    });
});

describe('par apprenant', function () {
    it('liste les apprenants avec leur progression', function () {
        assessment('trainer', $this->bruno, $this->c1, 1);
        assessment('self', $this->bruno, $this->c3, 1);

        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.index', $this->promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Learners')
                ->has('learners', 3)
                ->where('learners.1.name', 'Bruno Blanc')
                ->where('learners.1.percent', 33)
                ->where('learners.1.acquired', 1)
                ->where('learners.1.total', 3)
                ->where('learners.1.to_review', 1)
                ->where('learners.2.activated', false));
    });

    it('affiche les compétences d\'un apprenant dans l\'ordre, avec le précédent et le suivant', function () {
        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.show', [$this->promotion, $this->bruno]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Learner')
                ->where('learner.name', 'Bruno Blanc')
                ->has('competencies', 3)
                ->where('competencies.0.code', 'C1')
                ->where('previous', ['id' => $this->anne->id, 'name' => 'Anne Arnaud'])
                ->where('next', ['id' => $this->chloe->id, 'name' => 'Chloé Caron']));
    });
});

describe('enregistrement automatique', function () {
    it('n\'affiche pas de notification quand l\'enregistrement est silencieux', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.assessments.store', [$this->promotion, $this->anne, $this->c1]), ['value' => 1, 'quiet' => true])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlashMissing('toast');

        expect(Assessment::count())->toBe(1);
    });
});

it('est réservé au formateur', function () {
    $this->actingAs($this->anne);

    $this->get(route('promotions.competencies.index', $this->promotion))->assertForbidden();
    $this->get(route('promotions.competencies.show', [$this->promotion, $this->c1]))->assertForbidden();
    $this->get(route('promotions.learners.index', $this->promotion))->assertForbidden();
});
