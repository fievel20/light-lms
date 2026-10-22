<?php

use App\Models\Competency;
use App\Models\Framework;
use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->framework = Framework::factory()->create(['name' => 'Développeur Web']);
});

it('propose les référentiels dans les formulaires de promotion', function () {
    Framework::factory()->create(['name' => 'Concepteur Développeur']);
    $promotion = Promotion::factory()->create();

    foreach ([route('promotions.create'), route('promotions.edit', $promotion)] as $url) {
        $this->actingAs($this->trainer)
            ->get($url)
            ->assertInertia(fn (Assert $page) => $page
                ->has('frameworks', 2)
                ->where('frameworks.0', ['id' => Framework::where('name', 'Concepteur Développeur')->value('id'), 'name' => 'Concepteur Développeur']));
    }
});

it('affecte un référentiel à la création', function () {
    $this->actingAs($this->trainer)
        ->post(route('promotions.store'), [
            'name' => 'DWWM 2026',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'framework_id' => $this->framework->id,
        ])
        ->assertSessionHasNoErrors();

    expect(Promotion::sole()->framework_id)->toBe($this->framework->id);
});

it('change ou retire le référentiel d\'une promotion', function () {
    $promotion = Promotion::factory()->create();
    $data = ['name' => $promotion->name, 'starts_on' => '2026-09-01', 'ends_on' => '2027-06-30'];

    $this->actingAs($this->trainer)
        ->put(route('promotions.update', $promotion), [...$data, 'framework_id' => $this->framework->id])
        ->assertSessionHasNoErrors();

    expect($promotion->fresh()->framework_id)->toBe($this->framework->id);

    $this->actingAs($this->trainer)
        ->put(route('promotions.update', $promotion), [...$data, 'framework_id' => null])
        ->assertSessionHasNoErrors();

    expect($promotion->fresh()->framework_id)->toBeNull();
});

it('refuse un référentiel inexistant', function () {
    $this->actingAs($this->trainer)
        ->post(route('promotions.store'), [
            'name' => 'DWWM 2026',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'framework_id' => 999,
        ])
        ->assertSessionHasErrors('framework_id');
});

it('affiche le référentiel dans la liste et la fiche de la promotion', function () {
    Competency::factory()->count(2)->for($this->framework)->create();
    $promotion = Promotion::factory()->for($this->framework)->create();

    $this->actingAs($this->trainer)
        ->get(route('promotions.index'))
        ->assertInertia(fn (Assert $page) => $page->where('promotions.0.framework.name', 'Développeur Web'));

    $this->actingAs($this->trainer)
        ->get(route('promotions.show', $promotion))
        ->assertInertia(fn (Assert $page) => $page
            ->where('framework.id', $this->framework->id)
            ->where('framework.name', 'Développeur Web')
            ->where('framework.competencies_count', 2));
});

it('indique l\'absence de référentiel', function () {
    $promotion = Promotion::factory()->create();

    $this->actingAs($this->trainer)
        ->get(route('promotions.show', $promotion))
        ->assertInertia(fn (Assert $page) => $page->where('framework', null));
});
