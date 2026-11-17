<?php

namespace App\Http\Controllers;

use App\Actions\Quiz\GradeQuiz;
use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use App\Support\QuizPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The learner answers the quiz of an opened sequence, as often as they like,
 * and reads the correction of each attempt.
 */
class MyQuizController extends Controller
{
    public function __construct(
        private LearnerLearningPaths $paths,
        private QuizPresenter $presenter,
    ) {}

    public function play(Request $request, Promotion $promotion, LearningPath $learningPath, Sequence $sequence): Response
    {
        $quiz = $this->quiz($request, $promotion, $learningPath, $sequence);

        return Inertia::render('quiz/Play', [
            'mode' => 'learner',
            'quiz' => $this->presenter->forPlay($quiz, withAnswers: false),
            'sequence' => $sequence->only(['id', 'title']),
            'backUrl' => route('my-learning-paths.show', [$promotion, $learningPath, $sequence]),
            'submitUrl' => route('my-learning-paths.quiz.store', [$promotion, $learningPath, $sequence]),
            'closed' => $promotion->isArchived(),
        ]);
    }

    public function store(Request $request, Promotion $promotion, LearningPath $learningPath, Sequence $sequence, GradeQuiz $grade): RedirectResponse
    {
        $quiz = $this->quiz($request, $promotion, $learningPath, $sequence);
        /** @var User $learner */
        $learner = $request->user();

        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['answers' => 'Cette promotion est archivée : le quiz est fermé.']);
        }

        $answers = $request->input('answers');
        $graded = $grade->handle($quiz, is_array($answers) ? $answers : []);

        $attempt = $quiz->attempts()->create([
            ...$graded,
            'promotion_id' => $promotion->id,
            'user_id' => $learner->id,
            'finished_at' => now(),
        ]);

        return to_route('my-learning-paths.quiz.attempt', [$promotion, $learningPath, $sequence, $attempt]);
    }

    public function attempt(Request $request, Promotion $promotion, LearningPath $learningPath, Sequence $sequence, QuizAttempt $attempt): Response
    {
        $quiz = $this->quiz($request, $promotion, $learningPath, $sequence);
        /** @var User $learner */
        $learner = $request->user();

        abort_unless(
            $attempt->quiz_id === $quiz->id && $attempt->promotion_id === $promotion->id && $attempt->user_id === $learner->id,
            404,
        );

        return Inertia::render('quiz/Result', [
            'quiz' => $quiz->only(['title']),
            'sequence' => $sequence->only(['id', 'title']),
            'attempt' => $this->presenter->attempt($attempt, $sequence),
            'attempts' => $this->presenter->history($quiz, $promotion, $learner, fn (QuizAttempt $item) => route(
                'my-learning-paths.quiz.attempt', [$promotion, $learningPath, $sequence, $item],
            )),
            'playUrl' => $promotion->isArchived() ? null : route('my-learning-paths.quiz.play', [$promotion, $learningPath, $sequence]),
            'backUrl' => route('my-learning-paths.show', [$promotion, $learningPath, $sequence]),
        ]);
    }

    /**
     * The quiz of a sequence the learner can read (404 otherwise, or without questions).
     */
    private function quiz(Request $request, Promotion $promotion, LearningPath $learningPath, Sequence $sequence): Quiz
    {
        /** @var User $learner */
        $learner = $request->user();
        $this->paths->locate($promotion, $learningPath, $sequence, $learner);

        $quiz = $sequence->quiz()->withCount('questions')->first();

        abort_if($quiz === null || $quiz->questions_count === 0, 404);

        return $quiz;
    }
}
