<?php

namespace App\Http\Controllers;

use App\Actions\Demo\ResetDemoAccount;
use App\Actions\Demo\SaveDemoAccount;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * The demo account of a promotion: a made-up email and a simple password,
 * to show the learners' space. Its data is emptied at each logout.
 */
class PromotionDemoAccountController extends Controller
{
    public function update(Request $request, Promotion $promotion, SaveDemoAccount $save): RedirectResponse
    {
        $demo = $promotion->demoAccount()->first();

        $data = $request->validate([
            'demo_email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($demo?->id)],
            // Kept simple on purpose: the account holds nothing personal.
            'demo_password' => ['required', 'string', 'min:4', 'max:100'],
        ], attributes: ['demo_email' => 'adresse email', 'demo_password' => 'mot de passe']);

        $save->handle($promotion, $data['demo_email'], $data['demo_password']);

        Inertia::flash('toast', ['type' => 'success', 'message' => $demo ? 'Compte démo modifié.' : 'Compte démo créé.']);

        return to_route('promotions.show', $promotion);
    }

    /**
     * Empties the account now, without waiting for its logout.
     */
    public function reset(Promotion $promotion, ResetDemoAccount $reset): RedirectResponse
    {
        $reset->handle($promotion->demoAccount()->firstOrFail());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compte démo remis à zéro.']);

        return to_route('promotions.show', $promotion);
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->demoAccount()->firstOrFail()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compte démo supprimé.']);

        return to_route('promotions.show', $promotion);
    }
}
