<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Queries\ReviewQueue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PromotionReviewController extends Controller
{
    /**
     * Start reviewing: open the first learner waiting for an examination, in quick mode.
     */
    public function __invoke(Promotion $promotion, ReviewQueue $queue): RedirectResponse
    {
        $first = $queue->get($promotion)[0] ?? null;

        if ($first === null) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Tout est à jour, aucune autoévaluation à examiner ✨']);

            return to_route('promotions.progress', $promotion);
        }

        return to_route('promotions.learners.show', [$promotion, $first['id'], 'mode' => 'rapide']);
    }
}
