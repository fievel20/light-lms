<?php

namespace App\Http\Controllers;

use App\Models\ExpressImage;
use App\Models\ExpressQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The images of the questions of a quiz express, for the trainer's editor
 * and projected screen.
 */
class ExpressImageController extends Controller
{
    public function store(Request $request, ExpressQuiz $expressQuiz): JsonResponse
    {
        $request->validate(
            ['image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.SequenceImageController::MAX_SIZE]],
            attributes: ['image' => 'image'],
        );

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $path = $file->store($expressQuiz->imageDirectory(), ExpressImage::DISK);

        abort_if($path === false, 500, "L'image n'a pas pu être enregistrée.");

        $image = $expressQuiz->images()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return response()->json(['id' => $image->id], 201);
    }

    public function show(ExpressQuiz $expressQuiz, ExpressImage $image): StreamedResponse
    {
        abort_unless($image->express_quiz_id === $expressQuiz->id, 404);

        return self::stream($image);
    }

    public static function stream(ExpressImage $image): StreamedResponse
    {
        return Storage::disk(ExpressImage::DISK)->response($image->path, $image->original_name, [
            'Content-Type' => $image->mime_type,
            'Cache-Control' => 'private, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
