<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentSource;
use App\Models\Competency;
use App\Models\Promotion;
use App\Queries\LatestAssessments;
use App\Queries\PromotionProgress;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Par compétence": the competencies of a promotion, then every learner for one competency.
 */
class TrainerCompetencyController extends Controller
{
    public function __construct(private PromotionProgress $progress) {}

    public function index(Promotion $promotion): Response
    {
        $progress = $this->progress->get($promotion);

        return Inertia::render('trainer/Competencies', [
            'promotion' => $promotion->only(['id', 'name', 'archived_at']),
            'learnersCount' => count($progress['learners']),
            'competencies' => array_map(fn (array $competency) => [
                'id' => $competency['id'],
                'code' => $competency['code'],
                'name' => $competency['name'],
                'acquired_count' => $competency['acquired_count'],
                'to_review' => count(array_filter(
                    $progress['learners'],
                    fn (array $learner) => $learner['cells'][$competency['id']]['to_review'],
                )),
            ], $progress['competencies']),
        ]);
    }

    public function show(Promotion $promotion, Competency $competency, LatestAssessments $latest): Response
    {
        abort_unless($competency->framework_id === $promotion->framework_id, 404);

        $progress = $this->progress->get($promotion);
        $assessments = $latest->get($promotion);
        $ids = array_column($progress['competencies'], 'id');
        $position = (int) array_search($competency->id, $ids, true);
        $neighbour = fn (int $index) => isset($progress['competencies'][$index])
            ? ['id' => $progress['competencies'][$index]['id'], 'code' => $progress['competencies'][$index]['code']]
            : null;

        return Inertia::render('trainer/Competency', [
            'promotion' => $promotion->only(['id', 'name', 'archived_at']),
            'competency' => $progress['competencies'][$position],
            'previous' => $neighbour($position - 1),
            'next' => $neighbour($position + 1),
            'learners' => array_map(function (array $learner) use ($competency, $assessments) {
                $cell = $learner['cells'][$competency->id];
                $trainer = $assessments->get(LatestAssessments::key($learner['id'], $competency->id, AssessmentSource::Trainer));

                return [
                    'id' => $learner['id'],
                    'name' => $learner['name'],
                    'activated' => $learner['activated_at'] !== null,
                    'self' => $cell['self'],
                    'trainer' => $cell['trainer'],
                    'comment' => $trainer?->comment,
                    'acquired' => $cell['acquired'],
                    'to_review' => $cell['to_review'],
                ];
            }, $progress['learners']),
        ]);
    }
}
