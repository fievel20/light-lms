<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sequence opened to a promotion: its learners can read it.
 *
 * @property int $id
 * @property int $promotion_id
 * @property int $sequence_id
 * @property CarbonImmutable $released_at
 * @property int|null $released_by
 */
#[Fillable(['sequence_id', 'released_at', 'released_by'])]
class SequenceRelease extends Model
{
    public $timestamps = false;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }
}
