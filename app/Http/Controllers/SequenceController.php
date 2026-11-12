<?php

namespace App\Http\Controllers;

use App\Actions\Sequences\DuplicateSequence;
use App\Http\Requests\SequenceRequest;
use App\Models\Sequence;
use App\Support\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SequenceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sequences/Index', [
            'sequences' => Sequence::query()
                ->with('learningPaths:id,name')
                ->orderBy('title')
                ->get(['id', 'title', 'summary', 'updated_at'])
                ->map(fn (Sequence $sequence) => [
                    ...$sequence->only(['id', 'title', 'summary', 'updated_at']),
                    'learning_paths' => $sequence->learningPaths->pluck('name'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sequences/Create');
    }

    public function store(SequenceRequest $request): RedirectResponse
    {
        $sequence = Sequence::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Séquence créée.']);

        return to_route('sequences.show', $sequence);
    }

    public function show(Sequence $sequence, MarkdownRenderer $markdown): Response
    {
        return Inertia::render('sequences/Show', [
            'sequence' => [
                ...$sequence->only(['id', 'title', 'summary', 'updated_at']),
                'html' => $markdown->toHtml($sequence->content, $sequence),
            ],
        ]);
    }

    public function edit(Sequence $sequence): Response
    {
        return Inertia::render('sequences/Edit', [
            'sequence' => $sequence->only(['id', 'title', 'summary', 'content']),
            // Shown before deleting: the sequence leaves these paths.
            'learningPaths' => $sequence->learningPaths()->pluck('name'),
        ]);
    }

    public function update(SequenceRequest $request, Sequence $sequence): RedirectResponse
    {
        $sequence->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Séquence mise à jour.']);

        return to_route('sequences.show', $sequence);
    }

    public function destroy(Sequence $sequence): RedirectResponse
    {
        $sequence->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Séquence « {$sequence->title} » supprimée."]);

        return to_route('sequences.index');
    }

    /**
     * Live preview of the editor: the same rendering as the final page.
     */
    public function preview(Request $request, MarkdownRenderer $markdown): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:'.SequenceRequest::MAX_CONTENT],
            'sequence_id' => ['nullable', 'integer'],
        ]);

        // While editing, the sequence's own images are shown in the preview.
        $sequence = Sequence::query()->whereKey($validated['sequence_id'] ?? null)->first();

        return response()->json(['html' => $markdown->toHtml($validated['content'] ?? '', $sequence)]);
    }

    public function duplicate(Sequence $sequence, DuplicateSequence $duplicate): RedirectResponse
    {
        $copy = $duplicate->handle($sequence);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Séquence dupliquée : modifie la copie.']);

        return to_route('sequences.edit', $copy);
    }
}
