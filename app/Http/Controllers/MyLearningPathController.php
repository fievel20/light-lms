<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use App\Support\MarkdownRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    ): Response {
        /** @var User $learner */
        $learner = $request->user();

        [$opened, $index] = $this->locate($paths, $promotion, $learningPath, $sequence, $learner);

        $neighbour = fn (int $i) => $opened->get($i) === null ? null : [
            'id' => $opened[$i]['id'],
            'title' => $opened[$i]['title'],
        ];

        return Inertia::render('learner/Sequence', [
            'promotion' => [...$promotion->only(['id', 'name']), 'archived' => $promotion->isArchived()],
            'learningPath' => ['id' => $learningPath->id, 'name' => $learningPath->name],
            'sequence' => [
                ...$sequence->only(['id', 'title', 'summary']),
                'html' => $markdown->toHtml($sequence->content, $sequence),
                'completed_at' => $opened[$index]['completed_at'],
            ],
            'previous' => $index > 0 ? $neighbour($index - 1) : null,
            'next' => $neighbour($index + 1),
            'position' => $index + 1,
            'count' => $opened->count(),
        ]);
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

        $this->locate($paths, $promotion, $learningPath, $sequence, $learner);

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

    /**
     * The opened sequences of the path and the position of this one; 404 when
     * the learner is not in the promotion or cannot read the sequence there.
     *
     * @return array{0: Collection<int, array{id: int, title: string, summary: string|null, released_at: string, is_new: bool, completed_at: CarbonImmutable|null}>, 1: int}
     */
    private function locate(LearnerLearningPaths $paths, Promotion $promotion, LearningPath $learningPath, Sequence $sequence, User $learner): array
    {
        abort_unless($promotion->learners()->whereKey($learner->id)->exists(), 404);

        // Only an opened sequence of a path assigned to this promotion.
        $path = $paths->get($promotion, $learner)->firstWhere('id', $learningPath->id) ?? abort(404);
        $opened = $path['sequences']->values();
        $index = $opened->search(fn (array $item) => $item['id'] === $sequence->id);

        abort_if($index === false, 404);

        return [$opened, $index];
    }
}
