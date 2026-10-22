<?php

namespace App\Http\Requests;

use App\Models\Competency;
use App\Models\Framework;
use App\Validation\CompetencyRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompetencyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Framework $framework */
        $framework = $this->route('framework');
        $competency = $this->route('competency');

        $uniqueCode = Rule::unique('competencies', 'code')
            ->where('framework_id', $framework->id)
            ->ignore($competency instanceof Competency ? $competency->id : null);

        return CompetencyRules::rules($this->all(), uniqueCode: $uniqueCode);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CompetencyRules::attributes();
    }
}
