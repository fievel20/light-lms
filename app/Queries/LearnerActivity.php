<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\LearnerBadge;
use App\Models\User;

/**
 * Recent events for a learner: trainer validations and earned badges.
 */
class LearnerActivity
{
    /**
     * @return list<array<string, mixed>> newest first
     */
    public function get(User $learner, int $limit = 8): array
    {
        $validations = Assessment::query()
            ->where('learner_id', $learner->id)
            ->where('source', AssessmentSource::Trainer)
            ->with(['competency.levels', 'promotion:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Assessment $assessment) => [
                'type' => 'validation',
                'code' => $assessment->competency->code,
                'competency' => $assessment->competency->name,
                'level' => $this->levelLabel($assessment),
                'acquired' => $assessment->competency->isAcquiredWith($assessment->value),
                'comment' => $assessment->comment,
                'promotion' => $assessment->promotion->name,
                'at' => $assessment->created_at?->toIso8601String(),
            ]);

        $badges = $learner->badges()
            ->orderByDesc('earned_at')
            ->limit($limit)
            ->get()
            ->map(fn (LearnerBadge $badge) => [
                'type' => 'badge',
                'badge' => $badge->badge->toPresentation(),
                'at' => $badge->earned_at->toIso8601String(),
            ]);

        return array_values(
            $validations->concat($badges)
                ->sortByDesc('at')
                ->take($limit)
                ->all(),
        );
    }

    private function levelLabel(Assessment $assessment): string
    {
        $competency = $assessment->competency;

        if ($competency->type->value === 'binary') {
            return $assessment->value === 1 ? 'Acquis' : 'Non acquis';
        }

        return $competency->levels->firstWhere('value', $assessment->value)->label ?? (string) $assessment->value;
    }
}
