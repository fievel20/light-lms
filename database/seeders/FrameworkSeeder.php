<?php

namespace Database\Seeders;

use App\Actions\Frameworks\ImportFramework;
use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FrameworkSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Import the example framework and assign it to the DWWM demo promotion.
     */
    public function run(ImportFramework $import): void
    {
        $framework = $import->handle((string) file_get_contents(base_path('docs/exemples/referentiel-dwwm.json')));

        Promotion::where('name', 'DWWM 2026')->update(['framework_id' => $framework->id]);
    }
}
