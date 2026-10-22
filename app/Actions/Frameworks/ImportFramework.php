<?php

namespace App\Actions\Frameworks;

use App\Models\Framework;
use App\Validation\CompetencyRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportFramework
{
    public function __construct(private SaveCompetency $saveCompetency) {}

    /**
     * Create a framework and all its competencies from a JSON document, or nothing at all.
     *
     * Expected format: {"name", "description"?, "competencies": [{"code", "name",
     * "description"?, "type": "binary"|"scale", "levels"?: [{"value", "label"}],
     * "validation_threshold"?}]}
     *
     * @throws ValidationException
     */
    public function handle(string $json): Framework
    {
        $data = json_decode($json, true);

        if (! is_array($data) || array_is_list($data) && $data !== []) {
            throw ValidationException::withMessages(['file' => 'Le fichier n\'est pas un JSON valide.']);
        }

        $prefix = 'competencies.*.';
        $validated = Validator::make(
            $data,
            [
                'name' => ['required', 'string', 'max:255', 'unique:frameworks,name'],
                'description' => ['nullable', 'string', 'max:2000'],
                'competencies' => ['required', 'array', 'list', 'min:1', 'max:500'],
                ...CompetencyRules::rules($data, $prefix, uniqueCode: 'distinct'),
            ],
            attributes: [
                'name' => 'nom du référentiel',
                ...array_map(fn (string $name) => "{$name} (compétence n°:position)", CompetencyRules::attributes($prefix)),
            ],
        )->validate();

        return DB::transaction(function () use ($validated): Framework {
            $framework = Framework::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['competencies'] as $competency) {
                $this->saveCompetency->handle($framework, $competency);
            }

            return $framework;
        });
    }
}
