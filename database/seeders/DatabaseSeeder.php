<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Données de développement local uniquement (mot de passe : "password").
        User::factory()->trainer()->create([
            'name' => 'Formateur Démo',
            'email' => 'formateur@example.com',
        ]);

        User::factory()->create([
            'name' => 'Apprenant Démo',
            'email' => 'apprenant@example.com',
        ]);

        User::factory(8)->create();

        $this->call(PromotionSeeder::class);
    }
}
