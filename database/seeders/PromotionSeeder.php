<?php

namespace Database\Seeders;

use App\Models\Promotion;
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
        Promotion::factory()->create(['name' => 'DWWM 2026', 'starts_on' => '2026-09-01', 'ends_on' => '2027-06-30']);
        Promotion::factory()->create(['name' => 'CDA 2026', 'starts_on' => '2026-03-02', 'ends_on' => '2027-02-26']);
        Promotion::factory()->archived()->create(['name' => 'DWWM 2025', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30']);
    }
}
