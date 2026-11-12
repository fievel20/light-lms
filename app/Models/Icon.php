<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\IconFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An icon of the library, shared by all sequences: a cleaned SVG on the
 * private "local" disk, shown at the size of the text.
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property bool $monochrome
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'path', 'monochrome', 'size'])]
class Icon extends Model
{
    /** @use HasFactory<IconFactory> */
    use HasFactory;

    public const string DISK = 'local';

    public const string DIRECTORY = 'icons';

    protected static function booted(): void
    {
        static::deleted(function (Icon $icon): void {
            Storage::disk(self::DISK)->delete($icon->path);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['monochrome' => 'boolean'];
    }

    /**
     * The Markdown that displays this icon, e.g. ![Fusée](icon:3).
     */
    public function markdown(): string
    {
        $alt = str_replace(['[', ']'], '', $this->name);

        return "![{$alt}](icon:{$this->id})";
    }
}
