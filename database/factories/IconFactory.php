<?php

namespace Database\Factories;

use App\Models\Icon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Icon>
 */
class IconFactory extends Factory
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Icône '.fake()->unique()->word(),
            'path' => Icon::DIRECTORY.'/'.Str::uuid().'.svg',
            'monochrome' => true,
            'size' => strlen(self::SVG),
        ];
    }

    /**
     * Write the file on the disk too, so it can be served.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Icon $icon): void {
            Storage::disk(Icon::DISK)->put($icon->path, self::SVG);
        });
    }
}
