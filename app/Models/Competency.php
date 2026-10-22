<?php

namespace App\Models;

use App\Enums\CompetencyType;
use Carbon\CarbonImmutable;
use Database\Factories\CompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $framework_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property CompetencyType $type
 * @property int|null $validation_threshold
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['code', 'name', 'description', 'type', 'validation_threshold', 'position'])]
class Competency extends Model
{
    /** @use HasFactory<CompetencyFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Framework, $this>
     */
    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class);
    }

    /**
     * @return HasMany<ScaleLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(ScaleLevel::class)->orderBy('value');
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Once assessed, the type and level values are frozen and it cannot be deleted.
     */
    public function isAssessed(): bool
    {
        return $this->assessments()->exists();
    }

    /**
     * Whether a value can be given to this competency
     * (0 or 1 for a binary one, one of the level values for a scale).
     */
    public function acceptsValue(int $value): bool
    {
        return in_array($value, $this->allowedValues(), true);
    }

    /**
     * Whether a (trainer) value means the competency is acquired.
     */
    public function isAcquiredWith(?int $value): bool
    {
        if ($value === null) {
            return false;
        }

        return $this->type === CompetencyType::Binary
            ? $value === 1
            : $value >= (int) $this->validation_threshold;
    }

    /**
     * @return list<int>
     */
    public function allowedValues(): array
    {
        return $this->type === CompetencyType::Binary
            ? [0, 1]
            : array_values($this->levels->map(fn (ScaleLevel $level) => $level->value)->all());
    }

    /**
     * Replace the scale levels (empty for a binary competency).
     *
     * @param  array<int, array{value: int, label: string}>  $levels
     */
    public function syncLevels(array $levels): void
    {
        $this->levels()->delete();
        $this->levels()->createMany($levels);
        $this->unsetRelation('levels');
    }

    /**
     * Shape sent to the front end.
     *
     * @return array<string, mixed>
     */
    public function toPresentation(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'validation_threshold' => $this->validation_threshold,
            'levels' => $this->levels->map->only(['value', 'label'])->values()->all(),
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CompetencyType::class,
            'validation_threshold' => 'integer',
            'position' => 'integer',
        ];
    }
}
