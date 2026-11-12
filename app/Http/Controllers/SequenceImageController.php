<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use App\Models\SequenceImage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SequenceImageController extends Controller
{
    /** Kilobytes; Apache and PHP-FPM limits are set above it (see deploy/). */
    public const int MAX_SIZE = 5120;

    /**
     * Upload from the editor (button, drop or paste); answers the Markdown to insert.
     */
    public function store(Request $request, Sequence $sequence): JsonResponse
    {
        $request->validate(
            ['image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_SIZE]],
            attributes: ['image' => 'image'],
        );

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $path = $file->store($sequence->imageDirectory(), SequenceImage::DISK);

        abort_if($path === false, 500, "L'image n'a pas pu être enregistrée.");

        $image = $sequence->images()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return response()->json(['id' => $image->id, 'markdown' => $image->markdown()], 201);
    }

    public function show(Request $request, Sequence $sequence, SequenceImage $image): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Learners only see the images of the sequences opened to them.
        abort_unless($sequence->isReadableBy($user), 404);

        return Storage::disk(SequenceImage::DISK)->response($image->path, $image->original_name, [
            'Content-Type' => $image->mime_type,
            // Private: only for the signed-in user; images never change once uploaded.
            'Cache-Control' => 'private, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
