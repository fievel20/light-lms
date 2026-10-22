<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The competencies of a promotion's framework with a learner's latest
 * self-assessment and trainer validation.
 */
class LearnerCompetencies
{
    /**
     * @return list<array<string, mixed>>
     */
    public function get(Promotion $promotion, User $learner): array
    {
        if ($promotion->framework_id === null) {
            return [];
        }

        /** @var Collection<string, Assessment> $latest keyed by "competency_id:source" */
        $latest = Assessment::query()
            ->where('promotion_id', $promotion->id)
            ->where('learner_id', $learner->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (Assessment $assessment) => "{$assessment->competency_id}:{$assessment->source->value}")
            ->keyBy(fn (Assessment $assessment) => "{$assessment->competency_id}:{$assessment->source->value}");

        return array_values(Competency::query()
            ->where('framework_id', $promotion->framework_id)
            ->with('levels')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function (Competency $competency) use ($latest): array {
                $self = $latest->get("{$competency->id}:".AssessmentSource::Self->value);
                $trainer = $latest->get("{$competency->id}:".AssessmentSource::Trainer->value);

                return [
                    ...$competency->toPresentation(),
                    'self' => $self ? [
                        'value' => $self->value,
                        'created_at' => $self->created_at?->toIso8601String(),
                    ] : null,
                    'trainer' => $trainer ? [
                        'value' => $trainer->value,
                        'comment' => $trainer->comment,
                        'created_at' => $trainer->created_at?->toIso8601String(),
                    ] : null,
                    'acquired' => $competency->isAcquiredWith($trainer?->value),
                ];
            })
            ->all());
    }
}
