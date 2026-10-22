<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->framework = Framework::factory()->create(['name' => 'Développeur Web']);
    $this->c1 = Competency::factory()->for($this->framework)->create(['position' => 1]);
    $this->c2 = Competency::factory()->for($this->framework)->create(['position' => 2]);

    $this->promotion = Promotion::factory()->for($this->framework)->create(['name' => 'DWWM 2026', 'starts_on' => '2026-09-01']);
    $this->learner = User::factory()->create();
    $this->promotion->learners()->attach($this->learner);
});

describe('tableau de bord de l\'apprenant (US10)', function () {
    it('affiche sa progression dans chacune de ses promotions', function () {
        Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)->create(['competency_id' => $this->c1->id, 'value' => 1]);
        Assessment::factory()->for($this->promotion)->selfAssessed($this->learner)->create(['competency_id' => $this->c2->id, 'value' => 1]);

        $old = Promotion::factory()->archived()->create(['name' => 'Ancienne', 'starts_on' => '2024-09-01']);
        $old->learners()->attach($this->learner);

        $this->actingAs($this->learner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('learner/Dashboard')
                ->has('promotions', 2)
                ->where('promotions.0.name', 'DWWM 2026')
                ->where('promotions.0.framework', 'Développeur Web')
                ->where('promotions.0.total', 2)
                ->where('promotions.0.acquired', 1)
                ->where('promotions.0.self_assessed', 1)
                ->where('promotions.0.to_review', 1)
                ->where('promotions.0.percent', 50)
                ->where('promotions.1.name', 'Ancienne')
                ->where('promotions.1.framework', null)
                ->where('promotions.1.percent', 0));
    });

    it('s\'affiche sans promotion', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('learner/Dashboard')->where('promotions', []));
    });
});

describe('tableau de bord du formateur', function () {
    it('résume ses promotions en cours', function () {
        $invited = User::factory()->invited()->create();
        $this->promotion->learners()->attach($invited);
        Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)->create(['competency_id' => $this->c1->id, 'value' => 1]);
        Assessment::factory()->for($this->promotion)->selfAssessed($this->learner)->create(['competency_id' => $this->c2->id, 'value' => 1]);
        Promotion::factory()->archived()->create();

        $this->actingAs($this->trainer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('trainer/Dashboard')
                ->has('promotions', 1)
                ->where('promotions.0.id', $this->promotion->id)
                ->where('promotions.0.name', 'DWWM 2026')
                ->where('promotions.0.framework', 'Développeur Web')
                ->where('promotions.0.learners', 2)
                ->where('promotions.0.average_percent', 25)
                ->where('promotions.0.to_review', 1)
                ->where('pendingInvitations', 1));
    });
});

describe('suivi d\'une promotion (US11)', function () {
    it('affiche la matrice apprenants × compétences', function () {
        Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)->create(['competency_id' => $this->c1->id, 'value' => 1]);

        $this->actingAs($this->trainer)
            ->get(route('promotions.progress', $this->promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promotions/Progress')
                ->where('promotion.name', 'DWWM 2026')
                ->where('framework.name', 'Développeur Web')
                ->has('competencies', 2)
                ->has('learners', 1)
                ->where('learners.0.percent', 50)
                ->where("learners.0.cells.{$this->c1->id}.acquired", true)
                ->where('summary.average_percent', 50));
    });

    it('est réservé au formateur', function () {
        $this->actingAs($this->learner)
            ->get(route('promotions.progress', $this->promotion))
            ->assertForbidden();
    });
});

describe('fiche apprenant (US12)', function () {
    it('résume la progression et signale écarts et autoévaluations à examiner', function () {
        Assessment::factory()->for($this->promotion)->selfAssessed($this->learner)->create(['competency_id' => $this->c1->id, 'value' => 1, 'created_at' => now()->subHour()]);
        Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)->create(['competency_id' => $this->c1->id, 'value' => 0]);
        Assessment::factory()->for($this->promotion)->selfAssessed($this->learner)->create(['competency_id' => $this->c2->id, 'value' => 1]);

        $this->actingAs($this->trainer)
            ->get(route('promotions.learners.show', [$this->promotion, $this->learner]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('competencies.0.gap', true)
                ->where('competencies.0.to_review', false)
                ->where('competencies.1.gap', false)
                ->where('competencies.1.to_review', true)
                ->where('summary', [
                    'total' => 2,
                    'acquired' => 0,
                    'self_assessed' => 2,
                    'to_review' => 1,
                    'gaps' => 1,
                    'percent' => 0,
                ]));
    });
});
