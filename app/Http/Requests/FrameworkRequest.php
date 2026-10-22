<?php

namespace App\Http\Requests;

use App\Models\Framework;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FrameworkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $framework = $this->route('framework');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('frameworks')->ignore($framework instanceof Framework ? $framework->id : null),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
        ];
    }
}
