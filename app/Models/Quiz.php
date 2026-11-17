<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The quiz of a sequence (one at most), launched from its text with [Quizz].
 *
 * @property int $id
 * @property int $sequence_id
 * @property string $title
 * @property int|null $seconds_per_question
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'seconds_per_question'])]
class Quiz extends Model
{
    /** Allowed time per question, in seconds. */
    public const int MIN_SECONDS = 10;

    public const int MAX_SECONDS = 300;

    public const int MAX_QUESTIONS = 100;

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position')->orderBy('id');
    }
}
