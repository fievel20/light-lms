<?php

use App\Enums\CompetencyType;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->framework = Framework::factory()->create();
});

function binaryCompetency(array $overrides = []): array
{
    return [
        'code' => 'C1',
        'name' => 'Maquetter une application',
        'description' => 'Wireframes et maquettes',
        'type' => 'binary',
        ...$overrides,
    ];
}

function scaleCompetency(array $overrides = []): array
{
    return [
        'code' => 'C2',
        'name' => 'Développer une interface',
        'type' => 'scale',
        'levels' => [
            ['value' => 1, 'label' => 'Découverte'],
            ['value' => 2, 'label' => 'Guidé'],
            ['value' => 3, 'label' => 'Autonome'],
        ],
        'validation_threshold' => 2,
        ...$overrides,
    ];
}

it('est réservé au formateur', function () {
    $competency = Competency::factory()->for($this->framework)->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('frameworks.competencies.create', $this->framework))->assertForbidden();
    $this->post(route('frameworks.competencies.store', $this->framework), binaryCompetency())->assertForbidden();
    $this->put(route('frameworks.competencies.update', [$this->framework, $competency]), binaryCompetency())->assertForbidden();
    $this->delete(route('frameworks.competencies.destroy', [$this->framework, $competency]))->assertForbidden();
});

it('affiche le formulaire de création', function () {
    $this->actingAs($this->trainer)
        ->get(route('frameworks.competencies.create', $this->framework))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('frameworks/competencies/Create')
            ->where('framework.id', $this->framework->id));
});

it('crée une compétence binaire en fin de liste', function () {
    Competency::factory()->for($this->framework)->create(['position' => 4]);

    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), binaryCompetency())
        ->assertRedirect(route('frameworks.show', $this->framework))
        ->assertSessionHasNoErrors();

    $competency = Competency::where('code', 'C1')->sole();

    expect($competency->type)->toBe(CompetencyType::Binary)
        ->and($competency->name)->toBe('Maquetter une application')
        ->and($competency->position)->toBe(5)
        ->and($competency->validation_threshold)->toBeNull()
        ->and($competency->levels)->toBeEmpty();
});

it('crée une compétence sur une échelle avec ses niveaux', function () {
    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), scaleCompetency())
        ->assertSessionHasNoErrors();

    $competency = Competency::sole();

    expect($competency->type)->toBe(CompetencyType::Scale)
        ->and($competency->validation_threshold)->toBe(2)
        ->and($competency->levels->map->only(['value', 'label'])->all())->toBe([
            ['value' => 1, 'label' => 'Découverte'],
            ['value' => 2, 'label' => 'Guidé'],
            ['value' => 3, 'label' => 'Autonome'],
        ]);
});

it('fixe par défaut le seuil d\'acquisition au niveau le plus élevé', function () {
    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), scaleCompetency(['validation_threshold' => null]))
        ->assertSessionHasNoErrors();

    expect(Competency::sole()->validation_threshold)->toBe(3);
});

it('ignore les niveaux envoyés pour une compétence binaire', function () {
    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), binaryCompetency([
            'levels' => [['value' => 1, 'label' => 'A'], ['value' => 2, 'label' => 'B']],
            'validation_threshold' => 2,
        ]))
        ->assertSessionHasNoErrors();

    $competency = Competency::sole();

    expect($competency->levels)->toBeEmpty()
        ->and($competency->validation_threshold)->toBeNull();
});

it('autorise un même code dans deux référentiels différents', function () {
    Competency::factory()->create(['code' => 'C1']);

    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), binaryCompetency())
        ->assertSessionHasNoErrors();
});

