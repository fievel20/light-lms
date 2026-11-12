<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Sequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The sequences of a learning path: add at the end, reorder, remove.
 */
class LearningPathSequenceController extends Controller
{
    public function store(Request $request, LearningPath $learningPath): RedirectResponse
    {
        $validated = $request->validate(
            [
                'sequence_id' => [
                    'required', 'integer', 'exists:sequences,id',
                    Rule::unique('learning_path_sequence')->where('learning_path_id', $learningPath->id),
                ],
            ],
            ['sequence_id.unique' => 'Cette séquence est déjà dans le parcours.'],
            ['sequence_id' => 'séquence'],
        );

        $last = (int) $learningPath->sequences()->max('position');
        $learningPath->sequences()->attach($validated['sequence_id'], ['position' => $last + 1]);

        return to_route('learning-paths.show', $learningPath);
    }

    public function reorder(Request $request, LearningPath $learningPath): RedirectResponse
    {
        $validated = $request->validate([
            'sequences' => ['present', 'array', 'list'],
            'sequences.*' => ['integer'],
        ]);

        /** @var list<int> $order */
        $order = array_values(array_map('intval', $validated['sequences']));
        $current = $learningPath->sequenceIds();

        // Exactly the path's sequences, each once: otherwise the page is out of date.
        if (count($order) !== count($current) || count(array_unique($order)) !== count($order) || array_diff($order, $current) !== []) {
            throw ValidationException::withMessages([
                'sequences' => 'Le parcours a changé entre-temps : recharge la page.',
            ]);
        }

        DB::transaction(fn () => $learningPath->arrangeSequences($order));

        return to_route('learning-paths.show', $learningPath);
    }

    public function destroy(LearningPath $learningPath, Sequence $sequence): RedirectResponse
    {
        DB::transaction(function () use ($learningPath, $sequence): void {
            $learningPath->sequences()->detach($sequence);
            $learningPath->arrangeSequences($learningPath->sequenceIds());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "« {$sequence->title} » retirée du parcours."]);

        return to_route('learning-paths.show', $learningPath);
    }
}
