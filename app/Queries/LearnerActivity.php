<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Enums\CompetencyType;
use App\Models\Assessment;
use App\Models\User;

/**
 * Recent trainer validations of a learner's competencies.
 */
class LearnerActivity
{
    /**
     * @return list<array<string, mixed>> newest first
     */
    public function get(User $learner, int $limit = 8): array
    {
        return array_values(Assessment::query()
            ->where('learner_id', $learner->id)
            ->where('source', AssessmentSource::Trainer)
            ->with(['competency.levels', 'promotion:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Assessment $assessment) => [
                'code' => $assessment->competency->code,
                'competency' => $assessment->competency->name,
                'level' => $this->levelLabel($assessment),
                'acquired' => $assessment->competency->isAcquiredWith($assessment->value),
                'comment' => $assessment->comment,
                'promotion' => $assessment->promotion->name,
                'at' => $assessment->created_at?->toIso8601String(),
            ])
            ->all());
    }

    private function levelLabel(Assessment $assessment): string
    {
        $competency = $assessment->competency;

        if ($competency->type === CompetencyType::Binary) {
            return $assessment->value === 1 ? 'Acquis' : 'Non acquis';
        }

        return $competency->levels->firstWhere('value', $assessment->value)->label ?? (string) $assessment->value;
    }
}
