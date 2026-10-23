<?php

namespace App\Http\Controllers;

use App\Actions\Assessments\RecordAssessment;
use App\Enums\AssessmentSource;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\LearnerCompetencies;
use App\Queries\ReviewQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The trainer's view of one learner in a promotion, and competency validation.
 */
class LearnerAssessmentController extends Controller
{
    public function show(Promotion $promotion, User $learner, LearnerCompetencies $competencies, ReviewQueue $queue): Response
    {
        $this->ensureEnrolled($promotion, $learner);

        $rows = $competencies->get($promotion, $learner);

        return Inertia::render('promotions/Learner', [
            'promotion' => $promotion->only(['id', 'name', 'archived_at']),
            'learner' => $learner->only(['id', 'first_name', 'last_name', 'name', 'email', 'activated_at']),
            'framework' => $promotion->framework()->first(['id', 'name'])?->only(['id', 'name']),
            'competencies' => $rows,
            'summary' => LearnerCompetencies::totals($rows),
            // Lazy: only needed for the "next learner" button of the quick review.
            'nextToReview' => fn () => $queue->nextAfter($promotion, $learner->id),
        ]);
    }

    public function store(Request $request, Promotion $promotion, User $learner, Competency $competency, RecordAssessment $record): RedirectResponse
    {
        $this->ensureEnrolled($promotion, $learner);
        abort_unless($competency->framework_id === $promotion->framework_id, 404);

        $validated = $request->validate([
            'value' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['value' => 'niveau', 'comment' => 'commentaire']);

        /** @var User $trainer */
        $trainer = $request->user();

        $record->handle(
            $promotion,
            $learner,
            $competency,
            AssessmentSource::Trainer,
            (int) $validated['value'],
            $trainer,
            $validated['comment'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Validation de {$competency->code} enregistrée."]);

        return back();
    }

    private function ensureEnrolled(Promotion $promotion, User $learner): void
    {
        abort_unless($promotion->learners()->whereKey($learner->id)->exists(), 404);
    }
}
