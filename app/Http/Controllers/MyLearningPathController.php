<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\QuizAttempt;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use App\Support\MarkdownRenderer;
use App\Support\QuizPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The learner's paths: the sequences opened to their promotion.
 */
class MyLearningPathController extends Controller
{
    public function index(Request $request, LearnerLearningPaths $paths): Response
    {
        /** @var User $learner */
        $learner = $request->user();

        $promotions = $learner->promotions()
            ->orderByRaw('archived_at is not null')
            ->orderByDesc('starts_on')
            ->get(['promotions.id', 'name', 'archived_at']);

        $promotion = $request->filled('promotion')
            ? $promotions->firstWhere('id', $request->integer('promotion')) ?? abort(404)
            : $promotions->first();

        return Inertia::render('learner/LearningPaths', [
            'promotions' => $promotions->map->only(['id', 'name'])->values(),
            'promotion' => $promotion?->only(['id', 'name', 'archived_at']),
            'learningPaths' => $promotion ? $paths->get($promotion, $learner) : [],
        ]);
    }

    public function show(
        Request $request,
        Promotion $promotion,
        LearningPath $learningPath,
        Sequence $sequence,
        LearnerLearningPaths $paths,
        MarkdownRenderer $markdown,
        QuizPresenter $quizzes,
    ): Response {
        /** @var User $learner */
        $learner = $request->user();

        [$opened, $index] = $paths->locate($promotion, $learningPath, $sequence, $learner);

        $neighbour = fn (int $i) => $opened->get($i) === null ? null : [
            'id' => $opened[$i]['id'],
            'title' => $opened[$i]['title'],
        ];

        return Inertia::render('learner/Sequence', [
            'promotion' => [...$promotion->only(['id', 'name']), 'archived' => $promotion->isArchived()],
            'learningPath' => ['id' => $learningPath->id, 'name' => $learningPath->name],
            'sequence' => [
                ...$sequence->only(['id', 'title', 'summary']),
                // Cut where [Quizz] puts the quiz button.
                'html_parts' => MarkdownRenderer::splitAtQuiz($markdown->toHtml($sequence->content, $sequence)),
                'completed_at' => $opened[$index]['completed_at'],
            ],
            'previous' => $index > 0 ? $neighbour($index - 1) : null,
            'next' => $neighbour($index + 1),
            'position' => $index + 1,
            'count' => $opened->count(),
            'quiz' => $this->quiz($request, $promotion, $learningPath, $sequence, $learner, $quizzes),
        ]);
    }

    /**
     * The quiz played in the page, without the right answers; the correction
     * of an attempt shows there too (?tentative=id).
     *
     * @return array<string, mixed>|null
     */
    private function quiz(Request $request, Promotion $promotion, LearningPath $learningPath, Sequence $sequence, User $learner, QuizPresenter $quizzes): ?array
    {
        $summary = $quizzes->summary($sequence);
        $quiz = $sequence->quiz()->first();

        if ($summary === null || $quiz === null) {
            return null;
        }

        $correction = $quiz->attempts()
            ->where('promotion_id', $promotion->id)
            ->where('user_id', $learner->id)
            ->find($request->integer('tentative'));

        return [
            ...$summary,
            'questions' => $quizzes->forPlay($quiz, withAnswers: false)['questions'],
            'submit_url' => $promotion->isArchived() ? null : route('my-learning-paths.quiz.store', [$promotion, $learningPath, $sequence]),
            'attempts' => $quizzes->history($quiz, $promotion, $learner, fn (QuizAttempt $attempt) => route(
                'my-learning-paths.show', [$promotion, $learningPath, $sequence, 'tentative' => $attempt->id],
            )),
            'correction' => $correction instanceof QuizAttempt ? $quizzes->attempt($correction, $sequence) : null,
        ];
    }

    /**
     * "J'ai terminé" on a sequence, or its undo. Validating again keeps the
     * first date.
     */
    public function complete(
        Request $request,
        Promotion $promotion,
        LearningPath $learningPath,
        Sequence $sequence,
        LearnerLearningPaths $paths,
    ): RedirectResponse {
        /** @var User $learner */
        $learner = $request->user();

        $paths->locate($promotion, $learningPath, $sequence, $learner);

        $completed = $request->validate(['completed' => ['required', 'boolean']])['completed'];

        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['completed' => 'Cette promotion est archivée : les séquences ne peuvent plus être validées.']);
        }

        $attributes = ['promotion_id' => $promotion->id, 'sequence_id' => $sequence->id, 'user_id' => $learner->id];

        if (filter_var($completed, FILTER_VALIDATE_BOOLEAN)) {
            SequenceCompletion::query()->firstOrCreate($attributes, ['completed_at' => now()]);
            Inertia::flash('toast', ['type' => 'success', 'message' => "Bravo, « {$sequence->title} » est terminée !"]);
        } else {
            SequenceCompletion::query()->where($attributes)->delete();
        }

        return back();
    }
}
