<?php

namespace App\Actions\Progress;

use App\Enums\Badge;
use App\Models\User;
use App\Queries\LearnerCompetencies;

class SyncBadges
{
    public function __construct(private LearnerCompetencies $competencies) {}

    /**
     * Award the badges the learner now deserves. Badges are never taken back.
     *
     * @return list<Badge> the newly earned badges
     */
    public function handle(User $learner): array
    {
        $deserved = $this->deserved($learner);
        $owned = $learner->badges()->pluck('badge')->all();
        $new = array_values(array_filter($deserved, fn (Badge $badge) => ! in_array($badge, $owned, true)));

        foreach ($new as $badge) {
            $learner->badges()->firstOrCreate(['badge' => $badge], ['earned_at' => now()]);
        }

        return $new;
    }

    /**
     * @return list<Badge>
     */
    private function deserved(User $learner): array
    {
        $selfAssessed = 0;
        $acquired = 0;
        $confirmed = 0;
        $diligent = false;
        $expert = false;
        $bestPercent = 0;

        $promotions = $learner->promotions()->whereNotNull('framework_id')->get();

        foreach ($promotions as $promotion) {
            $rows = $this->competencies->get($promotion, $learner);
            $totals = LearnerCompetencies::totals($rows);

            $selfAssessed += $totals['self_assessed'];
            $acquired += $totals['acquired'];
            $diligent = $diligent || ($totals['total'] > 0 && $totals['self_assessed'] === $totals['total']);
            $bestPercent = max($bestPercent, $totals['percent']);

            foreach ($rows as $row) {
                $self = $row['self']['value'] ?? null;
                $trainer = $row['trainer']['value'] ?? null;

                if ($self !== null && $self === $trainer) {
                    $confirmed++;
                }

                $levelValues = $row['type'] === 'scale' ? array_column($row['levels'], 'value') : [];

                if ($trainer !== null && $levelValues !== []) {
                    $expert = $expert || $trainer === max($levelValues);
                }
            }
        }

        return array_values(array_filter([
            $selfAssessed > 0 ? Badge::FirstStep : null,
            $acquired > 0 ? Badge::FirstSuccess : null,
            $diligent ? Badge::Diligent : null,
            $confirmed >= Badge::CLEAR_SIGHTED_THRESHOLD ? Badge::ClearSighted : null,
            $expert ? Badge::Expert : null,
            $bestPercent >= 50 ? Badge::Halfway : null,
            $bestPercent >= 75 ? Badge::FinalSprint : null,
            $bestPercent >= 100 ? Badge::Graduate : null,
        ]));
    }
}
