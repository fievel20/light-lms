<?php

use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

it('est réservé au formateur', function () {
    $framework = Framework::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('frameworks.index'))->assertForbidden();
    $this->get(route('frameworks.show', $framework))->assertForbidden();
    $this->post(route('frameworks.store'), ['name' => 'X'])->assertForbidden();
    $this->put(route('frameworks.update', $framework), ['name' => 'X'])->assertForbidden();
    $this->delete(route('frameworks.destroy', $framework))->assertForbidden();
});

it('liste les référentiels par nom avec leurs compteurs', function () {
    $web = Framework::factory()->create(['name' => 'Développeur Web']);
    Competency::factory()->count(3)->for($web)->create();
    Promotion::factory()->for($web)->create();
    Framework::factory()->create(['name' => 'Concepteur Développeur']);

    $this->actingAs($this->trainer)
        ->get(route('frameworks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('frameworks/Index')
            ->has('frameworks', 2)
            ->where('frameworks.0.name', 'Concepteur Développeur')
            ->where('frameworks.1.name', 'Développeur Web')
            ->where('frameworks.1.competencies_count', 3)
            ->where('frameworks.1.promotions_count', 1));
});

it('crée un référentiel', function () {
    $this->actingAs($this->trainer)
        ->get(route('frameworks.create'))
        ->assertInertia(fn (Assert $page) => $page->component('frameworks/Create'));

    $response = $this->actingAs($this->trainer)
        ->post(route('frameworks.store'), ['name' => 'Développeur Web', 'description' => 'Titre pro DWWM']);

    $framework = Framework::sole();

    $response->assertRedirect(route('frameworks.show', $framework));
    expect($framework->name)->toBe('Développeur Web')
        ->and($framework->description)->toBe('Titre pro DWWM');
});

it('valide le référentiel', function (array $data, string $field) {
    Framework::factory()->create(['name' => 'Déjà pris']);

    $this->actingAs($this->trainer)
        ->post(route('frameworks.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'nom manquant' => [['name' => ''], 'name'],
    'nom déjà utilisé' => [['name' => 'Déjà pris'], 'name'],
    'nom trop long' => [['name' => str_repeat('a', 256)], 'name'],
    'description trop longue' => [['name' => 'Ok', 'description' => str_repeat('a', 2001)], 'description'],
]);

it('affiche un référentiel et ses compétences dans l\'ordre', function () {
    $framework = Framework::factory()->create();
    Competency::factory()->for($framework)->create(['code' => 'C2', 'position' => 2]);
    Competency::factory()->for($framework)->scale([1 => 'Découverte', 2 => 'Guidé', 3 => 'Autonome'], threshold: 2)
        ->create(['code' => 'C1', 'position' => 1]);

    $this->actingAs($this->trainer)
        ->get(route('frameworks.show', $framework))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('frameworks/Show')
            ->where('framework.id', $framework->id)
            ->has('competencies', 2)
            ->where('competencies.0.code', 'C1')
            ->where('competencies.0.type', 'scale')
            ->where('competencies.0.validation_threshold', 2)
            ->where('competencies.0.levels', [
                ['value' => 1, 'label' => 'Découverte'],
                ['value' => 2, 'label' => 'Guidé'],
                ['value' => 3, 'label' => 'Autonome'],
            ])
            ->where('competencies.1.code', 'C2')
            ->where('competencies.1.type', 'binary')
            ->where('competencies.1.levels', []));
});

it('modifie un référentiel', function () {
    $framework = Framework::factory()->create(['name' => 'Ancien']);

    $this->actingAs($this->trainer)
        ->get(route('frameworks.edit', $framework))
        ->assertInertia(fn (Assert $page) => $page->component('frameworks/Edit')->where('framework.name', 'Ancien'));

    $this->actingAs($this->trainer)
        ->put(route('frameworks.update', $framework), ['name' => 'Nouveau'])
        ->assertRedirect(route('frameworks.show', $framework));

    expect($framework->fresh()->name)->toBe('Nouveau');
});

it('supprime un référentiel non utilisé avec ses compétences', function () {
    $framework = Framework::factory()->create();
    Competency::factory()->for($framework)->scale([1 => 'A', 2 => 'B'])->create();

    $this->actingAs($this->trainer)
        ->delete(route('frameworks.destroy', $framework))
        ->assertRedirect(route('frameworks.index'));

    expect(Framework::count())->toBe(0)
        ->and(Competency::count())->toBe(0);
});

it('refuse de supprimer un référentiel affecté à une promotion', function () {
    $framework = Framework::factory()->create();
    Promotion::factory()->for($framework)->create(['name' => 'DWWM 2026']);

    $this->actingAs($this->trainer)
        ->delete(route('frameworks.destroy', $framework))
        ->assertSessionHasErrors(['framework' => 'Ce référentiel est utilisé par la promotion DWWM 2026.']);

    expect(Framework::count())->toBe(1);
});
