<?php

namespace Database\Factories;

use App\Models\ExpressQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpressQuiz>
 */
class ExpressQuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Quiz express '.fake()->unique()->word(),
            'seconds_per_question' => ExpressQuiz::DEFAULT_SECONDS,
        ];
    }

    /**
     * With questions: the first answer is right, except for the last one.
     */
    public function withQuestions(int $count = 3): static
    {
        return $this->afterCreating(function (ExpressQuiz $quiz) use ($count): void {
            foreach (range(1, $count) as $position) {
                $quiz->questions()->create([
                    'position' => $position,
                    'question' => "Question {$position} ?",
                    'choice_a' => 'Oui',
                    'choice_b' => 'Non',
                    'choice_c' => 'Peut-être',
                    'correct' => $position === $count ? 'b' : 'a',
                ]);
            }
        });
    }
}
