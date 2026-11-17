<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A finished attempt at a quiz, with a copy of each question as it was and
 * the answer given (null: time ran out).
 *
 * @property int $id
 * @property int $quiz_id
 * @property int $promotion_id
 * @property int $user_id
 * @property int $score
 * @property int $total
 * @property list<array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string, answer: string|null}> $questions
 * @property CarbonImmutable $finished_at
 */
#[Fillable(['promotion_id', 'user_id', 'score', 'total', 'questions', 'finished_at'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