it('valide la compétence', function (array $data, string $field) {
    Competency::factory()->for($this->framework)->create(['code' => 'PRIS']);

    $this->actingAs($this->trainer)
        ->post(route('frameworks.competencies.store', $this->framework), $data)
        ->assertSessionHasErrors($field);

    expect(Competency::count())->toBe(1);
})->with([
    'code manquant' => [binaryCompetency(['code' => '']), 'code'],
    'code trop long' => [binaryCompetency(['code' => str_repeat('A', 21)]), 'code'],
    'code déjà utilisé' => [binaryCompetency(['code' => 'PRIS']), 'code'],
    'nom manquant' => [binaryCompetency(['name' => '']), 'name'],
    'type inconnu' => [binaryCompetency(['type' => 'pourcentage']), 'type'],
    'échelle sans niveaux' => [scaleCompetency(['levels' => []]), 'levels'],
    'échelle à un seul niveau' => [scaleCompetency(['levels' => [['value' => 1, 'label' => 'Seul']]]), 'levels'],
    'libellé de niveau manquant' => [scaleCompetency(['levels' => [['value' => 1, 'label' => 'A'], ['value' => 2, 'label' => '']]]), 'levels.1.label'],
    'valeur de niveau négative' => [scaleCompetency(['levels' => [['value' => -1, 'label' => 'A'], ['value' => 2, 'label' => 'B']]]), 'levels.0.value'],
    'valeurs de niveau en double' => [scaleCompetency(['levels' => [['value' => 1, 'label' => 'A'], ['value' => 1, 'label' => 'B']]]), 'levels.1.value'],
    'seuil hors échelle' => [scaleCompetency(['validation_threshold' => 9]), 'validation_threshold'],
]);

it('affiche le formulaire de modification', function () {
    $competency = Competency::factory()->for($this->framework)->scale([1 => 'A', 2 => 'B'])->create();

    $this->actingAs($this->trainer)
        ->get(route('frameworks.competencies.edit', [$this->framework, $competency]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('frameworks/competencies/Edit')
            ->where('competency.id', $competency->id)
            ->where('competency.levels', [['value' => 1, 'label' => 'A'], ['value' => 2, 'label' => 'B']]));
});

it('modifie une compétence et remplace ses niveaux', function () {
    $competency = Competency::factory()->for($this->framework)->scale([1 => 'A', 2 => 'B', 3 => 'C'])->create(['code' => 'C2']);

    $this->actingAs($this->trainer)
        ->put(route('frameworks.competencies.update', [$this->framework, $competency]), scaleCompetency([
            'name' => 'Nouveau nom',
            'levels' => [['value' => 0, 'label' => 'Non acquis'], ['value' => 1, 'label' => 'Acquis']],
            'validation_threshold' => 1,
        ]))
        ->assertRedirect(route('frameworks.show', $this->framework))
        ->assertSessionHasNoErrors();

    $competency->refresh();

    expect($competency->name)->toBe('Nouveau nom')
        ->and($competency->levels->pluck('label')->all())->toBe(['Non acquis', 'Acquis']);
});

it('passe une compétence d\'une échelle à binaire', function () {
    $competency = Competency::factory()->for($this->framework)->scale()->create(['code' => 'C1']);

    $this->actingAs($this->trainer)
        ->put(route('frameworks.competencies.update', [$this->framework, $competency]), binaryCompetency())
        ->assertSessionHasNoErrors();

    $competency->refresh();

    expect($competency->type)->toBe(CompetencyType::Binary)
        ->and($competency->levels)->toBeEmpty()
        ->and($competency->validation_threshold)->toBeNull();
});

it('garde son propre code lors d\'une modification', function () {
    $competency = Competency::factory()->for($this->framework)->create(['code' => 'C1']);

    $this->actingAs($this->trainer)
        ->put(route('frameworks.competencies.update', [$this->framework, $competency]), binaryCompetency())
        ->assertSessionHasNoErrors();
});

it('supprime une compétence', function () {
    $competency = Competency::factory()->for($this->framework)->scale()->create();

    $this->actingAs($this->trainer)
        ->delete(route('frameworks.competencies.destroy', [$this->framework, $competency]))
        ->assertRedirect(route('frameworks.show', $this->framework));

    expect(Competency::count())->toBe(0);
});

it('refuse une compétence d\'un autre référentiel', function () {
    $other = Competency::factory()->create();

    $this->actingAs($this->trainer)
        ->get(route('frameworks.competencies.edit', [$this->framework, $other]))
        ->assertNotFound();
});
