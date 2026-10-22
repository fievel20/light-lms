<?php

namespace App\Http\Controllers;

use App\Http\Requests\FrameworkRequest;
use App\Models\Competency;
use App\Models\Framework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FrameworkController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('frameworks/Index', [
            'frameworks' => Framework::query()
                ->withCount(['competencies', 'promotions'])
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('frameworks/Create');
    }

    public function store(FrameworkRequest $request): RedirectResponse
    {
        $framework = Framework::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Référentiel créé. Ajoutez maintenant ses compétences.']);

        return to_route('frameworks.show', $framework);
    }

    public function show(Framework $framework): Response
    {
        return Inertia::render('frameworks/Show', [
            'framework' => $framework->only(['id', 'name', 'description']),
            'competencies' => $framework->competencies()
                ->with('levels')
                ->get()
                ->map(fn (Competency $competency) => $competency->toPresentation()),
            'promotions' => $framework->promotions()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Framework $framework): Response
    {
        return Inertia::render('frameworks/Edit', [
            'framework' => $framework->only(['id', 'name', 'description']),
        ]);
    }

    public function update(FrameworkRequest $request, Framework $framework): RedirectResponse
    {
        $framework->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Référentiel mis à jour.']);

        return to_route('frameworks.show', $framework);
    }

    public function destroy(Framework $framework): RedirectResponse
    {
        $promotions = $framework->promotions()->orderBy('name')->pluck('name');

        if ($promotions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'framework' => trans_choice(
                    'Ce référentiel est utilisé par la promotion :names.|Ce référentiel est utilisé par les promotions :names.',
                    $promotions->count(),
                    ['names' => $promotions->join(', ', ' et ')],
                ),
            ]);
        }

        $framework->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Référentiel « {$framework->name} » supprimé."]);

        return to_route('frameworks.index');
    }
}
