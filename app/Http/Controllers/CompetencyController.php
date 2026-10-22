<?php

namespace App\Http\Controllers;

use App\Actions\Frameworks\SaveCompetency;
use App\Enums\CompetencyType;
use App\Http\Requests\CompetencyRequest;
use App\Models\Competency;
use App\Models\Framework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyController extends Controller
{
    public function create(Framework $framework): Response
    {
        return Inertia::render('frameworks/competencies/Create', [
            'framework' => $framework->only(['id', 'name']),
            'types' => $this->types(),
        ]);
    }

    public function store(CompetencyRequest $request, Framework $framework, SaveCompetency $save): RedirectResponse
    {
        /** @var array{code: string, name: string, type: string} $data */
        $data = $request->validated();
        $competency = $save->handle($framework, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Compétence {$competency->code} ajoutée."]);

        return to_route('frameworks.show', $framework);
    }

    public function edit(Framework $framework, Competency $competency): Response
    {
        return Inertia::render('frameworks/competencies/Edit', [
            'framework' => $framework->only(['id', 'name']),
            'competency' => $competency->load('levels')->toPresentation(),
            'types' => $this->types(),
            'assessed' => $competency->isAssessed(),
        ]);
    }

    public function update(CompetencyRequest $request, Framework $framework, Competency $competency, SaveCompetency $save): RedirectResponse
    {
        /** @var array{code: string, name: string, type: string, levels?: array<int, array{value: int|string, label: string}>} $data */
        $data = $request->validated();

        if ($competency->isAssessed() && $this->changesScale($competency, $data)) {
            throw ValidationException::withMessages([
                'type' => 'Cette compétence a déjà été évaluée : son type et les valeurs de ses niveaux ne peuvent plus changer.',
            ]);
        }

        $save->handle($framework, $data, $competency);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Compétence {$competency->code} mise à jour."]);

        return to_route('frameworks.show', $framework);
    }

    public function destroy(Framework $framework, Competency $competency): RedirectResponse
    {
        if ($competency->isAssessed()) {
            throw ValidationException::withMessages([
                'competency' => "La compétence {$competency->code} a déjà été évaluée et ne peut pas être supprimée.",
            ]);
        }

        $competency->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Compétence {$competency->code} supprimée."]);

        return to_route('frameworks.show', $framework);
    }

    /**
     * Whether the update would change the type or the set of level values.
     *
     * @param  array{type: string, levels?: array<int, array{value: int|string, label: string}>}  $data
     */
    private function changesScale(Competency $competency, array $data): bool
    {
        $newValues = array_map('intval', array_column($data['levels'] ?? [], 'value'));
        $currentValues = $competency->type === CompetencyType::Scale ? $competency->allowedValues() : [];
        sort($newValues);
        sort($currentValues);

        return $data['type'] !== $competency->type->value || $newValues !== $currentValues;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function types(): array
    {
        return array_map(
            fn (CompetencyType $type) => ['value' => $type->value, 'label' => $type->label()],
            CompetencyType::cases(),
        );
    }
}
