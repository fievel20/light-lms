<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ObserverMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class ObserverController extends Controller
{
    /**
     * The trainer starts seeing the application as this learner.
     */
    public function store(User $learner, ObserverMode $observer): RedirectResponse
    {
        abort_unless($learner->isLearner(), 404);

        $observer->start($learner, URL::previous(route('dashboard')));

        return to_route('dashboard');
    }

    public function destroy(ObserverMode $observer): RedirectResponse
    {
        return redirect($observer->stop());
    }
}
