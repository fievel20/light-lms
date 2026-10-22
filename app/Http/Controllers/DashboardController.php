<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\User;
use App\Queries\LearnerCompetencies;
use App\Queries\PromotionProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LearnerCompetencies $competencies, PromotionProgress $progress): Response
    {
        /** @var User $user */
        $user = $request->user();

        return $user->isTrainer()
            ? $this->trainer($progress)
            : $this->learner($user, $competencies);
    }

    /**
     * US10: the learner's progress in each of their promotions.
     */
    private function learner(User $learner, LearnerCompetencies $competencies): Response
    {
        $promotions = $learner->promotions()
            ->with('framework:id,name')
            ->orderByRaw('archived_at is not null')
            ->orderByDesc('starts_on')
            ->get(['promotions.id', 'name', 'framework_id', 'starts_on', 'ends_on', 'archived_at'])
            ->map(fn (Promotion $promotion) => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'archived_at' => $promotion->archived_at?->toIso8601String(),
                'framework' => $promotion->framework?->name,
                ...LearnerCompetencies::totals($competencies->get($promotion, $learner)),
            ]);

        return Inertia::render('learner/Dashboard', [
            'promotions' => $promotions->values(),
        ]);
    }

    /**
     * The trainer's overview of current promotions.
     */
    private function trainer(PromotionProgress $progress): Response
    {
        $promotions = Promotion::query()
            ->archived(false)
            ->with('framework:id,name')
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->get();

        return Inertia::render('trainer/Dashboard', [
            'promotions' => $promotions->map(function (Promotion $promotion) use ($progress): array {
                $summary = $progress->get($promotion)['summary'];

                return [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'framework' => $promotion->framework?->name,
                    'starts_on' => $promotion->starts_on->toDateString(),
                    'ends_on' => $promotion->ends_on->toDateString(),
                    'learners' => $summary['learners'],
                    'competencies' => $summary['competencies'],
                    'average_percent' => $summary['average_percent'],
                    'to_review' => $summary['to_review'],
                ];
            })->values(),
            'pendingInvitations' => User::query()
                ->whereNull('activated_at')
                ->whereHas('promotions', fn ($query) => $query->whereNull('archived_at'))
                ->count(),
        ]);
    }
}
