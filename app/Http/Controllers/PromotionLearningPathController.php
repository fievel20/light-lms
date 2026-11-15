<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Learning paths of a promotion: assign, order, remove, and see which
 * sequences are open to its learners.
 */
class PromotionLearningPathController extends Controller
{
    public function index(Promotion $promotion): Response
    {
        $releases = $promotion->sequenceReleases()->pluck('released_at', 'sequence_id');
        $paths = $promotion->learningPaths()->with('sequences:id,title,summary')->get(['learning_paths.id', 'name', 'description']);

        return Inertia::render('promotions/LearningPaths', [
            'promotion' => $promotion->only(['id', 'name', 'archived_at']),
            'learningPaths' => $paths->map(fn (LearningPath $path) => [
                ...$path->only(['id', 'name', 'description']),
                'sequences' => $path->sequences->map(fn (Sequence $sequence) => [
                    ...$sequence->only(['id', 'title', 'summary']),
                    'released_at' => $releases->get($sequence->id),
                ])->values(),
            ])->values(),
            'availableLearningPaths' => LearningPath::query()
                ->whereKeyNot($paths->modelKeys())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (LearningPath $path) => $path->only(['id', 'name'])),
        ]);
    }

    public function store(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate(
            [
                'learning_path_id' => [
                    'required', 'integer', 'exists:learning_paths,id',
                    Rule::unique('learning_path_promotion')->where('promotion_id', $promotion->id),
                ],
            ],
            ['learning_path_id.unique' => 'Ce parcours est déjà affecté à la promotion.'],
            ['learning_path_id' => 'parcours'],
        );

        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['learning_path_id' => 'Impossible d\'affecter un parcours à une promotion archivée.']);
        }

        // Nothing is opened: the sequences stay hidden until released one by one.
        $promotion->learningPaths()->attach($validated['learning_path_id'], [
            'position' => (int) DB::table('learning_path_promotion')->where('promotion_id', $promotion->id)->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parcours affecté. Ses séquences sont masquées : ouvre-les une par une.']);

        return to_route('promotions.learning-paths.index', $promotion);
    }

    /**
     * The new order of the promotion's paths, as the learners will see them.
     */
    public function reorder(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'learning_paths' => ['present', 'array', 'list'],
            'learning_paths.*' => ['integer'],
        ]);

        /** @var list<int> $order */
        $order = array_values(array_map('intval', $validated['learning_paths']));
        $current = $promotion->learningPaths()->pluck('learning_paths.id')->all();

        // Exactly the assigned paths, each once: otherwise the page is out of date.
        if (count($order) !== count($current) || count(array_unique($order)) !== count($order) || array_diff($order, $current) !== []) {
            throw ValidationException::withMessages([
                'learning_paths' => 'Les parcours de la promotion ont changé entre-temps : recharge la page.',
            ]);
        }

        DB::transaction(function () use ($promotion, $order): void {
            foreach ($order as $index => $learningPathId) {
                $promotion->learningPaths()->updateExistingPivot($learningPathId, ['position' => $index + 1]);
            }
        });

        return to_route('promotions.learning-paths.index', $promotion);
    }

    public function destroy(Promotion $promotion, LearningPath $learningPath): RedirectResponse
    {
        DB::transaction(function () use ($promotion, $learningPath): void {
            $promotion->learningPaths()->detach($learningPath);

            // Openings go with the path, except for sequences still reachable through another assigned path.
            $promotion->sequenceReleases()
                ->whereIn('sequence_id', $learningPath->sequenceIds())
                ->whereNotIn('sequence_id', $promotion->pathSequenceIds())
                ->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Parcours « {$learningPath->name} » retiré de la promotion."]);

        return to_route('promotions.learning-paths.index', $promotion);
    }
}
