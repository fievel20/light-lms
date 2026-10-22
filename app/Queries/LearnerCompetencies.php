<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use App\Support\CompetencyCell;

/**
 * The competencies of a promotion's framework with a learner's latest
 * self-assessment and trainer validation.
 */
class LearnerCompetencies
{
    public function __construct(private LatestAssessments $latestAssessments) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function get(Promotion $promotion, User $learner): array
    {
        if ($promotion->framework_id === null) {
            return [];
        }

        $latest = $this->latestAssessments->get($promotion, $learner->id);

        return array_values(Competency::query()
            ->where('framework_id', $promotion->framework_id)
            ->with('levels')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function (Competency $competency) use ($latest, $learner): array {
                $self = $latest->get(LatestAssessments::key($learner->id, $competency->id, AssessmentSource::Self));
                $trainer = $latest->get(LatestAssessments::key($learner->id, $competency->id, AssessmentSource::Trainer));
                $cell = CompetencyCell::from($competency, $self, $trainer);

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
                    'acquired' => $cell['acquired'],
                    'gap' => $cell['gap'],
                    'to_review' => $cell['to_review'],
                ];
            })
            ->all());
    }

    /**
     * Totals for a list returned by get().
     *
     * @param  list<array<string, mixed>>  $competencies
     * @return array{total: int, acquired: int, self_assessed: int, to_review: int, gaps: int, percent: int}
     */
    public static function totals(array $competencies): array
    {
        return PromotionProgress::totals(array_map(fn (array $competency) => [
            'self' => $competency['self']['value'] ?? null,
            'trainer' => $competency['trainer']['value'] ?? null,
            'acquired' => (bool) $competency['acquired'],
            'gap' => (bool) $competency['gap'],
            'to_review' => (bool) $competency['to_review'],
        ], $competencies));
    }
}
