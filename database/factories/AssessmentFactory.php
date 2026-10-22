<?php

namespace Database\Factories;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'learner_id' => User::factory(),
            'competency_id' => Competency::factory(),
            'assessor_id' => fn (array $attributes) => $attributes['learner_id'],
            'source' => AssessmentSource::Self,
            'value' => 1,
            'comment' => null,
        ];
    }

    /**
     * Self-assessment by the given learner.
     */
    public function selfAssessed(User $learner): static
    {
        return $this->state(fn (array $attributes) => [
            'learner_id' => $learner->id,
            'assessor_id' => $learner->id,
            'source' => AssessmentSource::Self,
        ]);
    }

    /**
     * Validation of the learner's competency by the trainer.
     */
    public function byTrainer(User $trainer, User $learner): static
    {
        return $this->state(fn (array $attributes) => [
            'learner_id' => $learner->id,
            'assessor_id' => $trainer->id,
            'source' => AssessmentSource::Trainer,
        ]);
    }
}
