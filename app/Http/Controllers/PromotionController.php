<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromotionRequest;
use App\Models\Framework;
use App\Models\Promotion;
use App\Queries\PromotionProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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
                ->with('framework:id,name')
                ->get(['id', 'name', 'framework_id', 'starts_on', 'ends_on', 'archived_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('promotions/Create', [
            'frameworks' => $this->frameworkOptions(),
        ]);
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
            'framework' => $promotion->framework()
                ->withCount('competencies')
                ->first(['id', 'name'])
                ?->only(['id', 'name', 'competencies_count']),
            'learners' => $promotion->learners()
                ->orderByName()
                ->get(['users.id', 'first_name', 'last_name', 'email', 'invited_at', 'activated_at'])
                ->map->only(['id', 'first_name', 'last_name', 'name', 'email', 'invited_at', 'activated_at']),
        ]);
    }

    /**
     * US11: learners × competencies matrix.
     */
    public function progress(Promotion $promotion, PromotionProgress $progress): Response
    {
        return Inertia::render('promotions/Progress', [
            'promotion' => $promotion->only(['id', 'name', 'starts_on', 'ends_on', 'archived_at']),
            'framework' => $promotion->framework()->first(['id', 'name'])?->only(['id', 'name']),
            ...$progress->get($promotion),
        ]);
    }

    public function edit(Promotion $promotion): Response
    {
        return Inertia::render('promotions/Edit', [
            'promotion' => $promotion->only(['id', 'name', 'framework_id', 'starts_on', 'ends_on', 'archived_at']),
            'frameworks' => $this->frameworkOptions(),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function frameworkOptions(): Collection
    {
        return Framework::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Framework $framework) => ['id' => $framework->id, 'name' => $framework->name]);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $request->validated();
        $newFramework = isset($data['framework_id']) ? (int) $data['framework_id'] : null;

        if ($newFramework !== $promotion->framework_id && $promotion->assessments()->exists()) {
            throw ValidationException::withMessages([
                'framework_id' => 'Des compétences ont déjà été évaluées dans cette promotion : son référentiel ne peut plus changer.',
            ]);
        }

        $promotion->update($data);

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
