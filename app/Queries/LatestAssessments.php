<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * The latest self-assessment and trainer validation of each competency,
 * for every learner of a promotion (or a single learner).
 */
class LatestAssessments
{
    /**
     * @return Collection<string, Assessment> keyed by "learner_id:competency_id:source"
     */
    public function get(Promotion $promotion, ?int $learnerId = null): Collection
    {
        return Assessment::query()
            ->where('promotion_id', $promotion->id)
            ->when($learnerId !== null, fn ($query) => $query->where('learner_id', $learnerId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (Assessment $assessment) => self::key($assessment->learner_id, $assessment->competency_id, $assessment->source))
            ->keyBy(fn (Assessment $assessment) => self::key($assessment->learner_id, $assessment->competency_id, $assessment->source));
    }

    public static function key(int $learnerId, int $competencyId, AssessmentSource $source): string
    {
        return "{$learnerId}:{$competencyId}:{$source->value}";
    }
}
