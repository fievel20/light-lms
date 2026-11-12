<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int|null $framework_id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'framework_id', 'starts_on', 'ends_on'])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Framework, $this>
     */
    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * @return BelongsToMany<LearningPath, $this>
     */
    public function learningPaths(): BelongsToMany
    {
        return $this->belongsToMany(LearningPath::class)->withTimestamps()->orderBy('name');
    }

    /**
     * @return HasMany<SequenceRelease, $this>
     */
    public function sequenceReleases(): HasMany
    {
        return $this->hasMany(SequenceRelease::class);
    }

    /**
     * Ids of the sequences of the paths assigned to this promotion.
     *
     * @return list<int>
     */
    public function pathSequenceIds(): array
    {
        $ids = Sequence::query()
            ->whereHas('learningPaths.promotions', fn (Builder $query) => $query->whereKey($this->id))
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return array_values(array_map(intval(...), $ids));
    }

    /**
     * Ids of the sequences opened to this promotion.
     *
     * @return list<int>
     */
    public function releasedSequenceIds(): array
    {
        return array_values(array_map(intval(...), $this->sequenceReleases()->orderBy('sequence_id')->pluck('sequence_id')->all()));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function unarchive(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeArchived(Builder $query, bool $archived = true): void
    {
        $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'archived_at' => 'datetime',
        ];
    }
}
