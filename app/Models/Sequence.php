<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A teaching sequence ("séquence pédagogique"): a workshop written in Markdown.
 *
 * @property int $id
 * @property string $title
 * @property string|null $summary
 * @property string $content
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'summary', 'content'])]
class Sequence extends Model
{
    /** @use HasFactory<SequenceFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // The rows go with the foreign key; the files have to be removed here.
        static::deleting(function (Sequence $sequence): void {
            Storage::disk(SequenceImage::DISK)->delete($sequence->images()->pluck('path')->all());
        });
    }

    /**
     * @return HasMany<SequenceImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(SequenceImage::class);
    }

    /**
     * Folder of this sequence's images on the private disk.
     */
    public function imageDirectory(): string
    {
        return "sequences/{$this->id}";
    }
}
