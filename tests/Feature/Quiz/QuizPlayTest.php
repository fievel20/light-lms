<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\SequenceImage;
use App\Models\User;
use App\Support\MarkdownRenderer;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');

    $this->learner = User::factory()->create();
    $this->promotion = Promotion::factory()->create();
    $this->promotion->learners()->attach($this->learner);

    $this->path = LearningPath::factory()->create();
    $this->sequence = Sequence::factory()->create(['content' => "Avant\n\n[Quizz]\n\nAprès"]);
    $this->path->sequences()->attach($this->sequence);
    $this->promotion->learningPaths()->attach($this->path);
    $this->promotion->sequenceReleases()->create(['sequence_id' => $this->sequence->id, 'released_at' => now()]);

    $this->image = SequenceImage::factory()->for($this->sequence)->create();
    $this->quiz = $this->sequence->quiz()->create(['title' => 'Positionnement', 'seconds_per_question' => 30]);
    [$this->q1, $this->q2, $this->q3] = [
        $this->quiz->questions()->create(['position' => 1, 'question' => 'Capitale de l’Australie ?', 'choice_a' => 'Sydney', 'choice_b' => 'Canberra', 'correct' => 'b', 'sequence_image_id' => $this->image->id]),
        $this->quiz->questions()->create(['position' => 2, 'question' => '15 % de 240 ?', 'choice_a' => '24', 'choice_b' => '36', 'choice_c' => '30', 'correct' => 'b']),
        $this->quiz->questions()->create(['position' => 3, 'question' => 'La Terre est ronde.', 'choice_a' => 'Vrai', 'choice_b' => 'Faux', 'correct' => 'a']),
    ];
});

function quizRoute(string $name, array $extra = []): string
{
    return route($name, [test()->promotion, test()->path, test()->sequence, ...$extra]);
}

describe('code court [Quizz]', function () {
    it('remplace un paragraphe [Quizz] ou [Quiz] par l’emplacement du bouton', function (string $code) {
        $html = app(MarkdownRenderer::class)->toHtml("Avant\n\n{$code}\n\nAprès");

        expect($html)->toContain(MarkdownRenderer::QUIZ_MARKER)
            ->and(MarkdownRenderer::splitAtQuiz($html))->toHaveCount(2);
    })->with(['[Quizz]', '[Quiz]', '[quizz]']);

    it('laisse le code au milieu d’une phrase ou dans une liste', function () {
        $html = app(MarkdownRenderer::class)->toHtml("Voir [Quizz] plus bas\n\n- [Quizz]");

        expect($html)->not->toContain(MarkdownRenderer::QUIZ_MARKER)
            ->and(MarkdownRenderer::splitAtQuiz($html))->toHaveCount(1);
    });
});

describe('séquence', function () {
    it('découpe le texte autour du bouton et résume mes tentatives', function () {
        QuizAttempt::factory()->for($this->quiz)->create(['promotion_id' => $this->promotion->id, 'user_id' => $this->learner->id, 'score' => 2, 'total' => 3]);

        $this->actingAs($this->learner)
            ->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->sequence]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('sequence.html_parts', 2)
                ->where('quiz.title', 'Positionnement')
                ->where('quiz.questions_count', 3)
                ->where('quiz.attempts.0.score', 2));
    });

    it('n’affiche pas de quiz sans question', function () {
        $this->quiz->questions()->delete();

        $this->actingAs($this->learner)
            ->get(route('my-learning-paths.show', [$this->promotion, $this->path, $this->sequence]))
            ->assertInertia(fn (Assert $page) => $page->where('quiz', null));
    });
});

