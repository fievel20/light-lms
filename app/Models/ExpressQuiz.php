<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ExpressQuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A quiz played live in the room: the trainer projects a QR code, the
 * participants join without an account and the trainer leads the questions.
 *
 * @property int $id
 * @property string $title
 * @property int $seconds_per_question
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'seconds_per_question'])]
class ExpressQuiz extends Model
{
    /** @use HasFactory<ExpressQuizFactory> */
    use HasFactory;

    public const int DEFAULT_SECONDS = 20;

    protected static function booted(): void
    {
        // The rows go with the foreign key; the files have to be removed here.
        static::deleting(function (ExpressQuiz $quiz): void {
            Storage::disk(ExpressImage::DISK)->delete($quiz->images()->pluck('path')->all());
        });
    }

    /**
     * @return HasMany<ExpressQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ExpressQuestion::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ExpressImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ExpressImage::class);
    }

    /**
     * Folder of this quiz's images on the private disk.
     */
    public function imageDirectory(): string
    {
        return "express/{$this->id}";
    }
}
