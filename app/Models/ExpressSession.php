<?php

namespace App\Models;

use App\Enums\ExpressStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A live session of a quiz express. It keeps a copy of the questions taken
 * at launch, so editing the quiz changes neither this session nor its results.
 *
 * @property int $id
 * @property int $express_quiz_id
 * @property string $code
 * @property ExpressStatus $status
 * @property int $seconds_per_question
 * @property list<array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string}> $questions
 * @property int|null $current_position
 * @property CarbonImmutable|null $question_started_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $finished_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['code', 'status', 'seconds_per_question', 'questions', 'current_position', 'question_started_at', 'started_at', 'finished_at'])]
class ExpressSession extends Model
{
    /** A forgotten session closes itself after this many hours. */
    public const int LIFETIME_HOURS = 12;

    /**
     * Phones learn about a new question up to a second late: this much is
     * not counted against the speed bonus, nor against the deadline.
     */
    public const int GRACE_MS = 1000;

    public const int BASE_POINTS = 500;

    public const int MAX_BONUS = 500;

    /** No 0/O nor 1/I: the code is read aloud or typed from the screen. */
    private const string CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', ExpressStatus::Closed)
            ->where('created_at', '>', now()->subHours(self::LIFETIME_HOURS));
    }

    protected static function booted(): void
    {
        // Its text images are cached on disk.
        static::deleting(function (ExpressSession $session): void {
            Storage::disk(ExpressImage::DISK)->deleteDirectory(self::directory($session->id));
        });
    }

    /**
     * Folder of the session's cache (questions and answers drawn as images).
     */
    public static function directory(int $id): string
    {
        return "express-sessions/{$id}";
    }

    public static function newCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Codes are typed in any case, with spaces sometimes.
     */
    public static function normalizeCode(string $code): string
    {
        return Str::upper(preg_replace('/\s+/', '', $code) ?? '');
    }

    /**
     * @return BelongsTo<ExpressQuiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(ExpressQuiz::class, 'express_quiz_id');
    }

    /**
     * @return HasMany<ExpressParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ExpressParticipant::class);
    }

    /**
     * @return HasManyThrough<ExpressAnswer, ExpressParticipant, $this>
     */
    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(ExpressAnswer::class, ExpressParticipant::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== ExpressStatus::Closed
            && $this->created_at?->gt(now()->subHours(self::LIFETIME_HOURS)) !== false;
    }

    /**
     * The status as the screens see it: an expired session reads as closed.
     */
    public function currentStatus(): ExpressStatus
    {
        return $this->isOpen() ? $this->status : ExpressStatus::Closed;
    }

    public function questionCount(): int
    {
        return count($this->questions);
    }

    /**
     * @return array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string}|null
     */
    public function question(?int $position): ?array
    {
        return $position === null ? null : ($this->questions[$position - 1] ?? null);
    }

    /**
     * When answers to the current question close (without the grace).
     */
    public function deadline(): ?CarbonImmutable
    {
        return $this->question_started_at?->addSeconds($this->seconds_per_question);
    }

    /**
     * Milliseconds left to answer the current question (0 once over).
     */
    public function remainingMs(): int
    {
        $deadline = $this->deadline();

        if ($this->currentStatus() !== ExpressStatus::Question || $deadline === null) {
            return 0;
        }

        return max(0, (int) now()->diffInMilliseconds($deadline, false));
    }

    public function acceptsAnswers(): bool
    {
        $deadline = $this->deadline();

        return $this->currentStatus() === ExpressStatus::Question
            && $deadline !== null
            && now()->lte($deadline->addMilliseconds(self::GRACE_MS));
    }

    /**
     * Points for a right answer given now: 500, plus up to 500 for speed.
     */
    public function pointsNow(): int
    {
        $limitMs = $this->seconds_per_question * 1000;
        $elapsed = $this->question_started_at === null
            ? $limitMs
            : (int) $this->question_started_at->diffInMilliseconds(now()) - self::GRACE_MS;
        $elapsed = min($limitMs, max(0, $elapsed));

        return self::BASE_POINTS + (int) round(self::MAX_BONUS * (1 - $elapsed / $limitMs));
    }

    protected function casts(): array
    {
        return [
            'status' => ExpressStatus::class,
            'questions' => 'array',
            'question_started_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
