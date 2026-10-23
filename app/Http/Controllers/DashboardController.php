<?php

namespace App\Http\Controllers;

use App\Actions\Progress\PullCelebrations;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\LearnerActivity;
use App\Queries\LearnerCompetencies;
use App\Queries\PromotionProgress;
use App\Support\CurrentPromotion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private LearnerActivity $activity,
        private CurrentPromotion $currentPromotion,
    ) {}

    public function __invoke(
        Request $request,
        LearnerCompetencies $competencies,
        PromotionProgress $progress,
        PullCelebrations $celebrations,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        return $user->isTrainer()
            ? $this->trainer($user, $progress)
            : $this->learner($user, $competencies, $celebrations);
    }

    /**
     * US10: the learner's progress in each of their promotions.
     */
    private function learner(User $learner, LearnerCompetencies $competencies, PullCelebrations $celebrations): Response
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
            'activity' => $this->activity->get($learner),
            'celebrations' => $celebrations->handle($learner),
        ]);
    }

    /**
     * Overview of the promotion chosen in the menu.
     */
    private function trainer(User $trainer, PromotionProgress $progress): Response
    {
        $promotion = $this->currentPromotion->resolve($trainer);

        return Inertia::render('trainer/Dashboard', [
            'promotion' => $promotion ? [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'framework' => $promotion->framework()->value('name'),
                'starts_on' => $promotion->starts_on->toDateString(),
                'ends_on' => $promotion->ends_on->toDateString(),
                'archived_at' => $promotion->archived_at?->toIso8601String(),
            ] : null,
            'summary' => $promotion ? $progress->get($promotion)['summary'] : null,
            'pendingInvitations' => $promotion
                ? $promotion->learners()->whereNull('activated_at')->count()
                : 0,
        ]);
    }
}
