<?php

namespace App\Actions\Assessments;

use App\Enums\AssessmentSource;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecordAssessment
{
    /**
     * Record a new assessment (history is kept, the latest one counts).
     *
     * @throws ValidationException
     */
    public function handle(
        Promotion $promotion,
        User $learner,
        Competency $competency,
        AssessmentSource $source,
        int $value,
        User $assessor,
        ?string $comment = null,
    ): Assessment {
        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['value' => 'Cette promotion est archivée : les évaluations sont closes.']);
        }

        if (! $competency->acceptsValue($value)) {
            throw ValidationException::withMessages(['value' => 'Cette valeur ne correspond à aucun niveau de la compétence.']);
        }

        return Assessment::create([
            'promotion_id' => $promotion->id,
            'learner_id' => $learner->id,
            'competency_id' => $competency->id,
            'assessor_id' => $assessor->id,
            'source' => $source,
            'value' => $value,
            'comment' => $comment,
        ]);
    }
}
