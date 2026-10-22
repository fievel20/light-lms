<?php

use App\Models\User;
use App\Notifications\LearnerInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function invitedLearner(array $attributes = []): User
{
    return User::factory()->invited()->create(['first_name' => 'Marie', ...$attributes]);
}

function invitationUrl(User $learner): string
{
    return (new LearnerInvitation)->toMail($learner)->actionUrl;
}

describe('email d\'invitation', function () {
    it('est rédigé en français avec un lien d\'activation signé', function () {
        $learner = invitedLearner();
        $mail = (new LearnerInvitation)->toMail($learner);

        expect($mail->subject)->toBe('Ton accès à Light LMS')
            ->and($mail->greeting)->toBe('Bonjour Marie,')
            ->and($mail->actionText)->toBe('Activer mon compte')
            ->and(implode(' ', $mail->introLines))->toContain("Ton formateur t'a inscrit")
            ->and(implode(' ', $mail->outroLines))->toContain('7 jours')
            ->and(URL::hasValidSignature(Request::create($mail->actionUrl)))->toBeTrue()
            ->and($mail->actionUrl)->toStartWith(route('invitation.show', $learner));
    });

    it('est envoyé par email', function () {
        expect((new LearnerInvitation)->via(invitedLearner()))->toBe(['mail']);
    });
});

describe('page d\'activation', function () {
    it('s\'affiche avec un lien valide', function () {
        $learner = invitedLearner(['email' => 'marie@example.com']);

        $this->get(invitationUrl($learner))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/AcceptInvitation')
                ->where('email', 'marie@example.com')
                ->where('firstName', 'Marie')
                ->has('action'));
    });

    it('refuse un lien altéré', function () {
        $learner = invitedLearner();
        $other = invitedLearner();

        $tampered = str_replace("/invitation/{$learner->id}?", "/invitation/{$other->id}?", invitationUrl($learner));

        $this->get($tampered)->assertForbidden();
        $this->get(route('invitation.show', $learner))->assertForbidden();
    });

    it('refuse un lien expiré', function () {
        $url = invitationUrl(invitedLearner());

        $this->travel(8)->days();

        $this->get($url)->assertForbidden();
    });

    it('redirige vers la connexion si le compte est déjà activé', function () {
        $learner = invitedLearner(['activated_at' => now()]);

        $this->get(invitationUrl($learner))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Ton compte est déjà activé, tu peux te connecter.');
    });
});

describe('activation du compte', function () {
    it('enregistre le mot de passe, active le compte et connecte l\'apprenant', function () {
        $learner = invitedLearner();

        $this->post(invitationUrl($learner), [
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertRedirect(route('dashboard'));

        $learner->refresh();

        expect(Hash::check('motdepasse-solide', $learner->password))->toBeTrue()
            ->and($learner->isActivated())->toBeTrue()
            ->and($learner->email_verified_at)->not->toBeNull();

        $this->assertAuthenticatedAs($learner);
    });

    it('valide le mot de passe', function (array $data) {
        $learner = invitedLearner();

        $this->post(invitationUrl($learner), $data)->assertSessionHasErrors('password');

        expect($learner->fresh()->isActivated())->toBeFalse();
        $this->assertGuest();
    })->with([
        'manquant' => [['password' => '', 'password_confirmation' => '']],
        'trop court' => [['password' => 'court', 'password_confirmation' => 'court']],
        'confirmation différente' => [['password' => 'motdepasse-solide', 'password_confirmation' => 'autre-chose-123']],
    ]);

    it('refuse une activation sans signature', function () {
        $learner = invitedLearner();

        $this->post(route('invitation.accept', $learner), [
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertForbidden();
    });

    it('ne réactive pas un compte déjà activé', function () {
        $learner = invitedLearner(['activated_at' => now(), 'password' => 'ancien-mot-de-passe']);

        $this->post(invitationUrl($learner), [
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertRedirect(route('login'));

        expect(Hash::check('ancien-mot-de-passe', $learner->fresh()->password))->toBeTrue();
        $this->assertGuest();
    });

    it('empêche la connexion tant que le compte n\'est pas activé', function () {
        invitedLearner(['email' => 'marie@example.com']);

        $this->post(route('login.store'), ['email' => 'marie@example.com', 'password' => ''])
            ->assertSessionHasErrors();

        $this->assertGuest();
    });
});

describe('renvoi de l\'invitation', function () {
    beforeEach(function () {
        Notification::fake();
        $this->trainer = User::factory()->trainer()->create();
    });

    it('renvoie l\'invitation à un apprenant non activé', function () {
        $learner = invitedLearner(['invited_at' => now()->subDays(10)]);

        $this->actingAs($this->trainer)
            ->from('/promotions')
            ->post(route('learners.invitation.resend', $learner))
            ->assertRedirect('/promotions')
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($learner, LearnerInvitation::class);
        expect($learner->fresh()->invited_at->isToday())->toBeTrue();
    });

    it('ne renvoie pas d\'invitation à un compte déjà activé', function () {
        $learner = invitedLearner(['activated_at' => now()]);

        $this->actingAs($this->trainer)
            ->post(route('learners.invitation.resend', $learner))
            ->assertSessionHasErrors(['invitation' => 'Ce compte est déjà activé.']);

        Notification::assertNothingSent();
    });

    it('est réservé au formateur', function () {
        $learner = invitedLearner();

        $this->actingAs(User::factory()->create())
            ->post(route('learners.invitation.resend', $learner))
            ->assertForbidden();
    });

    it('ne concerne que les apprenants', function () {
        $this->actingAs($this->trainer)
            ->post(route('learners.invitation.resend', $this->trainer))
            ->assertNotFound();
    });
});
