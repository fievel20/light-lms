<?php

namespace App\Queries;

use App\Models\Promotion;

/**
 * Learners of a promotion with self-assessments the trainer has not
 * examined yet, in name order.
 */
class ReviewQueue
{
    public function __construct(private PromotionProgress $progress) {}

    /**
     * @return list<array{id: int, name: string, to_review: int}>
     */
    public function get(Promotion $promotion): array
    {
        $queue = [];

        foreach ($this->progress->get($promotion)['learners'] as $learner) {
            if ($learner['to_review'] > 0) {
                $queue[] = [
                    'id' => (int) $learner['id'],
                    'name' => (string) $learner['name'],
                    'to_review' => (int) $learner['to_review'],
                ];
            }
        }

        return $queue;
    }

    /**
     * The next learner to examine after the given one (wrapping around),
     * or null when nobody else is waiting.
     *
     * @return array{id: int, name: string, to_review: int}|null
     */
    public function nextAfter(Promotion $promotion, int $learnerId): ?array
    {
        $others = array_values(array_filter($this->get($promotion), fn (array $learner) => $learner['id'] !== $learnerId));

        if ($others === []) {
            return null;
        }

        $current = $this->sortKeyOf($promotion, $learnerId);

        foreach ($others as $learner) {
            if ($this->sortKeyOf($promotion, $learner['id']) > $current) {
                return $learner;
            }
        }

        return $others[0];
    }

    /**
     * Position of a learner in the promotion's name order.
     */
    private function sortKeyOf(Promotion $promotion, int $learnerId): int
    {
        return (int) array_search(
            $learnerId,
            $promotion->learners()->orderByName()->pluck('users.id')->all(),
            true,
        );
    }
}
