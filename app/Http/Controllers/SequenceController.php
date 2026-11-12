<?php

namespace App\Http\Controllers;

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
                ->orderBy('title')
                ->get(['id', 'title', 'summary', 'updated_at']),
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
                'html' => $markdown->toHtml($sequence->content),
            ],
        ]);
    }

    public function edit(Sequence $sequence): Response
    {
        return Inertia::render('sequences/Edit', [
            'sequence' => $sequence->only(['id', 'title', 'summary', 'content']),
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
        ]);

        return response()->json(['html' => $markdown->toHtml($validated['content'] ?? '')]);
    }
}
