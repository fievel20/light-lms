<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use App\Models\SequenceAttachment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files to download from a sequence (PDF, documents…): added from the
 * editor or by importing a folder, read like the images of the sequence.
 */
class SequenceAttachmentController extends Controller
{
    public function store(Request $request, Sequence $sequence): JsonResponse
    {
        $request->validate(
            ['file' => [
                'required',
                'file',
                'extensions:'.implode(',', array_keys(SequenceAttachment::TYPES)),
                'max:'.SequenceImageController::MAX_SIZE,
            ]],
            attributes: ['file' => 'pièce jointe'],
        );

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($sequence->imageDirectory(), $file->hashName(), SequenceAttachment::DISK);

        abort_if($path === false, 500, "La pièce jointe n'a pas pu être enregistrée.");

        $attachment = $sequence->attachments()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            // By extension: what the browser is told, never guessed from the content.
            'mime_type' => SequenceAttachment::TYPES[$extension],
            'size' => $file->getSize(),
        ]);

        return response()->json(['id' => $attachment->id, 'markdown' => $attachment->markdown()], 201);
    }

    public function show(Request $request, Sequence $sequence, SequenceAttachment $attachment): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Learners only get the files of the sequences opened to them.
        abort_unless($sequence->isReadableBy($user), 404);

        $inline = in_array($attachment->extension(), SequenceAttachment::INLINE, true);

        return Storage::disk(SequenceAttachment::DISK)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                'Cache-Control' => 'private, max-age=604800, immutable',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }
}
