<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Sequence;
use App\Support\QuizPresenter;
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

    /**
     * Saves the quiz: kept questions are updated (by id), new ones created,
     * missing ones deleted, and all are numbered in the order received.
     */
    public function update(QuizRequest $request, Sequence $sequence): RedirectResponse
    {
        /** @var array{title: string, seconds_per_question: int|null, questions: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($sequence, $validated): void {
            $quiz = $sequence->quiz()->updateOrCreate([], [
                'title' => $validated['title'],
                'seconds_per_question' => $validated['seconds_per_question'] ?? null,
            ]);

            $existing = $quiz->questions()->get()->keyBy('id');
            $kept = [];

            foreach ($validated['questions'] as $index => $data) {
                $attributes = [
                    'position' => $index + 1,
                    'question' => $data['question'],
                    'sequence_image_id' => $data['image_id'] ?? null,
                    'choice_a' => $data['choice_a'],
                    'choice_b' => $data['choice_b'],
                    'choice_c' => $data['choice_c'] ?? null,
                    'choice_d' => $data['choice_d'] ?? null,
                    'correct' => $data['correct'],
                ];

                $question = $existing->get($data['id'] ?? null);

                if ($question instanceof QuizQuestion) {
                    $question->update($attributes);
                    $kept[] = $question->id;
                } else {
                    $quiz->questions()->create($attributes);
                }
            }

            $quiz->questions()->whereKeyNot($kept)->whereIn('id', $existing->keys())->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz enregistré.']);

        return to_route('sequences.quiz.edit', $sequence);
    }

    /**
     * The trainer tries the quiz as a learner would; nothing is saved, the
     * page scores it with the answers it receives.
     */
    public function test(Sequence $sequence, QuizPresenter $presenter): Response
    {
        $quiz = $sequence->quiz()->withCount('questions')->first();

        abort_if($quiz === null || $quiz->questions_count === 0, 404);

        return Inertia::render('quiz/Play', [
            'mode' => 'test',
            'quiz' => $presenter->forPlay($quiz, withAnswers: true),
            'sequence' => $sequence->only(['id', 'title']),
            'backUrl' => route('sequences.show', $sequence),
            'submitUrl' => null,
            'closed' => false,
        ]);
    }

    public function destroy(Sequence $sequence): RedirectResponse
    {
        $sequence->quiz()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz supprimé.']);

        return to_route('sequences.show', $sequence);
    }
}
