<?php

namespace App\Http\Requests;

use App\Models\LearningPath;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningPathRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $path = $this->route('learning_path');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('learning_paths')->ignore($path instanceof LearningPath ? $path->id : null),
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
