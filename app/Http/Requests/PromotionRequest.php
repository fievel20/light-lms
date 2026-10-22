<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $promotion = $this->route('promotion');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('promotions')->ignore($promotion instanceof Promotion ? $promotion->id : null),
            ],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'La date de fin doit être identique ou postérieure à la date de début.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'starts_on' => 'date de début',
            'ends_on' => 'date de fin',
        ];
    }
}
