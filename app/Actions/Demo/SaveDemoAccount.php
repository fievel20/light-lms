<?php

namespace App\Actions\Demo;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the demo account of a promotion, or changes its email and
 * password. No invitation is sent: the email may be made up.
 */
class SaveDemoAccount
{
    public function handle(Promotion $promotion, string $email, string $password): User
    {
        return DB::transaction(function () use ($promotion, $email, $password): User {
            $demo = $promotion->demoAccount()->first() ?? new User([
                'first_name' => 'Démo',
                'last_name' => $promotion->name,
            ]);

            $demo->fill(['email' => Str::lower(trim($email)), 'password' => $password]);
            $demo->demo_password = $password;
            $demo->demo_promotion_id = $promotion->id;
            $demo->current_promotion_id = $promotion->id;
            $demo->email_verified_at ??= now();
            $demo->activated_at ??= now();
            $demo->save();

            $promotion->members()->syncWithoutDetaching([$demo->id]);

            return $demo;
        });
    }
}
