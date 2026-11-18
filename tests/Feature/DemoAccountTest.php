<?php

use App\Models\Assessment;
use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->promotion = Promotion::factory()->create(['name' => 'CIP 2026']);
    $this->learner = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie']);
    $this->promotion->learners()->attach($this->learner);

    $this->path = LearningPath::factory()->create();
    $this->sequence = Sequence::factory()->create();
    $this->path->sequences()->attach($this->sequence);
    $this->promotion->learningPaths()->attach($this->path);
    $this->promotion->sequenceReleases()->create(['sequence_id' => $this->sequence->id, 'released_at' => now()]);
});

function createDemo(string $email = 'demo@formation.test', string $password = 'demo'): User
{
    test()->actingAs(test()->trainer)
        ->put(route('promotions.demo-account.update', test()->promotion), ['demo_email' => $email, 'demo_password' => $password])
        ->assertRedirect(route('promotions.show', test()->promotion));

    return test()->promotion->demoAccount()->firstOrFail();
}

/** Some activity of a learner in the promotion. */
function useAccount(User $user): void
{
    $promotion = test()->promotion;
    $quiz = test()->sequence->quiz()->firstOrCreate([], ['title' => 'Quiz']);

    QuizAttempt::factory()->for($quiz)->create(['promotion_id' => $promotion->id, 'user_id' => $user->id]);
    SequenceCompletion::query()->create(['promotion_id' => $promotion->id, 'sequence_id' => test()->sequence->id, 'user_id' => $user->id, 'completed_at' => now()]);
    $assessment = Assessment::factory()->selfAssessed($user)->create(['promotion_id' => $promotion->id]);
    DB::table('competency_celebrations')->insert(['learner_id' => $user->id, 'promotion_id' => $promotion->id, 'competency_id' => $assessment->competency_id, 'celebrated_at' => now()]);
}

function activityOf(User $user): int
{
    return QuizAttempt::query()->where('user_id', $user->id)->count()
        + SequenceCompletion::query()->where('user_id', $user->id)->count()
        + Assessment::query()->where('learner_id', $user->id)->count()
        + DB::table('competency_celebrations')->where('learner_id', $user->id)->count();
}

describe('création', function () {
    it('crée un faux apprenant avec un mot de passe simple, sans invitation', function () {
        Notification::fake();

        $demo = createDemo();

        expect($demo->email)->toBe('demo@formation.test')
            ->and(Hash::check('demo', $demo->password))->toBeTrue()
            ->and($demo->demo_password)->toBe('demo')
            ->and($demo->isLearner())->toBeTrue()
            ->and($demo->isActivated())->toBeTrue()
            ->and($demo->hasVerifiedEmail())->toBeTrue()
            ->and($this->promotion->members()->whereKey($demo->id)->exists())->toBeTrue();

        Notification::assertNothingSent();
    });

    it('montre l’email et le mot de passe au formateur, sans compter le compte parmi les apprenants', function () {
        createDemo();

        $this->get(route('promotions.show', $this->promotion))
            ->assertInertia(fn (Assert $page) => $page
                ->where('demoAccount', ['email' => 'demo@formation.test', 'password' => 'demo'])
                ->has('learners', 1));
    });

    it('modifie l’email et le mot de passe', function () {
        $demo = createDemo();
        createDemo('visite@formation.test', 'abcd');

        expect($demo->fresh()->email)->toBe('visite@formation.test')
            ->and(Hash::check('abcd', $demo->fresh()->password))->toBeTrue()
            ->and(User::query()->whereNotNull('demo_promotion_id')->count())->toBe(1);
    });

    it('refuse un mot de passe de moins de 4 caractères ou une adresse déjà prise', function (string $email, string $password, string $field) {
        $this->actingAs($this->trainer)
            ->put(route('promotions.demo-account.update', $this->promotion), ['demo_email' => $email, 'demo_password' => $password])
            ->assertSessionHasErrors($field);

        expect($this->promotion->demoAccount()->exists())->toBeFalse();
    })->with([
        'mot de passe court' => ['demo@formation.test', 'abc', 'demo_password'],
        'adresse prise' => [fn () => test()->learner->email, 'demo', 'demo_email'],
    ]);

    it('est réservé au formateur', function () {
        $this->actingAs($this->learner)
            ->put(route('promotions.demo-account.update', $this->promotion), ['demo_email' => 'demo@formation.test', 'demo_password' => 'demo'])
            ->assertForbidden();
    });

    it('n’inscrit pas le compte démo comme apprenant', function () {
        createDemo();

        $this->post(route('promotions.learners.store', $this->promotion), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'demo@formation.test'])
            ->assertSessionHasErrors('email');
    });

    it('supprime le compte démo', function () {
        $demo = createDemo();

        $this->delete(route('promotions.demo-account.destroy', $this->promotion))->assertRedirect();

        expect(User::query()->find($demo->id))->toBeNull();
    });
});

