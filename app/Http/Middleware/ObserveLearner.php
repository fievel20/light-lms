<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ObserverMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the trainer observes a learner, each page is served as that learner,
 * and nothing can be changed. Only leaving the mode and signing out run as
 * the trainer.
 */
class ObserveLearner
{
    public function __construct(private ObserverMode $observer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $learnerId = $this->observer->learnerId();
        $trainer = $request->user();

        if ($learnerId === null || ! $trainer instanceof User) {
            return $next($request);
        }

        $learner = User::query()->find($learnerId);

        if (! $trainer->isTrainer() || ! $learner?->isLearner()) {
            $this->observer->stop();

            return $next($request);
        }

        if ($request->routeIs('observer.stop', 'logout')) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'Mode observateur : lecture seule, rien n’a été modifié.']);

            return back();
        }

        // A trainer page (browser history, bookmark): back to the learner's home.
        if (in_array('trainer', $request->route()?->gatherMiddleware() ?? [], true)) {
            return to_route('dashboard');
        }

        Auth::setUser($learner);

        try {
            return $next($request);
        } finally {
            // The session is saved after this: it must stay the trainer's.
            Auth::setUser($trainer);
        }
    }
}
