<?php

namespace App\Queries;

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceCompletion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What a promotion's learners can read: its assigned paths, each with its
 * opened sequences in the path's order. Hidden sequences are left out.
 * Given a learner, each sequence also says when they finished it.
 */
class LearnerLearningPaths
{
    /** Opened less than this many days ago: highlighted as new. */
    public const int NEW_DAYS = 7;

    /**
     * @return Collection<int, array{id: int, name: string, description: string|null, sequences: Collection<int, array{id: int, title: string, summary: string|null, released_at: string, is_new: bool, completed_at: CarbonImmutable|null}>}>
     */
    public function get(Promotion $promotion, ?User $learner = null): Collection
    {
        $releases = $promotion->sequenceReleases()->pluck('released_at', 'sequence_id');
        // Without a learner (the trainer's view), no user has id 0: no completion.
        $completions = $promotion->sequenceCompletions()
            ->where('user_id', $learner->id ?? 0)
            ->get(['sequence_id', 'completed_at'])
            ->mapWithKeys(fn (SequenceCompletion $completion) => [$completion->sequence_id => $completion->completed_at]);
        $newSince = CarbonImmutable::now()->subDays(self::NEW_DAYS);

        return $promotion->learningPaths()
            ->with('sequences:id,title,summary')
            ->get(['learning_paths.id', 'name', 'description'])
            ->map(fn (LearningPath $path) => [
                'id' => $path->id,
                'name' => $path->name,
                'description' => $path->description,
                'sequences' => $path->sequences
                    ->filter(fn (Sequence $sequence) => $releases->has($sequence->id))
                    ->map(fn (Sequence $sequence) => [
                        ...$sequence->only(['id', 'title', 'summary']),
                        'released_at' => (string) $releases->get($sequence->id),
                        'is_new' => CarbonImmutable::parse((string) $releases->get($sequence->id))->greaterThan($newSince),
                        'completed_at' => $completions->get($sequence->id),
                    ])
                    ->values(),
            ])
            ->filter(fn (array $path) => $path['sequences']->isNotEmpty())
            ->values();
    }
}
