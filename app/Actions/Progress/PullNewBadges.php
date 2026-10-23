<?php

namespace App\Actions\Progress;

use App\Models\LearnerBadge;
use App\Models\User;

class PullNewBadges
{
    public function __construct(private SyncBadges $sync) {}

    /**
     * Badges earned but not shown yet, marked as seen so each one is only
     * celebrated once.
     *
     * @return list<array{key: string, label: string, description: string}>
     */
    public function handle(User $learner): array
    {
        $this->sync->handle($learner);

        $unseen = $learner->badges()->whereNull('seen_at')->orderBy('id')->get();

        if ($unseen->isNotEmpty()) {
            $learner->badges()->whereKey($unseen->modelKeys())->update(['seen_at' => now()]);
        }

        return array_values($unseen->map(fn (LearnerBadge $badge) => $badge->badge->toPresentation())->all());
    }
}
