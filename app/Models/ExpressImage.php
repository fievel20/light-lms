<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An image of a quiz express question, on the private disk like the images
 * of the sequences.
 *
 * @property int $id
 * @property int $express_quiz_id
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['path', 'original_name', 'mime_type', 'size'])]
class ExpressImage extends Model
{
    public const string DISK = SequenceImage::DISK;

    /**
     * @return BelongsTo<ExpressQuiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(ExpressQuiz::class, 'express_quiz_id');
    }
}
