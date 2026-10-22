<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\PromotionProgress;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $framework = Framework::factory()->create();
    $this->c1 = Competency::factory()->for($framework)->create(['code' => 'C1', 'position' => 1]);
    $this->c2 = Competency::factory()->for($framework)->scale([1 => 'A', 2 => 'B', 3 => 'C'], threshold: 2)->create(['code' => 'C2', 'position' => 2]);
    $this->c3 = Competency::factory()->for($framework)->create(['code' => 'C3', 'position' => 3]);

    $this->promotion = Promotion::factory()->for($framework)->create();
    $this->marie = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Dupont']);
    $this->paul = User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Martin']);
    $this->promotion->learners()->attach([$this->marie->id, $this->paul->id]);
});

function assess(string $who, User $learner, Competency $competency, int $value, int $minutesAgo = 0): void
{
    $factory = Assessment::factory()->for(test()->promotion);
    $factory = $who === 'self' ? $factory->selfAssessed($learner) : $factory->byTrainer(test()->trainer, $learner);

    $factory->create([
        'competency_id' => $competency->id,
        'value' => $value,
        'created_at' => now()->subMinutes($minutesAgo),
    ]);
}

it('calcule l\'état de chaque cellule apprenant × compétence', function () {
    assess('self', $this->marie, $this->c1, 1, 10);
    assess('trainer', $this->marie, $this->c1, 1, 5);
    assess('self', $this->marie, $this->c2, 3, 10);
    assess('trainer', $this->marie, $this->c2, 1, 5);
    assess('self', $this->marie, $this->c3, 1, 1);

    $progress = app(PromotionProgress::class)->get($this->promotion);

    expect(collect($progress['competencies'])->pluck('code')->all())->toBe(['C1', 'C2', 'C3']);

    $marie = collect($progress['learners'])->firstWhere('id', $this->marie->id);

    expect($marie['name'])->toBe('Marie Dupont')
        ->and($marie['cells'][$this->c1->id])->toBe(['self' => 1, 'trainer' => 1, 'acquired' => true, 'gap' => false, 'to_review' => false])
        ->and($marie['cells'][$this->c2->id])->toBe(['self' => 3, 'trainer' => 1, 'acquired' => false, 'gap' => true, 'to_review' => false])
        ->and($marie['cells'][$this->c3->id])->toBe(['self' => 1, 'trainer' => null, 'acquired' => false, 'gap' => false, 'to_review' => true])
        ->and($marie['acquired'])->toBe(1)
        ->and($marie['self_assessed'])->toBe(3)
        ->and($marie['to_review'])->toBe(1)
        ->and($marie['percent'])->toBe(33);
});

it('signale une autoévaluation plus récente que la validation', function () {
    assess('trainer', $this->marie, $this->c1, 0, 10);
    assess('self', $this->marie, $this->c1, 1, 1);

    $marie = collect(app(PromotionProgress::class)->get($this->promotion)['learners'])->firstWhere('id', $this->marie->id);

    expect($marie['cells'][$this->c1->id]['to_review'])->toBeTrue();
});

it('trie les apprenants par nom et calcule les moyennes de la promotion', function () {
    assess('trainer', $this->marie, $this->c1, 1);
    assess('trainer', $this->marie, $this->c2, 2);
    assess('trainer', $this->paul, $this->c1, 1);

    $progress = app(PromotionProgress::class)->get($this->promotion);

    expect(collect($progress['learners'])->pluck('name')->all())->toBe(['Marie Dupont', 'Paul Martin'])
        ->and(collect($progress['learners'])->pluck('percent')->all())->toBe([67, 33])
        ->and($progress['summary'])->toBe([
            'learners' => 2,
            'competencies' => 3,
            'average_percent' => 50,
            'to_review' => 0,
        ])
        ->and(collect($progress['competencies'])->pluck('acquired_count')->all())->toBe([2, 1, 0]);
});

it('ignore les évaluations d\'une autre promotion', function () {
    $other = Promotion::factory()->for($this->c1->framework)->create();
    Assessment::factory()->for($other)->byTrainer($this->trainer, $this->marie)->create(['competency_id' => $this->c1->id, 'value' => 1]);

    $marie = collect(app(PromotionProgress::class)->get($this->promotion)['learners'])->firstWhere('id', $this->marie->id);

    expect($marie['acquired'])->toBe(0);
});

it('renvoie une progression vide sans référentiel', function () {
    $this->promotion->update(['framework_id' => null]);

    $progress = app(PromotionProgress::class)->get($this->promotion);

    expect($progress['competencies'])->toBe([])
        ->and(collect($progress['learners'])->pluck('percent')->all())->toBe([0, 0])
        ->and($progress['summary']['average_percent'])->toBe(0);
});
