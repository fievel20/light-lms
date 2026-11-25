<?php

namespace App\Http\Controllers;

use App\Http\Requests\SequenceRequest;
use App\Models\Sequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Importing a folder of Markdown files, dropped on the list of sequences:
 * the page reads the folder, creates one sequence per file, sends its
 * images and attachments one by one (within the upload limit), then saves
 * the text with its links pointing to them.
 */
class SequenceImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:'.SequenceRequest::MAX_CONTENT],
        ], attributes: ['title' => 'titre', 'content' => 'contenu']);

        $sequence = Sequence::create($validated);

        return response()->json([
            'id' => $sequence->id,
            'url' => route('sequences.show', $sequence),
        ], 201);
    }

    /**
     * The text once its images and attachments are uploaded.
     */
    public function update(Request $request, Sequence $sequence): JsonResponse
    {
        $validated = $request->validate(
            ['content' => ['required', 'string', 'max:'.SequenceRequest::MAX_CONTENT]],
            attributes: ['content' => 'contenu'],
        );

        $sequence->update($validated);

        return response()->json(['id' => $sequence->id]);
    }
}
