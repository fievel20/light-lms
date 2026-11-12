<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\SequenceCompletion;
use App\Models\User;
use App\Queries\LearnerLearningPaths;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Which learners of a promotion finished which opened sequences.
 */
class PromotionSequenceCompletionController extends Controller
{
    public function index(Promotion $promotion, LearnerLearningPaths $paths): Response
    {
        $learningPaths = $paths->get($promotion)->map(fn (array $path) => [
            'id' => $path['id'],
            'name' => $path['name'],
            'sequences' => $path['sequences']->map(fn (array $sequence) => [
                'id' => $sequence['id'],
                'title' => $sequence['title'],
                'released_at' => $sequence['released_at'],
            ]),
        ]);

        $opened = $learningPaths->flatMap(fn (array $path) => $path['sequences']->pluck('id'))->unique();
        $learners = $promotion->learners()->orderByName()->get();

        return Inertia::render('promotions/SequenceCompletions', [
            'promotion' => $promotion->only(['id', 'name']),
            'learningPaths' => $learningPaths,
            'learners' => $learners->map(fn (User $learner) => [
                'id' => $learner->id,
                'name' => $learner->name,
                'activated' => $learner->isActivated(),
            ]),
            // Hidden since: not shown, like the sequence itself.
            'completions' => $promotion->sequenceCompletions()
                ->whereIn('sequence_id', $opened)
                ->whereIn('user_id', $learners->modelKeys())
                ->orderBy('completed_at')
                ->get()
                ->map(fn (SequenceCompletion $completion) => [
                    'learner_id' => $completion->user_id,
                    'sequence_id' => $completion->sequence_id,
                    'completed_at' => $completion->completed_at,
                ]),
        ]);
    }
}
