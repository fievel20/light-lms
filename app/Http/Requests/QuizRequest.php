<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Models\Sequence;
use App\Validation\QuizQuestionRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The whole quiz at once: its settings and its questions in order (the
 * editor keeps them on the page, JSON imports included, until saved).
 */
class QuizRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Sequence $sequence */
        $sequence = $this->route('sequence');

        return [
            'title' => ['required', 'string', 'max:255'],
            'seconds_per_question' => ['nullable', 'integer', 'min:'.Quiz::MIN_SECONDS, 'max:'.Quiz::MAX_SECONDS],
            ...QuizQuestionRules::rules(
                Quiz::MAX_QUESTIONS,
                Rule::exists('sequence_images', 'id')->where('sequence_id', $sequence->id),
            ),
        ];
    }

    /**
     * The right answer must be one of the filled-in choices.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => QuizQuestionRules::check((array) $this->input('questions', []), $validator)];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'seconds_per_question' => 'temps par question',
            ...QuizQuestionRules::attributes(),
        ];
    }
}
