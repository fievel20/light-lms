<?php

namespace App\Validation;

use App\Enums\CompetencyType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validation rules for a competency, shared by the form and the JSON import.
 */
class CompetencyRules
{
    public const int MAX_LEVELS = 10;

    /**
     * @param  string  $prefix  e.g. "" for a form, "competencies.*." for an import
     * @param  array<string, mixed>  $data  the whole validated payload (to read sibling levels)
     * @param  ValidationRule|string|null  $uniqueCode  extra rule ensuring the code is unique
     * @return array<string, array<mixed>>
     */
    public static function rules(array $data, string $prefix = '', ValidationRule|string|null $uniqueCode = null): array
    {
        return [
            "{$prefix}code" => array_filter(['required', 'string', 'max:20', $uniqueCode]),
            "{$prefix}name" => ['required', 'string', 'max:255'],
            "{$prefix}description" => ['nullable', 'string', 'max:2000'],
            "{$prefix}type" => ['required', Rule::enum(CompetencyType::class)],
            "{$prefix}levels" => ["exclude_unless:{$prefix}type,scale", 'required', 'array', 'min:2', 'max:'.self::MAX_LEVELS],
            "{$prefix}levels.*.value" => ["exclude_unless:{$prefix}type,scale", 'required', 'integer', 'min:0', 'max:100', self::levelValueIsUnique($data)],
            "{$prefix}levels.*.label" => ["exclude_unless:{$prefix}type,scale", 'required', 'string', 'max:100'],
            "{$prefix}validation_threshold" => [
                "exclude_unless:{$prefix}type,scale",
                'nullable',
                'integer',
                self::thresholdMatchesALevel($data),
            ],
        ];
    }

    /**
     * French names for the attributes, used in error messages.
     *
     * @return array<string, string>
     */
    public static function attributes(string $prefix = ''): array
    {
        return [
            "{$prefix}code" => 'code',
            "{$prefix}name" => 'intitulé',
            "{$prefix}type" => 'type',
            "{$prefix}levels" => 'niveaux',
            "{$prefix}levels.*.value" => 'valeur du niveau',
            "{$prefix}levels.*.label" => 'libellé du niveau',
            "{$prefix}validation_threshold" => 'seuil d\'acquisition',
        ];
    }

    /**
     * Level values must be unique within one competency ("distinct" would
     * compare across all competencies of an import).
     *
     * @param  array<string, mixed>  $data
     */
    private static function levelValueIsUnique(array $data): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($data): void {
            if (! preg_match('/^(.*levels)\.(\d+)\.value$/', $attribute, $matches)) {
                return;
            }

            $levels = data_get($data, $matches[1]);
            $previous = is_array($levels) ? array_slice($levels, 0, (int) $matches[2]) : [];

            if (in_array((string) $value, array_map('strval', array_column($previous, 'value')), true)) {
                $fail('validation.distinct')->translate();
            }
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function thresholdMatchesALevel(array $data): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($data): void {
            $levelsPath = preg_replace('/validation_threshold$/', 'levels', $attribute);
            $levels = data_get($data, (string) $levelsPath);
            $values = is_array($levels) ? array_map('intval', array_column($levels, 'value')) : [];

            if ($value !== null && ! in_array((int) $value, $values, true)) {
                $fail('Le seuil d\'acquisition doit correspondre à l\'un des niveaux.');
            }
        };
    }
}
