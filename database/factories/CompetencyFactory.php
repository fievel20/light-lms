<?php

namespace Database\Factories;

use App\Enums\CompetencyType;
use App\Models\Competency;
use App\Models\Framework;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competency>
 */
class CompetencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'framework_id' => Framework::factory(),
            'code' => fake()->unique()->bothify('C##?'),
            'name' => fake()->sentence(4),
            'description' => null,
            'type' => CompetencyType::Binary,
            'validation_threshold' => null,
            'position' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * A competency evaluated on a scale.
     *
     * @param  array<int, string>  $levels  value => label
     */
    public function scale(array $levels = [1 => 'Découverte', 2 => 'Guidé', 3 => 'Autonome', 4 => 'Expert'], ?int $threshold = null): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'type' => CompetencyType::Scale,
                'validation_threshold' => $threshold ?? ($levels === [] ? null : max(array_keys($levels))),
            ])
            ->afterCreating(function (Competency $competency) use ($levels) {
                $competency->syncLevels(array_map(
                    fn (int $value, string $label) => ['value' => $value, 'label' => $label],
                    array_keys($levels),
                    $levels,
                ));
            });
    }
}
