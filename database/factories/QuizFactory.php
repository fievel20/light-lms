<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sequence_id' => Sequence::factory(),
            'title' => 'Quiz '.fake()->unique()->word(),
            'seconds_per_question' => 30,
        ];
    }
}
