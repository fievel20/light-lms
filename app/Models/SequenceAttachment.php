<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file of a sequence to download (PDF, document…), stored on the private
 * disk like its images and served by an authenticated route.
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
class SequenceAttachment extends Model
{
    public const string DISK = SequenceImage::DISK;

    /**
     * Allowed files, by extension, with the type they are served with.
     * No HTML, SVG or script: nothing the browser would run.
     */
    public const array TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
    ];

    /** Opened in the browser; the others are downloaded. */
    public const array INLINE = ['pdf', 'txt', 'mp3', 'mp4'];

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    /**
     * The Markdown link to this file, e.g. [fiche.pdf](fichier:12).
     */
    public function markdown(): string
    {
        $text = str_replace(['[', ']'], '', $this->original_name);

        return "[{$text}](fichier:{$this->id})";
    }
}
