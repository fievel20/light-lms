<?php

namespace Database\Seeders;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Random self-assessments and validations for the DWWM demo promotion.
     */
    public function run(): void
    {
        $promotion = Promotion::where('name', 'DWWM 2026')->whereNotNull('framework_id')->first();
        $trainer = User::where('role', 'trainer')->first();

        if ($promotion === null || $trainer === null) {
            return;
        }

        $competencies = Competency::where('framework_id', $promotion->framework_id)->with('levels')->get();

        foreach ($promotion->learners()->whereNotNull('activated_at')->get() as $learner) {
            foreach ($competencies as $competency) {
                if (fake()->boolean(25)) {
                    continue;
                }

                $values = $competency->allowedValues();
                $selfAt = now()->subDays(fake()->numberBetween(1, 20));

                $this->record($promotion, $learner, $competency, AssessmentSource::Self, fake()->randomElement($values), $learner, $selfAt);

                if (fake()->boolean(60)) {
                    $this->record($promotion, $learner, $competency, AssessmentSource::Trainer, fake()->randomElement($values), $trainer, $selfAt->addDay());
                }
            }
        }
    }

    private function record(Promotion $promotion, User $learner, Competency $competency, AssessmentSource $source, int $value, User $assessor, mixed $at): void
    {
        Assessment::create([
            'promotion_id' => $promotion->id,
            'learner_id' => $learner->id,
            'competency_id' => $competency->id,
            'assessor_id' => $assessor->id,
            'source' => $source,
            'value' => $value,
        ])->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }
}
