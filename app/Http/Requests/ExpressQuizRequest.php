<?php

namespace App\Http\Requests;

use App\Models\ExpressQuiz;
use App\Models\Quiz;
use App\Validation\QuizQuestionRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A quiz express saved as a whole, like the quiz of a sequence; its time
 * per question is required (the speed bonus depends on it).
 */
class ExpressQuizRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ExpressQuiz $quiz */
        $quiz = $this->route('express_quiz');

        return [
            'title' => ['required', 'string', 'max:255'],
            'seconds_per_question' => ['required', 'integer', 'min:'.Quiz::MIN_SECONDS, 'max:'.Quiz::MAX_SECONDS],
            ...QuizQuestionRules::rules(
                Quiz::MAX_QUESTIONS,
                Rule::exists('express_images', 'id')->where('express_quiz_id', $quiz->id),
            ),
        ];
    }

    /**
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
