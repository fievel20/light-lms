<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The demo account is shared: its visitors cannot change its name, email,
 * password or security (two-factor, passkeys). Only the trainer does.
 */
class ProtectDemoAccount
{
    private const LOCKED_ROUTES = [
        'profile.edit',
        'profile.update',
        'security.edit',
        'user-password.update',
        'password.confirm',
        'password.confirm.store',
        'two-factor.*',
        'passkey.store',
        'passkey.registration-options',
        'passkey.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isDemo() || ! $request->routeIs(...self::LOCKED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_FORBIDDEN, 'Compte démo : réglages verrouillés.');
        }

        // The settings menu opens on the only page left: "Apparence".
        if ($request->isMethodSafe()) {
            return to_route('appearance.edit');
        }

        Inertia::flash('toast', ['type' => 'warning', 'message' => 'Compte démo : le profil et le mot de passe ne se modifient pas.']);

        return back();
    }
}
