<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentPromotion;
use App\Support\ObserverMode;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // The trainer is looking at this learner's space (read only).
            'observer' => fn () => app(ObserverMode::class)->active()
                ? ['learner' => $request->user()?->name]
                : null,
            // A promotion's demo account: everything is emptied at logout.
            'demo' => fn () => $request->user() instanceof User && $request->user()->isDemo(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'promotionSwitcher' => function () use ($request) {
                $user = $request->user();

                return $user instanceof User && $user->isTrainer()
                    ? app(CurrentPromotion::class)->switcher($user)
                    : null;
            },
        ];
    }
}
