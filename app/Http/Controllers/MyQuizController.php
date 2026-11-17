<?php

namespace App\Http\Controllers;

use App\Actions\Quiz\GradeQuiz;
use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The learner answers the quiz in the page of an opened sequence, as often
 * as they like; the correction then shows in that page.
 */
class MyQuizController extends Controller
{
    public function store(
        Request $request,
        Promotion $promotion,
        LearningPath $learningPath,
        Sequence $sequence,
        LearnerLearningPaths $paths,
        GradeQuiz $grade,
    ): RedirectResponse {
        /** @var User $learner */
        $learner = $request->user();
        $paths->locate($promotion, $learningPath, $sequence, $learner);

        $quiz = $sequence->quiz()->withCount('questions')->first();

        abort_if($quiz === null || $quiz->questions_count === 0, 404);

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

        return to_route('my-learning-paths.show', [$promotion, $learningPath, $sequence, 'tentative' => $attempt->id]);
    }
}
