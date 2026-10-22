<?php

namespace App\Http\Controllers;

use App\Actions\Learners\EnrollLearner;
use App\Actions\Learners\EnrollmentException;
use App\Actions\Learners\EnrollmentResult;
use App\Http\Requests\StoreLearnerRequest;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PromotionLearnerController extends Controller
{
    public function store(StoreLearnerRequest $request, Promotion $promotion, EnrollLearner $enroll): RedirectResponse
    {
        /** @var array{first_name: string, last_name: string, email: string} $data */
        $data = $request->validated();

        try {
            $result = $enroll->handle($promotion, $data['first_name'], $data['last_name'], $data['email']);
        } catch (EnrollmentException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($result) {
            EnrollmentResult::Created => 'Apprenant ajouté, invitation envoyée par email.',
            EnrollmentResult::Enrolled => 'Apprenant existant ajouté à la promotion.',
        }]);

        return to_route('promotions.show', $promotion);
    }

    public function destroy(Promotion $promotion, User $learner): RedirectResponse
    {
        $promotion->learners()->detach($learner);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$learner->name} a été retiré(e) de la promotion."]);

        return to_route('promotions.show', $promotion);
    }
}
