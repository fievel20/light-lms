<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A participant's answer to a question of the session (by position), with
 * the points it earned. No answer, no row.
 *
 * @property int $id
 * @property int $express_participant_id
 * @property int $position
 * @property string $choice
 * @property int $points
 * @property CarbonImmutable $answered_at
 */
#[Fillable(['position', 'choice', 'points', 'answered_at'])]
class ExpressAnswer extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<ExpressParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExpressParticipant::class, 'express_participant_id');
    }

    protected function casts(): array
    {
        return ['answered_at' => 'immutable_datetime'];
    }
}
