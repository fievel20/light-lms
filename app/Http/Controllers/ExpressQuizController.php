<?php

namespace App\Http\Controllers;

use App\Actions\Express\DuplicateExpressQuiz;
use App\Actions\Quiz\SyncQuestions;
use App\Http\Requests\ExpressQuizRequest;
use App\Models\ExpressQuestion;
use App\Models\ExpressQuiz;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The quizzes express, written by the trainer with the same editor as the
 * quiz of a sequence.
 */
class ExpressQuizController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('express/Index', [
            'quizzes' => ExpressQuiz::query()
                ->withCount(['questions', 'sessions'])
                ->orderBy('title')
                ->get()
                ->map(fn (ExpressQuiz $quiz) => [
                    ...$quiz->only(['id', 'title', 'seconds_per_question', 'updated_at']),
                    'questions_count' => (int) $quiz->questions_count,
                    'sessions_count' => (int) $quiz->sessions_count,
                ]),
        ]);
    }

    /**
     * A new, empty quiz, then straight to its editor.
     */
    public function store(): RedirectResponse
    {
        $quiz = ExpressQuiz::create(['title' => 'Nouveau quiz express']);

        return to_route('express-quizzes.edit', $quiz);
    }

    public function edit(ExpressQuiz $expressQuiz): Response
    {
        $expressQuiz->load('questions');

        return Inertia::render('express/Edit', [
            'quiz' => [
                ...$expressQuiz->only(['id', 'title', 'seconds_per_question']),
                'questions' => $expressQuiz->questions->map(fn (ExpressQuestion $question) => [
                    ...$question->only(['id', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct']),
                    'image_id' => $question->express_image_id,
                    'image_url' => $question->express_image_id === null
                        ? null
                        : route('express-quizzes.images.show', [$expressQuiz, $question->express_image_id]),
                ]),
            ],
            'limits' => [
                'min_seconds' => Quiz::MIN_SECONDS,
                'max_seconds' => Quiz::MAX_SECONDS,
                'max_questions' => Quiz::MAX_QUESTIONS,
            ],
        ]);
    }

    public function update(ExpressQuizRequest $request, ExpressQuiz $expressQuiz, SyncQuestions $sync): RedirectResponse
    {
        /** @var array{title: string, seconds_per_question: int, questions: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($expressQuiz, $validated, $sync): void {
            $expressQuiz->update([
                'title' => $validated['title'],
                'seconds_per_question' => $validated['seconds_per_question'],
            ]);

            $sync->handle($expressQuiz->questions(), $validated['questions'], 'express_image_id');
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz express enregistré.']);

        return to_route('express-quizzes.edit', $expressQuiz);
    }

    public function duplicate(ExpressQuiz $expressQuiz, DuplicateExpressQuiz $duplicate): RedirectResponse
    {
        $copy = $duplicate->handle($expressQuiz);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quiz express dupliqué : modifie la copie.']);

        return to_route('express-quizzes.edit', $copy);
    }

    public function destroy(ExpressQuiz $expressQuiz): RedirectResponse
    {
        $expressQuiz->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Quiz express « {$expressQuiz->title} » supprimé."]);

        return to_route('express-quizzes.index');
    }
}
