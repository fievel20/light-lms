<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->promotion = Promotion::factory()->create();
    $this->marie = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie']);
    $this->ada = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $this->promotion->learners()->attach([$this->marie->id, $this->ada->id]);

    $this->path = LearningPath::factory()->create();
    $this->sequence = Sequence::factory()->create(['title' => 'Google Lens 1']);
    $this->hidden = Sequence::factory()->create(['title' => 'Masquée']);
    $this->path->sequences()->attach([$this->sequence->id, $this->hidden->id]);
    $this->promotion->learningPaths()->attach($this->path);
    $this->promotion->sequenceReleases()->create(['sequence_id' => $this->sequence->id, 'released_at' => now()]);

    $this->quiz = $this->sequence->quiz()->create(['title' => 'Positionnement']);
    $this->q1 = $this->quiz->questions()->create(['position' => 1, 'question' => 'Q1', 'choice_a' => 'Oui', 'choice_b' => 'Non', 'correct' => 'a']);
    $this->q2 = $this->quiz->questions()->create(['position' => 2, 'question' => 'Q2', 'choice_a' => 'Oui', 'choice_b' => 'Non', 'correct' => 'b']);
    $this->hidden->quiz()->create(['title' => 'Caché'])->questions()->create(['question' => 'Q', 'choice_a' => 'A', 'choice_b' => 'B', 'correct' => 'a']);
});

function attempt(User $learner, array $answers, string $at): QuizAttempt
{
    $questions = [];
    $score = 0;

    foreach ([test()->q1, test()->q2] as $index => $question) {
        $questions[] = ['id' => $question->id, 'question' => $question->question, 'image_id' => null, 'choices' => $question->choices(), 'correct' => $question->correct, 'answer' => $answers[$index]];
        $score += $answers[$index] === $question->correct ? 1 : 0;
    }

    return QuizAttempt::factory()->for(test()->quiz)->create([
        'promotion_id' => test()->promotion->id,
        'user_id' => $learner->id,
        'score' => $score,
        'total' => 2,
        'questions' => $questions,
        'finished_at' => Carbon::parse($at),
    ]);
}

it('liste les quiz des séquences ouvertes, avec participation et moyenne', function () {
    attempt($this->marie, ['b', 'a'], '2026-11-10 10:00');
    attempt($this->marie, ['a', 'b'], '2026-11-11 10:00');

    $this->actingAs($this->trainer)
        ->get(route('promotions.quizzes.index', $this->promotion))
        ->assertInertia(fn (Assert $page) => $page
            ->component('promotions/Quizzes')
            ->has('quizzes', 1)
            ->where('quizzes.0.sequence_title', 'Google Lens 1')
            ->where('quizzes.0.title', 'Positionnement')
            ->where('quizzes.0.participants', 1)
            ->where('quizzes.0.learners', 2)
            ->where('quizzes.0.attempts', 2)
            // Average of each participant's last attempt.
            ->where('quizzes.0.average_percent', 100));
});

it('montre toutes les tentatives de chaque apprenant et la réussite par question', function () {
    $first = attempt($this->marie, ['b', 'a'], '2026-11-10 10:00');
    attempt($this->marie, ['a', 'b'], '2026-11-11 10:00');
    attempt($this->ada, ['a', 'a'], '2026-11-12 10:00');

    $this->actingAs($this->trainer)
        ->get(route('promotions.quizzes.show', [$this->promotion, $this->sequence]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('promotions/Quiz')
            ->where('quiz.title', 'Positionnement')
            ->has('learners', 2)
            ->where('learners.0.name', 'Marie Curie')
            ->has('learners.0.attempts', 2)
            ->where('learners.0.attempts.0.id', $first->id)
            ->where('learners.0.attempts.0.score', 0)
            ->where('learners.0.attempts.1.score', 2)
            ->where('learners.1.name', 'Ada Lovelace')
            ->has('learners.1.attempts', 1)
            // Last attempts: Q1 right for both, Q2 right for Marie only.
            ->where('questions.0.question', 'Q1')
            ->where('questions.0.correct', 2)
            ->where('questions.0.answered', 2)
            ->where('questions.1.correct', 1));
});

it('montre la correction d’une tentative d’un apprenant', function () {
    $attempt = attempt($this->marie, ['b', 'b'], '2026-11-10 10:00');

    $this->actingAs($this->trainer)
        ->get(route('promotions.quizzes.attempt', [$this->promotion, $this->sequence, $attempt]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quiz/Result')
            ->where('learner', 'Marie Curie')
            ->where('attempt.score', 1)
            ->where('backUrl', route('promotions.quizzes.show', [$this->promotion, $this->sequence])));
});

it('ignore les séquences masquées et les tentatives d’une autre promotion', function () {
    $other = Promotion::factory()->create();
    $attempt = attempt($this->marie, ['a', 'b'], '2026-11-10 10:00');
    $attempt->update(['promotion_id' => $other->id]);

    $this->actingAs($this->trainer);

    $this->get(route('promotions.quizzes.show', [$this->promotion, $this->hidden]))->assertNotFound();
    $this->get(route('promotions.quizzes.attempt', [$this->promotion, $this->sequence, $attempt]))->assertNotFound();
    $this->get(route('promotions.quizzes.show', [$this->promotion, $this->sequence]))
        ->assertInertia(fn (Assert $page) => $page->has('learners.0.attempts', 0));
});

it('est réservé au formateur', function () {
    $this->actingAs($this->marie)
        ->get(route('promotions.quizzes.index', $this->promotion))
        ->assertForbidden();
});
