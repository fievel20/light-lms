<?php

namespace App\Actions\Sequences;

use App\Models\Sequence;
use App\Models\SequenceImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateSequence
{
    /**
     * Copy a sequence and its images; the copy's Markdown points to its own
     * images, so deleting one sequence never breaks the other.
     */
    public function handle(Sequence $source): Sequence
    {
        $disk = Storage::disk(SequenceImage::DISK);

        return DB::transaction(function () use ($source, $disk): Sequence {
            $copy = Sequence::create([
                'title' => Str::limit($source->title, 245, '').' (copie)',
                'summary' => $source->summary,
                'content' => $source->content,
            ]);

            $references = [];

            foreach ($source->images as $image) {
                $path = $copy->imageDirectory().'/'.Str::uuid().'.'.pathinfo($image->path, PATHINFO_EXTENSION);
                $disk->copy($image->path, $path);

                $copied = $copy->images()->create([
                    'path' => $path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                ]);

                $references["(image:{$image->id})"] = "(image:{$copied->id})";
            }

            $copy->update(['content' => strtr($source->content, $references)]);

            return $copy;
        });
    }
}
