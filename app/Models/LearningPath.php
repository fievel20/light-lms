<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LearningPathFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A learning path ("parcours"): an ordered list of sequences.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description'])]
class LearningPath extends Model
{
    /** @use HasFactory<LearningPathFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Sequence, $this>
     */
    public function sequences(): BelongsToMany
    {
        return $this->belongsToMany(Sequence::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position')
            ->orderBy('learning_path_sequence.id');
    }

    /**
     * @return BelongsToMany<Promotion, $this>
     */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class)->withTimestamps()->orderBy('name');
    }

    /**
     * Ids of the sequences, in the path's order.
     *
     * @return list<int>
     */
    public function sequenceIds(): array
    {
        return array_values(array_map(intval(...), $this->sequences()->pluck('sequences.id')->all()));
    }

    /**
     * Number the sequences 1, 2, 3… in the given order.
     *
     * @param  list<int>  $sequenceIds
     */
    public function arrangeSequences(array $sequenceIds): void
    {
        foreach ($sequenceIds as $index => $sequenceId) {
            $this->sequences()->updateExistingPivot($sequenceId, ['position' => $index + 1]);
        }
    }
}
