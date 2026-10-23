<?php

use App\Actions\Progress\SyncBadges;
use App\Enums\Badge;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $framework = Framework::factory()->create();

    // 4 compétences : 3 binaires et une échelle 1-3 (seuil 2).
    $this->competencies = Competency::factory()->count(3)->for($framework)
        ->sequence(fn ($sequence) => ['code' => 'C'.($sequence->index + 1), 'position' => $sequence->index + 1])
        ->create();
    $this->scale = Competency::factory()->for($framework)->scale([1 => 'A', 2 => 'B', 3 => 'C'], threshold: 2)
        ->create(['code' => 'C4', 'position' => 4]);

    $this->promotion = Promotion::factory()->for($framework)->create();
    $this->learner = User::factory()->create();
    $this->promotion->learners()->attach($this->learner);
});

function selfAssess(Competency $competency, int $value): void
{
    Assessment::factory()->for(test()->promotion)->selfAssessed(test()->learner)
        ->create(['competency_id' => $competency->id, 'value' => $value, 'created_at' => now()->subMinute()]);
}

function trainerValidates(Competency $competency, int $value): void
{
    Assessment::factory()->for(test()->promotion)->byTrainer(test()->trainer, test()->learner)
        ->create(['competency_id' => $competency->id, 'value' => $value]);
}

/**
 * @return list<string>
 */
function earnedBadges(): array
{
    app(SyncBadges::class)->handle(test()->learner);

    return test()->learner->badges()->orderBy('badge')->pluck('badge')->map(fn (Badge $badge) => $badge->value)->all();
}

it('n\'attribue aucun badge sans activité', function () {
    expect(earnedBadges())->toBe([]);
});

it('attribue « Premier pas » à la première autoévaluation', function () {
    selfAssess($this->competencies[0], 1);

    expect(earnedBadges())->toBe(['premier-pas']);
});

it('attribue « Première réussite » à la première compétence acquise', function () {
    trainerValidates($this->competencies[0], 1);

    expect(earnedBadges())->toContain('premiere-reussite');
});

it('attribue « Assidu » quand toutes les compétences sont autoévaluées', function () {
    foreach ($this->competencies as $competency) {
        selfAssess($competency, 0);
    }

    expect(earnedBadges())->not->toContain('assidu');

    selfAssess($this->scale, 1);

    expect(earnedBadges())->toContain('assidu');
});

it('attribue « Lucide » après 5 autoévaluations confirmées par le formateur', function () {
    $other = Promotion::factory()->for($this->scale->framework)->create();
    $other->learners()->attach($this->learner);

    // 3 confirmations dans la promotion, 1 écart.
    foreach ($this->competencies as $competency) {
        selfAssess($competency, 1);
        trainerValidates($competency, 1);
    }
    selfAssess($this->scale, 3);
    trainerValidates($this->scale, 2);

    // 4e confirmation dans une autre promotion : pas encore assez.
    $confirmInOther = function (Competency $competency, int $value) use ($other) {
        Assessment::factory()->for($other)->selfAssessed($this->learner)->create(['competency_id' => $competency->id, 'value' => $value, 'created_at' => now()->subMinute()]);
        Assessment::factory()->for($other)->byTrainer($this->trainer, $this->learner)->create(['competency_id' => $competency->id, 'value' => $value]);
    };
    $confirmInOther($this->scale, 2);

    expect(earnedBadges())->not->toContain('lucide');

    // 5e confirmation.
    $confirmInOther($this->competencies[0], 0);

    expect(earnedBadges())->toContain('lucide');
});

it('attribue « Expert » pour une validation au niveau maximal d\'une échelle', function () {
    trainerValidates($this->scale, 2);

    expect(earnedBadges())->not->toContain('expert');

    trainerValidates($this->scale, 3);

    expect(earnedBadges())->toContain('expert');
});

it('attribue les paliers de progression d\'une promotion', function () {
    trainerValidates($this->competencies[0], 1);
    trainerValidates($this->competencies[1], 1);

    expect(earnedBadges())->toContain('mi-parcours')->not->toContain('sprint-final');

    trainerValidates($this->competencies[2], 1);

    expect(earnedBadges())->toContain('sprint-final')->not->toContain('diplomable');

    trainerValidates($this->scale, 2);

    expect(earnedBadges())->toContain('diplomable');
});

it('conserve un badge gagné même si la progression baisse', function () {
    trainerValidates($this->competencies[0], 1);
    trainerValidates($this->competencies[1], 1);
    earnedBadges();

    $this->travel(1)->minute();
    trainerValidates($this->competencies[1], 0);

    expect(earnedBadges())->toContain('mi-parcours');
});

it('n\'attribue jamais deux fois le même badge', function () {
    selfAssess($this->competencies[0], 1);
    earnedBadges();
    earnedBadges();

    expect($this->learner->badges()->count())->toBe(1);
});

it('affiche tous les badges, gagnés ou à débloquer', function () {
    selfAssess($this->competencies[0], 1);

    $this->actingAs($this->learner)
        ->get(route('my-badges.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learner/Badges')
            ->has('badges', count(Badge::cases()))
            ->where('badges.0.key', 'premier-pas')
            ->where('badges.0.label', 'Premier pas')
            ->where('badges.0.earned', true)
            ->where('badges.1.earned', false)
            ->where('badges.1.earned_at', null));
});

it('fête chaque nouveau badge une seule fois', function () {
    selfAssess($this->competencies[0], 1);

    $this->actingAs($this->learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('newBadges', 1)
            ->where('newBadges.0.key', 'premier-pas'));

    $this->actingAs($this->learner)
        ->get(route('my-competencies.index'))
        ->assertInertia(fn (Assert $page) => $page->where('newBadges', []));
});

it('est réservé aux apprenants', function () {
    $this->actingAs($this->trainer)->get(route('my-badges.index'))->assertForbidden();
});