describe('répondre', function () {
    it('présente les questions sans les bonnes réponses', function () {
        $this->actingAs($this->learner)
            ->get(quizRoute('my-learning-paths.quiz.play'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quiz/Play')
                ->where('quiz.seconds_per_question', 30)
                ->has('quiz.questions', 3)
                ->where('quiz.questions.0.choices', ['a' => 'Sydney', 'b' => 'Canberra'])
                ->where('quiz.questions.0.image_url', route('sequences.images.show', [$this->sequence, $this->image]))
                ->missing('quiz.questions.0.correct'));
    });

    it('corrige côté serveur et garde une copie de la tentative', function () {
        $this->actingAs($this->learner)
            ->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => [
                $this->q1->id => 'b',
                $this->q2->id => 'c',
                $this->q3->id => null,
            ]])
            ->assertRedirect(quizRoute('my-learning-paths.quiz.attempt', [QuizAttempt::sole()]));

        $attempt = QuizAttempt::sole();
        expect($attempt->score)->toBe(1)
            ->and($attempt->total)->toBe(3)
            ->and($attempt->promotion_id)->toBe($this->promotion->id)
            ->and($attempt->questions[1])->toMatchArray(['question' => '15 % de 240 ?', 'correct' => 'b', 'answer' => 'c'])
            ->and($attempt->questions[2]['answer'])->toBeNull();

        // Editing the quiz later does not change the attempt.
        $this->q1->update(['question' => 'Modifiée', 'correct' => 'a']);
        expect($attempt->fresh()->questions[0]['question'])->toBe('Capitale de l’Australie ?');
    });

    it('permet de recommencer', function () {
        $this->actingAs($this->learner);
        $answers = [$this->q1->id => 'b', $this->q2->id => 'b', $this->q3->id => 'a'];

        $this->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => $answers]);
        $this->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => $answers]);

        expect(QuizAttempt::count())->toBe(2)
            ->and(QuizAttempt::query()->pluck('score')->all())->toBe([3, 3]);
    });

    it('refuse des réponses qui ne correspondent plus au quiz', function (Closure $answers) {
        $this->actingAs($this->learner)
            ->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => $answers($this)])
            ->assertSessionHasErrors('answers');

        expect(QuizAttempt::count())->toBe(0);
    })->with([
        'question manquante' => [fn ($t) => [$t->q1->id => 'a', $t->q2->id => 'a']],
        'question inconnue' => [fn ($t) => [$t->q1->id => 'a', $t->q2->id => 'a', $t->q3->id => 'a', 999 => 'a']],
        'réponse vide choisie' => [fn ($t) => [$t->q1->id => 'c', $t->q2->id => 'a', $t->q3->id => 'a']],
    ]);

    it('ferme le quiz d’une promotion archivée', function () {
        $this->promotion->archive();

        $this->actingAs($this->learner)
            ->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => [$this->q1->id => 'a', $this->q2->id => 'a', $this->q3->id => 'a']])
            ->assertSessionHasErrors('answers');
    });

    it('cache le quiz d’une séquence masquée', function () {
        $this->promotion->sequenceReleases()->delete();

        $this->actingAs($this->learner)->get(quizRoute('my-learning-paths.quiz.play'))->assertNotFound();
    });
});

describe('correction', function () {
    it('montre ma correction et l’historique de mes tentatives', function () {
        $first = QuizAttempt::factory()->for($this->quiz)->create(['promotion_id' => $this->promotion->id, 'user_id' => $this->learner->id, 'score' => 1, 'total' => 3]);
        $this->actingAs($this->learner)->post(quizRoute('my-learning-paths.quiz.store'), ['answers' => [$this->q1->id => 'b', $this->q2->id => 'b', $this->q3->id => 'b']]);
        $second = QuizAttempt::query()->latest('id')->first();

        $this->get(quizRoute('my-learning-paths.quiz.attempt', [$second]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('quiz/Result')
                ->where('attempt.score', 2)
                ->where('attempt.questions.2.correct', 'a')
                ->where('attempt.questions.2.answer', 'b')
                ->where('attempt.questions.0.image_url', route('sequences.images.show', [$this->sequence, $this->image]))
                ->has('attempts', 2)
                ->where('attempts.0.id', $first->id));
    });

    it('ne montre pas la tentative d’un autre apprenant', function () {
        $other = User::factory()->create();
        $this->promotion->learners()->attach($other);
        $attempt = QuizAttempt::factory()->for($this->quiz)->create(['promotion_id' => $this->promotion->id, 'user_id' => $other->id]);

        $this->actingAs($this->learner)->get(quizRoute('my-learning-paths.quiz.attempt', [$attempt]))->assertNotFound();
    });
});

describe('test par le formateur', function () {
    it('donne au formateur le quiz avec les bonnes réponses, sans rien enregistrer', function () {
        $this->actingAs(User::factory()->trainer()->create())
            ->get(route('sequences.quiz.test', $this->sequence))
            ->assertInertia(fn (Assert $page) => $page
                ->component('quiz/Play')
                ->where('mode', 'test')
                ->where('quiz.questions.2.correct', 'a'));

        expect(QuizAttempt::count())->toBe(0);
    });

    it('est réservé au formateur', function () {
        $this->actingAs($this->learner)->get(route('sequences.quiz.test', $this->sequence))->assertForbidden();
    });
});
