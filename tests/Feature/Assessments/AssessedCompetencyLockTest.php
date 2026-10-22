<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->framework = Framework::factory()->create();
    $this->competency = Competency::factory()->for($this->framework)
        ->scale([1 => 'Découverte', 2 => 'Guidé', 3 => 'Autonome'], threshold: 3)
        ->create(['code' => 'C1']);
    $this->promotion = Promotion::factory()->for($this->framework)->create();

    Assessment::factory()->for($this->promotion)->create(['competency_id' => $this->competency->id, 'value' => 2]);
});

function assessedUpdate(array $overrides = []): array
{
    return [
        'code' => 'C1',
        'name' => 'Nouvel intitulé',
        'type' => 'scale',
        'levels' => [
            ['value' => 1, 'label' => 'Débutant'],
            ['value' => 2, 'label' => 'Intermédiaire'],
            ['value' => 3, 'label' => 'Confirmé'],
        ],
        'validation_threshold' => 2,
        ...$overrides,
    ];
}

it('signale au formulaire qu\'une compétence a déjà été évaluée', function () {
    $this->actingAs($this->trainer)
        ->get(route('frameworks.competencies.edit', [$this->framework, $this->competency]))
        ->assertInertia(fn (Assert $page) => $page->where('assessed', true));
});

it('autorise à modifier intitulé, libellés et seuil', function () {
    $this->actingAs($this->trainer)
        ->put(route('frameworks.competencies.update', [$this->framework, $this->competency]), assessedUpdate())
        ->assertSessionHasNoErrors();

    $this->competency->refresh();

    expect($this->competency->name)->toBe('Nouvel intitulé')
        ->and($this->competency->validation_threshold)->toBe(2)
        ->and($this->competency->levels->pluck('label')->all())->toBe(['Débutant', 'Intermédiaire', 'Confirmé']);
});

it('interdit de changer le type ou les valeurs des niveaux', function (array $data) {
    $this->actingAs($this->trainer)
        ->put(route('frameworks.competencies.update', [$this->framework, $this->competency]), assessedUpdate($data))
        ->assertSessionHasErrors(['type' => 'Cette compétence a déjà été évaluée : son type et les valeurs de ses niveaux ne peuvent plus changer.']);

    expect($this->competency->fresh()->name)->not->toBe('Nouvel intitulé');
})->with([
    'passage en binaire' => [['type' => 'binary']],
    'niveau ajouté' => [['levels' => [['value' => 1, 'label' => 'A'], ['value' => 2, 'label' => 'B'], ['value' => 3, 'label' => 'C'], ['value' => 4, 'label' => 'D']]]],
    'valeur modifiée' => [['levels' => [['value' => 0, 'label' => 'A'], ['value' => 2, 'label' => 'B'], ['value' => 3, 'label' => 'C']]]],
]);

it('interdit de supprimer une compétence évaluée', function () {
    $this->actingAs($this->trainer)
        ->delete(route('frameworks.competencies.destroy', [$this->framework, $this->competency]))
        ->assertSessionHasErrors(['competency' => 'La compétence C1 a déjà été évaluée et ne peut pas être supprimée.']);

    expect(Competency::count())->toBe(1);
});

it('interdit de changer le référentiel d\'une promotion déjà évaluée', function () {
    $other = Framework::factory()->create();

    $this->actingAs($this->trainer)
        ->put(route('promotions.update', $this->promotion), [
            'name' => $this->promotion->name,
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'framework_id' => $other->id,
        ])
        ->assertSessionHasErrors(['framework_id' => 'Des compétences ont déjà été évaluées dans cette promotion : son référentiel ne peut plus changer.']);

    expect($this->promotion->fresh()->framework_id)->toBe($this->framework->id);
});

it('autorise à modifier une promotion évaluée sans changer son référentiel', function () {
    $this->actingAs($this->trainer)
        ->put(route('promotions.update', $this->promotion), [
            'name' => 'Nouveau nom',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'framework_id' => $this->framework->id,
        ])
        ->assertSessionHasNoErrors();
});
