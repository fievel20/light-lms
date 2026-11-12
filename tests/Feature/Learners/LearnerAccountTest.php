<?php

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\LearnerInvitation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->trainer = User::factory()->trainer()->create();
    $this->learner = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie', 'email' => 'marie@example.com']);
});

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('learners.index'))->assertForbidden();
    $this->get(route('learners.edit', $this->learner))->assertForbidden();
    $this->put(route('learners.email.update', $this->learner), ['email' => 'x@example.com'])->assertForbidden();
    $this->post(route('learners.password.reset-link', $this->learner))->assertForbidden();
    $this->put(route('learners.password.update', $this->learner), ['password' => 'Provisoire-2026!'])->assertForbidden();
});

it('ne touche pas au compte formateur', function () {
    $this->actingAs($this->trainer);

    $this->get(route('learners.edit', $this->trainer))->assertNotFound();
    $this->put(route('learners.email.update', $this->trainer), ['email' => 'x@example.com'])->assertNotFound();
    $this->put(route('learners.password.update', $this->trainer), ['password' => 'Provisoire-2026!'])->assertNotFound();
});

it('liste tous les apprenants avec leurs promotions', function () {
    $promotion = Promotion::factory()->create(['name' => 'DWWM 2026']);
    $promotion->learners()->attach($this->learner);
    User::factory()->invited()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $this->actingAs($this->trainer)
        ->get(route('learners.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('learners/Index')
            ->has('learners', 2)
            ->where('learners.0.name', 'Marie Curie')
            ->where('learners.0.email', 'marie@example.com')
            ->where('learners.0.activated', true)
            ->where('learners.0.promotions', ['DWWM 2026'])
            ->where('learners.1.name', 'Ada Lovelace')
            ->where('learners.1.activated', false));
});

it('change l’email d’un apprenant', function () {
    $this->actingAs($this->trainer)
        ->put(route('learners.email.update', $this->learner), ['email' => '  Marie.Curie@Example.org '])
        ->assertRedirect(route('learners.edit', $this->learner));

    expect($this->learner->fresh()->email)->toBe('marie.curie@example.org');
    Notification::assertNothingSent();
});

it('renvoie l’invitation à la nouvelle adresse si le compte n’est pas activé', function () {
    $invited = User::factory()->invited()->create();

    $this->actingAs($this->trainer)
        ->put(route('learners.email.update', $invited), ['email' => 'bonne-adresse@example.com']);

    expect($invited->fresh()->email)->toBe('bonne-adresse@example.com');
    Notification::assertSentTo($invited, LearnerInvitation::class);
});

it('refuse une adresse invalide ou déjà prise', function (string $email) {
    $this->actingAs($this->trainer)
        ->put(route('learners.email.update', $this->learner), ['email' => $email])
        ->assertSessionHasErrors('email');

    expect($this->learner->fresh()->email)->toBe('marie@example.com');
})->with([
    'invalide' => ['pas-une-adresse'],
    'prise' => fn () => $this->trainer->email,
]);

it('envoie le lien de réinitialisation du mot de passe', function () {
    $this->actingAs($this->trainer)
        ->post(route('learners.password.reset-link', $this->learner))
        ->assertRedirect(route('learners.edit', $this->learner))
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($this->learner, ResetPassword::class);
});

it('n’envoie pas de lien à un compte pas encore activé', function () {
    $invited = User::factory()->invited()->create();

    $this->actingAs($this->trainer)
        ->post(route('learners.password.reset-link', $invited))
        ->assertSessionHasErrors('reset_link');

    Notification::assertNothingSent();
});

it('définit un mot de passe provisoire et déconnecte l’apprenant de ses autres appareils', function () {
    // As in production (.env.example); tests run with the array driver.
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert([
        'id' => 'session-de-marie',
        'user_id' => $this->learner->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($this->trainer)
        ->put(route('learners.password.update', $this->learner), ['password' => 'Provisoire-2026!'])
        ->assertRedirect(route('learners.edit', $this->learner));

    expect(Hash::check('Provisoire-2026!', $this->learner->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $this->learner->id)->exists())->toBeFalse();
});

it('active le compte d’un apprenant invité avec le mot de passe provisoire', function () {
    $invited = User::factory()->invited()->create();

    $this->actingAs($this->trainer)
        ->put(route('learners.password.update', $invited), ['password' => 'Provisoire-2026!']);

    $invited->refresh();
    expect($invited->isActivated())->toBeTrue()
        ->and($invited->email_verified_at)->not->toBeNull();
});

it('refuse un mot de passe provisoire vide ou trop court', function () {
    $this->actingAs($this->trainer)
        ->put(route('learners.password.update', $this->learner), ['password' => 'abc'])
        ->assertSessionHasErrors('password');
});
