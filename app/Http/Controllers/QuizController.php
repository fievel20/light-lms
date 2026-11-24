<?php

namespace App\Http\Controllers;

use App\Actions\Quiz\SyncQuestions;
use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Sequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The quiz of a sequence, edited as a whole: settings and ordered questions.
 */
class QuizController extends Controller
{
    public function edit(Sequence $sequence): Response
    {
        $quiz = $sequence->quiz()->with('questions')->first();

        return Inertia::render('sequences/Quiz', [
            'sequence' => $sequence->only(['id', 'title']),
            'quiz' => $quiz === null ? null : [
                ...$quiz->only(['title', 'seconds_per_question']),
                'questions' => $quiz->questions->map(fn (QuizQuestion $question) => [
                    ...$question->only(['id', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct']),
                    'image_id' => $question->sequence_image_id,
                    'image_url' => $question->sequence_image_id === null
                        ? null
                        : route('sequences.images.show', [$sequence, $question->sequence_image_id]),
                ]),
            ],
            'limits' => [
                'min_seconds' => Quiz::MIN_SECONDS,
                'max_seconds' => Quiz::MAX_SECONDS,
                'max_questions' => Quiz::MAX_QUESTIONS,
            ],
        ]);
    }

    public function update(QuizRequest $request, Sequence $sequence, SyncQuestions $sync): RedirectResponse
    {
        /** @var array{title: string, seconds_per_question: int|null, questions: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($sequence, $validated, $sync): void {
            $quiz = $sequence->quiz()->updateOrCreate([], [
                'title' => $validated['title'],
                'seconds_per_question' => $validated['seconds_per_question'] ?? null,
            ]);

            $sync->handle($quiz->questions(), $validated['questions'], 'sequence_image_id');
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz enregistré.']);

        return to_route('sequences.quiz.edit', $sequence);
    }

    public function destroy(Sequence $sequence): RedirectResponse
    {
        $sequence->quiz()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz supprimé.']);

        return to_route('sequences.show', $sequence);
    }
}
