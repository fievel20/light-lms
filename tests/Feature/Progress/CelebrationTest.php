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
    $this->scale = Competency::factory()->for($framework)->scale([1 => 'A', 2 => 'B', 3 => 'C'], threshold: 3)->create(['code' => 'C2', 'position' => 2]);

    $this->promotion = Promotion::factory()->for($framework)->create(['name' => 'DWWM 2026']);
    $this->learner = User::factory()->create();
    $this->promotion->learners()->attach($this->learner);
});

function validate(Competency $competency, int $value): void
{
    Assessment::factory()->for(test()->promotion)->byTrainer(test()->trainer, test()->learner)
        ->create(['competency_id' => $competency->id, 'value' => $value]);
}

it('célèbre une seule fois une compétence nouvellement acquise', function () {
    validate($this->binary, 1);
    validate($this->scale, 2);

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('celebrations', 1)
            ->where('celebrations.0', [
                'promotion' => 'DWWM 2026',
                'code' => 'C1',
                'name' => 'Maquetter',
            ]));

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('celebrations', []));
});

it('célèbre aussi depuis la page « Mes compétences »', function () {
    validate($this->scale, 3);

    $this->actingAs($this->learner)
        ->get(route('my-competencies.index'))
        ->assertInertia(fn (Assert $page) => $page->where('celebrations.0.code', 'C2'));

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('celebrations', []));
});

it('ne célèbre que les compétences de l\'apprenant connecté', function () {
    $other = User::factory()->create();
    $this->promotion->learners()->attach($other);
    validate($this->binary, 1);

    $this->actingAs($other)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('celebrations', []));

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('celebrations', 1));
});

it('ne célèbre pas une compétence redevenue non acquise', function () {
    validate($this->binary, 1);
    $this->travel(1)->minute();
    validate($this->binary, 0);

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('celebrations', []));
});