describe('utilisation', function () {
    it('se connecte et lit les séquences comme un apprenant', function () {
        createDemo();
        auth()->logout();

        $this->post(route('login.store'), ['email' => 'demo@formation.test', 'password' => 'demo']);

        $this->assertAuthenticated();
        $this->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->sequence]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('demo', true));
    });

    it('fait le quiz sans apparaître dans les résultats du formateur', function () {
        $demo = createDemo();
        $quiz = $this->sequence->quiz()->create(['title' => 'Quiz']);
        $question = $quiz->questions()->create(['position' => 1, 'question' => 'Q', 'choice_a' => 'Oui', 'choice_b' => 'Non', 'correct' => 'a']);

        $this->actingAs($demo)
            ->post(route('my-learning-paths.quiz.store', [$this->promotion, $this->path, $this->sequence]), ['answers' => [$question->id => 'a']])
            ->assertRedirect();

        expect(QuizAttempt::query()->where('user_id', $demo->id)->count())->toBe(1);

        $this->actingAs($this->trainer)
            ->get(route('promotions.quizzes.index', $this->promotion))
            ->assertInertia(fn (Assert $page) => $page
                ->where('quizzes.0.participants', 0)
                ->where('quizzes.0.attempts', 0)
                ->where('quizzes.0.learners', 1));
    });

    it('n’apparaît pas dans l’administration des comptes apprenants', function () {
        createDemo();

        $this->get(route('learners.index'))
            ->assertInertia(fn (Assert $page) => $page->has('learners', 1)->where('learners.0.name', 'Marie Curie'));
    });

    it('ne modifie ni son profil ni son mot de passe', function () {
        $demo = createDemo();

        $this->actingAs($demo);
        $this->get(route('profile.edit'))->assertRedirect(route('appearance.edit'));
        $this->get(route('security.edit'))->assertRedirect(route('appearance.edit'));
        $this->patch(route('profile.update'), ['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.com'])->assertRedirect();
        $this->put(route('user-password.update'), ['current_password' => 'demo', 'password' => 'NouveauMotDePasse1!', 'password_confirmation' => 'NouveauMotDePasse1!']);

        expect($demo->fresh()->email)->toBe('demo@formation.test')
            ->and(Hash::check('demo', $demo->fresh()->password))->toBeTrue();
    });
});

describe('remise à zéro', function () {
    it('efface tout ce que le compte démo a fait quand il se déconnecte', function () {
        $demo = createDemo();
        useAccount($demo);
        useAccount($this->learner);

        $this->actingAs($demo)->post(route('logout'));

        expect(activityOf($demo))->toBe(0)
            ->and(activityOf($this->learner))->toBe(4)
            ->and(User::query()->find($demo->id))->not->toBeNull();
    });

    it('efface aussi à la connexion, si la session précédente s’est terminée sans déconnexion', function () {
        $demo = createDemo();
        auth()->logout();
        useAccount($demo);

        $this->post(route('login.store'), ['email' => 'demo@formation.test', 'password' => 'demo']);

        expect(activityOf($demo))->toBe(0);
    });

    it('ne touche pas aux données d’un vrai apprenant qui se déconnecte', function () {
        useAccount($this->learner);

        $this->actingAs($this->learner)->post(route('logout'));

        expect(activityOf($this->learner))->toBe(4);
    });

    it('se remet à zéro depuis la fiche de la promotion', function () {
        $demo = createDemo();
        useAccount($demo);

        $this->actingAs($this->trainer)
            ->post(route('promotions.demo-account.reset', $this->promotion))
            ->assertRedirect(route('promotions.show', $this->promotion));

        expect(activityOf($demo))->toBe(0);
    });
});
