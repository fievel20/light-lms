<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use App\Support\QuizPresenter;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Quiz results of a promotion: every attempt, to follow each learner's
 * progress, and the questions most often missed.
 */
class PromotionQuizController extends Controller
{
    public function __construct(private LearnerLearningPaths $paths) {}

    public function index(Promotion $promotion): Response
    {
        $learners = $promotion->learners()->count();

        $quizzes = $this->openedQuizzes($promotion)->map(function (Quiz $quiz) use ($promotion, $learners) {
            $attempts = $quiz->attempts()->where('promotion_id', $promotion->id)->orderBy('finished_at')->orderBy('id')->get();
            // Each participant's last score, in percent.
            $lastPercents = $attempts->groupBy('user_id')->map(function (Collection $mine): float {
                $last = $mine->last();

                return $last instanceof QuizAttempt && $last->total > 0 ? $last->score / $last->total * 100 : 0.0;
            });

            return [
                'sequence_id' => $quiz->sequence_id,
                'sequence_title' => $quiz->sequence->title,
                'title' => $quiz->title,
                'questions' => (int) $quiz->questions_count,
                'learners' => $learners,
                'participants' => $lastPercents->count(),
                'attempts' => $attempts->count(),
                'average_percent' => $lastPercents->isEmpty() ? null : (int) round((float) $lastPercents->avg()),
            ];
        });

        return Inertia::render('promotions/Quizzes', [
            'promotion' => $promotion->only(['id', 'name']),
            'quizzes' => $quizzes->values(),
        ]);
    }

    public function show(Promotion $promotion, Sequence $sequence): Response
    {
        $quiz = $this->quizOf($promotion, $sequence);
        $attempts = $quiz->attempts()->where('promotion_id', $promotion->id)->orderBy('finished_at')->orderBy('id')->get()->groupBy('user_id');

        $learners = $promotion->learners()->orderByName()->get()->map(fn (User $learner) => [
            'id' => $learner->id,
            'name' => $learner->name,
            'attempts' => ($attempts->get($learner->id) ?? collect())->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
                'finished_at' => $attempt->finished_at,
                'url' => route('promotions.quizzes.attempt', [$promotion, $sequence, $attempt]),
            ])->values(),
        ]);

        return Inertia::render('promotions/Quiz', [
            'promotion' => $promotion->only(['id', 'name']),
            'sequence' => $sequence->only(['id', 'title']),
            'quiz' => $quiz->only(['title']),
            'learners' => $learners,
            'questions' => $this->successByQuestion($quiz, $attempts->map(fn (Collection $mine) => $mine->last())),
        ]);
    }

    public function attempt(Promotion $promotion, Sequence $sequence, QuizAttempt $attempt, QuizPresenter $presenter): Response
    {
        $quiz = $this->quizOf($promotion, $sequence);

        abort_unless($attempt->quiz_id === $quiz->id && $attempt->promotion_id === $promotion->id, 404);

        /** @var User $learner */
        $learner = $attempt->learner()->firstOrFail();

        return Inertia::render('quiz/Result', [
            'quiz' => $quiz->only(['title']),
            'sequence' => $sequence->only(['id', 'title']),
            'learner' => $learner->name,
            'attempt' => $presenter->attempt($attempt, $sequence),
            'attempts' => $presenter->history($quiz, $promotion, $learner, fn (QuizAttempt $item) => route(
                'promotions.quizzes.attempt', [$promotion, $sequence, $item],
            )),
            'backUrl' => route('promotions.quizzes.show', [$promotion, $sequence]),
        ]);
    }

    /**
     * Quizzes with questions, in the sequences opened to the promotion, in
     * the order of its paths.
     *
     * @return Collection<int, Quiz>
     */
    private function openedQuizzes(Promotion $promotion): Collection
    {
        $order = $this->paths->get($promotion)
            ->flatMap(fn (array $path) => $path['sequences']->pluck('id'))
            ->unique()
            ->values();

        $quizzes = Quiz::query()
            ->whereIn('sequence_id', $order)
            ->whereHas('questions')
            ->withCount('questions')
            ->with('sequence:id,title')
            ->get()
            ->keyBy('sequence_id');

        return $order->map(fn (int $id) => $quizzes->get($id))->filter()->values();
    }

    private function quizOf(Promotion $promotion, Sequence $sequence): Quiz
    {
        return $this->openedQuizzes($promotion)->firstWhere('sequence_id', $sequence->id) ?? abort(404);
    }

    /**
     * For each question of the quiz, how many learners got it right in their
     * last attempt (to spot the questions often missed).
     *
     * @param  Collection<int|string, QuizAttempt>  $lastAttempts
     * @return list<array{id: int, question: string, correct: int, answered: int}>
     */
    private function successByQuestion(Quiz $quiz, Collection $lastAttempts): array
    {
        $rows = [];

        foreach ($quiz->questions()->get() as $question) {
            $rows[$question->id] = ['id' => $question->id, 'question' => $question->question, 'correct' => 0, 'answered' => 0];
        }

        foreach ($lastAttempts as $attempt) {
            foreach ($attempt->questions as $copy) {
                if (! isset($rows[$copy['id']])) {
                    continue;
                }

                $rows[$copy['id']]['answered']++;
                $rows[$copy['id']]['correct'] += $copy['answer'] === $copy['correct'] ? 1 : 0;
            }
        }

        return array_values($rows);
    }
}
