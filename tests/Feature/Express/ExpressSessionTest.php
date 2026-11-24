<?php

use App\Enums\ExpressStatus;
use App\Models\ExpressAnswer;
use App\Models\ExpressQuiz;
use App\Models\ExpressSession;
use App\Models\User;
use App\Support\TextImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Carbon::setTestNow('2026-11-24 10:00:00.000');
    // The phones' fetch() sends the cookie; JSON test requests only with this.
    $this->withCredentials();

    $this->trainer = User::factory()->trainer()->create();
    $this->quiz = ExpressQuiz::factory()->create(['title' => 'Culture générale', 'seconds_per_question' => 20]);
    $this->quiz->questions()->create(['position' => 1, 'question' => 'Capitale de l’Australie ?', 'choice_a' => 'Sydney', 'choice_b' => 'Canberra', 'choice_c' => 'Perth', 'correct' => 'b']);
    $this->quiz->questions()->create(['position' => 2, 'question' => 'La Terre est ronde.', 'choice_a' => 'Vrai', 'choice_b' => 'Faux', 'correct' => 'a']);
});

afterEach(fn () => Carbon::setTestNow());

function launch(): ExpressSession
{
    test()->actingAs(test()->trainer)->post(route('express-quizzes.sessions.store', test()->quiz))->assertRedirect();
    auth()->logout();

    return ExpressSession::query()->latest('id')->firstOrFail();
}

/** Joins as a participant; answers the cookie that identifies them. */
function joinAs(ExpressSession $session, string $name): array
{
    $response = test()->post(route('express.play.join', $session->code), ['name' => $name]);
    $response->assertRedirect(route('express.play.show', $session->code));

    $cookie = "express_{$session->id}";

    return [$cookie => $response->getCookie($cookie)?->getValue()];
}

function hostAction(ExpressSession $session, string $action): void
{
    test()->actingAs(test()->trainer)->post(route("express-sessions.{$action}", $session))->assertOk();
    auth()->logout();
}

function answerAs(array $cookie, ExpressSession $session, string $choice, ?int $position = null)
{
    return test()->withCookies($cookie)->postJson(route('express.play.answer', $session->code), [
        'position' => $position ?? $session->fresh()->current_position,
        'choice' => $choice,
    ]);
}

describe('lancement', function () {
    it('ouvre une session avec un code, une copie des questions et un QR code', function () {
        $session = launch();

        expect($session->code)->toMatch('/^[A-HJ-NP-Z2-9]{6}$/')
            ->and($session->status)->toBe(ExpressStatus::Lobby)
            ->and($session->questions)->toHaveCount(2)
            ->and($session->questions[0]['choices'])->toBe(['a' => 'Sydney', 'b' => 'Canberra', 'c' => 'Perth']);

        // Editing the quiz afterwards does not change the session.
        $this->quiz->questions()->first()?->update(['question' => 'Modifiée']);
        expect($session->fresh()->questions[0]['question'])->toBe('Capitale de l’Australie ?');

        $this->actingAs($this->trainer)
            ->get(route('express-sessions.show', $session))
            ->assertInertia(fn (Assert $page) => $page
                ->component('express/Host')
                ->where('joinUrl', route('express.play.show', $session->code))
                ->where('qrCode', fn (string $svg) => str_contains($svg, '<svg'))
                ->where('state.status', 'lobby'));
    });

    it('reprend la session encore ouverte au lieu d’en créer une autre', function () {
        $first = launch();

        expect(launch()->id)->toBe($first->id);
    });

    it('refuse de lancer un quiz sans question', function () {
        $empty = ExpressQuiz::factory()->create();

        $this->actingAs($this->trainer)->post(route('express-quizzes.sessions.store', $empty))->assertRedirect();

        expect(ExpressSession::query()->count())->toBe(0);
    });

    it('réserve le pilotage au formateur', function () {
        $session = launch();

        $this->actingAs(User::factory()->create())->post(route('express-sessions.next', $session))->assertForbidden();
        auth()->logout();
        $this->post(route('express-sessions.next', $session))->assertRedirect(route('login'));
    });
});

