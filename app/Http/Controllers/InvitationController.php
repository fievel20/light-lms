<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an invited learner choose a password through a signed link.
 */
class InvitationController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if ($user->isActivated()) {
            return $this->alreadyActivated();
        }

        return Inertia::render('auth/AcceptInvitation', [
            'email' => $user->email,
            'firstName' => $user->first_name,
            'action' => $request->fullUrl(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function accept(Request $request, User $user): RedirectResponse
    {
        if ($user->isActivated()) {
            return $this->alreadyActivated();
        }

        $request->validate(['password' => $this->passwordRules()]);

        $user->forceFill([
            'password' => $request->string('password')->value(),
            'activated_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bienvenue ! Ton compte est activé.']);

        return to_route('dashboard');
    }

    private function alreadyActivated(): RedirectResponse
    {
        return to_route('login')->with('status', 'Ton compte est déjà activé, tu peux te connecter.');
    }
}
