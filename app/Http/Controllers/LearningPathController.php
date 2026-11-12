<?php

namespace App\Http\Controllers;

use App\Http\Requests\LearningPathRequest;
use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LearningPathController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('learning-paths/Index', [
            'learningPaths' => LearningPath::query()
                ->withCount('sequences')
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('learning-paths/Create');
    }

    public function store(LearningPathRequest $request): RedirectResponse
    {
        $path = LearningPath::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parcours créé. Ajoute maintenant ses séquences.']);

        return to_route('learning-paths.show', $path);
    }

    public function show(LearningPath $learningPath): Response
    {
        $sequences = $learningPath->sequences()->get(['sequences.id', 'title', 'summary']);

        return Inertia::render('learning-paths/Show', [
            'learningPath' => $learningPath->only(['id', 'name', 'description']),
            'sequences' => $sequences->map(fn (Sequence $sequence) => $sequence->only(['id', 'title', 'summary']))->values(),
            'promotions' => $learningPath->promotions()->get(['promotions.id', 'name'])
                ->map(fn (Promotion $promotion) => $promotion->only(['id', 'name'])),
            'availableSequences' => Sequence::query()
                ->whereKeyNot($sequences->modelKeys())
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    public function edit(LearningPath $learningPath): Response
    {
        return Inertia::render('learning-paths/Edit', [
            'learningPath' => $learningPath->only(['id', 'name', 'description']),
        ]);
    }

    public function update(LearningPathRequest $request, LearningPath $learningPath): RedirectResponse
    {
        $learningPath->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parcours mis à jour.']);

        return to_route('learning-paths.show', $learningPath);
    }

    public function destroy(LearningPath $learningPath): RedirectResponse
    {
        $promotions = $learningPath->promotions()->pluck('name');

        if ($promotions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'learning_path' => trans_choice(
                    'Ce parcours est affecté à la promotion :names.|Ce parcours est affecté aux promotions :names.',
                    $promotions->count(),
                    ['names' => $promotions->join(', ', ' et ')],
                ),
            ]);
        }

        $learningPath->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Parcours « {$learningPath->name} » supprimé."]);

        return to_route('learning-paths.index');
    }
}