describe('arrivée', function () {
    it('demande un nom sans compte, puis fait patienter', function () {
        $session = launch();

        $this->get(route('express.play.show', $session->code))
            ->assertInertia(fn (Assert $page) => $page->component('express/Play')->where('state.me', null));

        $cookie = joinAs($session, '  Marie  ');

        $this->withCookies($cookie)->getJson(route('express.play.state', $session->code))
            ->assertJsonPath('status', 'lobby')
            ->assertJsonPath('me.name', 'Marie');

        $this->actingAs($this->trainer)->getJson(route('express-sessions.state', $session))
            ->assertJsonPath('participants.0.name', 'Marie');
    });

    it('refuse un nom déjà pris, en majuscules ou non', function () {
        $session = launch();
        joinAs($session, 'Marie');

        $this->post(route('express.play.join', $session->code), ['name' => 'MARIE'])->assertSessionHasErrors('name');
    });

    it('accepte les retardataires pendant les questions, pas après', function () {
        $session = launch();
        hostAction($session, 'next');

        joinAs($session, 'En retard');

        hostAction($session, 'next');
        hostAction($session, 'next');
        expect($session->fresh()->status)->toBe(ExpressStatus::Ranking);

        $this->post(route('express.play.join', $session->code), ['name' => 'Trop tard'])->assertSessionHasErrors('name');
    });

    it('retrouve une session avec le code tapé, sans espaces ni majuscules', function () {
        $session = launch();

        $this->get(route('express.play.find', ['code' => ' '.strtolower($session->code)]))
            ->assertRedirect(route('express.play.show', $session->code));
    });

    it('laisse le formateur retirer un participant', function () {
        $session = launch();
        joinAs($session, 'Pseudo idiot');
        $participant = $session->participants()->sole();

        $this->actingAs($this->trainer)
            ->deleteJson(route('express-sessions.participants.destroy', [$session, $participant]))
            ->assertJsonCount(0, 'participants');
    });
});

describe('questions', function () {
    it('envoie la question en images au téléphone, sans texte ni bonne réponse', function () {
        $session = launch();
        $cookie = joinAs($session, 'Marie');
        hostAction($session, 'next');

        $response = $this->withCookies($cookie)->getJson(route('express.play.state', $session->code))
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.position', 1)
            ->assertJsonPath('remaining_ms', 20000);

        if (app(TextImage::class)->available()) {
            $response->assertJsonPath('text_mode', 'image')
                ->assertJsonPath('question.text', null)
                ->assertJsonPath('question.choices.1.text', null);
            expect($response->getContent())->not->toContain('Canberra')->not->toContain('Australie');

            $this->withCookies($cookie)->get(route('express.play.text', [$session->code, 1, 'q']))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
        }

        expect($response->json('question'))->not->toHaveKey('correct');
    });

    it('ne donne les images d’une question qu’aux participants, et une fois posée', function () {
        $session = launch();
        $cookie = joinAs($session, 'Marie');
        hostAction($session, 'next');

        $this->get(route('express.play.text', [$session->code, 1, 'a']))->assertForbidden();
        $this->withCookies($cookie)->get(route('express.play.text', [$session->code, 2, 'a']))->assertNotFound();
    });

    it('compte 500 points plus le bonus de rapidité pour une bonne réponse', function () {
        $session = launch();
        $fast = joinAs($session, 'Rapide');
        $slow = joinAs($session, 'Lente');
        $wrong = joinAs($session, 'Fausse');
        hostAction($session, 'next');

        // Within the second of grace: full bonus.
        Carbon::setTestNow('2026-11-24 10:00:00.800');
        answerAs($fast, $session, 'b')->assertOk()->assertJsonPath('question.answer', 'b');
        answerAs($wrong, $session, 'a')->assertOk();

        // 10 s after the start, 9 s counted out of 20: 500 + 275.
        Carbon::setTestNow('2026-11-24 10:00:10.000');
        answerAs($slow, $session, 'b')->assertOk();

        $scores = $session->participants()->pluck('score', 'name')->all();
        expect($scores)->toEqual(['Rapide' => 1000, 'Lente' => 775, 'Fausse' => 0]);
    });

    it('garde la première réponse et refuse les réponses hors délai ou à une autre question', function () {
        $session = launch();
        $cookie = joinAs($session, 'Marie');
        $late = joinAs($session, 'Lent');
        hostAction($session, 'next');

        answerAs($cookie, $session, 'a')->assertOk();
        answerAs($cookie, $session, 'b')->assertOk();
        answerAs($late, $session, 'b', position: 2)->assertUnprocessable();

        // Past the 20 s and the second of grace.
        Carbon::setTestNow('2026-11-24 10:00:21.500');
        answerAs($late, $session, 'b')->assertUnprocessable();

        expect(ExpressAnswer::query()->pluck('choice')->all())->toBe(['a']);
    });

    it('fait passer à la question suivante pour tout le monde, puis au classement', function () {
        $session = launch();
        hostAction($session, 'next');

        $this->actingAs($this->trainer)->postJson(route('express-sessions.next', $session))
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('position', 2)
            ->assertJsonPath('question.question', 'La Terre est ronde.');

        $this->postJson(route('express-sessions.next', $session))->assertJsonPath('status', 'ranking');
    });
});

