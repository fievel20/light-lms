<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\Competency;

/**
 * State of one competency for one learner, from their latest assessments.
 */
final class CompetencyCell
{
    /**
     * @return array{self: int|null, trainer: int|null, acquired: bool, gap: bool, to_review: bool}
     */
    public static function from(Competency $competency, ?Assessment $self, ?Assessment $trainer): array
    {
        return [
            'self' => $self?->value,
            'trainer' => $trainer?->value,
            'acquired' => $competency->isAcquiredWith($trainer?->value),
            // The learner and the trainer disagree.
            'gap' => $self !== null && $trainer !== null && $self->value !== $trainer->value,
            // A self-assessment the trainer has not looked at yet.
            'to_review' => $self !== null && ($trainer === null || self::isNewer($self, $trainer)),
        ];
    }

    private static function isNewer(Assessment $a, Assessment $b): bool
    {
        if ($a->created_at == $b->created_at) {
            return $a->id > $b->id;
        }

        return $a->created_at > $b->created_at;
    }
}
