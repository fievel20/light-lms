<?php

namespace App\Queries;

use App\Enums\AssessmentSource;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use App\Support\CompetencyCell;
use Illuminate\Database\Eloquent\Collection;

/**
 * Learners × competencies matrix of a promotion, with per-learner and
 * per-competency totals.
 */
class PromotionProgress
{
    public function __construct(private LatestAssessments $latestAssessments) {}

    /**
     * @return array{
     *     competencies: list<array<string, mixed>>,
     *     learners: list<array<string, mixed>>,
     *     summary: array{learners: int, competencies: int, average_percent: int, to_review: int},
     * }
     */
    public function get(Promotion $promotion): array
    {
        $competencies = $this->competencies($promotion);
        $latest = $this->latestAssessments->get($promotion);
        $acquiredByCompetency = array_fill_keys($competencies->modelKeys(), 0);

        $learners = array_values($promotion->learners()
            ->orderByName()
            ->get(['users.id', 'first_name', 'last_name', 'email', 'activated_at'])
            ->map(function (User $learner) use ($competencies, $latest, &$acquiredByCompetency): array {
                $cells = [];

                foreach ($competencies as $competency) {
                    $cell = CompetencyCell::from(
                        $competency,
                        $latest->get(LatestAssessments::key($learner->id, $competency->id, AssessmentSource::Self)),
                        $latest->get(LatestAssessments::key($learner->id, $competency->id, AssessmentSource::Trainer)),
                    );
                    $cells[$competency->id] = $cell;
                    $acquiredByCompetency[$competency->id] += (int) $cell['acquired'];
                }

                return [
                    'id' => $learner->id,
                    'name' => $learner->name,
                    'activated_at' => $learner->activated_at?->toIso8601String(),
                    ...self::totals($cells),
                    'cells' => $cells,
                ];
            })
            ->all());

        return [
            'competencies' => array_values($competencies
                ->map(fn (Competency $competency) => [
                    ...$competency->toPresentation(),
                    'acquired_count' => $acquiredByCompetency[$competency->id],
                ])
                ->all()),
            'learners' => $learners,
            'summary' => [
                'learners' => count($learners),
                'competencies' => $competencies->count(),
                'average_percent' => $learners === [] ? 0 : (int) round(array_sum(array_column($learners, 'percent')) / count($learners)),
                'to_review' => array_sum(array_column($learners, 'to_review')),
            ],
        ];
    }

    /**
     * @param  array<int, array{self: int|null, trainer: int|null, acquired: bool, gap: bool, to_review: bool}>  $cells
     * @return array{total: int, acquired: int, self_assessed: int, to_review: int, gaps: int, percent: int}
     */
    public static function totals(array $cells): array
    {
        $total = count($cells);
        $acquired = count(array_filter($cells, fn (array $cell) => $cell['acquired']));

        return [
            'total' => $total,
            'acquired' => $acquired,
            'self_assessed' => count(array_filter($cells, fn (array $cell) => $cell['self'] !== null)),
            'to_review' => count(array_filter($cells, fn (array $cell) => $cell['to_review'])),
            'gaps' => count(array_filter($cells, fn (array $cell) => $cell['gap'])),
            'percent' => $total === 0 ? 0 : (int) round($acquired * 100 / $total),
        ];
    }

    /**
     * @return Collection<int, Competency>
     */
    private function competencies(Promotion $promotion): Collection
    {
        if ($promotion->framework_id === null) {
            return new Collection;
        }

        return Competency::query()
            ->where('framework_id', $promotion->framework_id)
            ->with('levels')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }
}
