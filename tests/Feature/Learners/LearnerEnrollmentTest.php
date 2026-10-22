<?php

use App\Enums\Role;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\LearnerInvitation;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();

    $this->trainer = User::factory()->trainer()->create();
    $this->promotion = Promotion::factory()->create(['name' => 'DWWM 2026']);
});

function newLearner(array $overrides = []): array
{
    return [
        'first_name' => 'Marie',
        'last_name' => 'Dupont',
        'email' => 'marie.dupont@example.com',
        ...$overrides,
    ];
}

describe('accès', function () {
    it('refuse la gestion des apprenants à un apprenant', function () {
        $learner = User::factory()->create();
        $this->promotion->learners()->attach($learner);

        $this->actingAs($learner);

        $this->get(route('promotions.show', $this->promotion))->assertForbidden();
        $this->post(route('promotions.learners.store', $this->promotion), newLearner())->assertForbidden();
        $this->delete(route('promotions.learners.destroy', [$this->promotion, $learner]))->assertForbidden();
    });
});

describe('fiche promotion', function () {
    it('affiche la promotion et ses apprenants triés par nom', function () {
        $this->promotion->learners()->attach([
            User::factory()->create(['first_name' => 'Zoé', 'last_name' => 'Martin'])->id,
            User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Durand'])->id,
        ]);
        User::factory()->create(['last_name' => 'Ailleurs']);

        $this->actingAs($this->trainer)
            ->get(route('promotions.show', $this->promotion))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promotions/Show')
                ->where('promotion.name', 'DWWM 2026')
                ->has('learners', 2)
                ->where('learners.0.name', 'Paul Durand')
                ->where('learners.1.name', 'Zoé Martin')
                ->has('learners.0', fn (Assert $learner) => $learner
                    ->hasAll(['id', 'first_name', 'last_name', 'name', 'email', 'invited_at', 'activated_at'])
                    ->etc()));
    });
});

describe('ajout d\'un apprenant', function () {
    it('crée un nouvel apprenant, l\'inscrit et lui envoie une invitation', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner())
            ->assertRedirect(route('promotions.show', $this->promotion))
            ->assertSessionHasNoErrors();

        $learner = User::where('email', 'marie.dupont@example.com')->sole();

        expect($learner->role)->toBe(Role::Learner)
            ->and($learner->first_name)->toBe('Marie')
            ->and($learner->last_name)->toBe('Dupont')
            ->and($learner->password)->toBeNull()
            ->and($learner->invited_at)->not->toBeNull()
            ->and($learner->activated_at)->toBeNull()
            ->and($this->promotion->learners()->whereKey($learner->id)->exists())->toBeTrue();

        Notification::assertSentTo($learner, LearnerInvitation::class);
    });

    it('normalise l\'email en minuscules', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner(['email' => ' Marie.Dupont@Example.COM ']));

        expect(User::where('email', 'marie.dupont@example.com')->exists())->toBeTrue();
    });

    it('inscrit un apprenant existant sans le recréer ni le réinviter', function () {
        $existing = User::factory()->create(['email' => 'marie.dupont@example.com', 'first_name' => 'Marie-Anne']);
        Promotion::factory()->create()->learners()->attach($existing);

        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner())
            ->assertSessionHasNoErrors();

        expect(User::where('email', 'marie.dupont@example.com')->count())->toBe(1)
            ->and($existing->fresh()->first_name)->toBe('Marie-Anne')
            ->and($existing->promotions()->count())->toBe(2);

        Notification::assertNothingSent();
    });

    it('refuse un apprenant déjà inscrit dans la promotion', function () {
        $existing = User::factory()->create(['email' => 'marie.dupont@example.com']);
        $this->promotion->learners()->attach($existing);

        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner())
            ->assertSessionHasErrors(['email' => 'Cet apprenant fait déjà partie de la promotion.']);
    });

    it('refuse l\'adresse du formateur', function () {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner(['email' => $this->trainer->email]))
            ->assertSessionHasErrors(['email' => 'Cette adresse appartient au compte formateur.']);

        expect($this->promotion->learners()->count())->toBe(0);
    });

    it('refuse l\'ajout dans une promotion archivée', function () {
        $archived = Promotion::factory()->archived()->create();

        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $archived), newLearner())
            ->assertSessionHasErrors('email');

        expect(User::where('email', 'marie.dupont@example.com')->exists())->toBeFalse();
    });

    it('valide les données', function (array $data, string $field) {
        $this->actingAs($this->trainer)
            ->post(route('promotions.learners.store', $this->promotion), newLearner($data))
            ->assertSessionHasErrors($field);

        expect(User::count())->toBe(1);
    })->with([
        'prénom manquant' => [['first_name' => ''], 'first_name'],
        'nom manquant' => [['last_name' => ''], 'last_name'],
        'email manquant' => [['email' => ''], 'email'],
        'email invalide' => [['email' => 'marie'], 'email'],
    ]);
});

describe('retrait d\'un apprenant', function () {
    it('retire l\'apprenant de la promotion sans supprimer son compte', function () {
        $learner = User::factory()->create();
        $other = Promotion::factory()->create();
        $this->promotion->learners()->attach($learner);
        $other->learners()->attach($learner);

        $this->actingAs($this->trainer)
            ->delete(route('promotions.learners.destroy', [$this->promotion, $learner]))
            ->assertRedirect(route('promotions.show', $this->promotion));

        expect($this->promotion->learners()->count())->toBe(0)
            ->and($other->learners()->count())->toBe(1)
            ->and($learner->fresh())->not->toBeNull();
    });
});
