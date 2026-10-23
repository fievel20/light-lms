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
    $this->binary = Competency::factory()->for($framework)->create(['code' => 'C1', 'name' => 'Maquetter', 'position' => 1]);
    $this->scale = Competency::factory()->for($framework)->scale([1 => 'Guidé', 2 => 'Autonome'], threshold: 2)->create(['code' => 'C2', 'name' => 'Coder', 'position' => 2]);

    $this->promotion = Promotion::factory()->for($framework)->create(['name' => 'DWWM 2026']);
    $this->learner = User::factory()->create();
    $this->promotion->learners()->attach($this->learner);
});

it('liste les validations du formateur, les plus récentes d\'abord', function () {
    Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)
        ->create(['competency_id' => $this->binary->id, 'value' => 1, 'created_at' => now()->subDays(2)]);
    Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)
        ->create(['competency_id' => $this->scale->id, 'value' => 1, 'comment' => 'Encore un effort', 'created_at' => now()->subDay()]);

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('activity', 2)
            ->where('activity.0.code', 'C2')
            ->where('activity.0.competency', 'Coder')
            ->where('activity.0.level', 'Guidé')
            ->where('activity.0.acquired', false)
            ->where('activity.0.comment', 'Encore un effort')
            ->where('activity.0.promotion', 'DWWM 2026')
            ->where('activity.1.code', 'C1')
            ->where('activity.1.level', 'Acquis')
            ->where('activity.1.acquired', true)
            ->missing('newBadges'));
});

it('n\'a plus de page de badges', function () {
    $this->actingAs($this->learner)->get('/mes-badges')->assertNotFound();
});

it('ignore les autoévaluations et l\'activité des autres apprenants', function () {
    $other = User::factory()->create();
    $this->promotion->learners()->attach($other);
    Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $other)->create(['competency_id' => $this->binary->id, 'value' => 1]);
    Assessment::factory()->for($this->promotion)->selfAssessed($this->learner)->create(['competency_id' => $this->binary->id, 'value' => 1]);

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('activity', []));
});

it('limite le fil aux 8 événements les plus récents', function () {
    foreach (range(1, 10) as $minutes) {
        Assessment::factory()->for($this->promotion)->byTrainer($this->trainer, $this->learner)
            ->create(['competency_id' => $this->binary->id, 'value' => 0, 'created_at' => now()->subMinutes($minutes)]);
    }

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('activity', 8));
});
