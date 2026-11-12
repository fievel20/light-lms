<?php

namespace App\Http\Controllers;

use App\Actions\Learners\EnrollLearner;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account administration of the learners: the trainer fixes a mistyped email
 * or gets a learner back in when they forgot their password.
 */
class LearnerAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('learners/Index', [
            'learners' => User::query()
                ->where('role', Role::Learner)
                ->with('promotions:id,name')
                ->orderByName()
                ->get()
                ->map(fn (User $learner) => [
                    'id' => $learner->id,
                    'name' => $learner->name,
                    'email' => $learner->email,
                    'activated' => $learner->isActivated(),
                    'promotions' => $learner->promotions->pluck('name'),
                ]),
        ]);
    }

    public function edit(User $learner): Response
    {
        abort_unless($learner->isLearner(), 404);

        return Inertia::render('learners/Edit', [
            'learner' => [
                'id' => $learner->id,
                'name' => $learner->name,
                'email' => $learner->email,
                'activated' => $learner->isActivated(),
            ],
        ]);
    }

    /**
     * A new address; a learner who never activated their account is invited
     * again there (the first invitation probably went to the wrong address).
     */
    public function updateEmail(Request $request, User $learner, EnrollLearner $enroll): RedirectResponse
    {
        abort_unless($learner->isLearner(), 404);

        $request->merge(['email' => Str::lower(trim($request->string('email')->value()))]);
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($learner)],
        ], ['email.unique' => 'Cette adresse est déjà utilisée par un autre compte.']);

        $learner->forceFill(['email' => $validated['email']])->save();

        if ($learner->isActivated()) {
            $message = 'Email mis à jour.';
        } else {
            $enroll->invite($learner);
            $message = "Email mis à jour, invitation renvoyée à {$learner->email}.";
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('learners.edit', $learner);
    }

    /**
     * The usual "forgot password" email: the learner chooses a new password.
     */
    public function sendResetLink(User $learner): RedirectResponse
    {
        abort_unless($learner->isLearner(), 404);

        if (! $learner->isActivated()) {
            throw ValidationException::withMessages([
                'reset_link' => 'Ce compte n’est pas encore activé : renvoie plutôt l’invitation.',
            ]);
        }

        $status = Password::broker()->sendResetLink(['email' => $learner->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['reset_link' => __($status)]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Lien de réinitialisation envoyé à {$learner->email}."]);

        return to_route('learners.edit', $learner);
    }

    /**
     * A password the trainer gives the learner by word of mouth (no access to
     * their mailbox, for instance). Their other sessions are closed.
     */
    public function updatePassword(Request $request, User $learner): RedirectResponse
    {
        abort_unless($learner->isLearner(), 404);

        $request->validate(['password' => ['required', 'string', PasswordRule::default()]]);

        DB::transaction(function () use ($request, $learner): void {
            $learner->forceFill([
                'password' => $request->string('password')->value(),
                'remember_token' => Str::random(60),
                // An invited learner can sign in straight away.
                'activated_at' => $learner->activated_at ?? now(),
                'email_verified_at' => $learner->email_verified_at ?? now(),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table((string) config('session.table', 'sessions'))->where('user_id', $learner->id)->delete();
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mot de passe provisoire enregistré pour {$learner->name}."]);

        return to_route('learners.edit', $learner);
    }
}
