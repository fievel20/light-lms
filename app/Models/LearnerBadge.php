<?php

namespace App\Models;

use App\Enums\Badge;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $learner_id
 * @property Badge $badge
 * @property CarbonImmutable $earned_at
 * @property CarbonImmutable|null $seen_at
 */
#[Fillable(['learner_id', 'badge', 'earned_at', 'seen_at'])]
class LearnerBadge extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<User, $this>
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'badge' => Badge::class,
            'earned_at' => 'datetime',
            'seen_at' => 'datetime',
        ];
    }
}
