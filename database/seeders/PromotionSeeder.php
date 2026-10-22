<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo promotions for local development.
     */
    public function run(): void
    {
        $dwwm = Promotion::factory()->create(['name' => 'DWWM 2026', 'starts_on' => '2026-09-01', 'ends_on' => '2027-06-30']);
        $cda = Promotion::factory()->create(['name' => 'CDA 2026', 'starts_on' => '2026-03-02', 'ends_on' => '2027-02-26']);

        $learners = User::query()->where('role', Role::Learner)->orderBy('id')->get();
        $dwwm->learners()->attach($learners->take(6));
        $cda->learners()->attach($learners->skip(5));

        $dwwm->learners()->attach(User::factory()->invited()->count(2)->create());
        Promotion::factory()->archived()->create(['name' => 'DWWM 2025', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30']);
    }
}
