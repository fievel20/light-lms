<?php

namespace Database\Factories;

use App\Models\Sequence;
use App\Models\SequenceImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<SequenceImage>
 */
class SequenceImageFactory extends Factory
{
    /** A 1×1 transparent PNG. */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sequence_id' => Sequence::factory(),
            'path' => 'sequences/test/'.Str::uuid().'.png',
            'original_name' => 'capture.png',
            'mime_type' => 'image/png',
            'size' => 68,
        ];
    }

    /**
     * Write the file on the disk too, so it can be served or copied.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SequenceImage $image): void {
            Storage::disk(SequenceImage::DISK)->put($image->path, (string) base64_decode(self::PNG));
        });
    }
}
