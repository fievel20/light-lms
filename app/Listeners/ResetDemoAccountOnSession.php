<?php

namespace App\Listeners;

use App\Actions\Demo\ResetDemoAccount;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * The demo account starts empty: its data is deleted at logout, and again
 * at login in case the last session ended without one (expired, closed tab).
 */
class ResetDemoAccountOnSession
{
    public function __construct(private ResetDemoAccount $reset) {}

    public function handle(Login|Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->reset->handle($event->user);
        }
    }
}
