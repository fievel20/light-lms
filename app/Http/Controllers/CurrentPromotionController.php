<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentPromotionController extends Controller
{
    /**
     * Switch the promotion the trainer is working on.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['promotion_id' => ['required', 'integer', 'exists:promotions,id']],
            attributes: ['promotion_id' => 'promotion'],
        );

        /** @var User $trainer */
        $trainer = $request->user();
        $trainer->forceFill(['current_promotion_id' => (int) $validated['promotion_id']])->save();

        return to_route('dashboard');
    }
}
