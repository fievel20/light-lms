<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sequence the learner marked as finished, in one of their promotions.
 *
 * @property int $id
 * @property int $promotion_id
 * @property int $sequence_id
 * @property int $user_id
 * @property CarbonImmutable $completed_at
 */
#[Fillable(['promotion_id', 'sequence_id', 'user_id', 'completed_at'])]
class SequenceCompletion extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Promotion, $this>
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