describe('fin', function () {
    it('affiche le classement puis la correction avec la répartition des réponses', function () {
        $session = launch();
        $marie = joinAs($session, 'Marie');
        $ada = joinAs($session, 'Ada');
        $zoe = joinAs($session, 'Zoé');
        hostAction($session, 'next');
        answerAs($marie, $session, 'b');
        answerAs($ada, $session, 'b');
        answerAs($zoe, $session, 'a');
        hostAction($session, 'next');
        answerAs($marie, $session, 'a');
        hostAction($session, 'next');

        $this->actingAs($this->trainer)->getJson(route('express-sessions.state', $session))
            ->assertJsonPath('status', 'ranking')
            ->assertJsonPath('ranking.0.name', 'Marie')
            ->assertJsonPath('ranking.0.score', 2000)
            ->assertJsonPath('ranking.1.rank', 2)
            ->assertJsonPath('ranking.2.name', 'Zoé')
            ->assertJsonPath('ranking.2.rank', 3);
        auth()->logout();

        $this->withCookies($ada)->getJson(route('express.play.state', $session->code))
            ->assertJsonPath('result', ['rank' => 2, 'of' => 3, 'score' => 1000])
            ->assertJsonPath('correction.0.correct', 'b')
            ->assertJsonPath('correction.0.answer', 'b')
            ->assertJsonPath('correction.0.points', 1000)
            ->assertJsonPath('correction.1.answer', null);

        hostAction($session, 'correct');
        $this->actingAs($this->trainer)->getJson(route('express-sessions.state', $session))
            ->assertJsonPath('status', 'correction')
            ->assertJsonPath('correction.0.correct', 'b')
            ->assertJsonPath('correction.0.counts', ['b' => 2, 'a' => 1]);
    });

    it('termine la session : plus personne ne rejoint', function () {
        $session = launch();
        hostAction($session, 'close');

        expect($session->fresh()->status)->toBe(ExpressStatus::Closed);
        $this->post(route('express.play.join', $session->code), ['name' => 'Marie'])->assertSessionHasErrors('name');
    });

    it('ferme d’elle-même une session oubliée depuis 12 heures', function () {
        $session = launch();

        Carbon::setTestNow('2026-11-24 22:00:01');

        expect($session->fresh()->currentStatus())->toBe(ExpressStatus::Closed);
        expect(launch()->id)->not->toBe($session->id);
        expect($session->fresh()->status)->toBe(ExpressStatus::Closed);
    });
});

describe('sessions passées', function () {
    it('liste les sessions d’un quiz et montre le classement et la correction de chacune', function () {
        $session = launch();
        $marie = joinAs($session, 'Marie');
        hostAction($session, 'next');
        answerAs($marie, $session, 'b');
        hostAction($session, 'next');
        hostAction($session, 'next');
        hostAction($session, 'close');

        $this->actingAs($this->trainer)
            ->get(route('express-quizzes.sessions.index', $this->quiz))
            ->assertInertia(fn (Assert $page) => $page
                ->component('express/Sessions')
                ->where('sessions.0.participants', 1)
                ->where('sessions.0.status', 'closed'));

        $this->get(route('express-sessions.results', $session))
            ->assertInertia(fn (Assert $page) => $page
                ->component('express/Results')
                ->where('ranking.0.name', 'Marie')
                ->where('ranking.0.score', 1000)
                ->where('correction.0.counts', ['b' => 1]));
    });

    it('supprime une session et ses réponses', function () {
        $session = launch();
        $marie = joinAs($session, 'Marie');
        hostAction($session, 'next');
        answerAs($marie, $session, 'b');

        $this->actingAs($this->trainer)
            ->delete(route('express-sessions.destroy', $session))
            ->assertRedirect(route('express-quizzes.sessions.index', $this->quiz));

        expect(ExpressSession::query()->count())->toBe(0)
            ->and(ExpressAnswer::query()->count())->toBe(0);
    });
});
