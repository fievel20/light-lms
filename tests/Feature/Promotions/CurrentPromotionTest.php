<?php

use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->older = Promotion::factory()->create(['name' => 'CDA 2026', 'starts_on' => '2026-03-01']);
    $this->recent = Promotion::factory()->create(['name' => 'DWWM 2026', 'starts_on' => '2026-09-01']);
    $this->archived = Promotion::factory()->archived()->create(['name' => 'DWWM 2025', 'starts_on' => '2025-09-01']);
});

it('partage le sélecteur de promotion avec les pages du formateur', function () {
    $this->actingAs($this->trainer)
        ->get(route('promotions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('promotionSwitcher.current', ['id' => $this->recent->id, 'name' => 'DWWM 2026'])
            ->where('promotionSwitcher.active', [
                ['id' => $this->recent->id, 'name' => 'DWWM 2026'],
                ['id' => $this->older->id, 'name' => 'CDA 2026'],
            ])
            ->where('promotionSwitcher.archived', [
                ['id' => $this->archived->id, 'name' => 'DWWM 2025'],
            ]));
});

it('mémorise la promotion choisie et ouvre son accueil', function () {
    $this->actingAs($this->trainer)
        ->put(route('current-promotion.update'), ['promotion_id' => $this->older->id])
        ->assertRedirect(route('dashboard'));

    expect($this->trainer->fresh()->current_promotion_id)->toBe($this->older->id);

    $this->actingAs($this->trainer)
        ->get(route('promotions.index'))
        ->assertInertia(fn (Assert $page) => $page->where('promotionSwitcher.current.id', $this->older->id));
});

it('permet de choisir une promotion archivée', function () {
    $this->actingAs($this->trainer)
        ->put(route('current-promotion.update'), ['promotion_id' => $this->archived->id])
        ->assertSessionHasNoErrors();

    expect($this->trainer->fresh()->current_promotion_id)->toBe($this->archived->id);
});

it('refuse une promotion inexistante', function () {
    $this->actingAs($this->trainer)
        ->put(route('current-promotion.update'), ['promotion_id' => 999])
        ->assertSessionHasErrors('promotion_id');
});

it('n\'a pas de promotion active sans promotion', function () {
    Promotion::query()->delete();

    $this->actingAs($this->trainer)
        ->get(route('promotions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('promotionSwitcher.current', null)
            ->where('promotionSwitcher.active', [])
            ->where('promotionSwitcher.archived', []));
});

it('ne concerne pas les apprenants', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('promotionSwitcher', null));

    $this->actingAs($learner)
        ->put(route('current-promotion.update'), ['promotion_id' => $this->recent->id])
        ->assertForbidden();
});
