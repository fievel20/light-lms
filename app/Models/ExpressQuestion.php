<?php

namespace App\Models;

use App\Models\Concerns\HasChoices;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question of a quiz express, like those of a sequence quiz.
 *
 * @property int $id
 * @property int $express_quiz_id
 * @property int $position
 * @property string $question
 * @property int|null $express_image_id
 * @property string $choice_a
 * @property string $choice_b
 * @property string|null $choice_c
 * @property string|null $choice_d
 * @property string $correct
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['position', 'question', 'express_image_id', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct'])]
class ExpressQuestion extends Model
{
    use HasChoices;

    /**
     * @return BelongsTo<ExpressQuiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(ExpressQuiz::class, 'express_quiz_id');
    }

    /**
     * @return BelongsTo<ExpressImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ExpressImage::class, 'express_image_id');
    }
}
