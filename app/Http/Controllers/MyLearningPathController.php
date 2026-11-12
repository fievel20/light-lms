<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use App\Support\MarkdownRenderer;
use Illuminate\Http\Request;
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
            'learningPaths' => $promotion ? $paths->get($promotion) : [],
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

        abort_unless($promotion->learners()->whereKey($learner->id)->exists(), 404);

        // Only an opened sequence of a path assigned to this promotion.
        $path = $paths->get($promotion)->firstWhere('id', $learningPath->id) ?? abort(404);
        $opened = $path['sequences']->values();
        $index = $opened->search(fn (array $item) => $item['id'] === $sequence->id);

        abort_if($index === false, 404);

        $neighbour = fn (int $i) => $opened->get($i) === null ? null : [
            'id' => $opened[$i]['id'],
            'title' => $opened[$i]['title'],
        ];

        return Inertia::render('learner/Sequence', [
            'promotion' => $promotion->only(['id', 'name']),
            'learningPath' => ['id' => $learningPath->id, 'name' => $learningPath->name],
            'sequence' => [
                ...$sequence->only(['id', 'title', 'summary']),
                'html' => $markdown->toHtml($sequence->content, $sequence),
            ],
            'previous' => $index > 0 ? $neighbour($index - 1) : null,
            'next' => $neighbour($index + 1),
            'position' => $index + 1,
            'count' => $opened->count(),
        ]);
    }
}
