<?php

namespace App\Http\Controllers;

use App\Actions\Progress\SyncBadges;
use App\Enums\Badge;
use App\Models\LearnerBadge;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyBadgeController extends Controller
{
    /**
     * Every badge, earned or still to unlock.
     */
    public function index(Request $request, SyncBadges $sync): Response
    {
        /** @var User $learner */
        $learner = $request->user();
        $sync->handle($learner);

        $earned = $learner->badges()->get()->keyBy(fn (LearnerBadge $badge) => $badge->badge->value);

        return Inertia::render('learner/Badges', [
            'badges' => array_map(fn (Badge $badge) => [
                ...$badge->toPresentation(),
                'earned' => $earned->has($badge->value),
                'earned_at' => $earned->get($badge->value)?->earned_at->toIso8601String(),
            ], Badge::cases()),
        ]);
    }
}
