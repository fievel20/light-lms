<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromotionRequest;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    /**
     * List current promotions, or archived ones on demand.
     */
    public function index(Request $request): Response
    {
        $archived = $request->boolean('archived');

        return Inertia::render('promotions/Index', [
            'archived' => $archived,
            'promotions' => Promotion::query()
                ->archived($archived)
                ->orderByDesc('starts_on')
                ->orderBy('name')
                ->get(['id', 'name', 'starts_on', 'ends_on', 'archived_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('promotions/Create');
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $promotion = Promotion::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Promotion « {$promotion->name} » créée."]);

        return to_route('promotions.index');
    }

    public function show(Promotion $promotion): Response
    {
        return Inertia::render('promotions/Show', [
            'promotion' => $promotion->only(['id', 'name', 'starts_on', 'ends_on', 'archived_at']),
            'learners' => $promotion->learners()
                ->orderByName()
                ->get(['users.id', 'first_name', 'last_name', 'email', 'invited_at', 'activated_at'])
                ->map->only(['id', 'first_name', 'last_name', 'name', 'email', 'invited_at', 'activated_at']),
        ]);
    }

    public function edit(Promotion $promotion): Response
    {
        return Inertia::render('promotions/Edit', [
            'promotion' => $promotion->only(['id', 'name', 'starts_on', 'ends_on', 'archived_at']),
        ]);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promotion mise à jour.']);

        return to_route('promotions.index');
    }

    public function archive(Promotion $promotion): RedirectResponse
    {
        $promotion->archive();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Promotion « {$promotion->name} » archivée."]);

        return to_route('promotions.index');
    }

    public function unarchive(Promotion $promotion): RedirectResponse
    {
        $promotion->unarchive();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Promotion « {$promotion->name} » désarchivée."]);

        return to_route('promotions.index', ['archived' => 1]);
    }
}
