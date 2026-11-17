<?php

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'promotion_id' => Promotion::factory(),
            'user_id' => User::factory(),
            'score' => 1,
            'total' => 1,
            'questions' => [[
                'id' => 1,
                'question' => 'La Terre est ronde.',
                'image_id' => null,
                'choices' => ['a' => 'Vrai', 'b' => 'Faux'],
                'correct' => 'a',
                'answer' => 'a',
            ]],
            'finished_at' => now(),
        ];
    }
}
