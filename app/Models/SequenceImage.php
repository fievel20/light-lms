<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SequenceImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An image of a sequence, stored on the private "local" disk and served by
 * an authenticated route (screenshots may show real names or addresses).
 *
 * @property int $id
 * @property int $sequence_id
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['path', 'original_name', 'mime_type', 'size'])]
class SequenceImage extends Model
{
    /** @use HasFactory<SequenceImageFactory> */
    use HasFactory;

    public const string DISK = 'local';

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * The Markdown that displays this image, e.g. ![Capture](image:12).
     */
    public function markdown(): string
    {
        $alt = str_replace(['[', ']'], '', pathinfo($this->original_name, PATHINFO_FILENAME));

        return "![{$alt}](image:{$this->id})";
    }
}
