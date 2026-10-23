<?php

namespace App\Http\Controllers;

use App\Actions\Assessments\RecordAssessment;
use App\Actions\Progress\PullCelebrations;
use App\Actions\Progress\PullNewBadges;
use App\Enums\AssessmentSource;
use App\Models\Competency;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\LearnerCompetencies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The learner's own competencies and self-assessments.
 */
class MyCompetencyController extends Controller
{
    public function index(
        Request $request,
        LearnerCompetencies $competencies,
        PullCelebrations $celebrations,
        PullNewBadges $newBadges,
    ): Response {
        /** @var User $learner */
        $learner = $request->user();

        $promotions = $learner->promotions()
            ->orderByRaw('archived_at is not null')
            ->orderByDesc('starts_on')
            ->get(['promotions.id', 'name', 'framework_id', 'starts_on', 'archived_at']);

        $promotion = $request->filled('promotion')
            ? $promotions->firstWhere('id', $request->integer('promotion')) ?? abort(404)
            : $promotions->first();

        $framework = $promotion?->framework()->first(['id', 'name']);

        return Inertia::render('learner/Competencies', [
            'promotions' => $promotions->map->only(['id', 'name'])->values(),
            'promotion' => $promotion?->only(['id', 'name', 'archived_at']),
            'framework' => $framework?->only(['id', 'name']),
            'competencies' => $promotion ? $competencies->get($promotion, $learner) : [],
            'celebrations' => $celebrations->handle($learner),
            'newBadges' => $newBadges->handle($learner),
        ]);
    }

    public function store(Request $request, Promotion $promotion, Competency $competency, RecordAssessment $record): RedirectResponse
    {
        /** @var User $learner */
        $learner = $request->user();

        abort_unless($promotion->learners()->whereKey($learner->id)->exists(), 404);
        abort_unless($competency->framework_id === $promotion->framework_id, 404);

        $validated = $request->validate(['value' => ['required', 'integer']], attributes: ['value' => 'niveau']);

        $record->handle($promotion, $learner, $competency, AssessmentSource::Self, (int) $validated['value'], $learner);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Autoévaluation de {$competency->code} enregistrée."]);

        return back();
    }
}
