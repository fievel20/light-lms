<?php

namespace Database\Factories;

use App\Models\Competency;
use App\Models\ScaleLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScaleLevel>
 */
class ScaleLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competency_id' => Competency::factory()->scale([]),
            'value' => fake()->unique()->numberBetween(1, 10),
            'label' => fake()->word(),
        ];
    }
}
