<?php

use App\Models\Promotion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

function validPromotion(array $overrides = []): array
{
    return [
        'name' => 'DWWM 2026',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-06-30',
        ...$overrides,
    ];
}

describe('accès', function () {
    it('redirige un invité vers la connexion', function () {
        $this->get(route('promotions.index'))->assertRedirect(route('login'));
    });

    it('refuse toutes les actions à un apprenant', function () {
        $learner = User::factory()->create();
        $promotion = Promotion::factory()->create();

        $this->actingAs($learner);

        $this->get(route('promotions.index'))->assertForbidden();
        $this->get(route('promotions.create'))->assertForbidden();
        $this->post(route('promotions.store'), validPromotion())->assertForbidden();
        $this->get(route('promotions.edit', $promotion))->assertForbidden();
        $this->put(route('promotions.update', $promotion), validPromotion())->assertForbidden();
        $this->patch(route('promotions.archive', $promotion))->assertForbidden();
        $this->patch(route('promotions.unarchive', $promotion))->assertForbidden();
    });
});

describe('liste', function () {
    it('affiche les promotions en cours, les plus récentes en premier', function () {
        Promotion::factory()->create(['name' => 'Ancienne', 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31']);
        Promotion::factory()->create(['name' => 'Récente', 'starts_on' => '2026-09-01', 'ends_on' => '2027-06-30']);
        Promotion::factory()->archived()->create(['name' => 'Archivée']);

        $this->actingAs($this->trainer)
            ->get(route('promotions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promotions/Index')
                ->where('archived', false)
                ->has('promotions', 2)
                ->where('promotions.0.name', 'Récente')
                ->where('promotions.0.starts_on', '2026-09-01')
                ->where('promotions.1.name', 'Ancienne'));
    });

    it('affiche uniquement les promotions archivées sur demande', function () {
        Promotion::factory()->create();
        Promotion::factory()->archived()->create(['name' => 'Archivée']);

        $this->actingAs($this->trainer)
            ->get(route('promotions.index', ['archived' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('archived', true)
                ->has('promotions', 1)
                ->where('promotions.0.name', 'Archivée'));
    });
});

describe('création', function () {
    it('affiche le formulaire de création', function () {
        $this->actingAs($this->trainer)
            ->get(route('promotions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('promotions/Create'));
    });

    it('crée une promotion', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.store'), validPromotion())
            ->assertRedirect(route('promotions.index'));

        $promotion = Promotion::sole();

        expect($promotion->name)->toBe('DWWM 2026')
            ->and($promotion->starts_on->toDateString())->toBe('2026-09-01')
            ->and($promotion->ends_on->toDateString())->toBe('2027-06-30')
            ->and($promotion->isArchived())->toBeFalse();
    });

    it('valide les données', function (array $data, string $field) {
        Promotion::factory()->create(['name' => 'Déjà prise']);

        $this->actingAs($this->trainer)
            ->post(route('promotions.store'), validPromotion($data))
            ->assertSessionHasErrors($field);

        expect(Promotion::count())->toBe(1);
    })->with([
        'nom manquant' => [['name' => ''], 'name'],
        'nom trop long' => [['name' => str_repeat('a', 256)], 'name'],
        'nom déjà utilisé' => [['name' => 'Déjà prise'], 'name'],
        'début manquant' => [['starts_on' => ''], 'starts_on'],
        'début invalide' => [['starts_on' => 'demain'], 'starts_on'],
        'fin manquante' => [['ends_on' => ''], 'ends_on'],
        'fin avant le début' => [['ends_on' => '2026-08-31'], 'ends_on'],
    ]);

    it('affiche les erreurs de validation en français', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.store'), validPromotion(['name' => '']))
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    });

    it('explique clairement une date de fin antérieure au début', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.store'), validPromotion(['ends_on' => '2026-08-31']))
            ->assertSessionHasErrors(['ends_on' => 'La date de fin doit être identique ou postérieure à la date de début.']);
    });
});

describe('modification', function () {
    it('affiche le formulaire de modification', function () {
        $promotion = Promotion::factory()->create(['name' => 'DWWM 2026']);

        $this->actingAs($this->trainer)
            ->get(route('promotions.edit', $promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promotions/Edit')
                ->where('promotion.id', $promotion->id)
                ->where('promotion.name', 'DWWM 2026'));
    });

    it('modifie une promotion', function () {
        $promotion = Promotion::factory()->create();

        $this->actingAs($this->trainer)
            ->put(route('promotions.update', $promotion), validPromotion(['name' => 'CDA 2026']))
            ->assertRedirect(route('promotions.index'));

        expect($promotion->fresh()->name)->toBe('CDA 2026');
    });

    it('permet de conserver le même nom', function () {
        $promotion = Promotion::factory()->create(['name' => 'DWWM 2026']);

        $this->actingAs($this->trainer)
            ->put(route('promotions.update', $promotion), validPromotion())
            ->assertSessionHasNoErrors();
    });
});

describe('archivage', function () {
    it('archive une promotion', function () {
        $promotion = Promotion::factory()->create();

        $this->actingAs($this->trainer)
            ->patch(route('promotions.archive', $promotion))
            ->assertRedirect(route('promotions.index'));

        expect($promotion->fresh()->isArchived())->toBeTrue();
    });

    it('désarchive une promotion', function () {
        $promotion = Promotion::factory()->archived()->create();

        $this->actingAs($this->trainer)
            ->patch(route('promotions.unarchive', $promotion))
            ->assertRedirect(route('promotions.index', ['archived' => 1]));

        expect($promotion->fresh()->isArchived())->toBeFalse();
    });
});
