<?php

namespace App\Http\Controllers;

use App\Actions\Learners\EnrollLearner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LearnerInvitationController extends Controller
{
    /**
     * Send the invitation email again to a learner who has not activated their account.
     */
    public function store(User $learner, EnrollLearner $enroll): RedirectResponse
    {
        abort_unless($learner->isLearner(), 404);

        if ($learner->isActivated()) {
            throw ValidationException::withMessages(['invitation' => 'Ce compte est déjà activé.']);
        }

        $enroll->invite($learner);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invitation renvoyée à {$learner->email}."]);

        return back();
    }
}
