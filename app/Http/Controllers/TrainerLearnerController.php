<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Queries\PromotionProgress;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Par apprenant": the learners of a promotion with their progress.
 */
class TrainerLearnerController extends Controller
{
    public function index(Promotion $promotion, PromotionProgress $progress): Response
    {
        $data = $progress->get($promotion);

        return Inertia::render('trainer/Learners', [
            'promotion' => $promotion->only(['id', 'name', 'archived_at']),
            'competenciesCount' => count($data['competencies']),
            'learners' => array_map(fn (array $learner) => [
                'id' => $learner['id'],
                'name' => $learner['name'],
                'activated' => $learner['activated_at'] !== null,
                'percent' => $learner['percent'],
                'acquired' => $learner['acquired'],
                'total' => $learner['total'],
                'to_review' => $learner['to_review'],
            ], $data['learners']),
        ]);
    }
}
