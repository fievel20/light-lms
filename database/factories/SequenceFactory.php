<?php

namespace Database\Factories;

use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sequence>
 */
class SequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Atelier '.fake()->unique()->word(),
            'summary' => fake()->optional()->sentence(),
            'content' => "1. Lance l'exercice\n2. Atteins l'objectif 🎯",
        ];
    }
}
