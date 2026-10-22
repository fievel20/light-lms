<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 year', '+6 months');

        return [
            'name' => fake()->unique()->bothify('Promo ???-####'),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+10 months'),
        ];
    }

    /**
     * Indicate that the promotion is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
