<?php

namespace App\Actions\Frameworks;

use App\Enums\CompetencyType;
use App\Models\Competency;
use App\Models\Framework;
use Illuminate\Support\Facades\DB;

class SaveCompetency
{
    /**
     * Create or update a competency and its scale levels from validated data.
     *
     * A new competency is appended at the end of the framework. For a scale,
     * the validation threshold defaults to the highest level.
     *
     * @param  array{code: string, name: string, description?: string|null, type: string, levels?: array<int, array{value: int|string, label: string}>, validation_threshold?: int|string|null}  $data
     */
    public function handle(Framework $framework, array $data, ?Competency $competency = null): Competency
    {
        $type = CompetencyType::from($data['type']);
        $levels = $type === CompetencyType::Scale
            ? array_map(fn (array $level) => ['value' => (int) $level['value'], 'label' => trim($level['label'])], $data['levels'] ?? [])
            : [];

        $values = array_column($levels, 'value');
        $threshold = $type === CompetencyType::Scale && $values !== []
            ? (int) ($data['validation_threshold'] ?? max($values))
            : null;

        return DB::transaction(function () use ($framework, $data, $competency, $type, $levels, $threshold): Competency {
            $competency ??= $framework->competencies()->make([
                'position' => (int) $framework->competencies()->max('position') + 1,
            ]);

            $competency->fill([
                'code' => trim($data['code']),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'type' => $type,
                'validation_threshold' => $threshold,
            ])->save();

            $competency->syncLevels($levels);

            return $competency;
        });
    }
}
