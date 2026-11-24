<?php

namespace App\Actions\Express;

use App\Models\ExpressImage;
use App\Models\ExpressQuestion;
use App\Models\ExpressQuiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateExpressQuiz
{
    /**
     * Copy a quiz express with its questions and images; the copy has its
     * own image files, so deleting one quiz never breaks the other.
     */
    public function handle(ExpressQuiz $source): ExpressQuiz
    {
        $disk = Storage::disk(ExpressImage::DISK);

        return DB::transaction(function () use ($source, $disk): ExpressQuiz {
            $copy = ExpressQuiz::create([
                'title' => Str::limit($source->title, 245, '').' (copie)',
                'seconds_per_question' => $source->seconds_per_question,
            ]);

            $images = [];

            foreach ($source->images as $image) {
                $path = $copy->imageDirectory().'/'.Str::uuid().'.'.pathinfo($image->path, PATHINFO_EXTENSION);
                $disk->copy($image->path, $path);

                $images[$image->id] = $copy->images()->create([
                    'path' => $path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                ])->id;
            }

            foreach ($source->questions as $question) {
                /** @var ExpressQuestion $question */
                $copy->questions()->create([
                    ...$question->only(['position', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct']),
                    'express_image_id' => $images[$question->express_image_id] ?? null,
                ]);
            }

            return $copy;
        });
    }
}
