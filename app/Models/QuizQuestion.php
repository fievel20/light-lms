<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question with two to four choices (a, b, then optional c and d) and the
 * letter of the right one.
 *
 * @property int $id
 * @property int $quiz_id
 * @property int $position
 * @property string $question
 * @property int|null $sequence_image_id
 * @property string $choice_a
 * @property string $choice_b
 * @property string|null $choice_c
 * @property string|null $choice_d
 * @property string $correct
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['position', 'question', 'sequence_image_id', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct'])]
class QuizQuestion extends Model
{
    public const array LETTERS = ['a', 'b', 'c', 'd'];

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<SequenceImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(SequenceImage::class, 'sequence_image_id');
    }

    /**
     * The choices that are filled in, by letter: ['a' => 'Sydney', …].
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        $choices = [];

        foreach (self::LETTERS as $letter) {
            $text = $this->{"choice_{$letter}"};

            if (is_string($text) && $text !== '') {
                $choices[$letter] = $text;
            }
        }

        return $choices;
    }
}
