<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
