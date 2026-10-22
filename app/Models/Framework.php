<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FrameworkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A competency framework ("référentiel"): a flat list of competencies.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description'])]
class Framework extends Model
{
    /** @use HasFactory<FrameworkFactory> */
    use HasFactory;

    /**
     * @return HasMany<Competency, $this>
     */
    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
