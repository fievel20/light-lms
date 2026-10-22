<?php

namespace App\Actions\Progress;

use App\Models\Promotion;
use App\Models\User;
use App\Queries\LearnerCompetencies;
use Illuminate\Support\Facades\DB;

class PullCelebrations
{
    public function __construct(private LearnerCompetencies $competencies) {}

    /**
     * Competencies newly acquired by the learner, marked as celebrated so
     * each one is only congratulated once.
     *
     * @return list<array{promotion: string, code: string, name: string}>
     */
    public function handle(User $learner): array
    {
        $celebrations = [];
        $now = now();

        $promotions = $learner->promotions()
            ->whereNotNull('framework_id')
            ->orderBy('promotions.id')
            ->get(['promotions.id', 'name', 'framework_id']);

        foreach ($promotions as $promotion) {
            /** @var Promotion $promotion */
            $alreadyCelebrated = DB::table('competency_celebrations')
                ->where('learner_id', $learner->id)
                ->where('promotion_id', $promotion->id)
                ->pluck('competency_id')
                ->all();

            foreach ($this->competencies->get($promotion, $learner) as $competency) {
                if (! $competency['acquired'] || in_array($competency['id'], $alreadyCelebrated, true)) {
                    continue;
                }

                DB::table('competency_celebrations')->insertOrIgnore([
                    'learner_id' => $learner->id,
                    'promotion_id' => $promotion->id,
                    'competency_id' => $competency['id'],
                    'celebrated_at' => $now,
                ]);

                $celebrations[] = [
                    'promotion' => $promotion->name,
                    'code' => (string) $competency['code'],
                    'name' => (string) $competency['name'],
                ];
            }
        }

        return $celebrations;
    }
}
